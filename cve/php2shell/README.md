# php2shell — CVE demo (CWE-78 OS Command Injection)

A self-contained **security training demo**. It runs a slick little PHP 8.0 site
(`NetScope Diagnostics`) whose "ping a host" tool contains the classic
*PHP-to-shell* bug: untrusted input concatenated into a shell command. The site
shows a live **VULNERABLE / SECURE** status banner that flips once the fix PR is
merged and redeployed.

The switch is **driven by the PHP runtime itself** — no manual flag:

- running on **PHP 8.0.x** → vulnerable code path, banner is 🔴 VULNERABLE
- running on **any other version** → hardened code path, banner is 🟢 SECURE

So the remediation is a one-line Dockerfile change (bump the base image off
`php:8.0`). On redeploy the app detects the newer runtime and secures itself.

> ⚠️ Intentionally vulnerable. Run it only in an isolated lab/cluster. Do not
> expose it to untrusted networks.

## What it demonstrates

| | Vulnerable build | Secure build |
|---|---|---|
| Status banner | 🔴 VULNERABLE | 🟢 SECURE |
| `127.0.0.1` | pings | pings |
| `127.0.0.1; id` | **runs `id`** (command injection) | rejected by allowlist |
| Code path | raw string concat → `shell_exec` | validate + `escapeshellarg()` |
| Triggered by | PHP 8.0.x runtime | PHP > 8.0 runtime |

The behavior is decided at runtime by `is_vulnerable()` in
[`src/config.php`](src/config.php), which checks `PHP_MAJOR_VERSION` /
`PHP_MINOR_VERSION`.

## Build & run locally

```sh
cd cve/php2shell
docker build -t php2shell-demo:latest .
docker run --rm -p 8080:80 php2shell-demo:latest
# open http://localhost:8080
```

## Deploy with Fleet (Rancher GitOps)

1. Build and push the image to your registry, then set it in
   [`k8s/deployment.yaml`](k8s/deployment.yaml) (`image:`).
2. Register it with Fleet — apply [`fleet-gitrepo.yaml`](fleet-gitrepo.yaml) (or
   create the GitRepo in the Rancher UI) pointing at this repo, path
   `cve/php2shell/k8s`.
3. Fleet deploys the `php2shell-demo` Deployment + LoadBalancer Service.

## Fixing the CVE (the PR)

1. Branch, then bump the base image in the `Dockerfile`:
   `FROM php:8.0-apache` → `FROM php:8.3-apache`.
2. Rebuild + push the image; merge the PR.
3. Fleet rolls out the new build. The app detects the newer PHP runtime, the
   banner turns **SECURE**, and injection payloads are rejected.

## Layout

```
Dockerfile              PHP 8.0 + Apache + ping
src/index.php           static-styled site + demo UI + live status banner
src/diagnostics.php     vulnerable vs. hardened ping logic
src/config.php          is_vulnerable() — runtime PHP-version check
src/assets/style.css    dark terminal aesthetic (red → green on fix)
k8s/                    Deployment, Service, fleet.yaml bundle
fleet-gitrepo.yaml      sample Fleet GitRepo registration
```
