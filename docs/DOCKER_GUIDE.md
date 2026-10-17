# 🐳 OracleSecLab — Docker Deployment Guide

Run the entire lab (Oracle Database + PHP/Apache portal) with a single command.
No manual Oracle XE, Instant Client, or OCI8 installation required.

> ⚠️ This is a **deliberately vulnerable** application. Run it only on an
> isolated, private machine — never expose ports `8080` / `8081` / `1521` to
> the public internet.

---

## What this gives you

| Service | Image / Build | Purpose |
|:---|:---|:---|
| `oracle` | `gvenzl/oracle-free:23-slim` | Oracle Database 23c Free. Auto-creates the `dbs401_user` schema in `FREEPDB1`. |
| `web` | `ghcr.io/khanh1916/oracle-sec-lab-web` (prebuilt by CI) or `docker/Dockerfile` | PHP 8.1 + Apache + OCI8. Serves the portal on `:80` **and** the baseline partner manifest on `:8081`. |

**Nothing about the challenges changes.** The three vulnerabilities, all flags,
the fragmented encodings, decoy tables, and pre-seeded accounts are identical to
the VM setup. Docker is just a different way to run the exact same code:

- `config.php` and `database/init_passwords.php` read DB settings from
  environment variables, **falling back to the original VM values** when unset.
- The partner manifest served on `:8081` is byte-for-byte the one `setup.sh`
  wrote (version `3.0.5`, same `flag_part` hex), so Challenge 3 is unchanged.
- `schema.sql` / `seed.sql` are imported verbatim — no data was modified.

---

## Prerequisites

- **Docker** + **Docker Compose v2** (`docker compose`, not `docker-compose`).
- ~**4 GB RAM** free (Oracle needs ~2 GB) and ~10 GB disk.
- The images are `linux/amd64`. On Apple Silicon they run under emulation
  (already pinned via `platform: linux/amd64`); expect a slower first boot.

---

## Quick start

From the repository root, pick one:

**Fast — use the prebuilt image from GHCR** (recommended):

```bash
docker compose pull
docker compose up -d
```

**Build locally** (after editing PHP source, or if GHCR is unreachable):

```bash
docker compose up -d --build
```

Run `pull` before `up`: because the `web` service also has a `build:` section,
a plain `up` with no local image builds from source instead of pulling.

The **first** start still takes a few minutes: Oracle initializes its database,
then the web container imports the schema, seed, and bcrypt password hashes.
A local build adds ~10 minutes (it downloads Instant Client and compiles OCI8).
Watch progress with:

```bash
docker compose logs -f web
```

Wait until you see `Starting Apache (app :80, partner manifest :8081)`.
Then open:

```
http://localhost:8080/dbs401-oracle-app
```

Log in with any pre-configured account, e.g. `student1` / `Student@123` or
`admin` / `Admin@DBS401!2024`.

---

## Everyday commands

| Action | Command |
|:---|:---|
| Start | `docker compose up -d` |
| Update to the newest published image | `docker compose pull && docker compose up -d` |
| Stop, **keep** data + student progress | `docker compose down` |
| Stop and **RESET** lab to baseline | `docker compose down -v` |
| Rebuild after editing PHP source | `docker compose up -d --build` |
| Tail web logs | `docker compose logs -f web` |
| Shell into the web container | `docker compose exec web bash` |
| SQL*Plus inside the web container | `docker compose exec web sqlplus dbs401_user/dbs401_pass@//oracle:1521/FREEPDB1` |

`down -v` removes the `oracle-data` volume — the next `up` re-imports a clean
schema and seed. Use it to reset the lab between classes or teams.

---

## Solving the challenges under Docker

- **Challenge 1 (SQL Injection)** and **Challenge 2 (Negative Quantity)** need
  no special handling — attack `http://localhost:8080/dbs401-oracle-app/...`.

- **Challenge 3 (Supply Chain)** works the same way. The admin "check for
  updates" fetches `update_url` from `CONFIG_STORE` (default
  `http://127.0.0.1:8081/manifest.json`, served by the web container itself).
  To exploit it, you poison `update_url` via `partner_config.php` to point at an
  attacker-controlled manifest that advertises a higher version.

  If you run that rogue manifest server **on your host machine**, the target
  (web container) can reach it at `host.docker.internal`. For example, serve a
  manifest on host port `9000` and set the update URL to:

  ```
  http://host.docker.internal:9000/manifest.json
  ```

  (On Linux this hostname is wired up via the `extra_hosts` entry in
  `docker-compose.yml`.) Everything else about the exploit — the version
  comparison, the hex `flag_part`, the server-side suffix — is unchanged.

---

## Connecting with external DB tools (optional)

Port `1521` is published, so SQL Developer / DBeaver / `sqlplus` on your host can
connect:

```
Host: localhost   Port: 1521   Service: FREEPDB1
User: dbs401_user Password: dbs401_pass
```

---

## Troubleshooting

| Symptom | Fix |
|:---|:---|
| `web` exits / loops "database not ready" | Oracle is still initializing on first boot — wait a few minutes; the web container retries automatically. |
| `pull` warns the `web` image "must be built from source" | GHCR was unreachable or the package isn't public — use `docker compose up -d --build`. |
| OCI8 build fails during `docker build` | Usually a transient Instant Client download; re-run `docker compose build web`. |
| Portal shows "Database connection failed" | Check `docker compose ps` — the `oracle` service must be `healthy`. |
| Want a totally clean slate | `docker compose down -v && docker compose up -d --build` |

---

The VM installer (`setup.sh` + `docs/DEPLOYMENT_GUIDE.md`) remains fully
supported — Docker is an additional option, not a replacement.
