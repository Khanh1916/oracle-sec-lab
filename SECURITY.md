# Security Policy

## This project is deliberately vulnerable

OracleSecLab is an **intentionally vulnerable** training application. It exists
to be attacked in a private lab. The documented weaknesses are features, not
defects.

**Please do NOT report the following** — they are the exercises themselves:

- The Oracle UNION SQL injection in `search.php` (Challenge 1)
- The negative-quantity business-logic flaw in `store.php` (Challenge 2)
- The broken access control and update-manifest poisoning in
  `partner_config.php` / `admin.php` (Challenge 3)
- Any weak credential, missing authorization, verbose error, decoy, or
  injectable query that is part of a challenge or walkthrough
  (see [`docs/ANSWER_KEY.md`](docs/ANSWER_KEY.md))

Reports about these will be closed as "working as intended."

## What IS worth reporting

Problems that go **beyond the intended challenge surface**, for example:

- A way to escape the container or reach the host from the web app
- The lab exposing the Docker/Kubernetes host or other services it should not
- A vulnerability in the **non-challenge tooling** (the `setup.sh` installer,
  the Python helpers, the CI workflows) that could harm someone *running* the
  lab rather than *playing* it
- A supply-chain problem in the published image
  `ghcr.io/khanh1916/oracle-sec-lab-web` (unexpected contents, leaked real
  secrets, etc.)
- Dependency CVEs that affect the host rather than the simulated app

## How to report

Please report privately rather than opening a public issue:

1. Preferred: GitHub **"Report a vulnerability"** under the repository's
   **Security** tab (private advisory).
2. Alternatively, contact the maintainer through their
   [GitHub profile](https://github.com/Khanh1916).

Include what you did, what happened, and why it falls outside the intended
challenge scope. You'll get an acknowledgement as soon as possible.

## Reminder: run it safely

Never deploy this application on a public, internet-facing, or shared host.
Keep it on an isolated, private machine or network. The lab ships with
well-known default credentials and, by design, no real security controls.

## Supported versions

Only the latest release and the `master` branch receive fixes. Older tags are
provided for reference and are not maintained.
