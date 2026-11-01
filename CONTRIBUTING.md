# Contributing to OracleSecLab

Thanks for your interest! This is a teaching lab, so the contribution rules are
a little different from a normal app.

## The golden rule: the vulnerabilities are the product

The challenge weaknesses in the main application are **intentional**. Do not
"harden" them in a pull request — that breaks the exercises.

- Keep the vulnerable behaviour in `search.php`, `store.php`,
  `partner_config.php`, and `admin.php`.
- Secure, remediated versions live in [`secure_versions/`](secure_versions/).
  If you want to show a fix, add or improve a file there, don't patch the main
  app.
- If you genuinely need to change how a challenge works, say so explicitly and
  update [`docs/ANSWER_KEY.md`](docs/ANSWER_KEY.md) to match.

## Don't leak answers

Player-facing docs (the README especially) must not contain real flag values.
Keep flags and full solutions in `docs/ANSWER_KEY.md`. Remember you can run with
`RANDOMIZE_FLAGS=1` so every instance has its own flags.

## Running the lab locally

```bash
docker compose up -d          # add RANDOMIZE_FLAGS=1 for per-instance flags
```

Portal: http://localhost:8080/dbs401-oracle-app — see
[`docs/DOCKER_GUIDE.md`](docs/DOCKER_GUIDE.md) for details, Kubernetes, and the VM path.

## Running the tests

The end-to-end test boots the stack and exploits all three challenges, asserting
each still yields a flag:

```bash
pip install "requests>=2.31,<3"
docker compose up -d --wait
python tests/e2e_challenges.py
```

CI runs this automatically (`.github/workflows/e2e.yml`) with
`RANDOMIZE_FLAGS=1`. **Any change to a challenge, the schema, the seed, the
entrypoint, or the flag generator must keep this test green.**

## Pull requests

- Keep changes focused; one logical change per PR.
- Update docs when you change behaviour, setup, or flags.
- Fill in the PR checklist.
- Match the style of the surrounding code.

## Reporting problems

- Bugs in **running** the lab: open a deployment/setup issue.
- The intentional challenge vulns: not bugs — please don't report them.
- Anything that could harm someone running the lab (container escape, host
  exposure, tooling vulnerabilities): report privately per
  [`SECURITY.md`](SECURITY.md).
