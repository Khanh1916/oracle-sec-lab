#!/usr/bin/env python3
"""
DBS401 - Group 02
tools/decode_helper.py  –  Decode Helper for CTF Players

⚠️  Chỉ sử dụng trong môi trường lab DBS401 nội bộ.

Dùng script này để:
  1. Decode hex string → ASCII
  2. Decode base64 string → ASCII
  3. Reverse string
  4. Auto-detect và decode một chuỗi lạ
  5. Assemble flag từ các parts

Usage:
  python3 decode_helper.py --hex "4442533430317B53514C5F"
  python3 decode_helper.py --b64 "MHI0Y2wzIX0="
  python3 decode_helper.py --rev "_n01tc3jn1"
  python3 decode_helper.py --auto "4442533430317B53514C5F"
  python3 decode_helper.py --assemble
"""

import argparse
import base64
import sys


BANNER = """
╔══════════════════════════════════════════════════════╗
║  DBS401 - Group 02  |  CTF Decode Helper             ║
║  Lab use only – Not for real systems                 ║
╚══════════════════════════════════════════════════════╝
"""


def hex_decode(hex_str: str) -> str:
    hex_str = hex_str.strip().replace(' ', '').replace('0x', '')
    try:
        return bytes.fromhex(hex_str).decode('utf-8')
    except Exception as e:
        return f"[ERROR] hex_decode: {e}"


def b64_decode(b64_str: str) -> str:
    b64_str = b64_str.strip()
    pad = 4 - len(b64_str) % 4
    if pad != 4:
        b64_str += '=' * pad
    try:
        return base64.b64decode(b64_str).decode('utf-8')
    except Exception as e:
        return f"[ERROR] b64_decode: {e}"


def reverse_str(s: str) -> str:
    return s.strip()[::-1]


def auto_detect(s: str) -> dict:
    """Try all decoders and return all successful results."""
    results = {}
    s = s.strip()

    # Try hex
    hex_clean = s.replace(' ', '').replace('0x', '')
    if all(c in '0123456789abcdefABCDEF' for c in hex_clean) and len(hex_clean) % 2 == 0:
        r = hex_decode(hex_clean)
        if '[ERROR]' not in r:
            results['hex'] = r

    # Try base64
    try:
        padded = s + '=' * (4 - len(s) % 4) if len(s) % 4 else s
        r = base64.b64decode(padded).decode('utf-8')
        results['base64'] = r
    except Exception:
        pass

    # Try reverse
    results['reversed'] = reverse_str(s)

    return results


def assemble_flag1():
    """
    FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
    Khai thác qua UNION-based SQL Injection tại search.php
      Part A → FLAGS table           → hex encoded
      Part B → AUDIT_LOGS table      → reversed string trong JSON
      Part C → CONFIG_STORE table    → base64 encoded
    """
    print("\n━━━ FLAG 1 ASSEMBLY ━━━")
    part_a_hex = "4442533430317B53514C5F"
    part_b_rev = "_n01tc3jn1"
    part_c_b64 = "MHI0Y2wzIX0="

    a = hex_decode(part_a_hex)
    b = reverse_str(part_b_rev)
    c = b64_decode(part_c_b64)

    print(f"  Part A (hex decode) : {part_a_hex!r}  →  {a!r}")
    print(f"  Part B (reverse)    : {part_b_rev!r}  →  {b!r}")
    print(f"  Part C (b64 decode) : {part_c_b64!r}  →  {c!r}")
    print(f"\n  ✅ FLAG 1 = {a + b + c}")
    return a + b + c


def assemble_flag2():
    """
    FLAG 2: DBS401{LOGIC_GURU_2024}
    Khai thác qua Business Logic (Negative Quantity) tại store.php:
      1. Đăng nhập student1 / Student@123
      2. POST store.php với quantity=-20000, item_id=1 (giá 100 credits)
         → cost = -20000 * 100 = -2,000,000
         → newCredits = currentCredits - (-2,000,000) = currentCredits + 2,000,000
      3. Số dư Credits tăng vọt lên > 999,999
      4. Mua item "Exam Leak 2024 (CLASSIFIED)" → FLAG 2 hiển thị

    Flag này không cần decode – nó hiển thị trực tiếp trên trang store.php.
    """
    print("\n━━━ FLAG 2 ASSEMBLY ━━━")
    print("  Vulnerability : Business Logic – Negative Quantity (store.php)")
    print("  Attack step   : POST quantity=-20000 to item_id=1 (100 credits/unit)")
    print("  Result        : credits - (-2,000,000) = credits + 2,000,000")
    print("  Then buy      : 'Exam Leak 2024 (CLASSIFIED)' at 999,999 credits")
    flag2 = "DBS401{LOGIC_GURU_2024}"
    print(f"\n  ✅ FLAG 2 = {flag2}")
    return flag2


def assemble_flag3():
    """
    FLAG 3: DBS401{5upp1y_Ch41n_P0150n1ng_0912}
    Khai thác qua Supply Chain Poisoning tại admin.php:
      1. Dùng SQLi (Vuln 1) tìm update_url trong CONFIG_STORE (is_public=0)
         → giá trị mặc định: http://127.0.0.1:8081/manifest.json
      2. Sửa update_url trong CONFIG_STORE trỏ về server của hacker
         (ví dụ: http://hacker-server.io/evil.json)
      3. evil.json chứa:
         {"version":"9.9.9","flag_part":"<hex_encoded_flag>"}
         với version > APP_VERSION (3.1.0) để trigger update thành công
      4. Admin truy cập admin.php → nhấn "Check for Partner Updates"
      5. Hệ thống fetch manifest từ server hacker → hiển thị flag_part
      6. Decode hex → FLAG 3

    Hex của FLAG 3:
    4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d
    """
    print("\n━━━ FLAG 3 ASSEMBLY ━━━")
    flag3_hex = "4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d"
    flag3 = hex_decode(flag3_hex)
    print(f"  Vulnerability : Supply Chain Poisoning (admin.php)")
    print(f"  Hex from manifest: {flag3_hex}")
    print(f"  hex_decode    : {flag3_hex!r}  →  {flag3!r}")
    print(f"\n  ✅ FLAG 3 = {flag3}")
    return flag3


def main():
    print(BANNER)
    parser = argparse.ArgumentParser(description='DBS401 CTF Decode Helper')
    parser.add_argument('--hex',      help='Decode hex string to ASCII')
    parser.add_argument('--b64',      help='Decode base64 string to ASCII')
    parser.add_argument('--rev',      help='Reverse a string')
    parser.add_argument('--auto',     help='Auto-detect encoding and decode')
    parser.add_argument('--assemble', action='store_true',
                        help='Show all 3 flag assemblies')
    args = parser.parse_args()

    if args.hex:
        result = hex_decode(args.hex)
        print(f"Hex decode: {args.hex!r}")
        print(f"Result:     {result!r}")

    elif args.b64:
        result = b64_decode(args.b64)
        print(f"Base64 decode: {args.b64!r}")
        print(f"Result:        {result!r}")

    elif args.rev:
        result = reverse_str(args.rev)
        print(f"Reverse: {args.rev!r}")
        print(f"Result:  {result!r}")

    elif args.auto:
        print(f"Auto-detect decoding for: {args.auto!r}\n")
        results = auto_detect(args.auto)
        if not results:
            print("No successful decoding found.")
        for method, value in results.items():
            print(f"  [{method:8s}]  →  {value!r}")

    elif args.assemble:
        f1 = assemble_flag1()
        f2 = assemble_flag2()
        f3 = assemble_flag3()
        print("\n" + "="*55)
        print("  ALL FLAGS:")
        print(f"  Flag 1: {f1}")
        print(f"  Flag 2: {f2}")
        print(f"  Flag 3: {f3}")
        print("="*55)

    else:
        parser.print_help()
        print("\nExamples:")
        print("  python3 decode_helper.py --hex 4442533430317B53514C5F")
        print("  python3 decode_helper.py --b64 MHI0Y2wzIX0=")
        print("  python3 decode_helper.py --rev '_n01tc3jn1'")
        print("  python3 decode_helper.py --auto 4442533430317B53514C5F")
        print("  python3 decode_helper.py --assemble")


if __name__ == '__main__':
    main()