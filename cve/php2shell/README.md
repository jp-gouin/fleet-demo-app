# php2shell — PHP RCE demo (CVE-2026-109618 / CVE-2026-109619)

A self-contained **security training demo** for **php2shell** — the critical
unauthenticated remote-code-execution flaw in the PHP runtime itself, disclosed
20 Oct 2026 and patched 21 Oct 2026. It runs a slick little site
(`NetScope Diagnostics`) with a live **VULNERABLE / SECURE** status banner that
flips once the fix PR is merged and redeployed.

The status is **driven by the PHP runtime version** — no manual flag. A build is
vulnerable until its PHP branch reaches the first patched release:

| Branch | First patched release |
|---|---|
| 8.2.x | 8.2.35 |
| 8.3.x | **8.3.36** |
| 8.4.x | 8.4.27 |
| 8.5.x | 8.5.12 |

This demo ships on **`php:8.3.35-apache`** (🔴 vulnerable). The remediation is a
one-line Dockerfile bump to **`php:8.3.36-apache`** (🟢 secure) — the first 8.3
build with the php2shell fix, which also avoids the regression reported in
8.4.27 / 8.5.12.

> ⚠️ Intentionally runs a known-vulnerable PHP runtime. Deploy only in an
> isolated lab/cluster. Do not expose it to untrusted networks.

## What it demonstrates

| | Vulnerable build | Secure build |
|---|---|---|
| PHP runtime | 8.3.35 | 8.3.36 |
| Status banner | 🔴 VULNERABLE | 🟢 SECURE |
| php2shell exposure | unpatched RCE | patched |

The status is decided at runtime by `is_vulnerable()` in
[`src/config.php`](src/config.php), which compares `PHP_VERSION` against the first
patched release for its branch.

## Build & run locally

```sh
cd cve/php2shell
docker build -t php2shell-demo:8.3.35 .
docker run --rm -p 8080:80 php2shell-demo:8.3.35
# open http://localhost:8080
```

## Deploy with Fleet (Rancher GitOps)

1. CI builds and pushes the image to `ghcr.io/<owner>/php2shell-demo`, tagged with
   the **PHP version** (e.g. `:8.3.35`) — no `latest`. The tag in
   [`k8s/deployment.yaml`](k8s/deployment.yaml) matches.
2. Register it with Fleet — apply [`fleet-gitrepo.yaml`](fleet-gitrepo.yaml) (or
   create the GitRepo in the Rancher UI) pointing at this repo, path
   `cve/php2shell/k8s`.
3. Fleet deploys the `php2shell-demo` Deployment + LoadBalancer Service.

## Fixing the CVE (the PR)

1. Branch, then bump the base image in the `Dockerfile`:
   `FROM php:8.3.35-apache` → `FROM php:8.3.36-apache`, and the image tag in
   `k8s/deployment.yaml` to `:8.3.36`.
2. CI builds the `:8.3.36` image on the PR, and pushes it on merge.
3. Because the Deployment tag changed, Fleet rolls out the new build. The app
   detects the patched PHP runtime and the banner turns **SECURE**.

## Layout

```
Dockerfile              PHP 8.3.35 + Apache + ping (fix = bump to 8.3.36)
src/index.php           static-styled site + live status banner
src/diagnostics.php     diagnostics tool logic
src/config.php          is_vulnerable() — php2shell runtime-version check
src/assets/style.css    dark terminal aesthetic (red → green on fix)
k8s/                    Deployment, Service, fleet.yaml bundle
fleet-gitrepo.yaml      sample Fleet GitRepo registration
```
