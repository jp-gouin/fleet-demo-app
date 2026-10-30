# php2shell — PHP RCE demo (CVE-2026-109618 / CVE-2026-109619)

A self-contained **security training demo** for **php2shell** — the critical
unauthenticated remote-code-execution flaw in the PHP runtime itself, disclosed
20 Oct 2026 and patched 21 Oct 2026. It runs a slick little site
(`NetScope Diagnostics`) with a live **VULNERABLE / SECURE** status banner that
flips once the fix PR is merged and redeployed.

## How the status is decided

The app runs on **Ubuntu 24.04** with Apache + mod_php 8.3. Ubuntu backports
security fixes **without changing the upstream version**, so both the vulnerable
and patched builds report `PHP 8.3.6`. What differs is the Ubuntu **package
revision**:

| Build | php8.3 package | php2shell | Banner |
|---|---|---|---|
| Vulnerable | GA release (`noble/main`) | unpatched RCE | 🔴 VULNERABLE |
| Fixed | `8.3.6-0ubuntu0.24.04.13` | patched | 🟢 SECURE |

A single Dockerfile `ARG PATCH_STATE` chooses which. The installed revision is
recorded at build time in `/etc/php2shell.pkgver`, and `is_vulnerable()` in
[`src/config.php`](src/config.php) reads it: a build carrying `…24.04.13` (or
later) is secure, anything else is vulnerable.

> Why an ARG instead of pinning `…24.04.1`? Ubuntu has removed that revision
> (and `.12` of the code packages) from the archive, and the GA version string
> isn't stable across mirrors/arches. Toggling GA vs. the security pocket is the
> robust way to reproduce the pre/post-patch states.

> ⚠️ Intentionally ships a known-vulnerable PHP build. Deploy only in an
> isolated lab/cluster. Do not expose it to untrusted networks.

## Build & run locally

```sh
cd cve/php2shell
# Vulnerable (default):
docker build -t php2shell-demo:8.3.6-vuln .
# Fixed:
docker build --build-arg PATCH_STATE=fixed -t php2shell-demo:8.3.6-fixed .

docker run --rm -p 8080:80 php2shell-demo:8.3.6-vuln
# open http://localhost:8080
```

## Deploy with Fleet (Rancher GitOps)

1. CI builds the image, reads the installed php8.3 revision, and pushes it to
   `ghcr.io/<owner>/php2shell-demo` tagged by security state — `:8.3.6-vuln` or
   `:8.3.6-fixed` (no `latest`). The tag in
   [`k8s/deployment.yaml`](k8s/deployment.yaml) matches.
2. Register it with Fleet — apply [`fleet-gitrepo.yaml`](fleet-gitrepo.yaml) (or
   create the GitRepo in the Rancher UI) pointing at this repo, path
   `cve/php2shell/k8s`.
3. Fleet deploys the `php2shell-demo` Deployment + Service.

## Fixing the CVE (the PR)

1. Branch, then in the `Dockerfile` flip `ARG PATCH_STATE=vulnerable` →
   `ARG PATCH_STATE=fixed`, and in `k8s/deployment.yaml` change the image tag
   `:8.3.6-vuln` → `:8.3.6-fixed`.
2. CI builds the `:8.3.6-fixed` image on the PR, and pushes it on merge.
3. Because the Deployment tag changed, Fleet rolls out the new build. The app
   reads the patched php8.3 revision and the banner turns **SECURE**.

## Layout

```
Dockerfile              Ubuntu 24.04 + Apache + mod_php 8.3 (ARG PATCH_STATE)
src/index.php           static-styled site + live status banner
src/diagnostics.php     diagnostics tool logic
src/config.php          is_vulnerable() — reads /etc/php2shell.pkgver
src/assets/style.css    dark terminal aesthetic (red → green on fix)
k8s/                    Deployment, Service, fleet.yaml bundle
fleet-gitrepo.yaml      sample Fleet GitRepo registration
```
