# ☸️ OracleSecLab — Kubernetes Deployment

## Why Kubernetes? (Read this first)

**Kubernetes is NOT here to make the lab lighter.** On a single machine, Docker
Compose is simpler and uses fewer resources — use that for personal practice.

K8s earns its place in exactly one scenario: **running many isolated copies of
the lab at once on shared infrastructure** — i.e. a classroom or CTF event where
every student or team needs their *own* vulnerable instance. It gives you:

| Benefit | Why it matters for this lab |
|:---|:---|
| **Tenant isolation** | Each team runs in its own namespace. When Team A poisons `update_url` (Challenge 3) or inflates credits (Challenge 2), it **cannot** affect Team B's database or flags. |
| **Mass provisioning / teardown** | Spin up 30 independent instances with a `for` loop, and delete them all after the exam with one command. |
| **Self-healing** | If a web or Oracle pod crashes, K8s restarts it automatically — no instructor babysitting during a timed competition. |
| **Resource governance** | Per-namespace `ResourceQuota` caps CPU/RAM so one noisy team can't starve the rest. |
| **Multi-node scheduling** | Many Oracle instances (~2 GB RAM each) won't fit on one host; K8s spreads them across a node pool. |

If you just want to *play the lab yourself*, use
[Docker Compose](../docs/DOCKER_GUIDE.md) instead and skip this directory.

> ⚠️ Deliberately vulnerable app — deploy only on a private/isolated cluster,
> never on an internet-facing one.

**Nothing about the challenges changes.** These manifests run the *same* web
image built from `docker/Dockerfile`, with the same env-var wiring, the same
in-pod partner server on `:8081`, and the same `schema.sql`/`seed.sql`.

---

## Files

| File | Purpose |
|:---|:---|
| `configmap.yaml` | Non-secret DB connection settings (`DB_HOST=oracle`, service `FREEPDB1`, …) |
| `secret.yaml` | Lab credentials (well-known defaults, not real secrets) |
| `oracle-statefulset.yaml` | Oracle 23c Free + 10 Gi PersistentVolume + health probes |
| `oracle-service.yaml` | Headless Service `oracle` (stable in-cluster DNS) |
| `web-deployment.yaml` | PHP/Apache portal (same image as Docker), partner server in-pod |
| `web-service.yaml` | ClusterIP for the portal (port 80) |
| `ingress.yaml` | *Optional* per-team hostname (needs an ingress controller) |
| `kustomization.yaml` | Base bundle for one instance |

---

## Prerequisites

- A Kubernetes cluster: `minikube`, `kind`, `k3s`, or a real cluster with a
  default `StorageClass` (for the Oracle PVC) and **amd64** nodes.
- `kubectl` (and `kustomize`, built into modern `kubectl`).
- Outbound access to `ghcr.io` from the nodes (to pull the web image).

### The web image

CI publishes the web image to GHCR on every push to `master`:

```
ghcr.io/khanh1916/oracle-sec-lab-web:latest
```

`web-deployment.yaml` already points at it, so the cluster pulls it directly —
**no build step needed**. Pin a specific build with an immutable tag instead of
`latest` (e.g. `:sha-5b9d55f` or `:1.0.0`, see the package page) when you want
every team on the exact same image.

**Optional — use a local build** (e.g. after editing PHP source, or offline).
Tag it with the same name so no manifest change is needed:

```bash
# from the repository root
docker build -t ghcr.io/khanh1916/oracle-sec-lab-web:latest -f docker/Dockerfile .

# minikube
minikube image load ghcr.io/khanh1916/oracle-sec-lab-web:latest
# kind
kind load docker-image ghcr.io/khanh1916/oracle-sec-lab-web:latest
```

`imagePullPolicy: IfNotPresent` makes the cluster use the loaded image instead
of pulling.

---

## Single instance (quick try)

```bash
kubectl create namespace oracle-sec-lab
kubectl apply -k k8s/ -n oracle-sec-lab

# watch it come up (Oracle first boot takes several minutes)
kubectl get pods -n oracle-sec-lab -w
```

Once the `web` pod is Ready, forward the portal to your machine:

```bash
kubectl port-forward -n oracle-sec-lab svc/web 8080:80
```

Open **http://localhost:8080/dbs401-oracle-app** and log in
(`student1` / `Student@123`, or `admin` / `Admin@DBS401!2024`).

Tear down:

```bash
kubectl delete namespace oracle-sec-lab
```

---

## Multi-team CTF (the real reason for K8s)

Give each team an isolated namespace from the same base bundle:

```bash
# Provision teams 01..10
for i in $(seq -w 1 10); do
  ns="team$i"
  kubectl create namespace "$ns"
  kubectl apply -k k8s/ -n "$ns"
done
```

Each namespace gets its own Oracle DB, its own flags, and its own partner
server — fully isolated. Expose them however suits your setup:

- **Ingress (recommended):** apply `ingress.yaml` per namespace with a distinct
  host (e.g. `team01.oraclelab.local`, `team02.oraclelab.local`), pointing DNS
  or `/etc/hosts` at the ingress controller.
- **Quick demo:** `kubectl port-forward -n team01 svc/web 8081:80`,
  `... -n team02 svc/web 8082:80`, and so on.

Add a safety cap per team (optional):

```bash
kubectl create quota lab-quota -n team01 \
  --hard=requests.cpu=1,requests.memory=3Gi,limits.memory=4Gi
```

Tear everything down after the event:

```bash
for i in $(seq -w 1 10); do kubectl delete namespace "team$i"; done
```

---

## Challenge 3 (Supply Chain) under Kubernetes

The baseline flow is unchanged: `admin.php` reads `update_url` from
`CONFIG_STORE` (default `http://127.0.0.1:8081/manifest.json`, served inside the
web pod). To exploit, poison `update_url` via `partner_config.php` to point at a
rogue manifest advertising a higher version.

Host your rogue manifest somewhere the **web pod** can reach. Options:
- Run it as another pod/Service in the same namespace and set the URL to that
  Service's DNS name, e.g. `http://rogue.team01.svc.cluster.local/manifest.json`.
- Use any HTTP endpoint reachable from the pod network.

The version comparison, the hex `flag_part`, and the server-side suffix are all
identical to the Docker/VM setup.

---

## Troubleshooting

| Symptom | Fix |
|:---|:---|
| `oracle` pod stuck `0/1` for minutes | Normal on first boot — DB creation is slow; the startupProbe allows ~10 min. |
| `web` pod `ImagePullBackOff` | Nodes can't reach `ghcr.io`, or the GHCR package isn't public yet. Check `kubectl describe pod`; as a fallback, load a local build (see "The web image"). |
| PVC `Pending` | Your cluster has no default `StorageClass`; install one (minikube/kind ship one by default). |
| Portal: "Database connection failed" | The `oracle` pod isn't Ready yet; the web pod retries automatically — wait and refresh. |
