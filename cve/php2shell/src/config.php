<?php
/**
 * Demo configuration.
 *
 * The vulnerability is tied to the PHP runtime, not a manual flag. This demo
 * tracks php2shell (CVE-2026-109618 / CVE-2026-109619), the critical
 * unauthenticated PHP RCE fixed on 2026-10-21.
 *
 * On Ubuntu 24.04, security fixes are backported without changing the upstream
 * version, so both the vulnerable and patched builds report PHP 8.3.6. What
 * differs is the Ubuntu package revision: php2shell is fixed in
 * 8.3.6-0ubuntu0.24.04.13. The installed revision is captured at build time in
 * /etc/php2shell.pkgver, and the app reads it to decide its status.
 *
 * The "fix PR" flips the Dockerfile's PATCH_STATE arg from "vulnerable" (GA
 * php8.3) to "fixed" (the …24.04.13 security update). On the next deploy the app
 * reads the patched revision and the banner flips from VULNERABLE to SECURE.
 */

define('APP_NAME', 'NetScope Diagnostics');
define('APP_VERSION', '1.0.0');

// php2shell is fixed in Ubuntu's 8.3.6-0ubuntu0.24.04.13.
define('PHP2SHELL_FIXED_UBUNTU_REVISION', 13);
define('PHP_PKG_VERSION_FILE', '/etc/php2shell.pkgver');

/**
 * The installed Ubuntu php8.3 package version recorded at build time, e.g.
 * "8.3.6-0ubuntu0.24.04.13" or a GA string like "8.3.6-0ubuntu1". Null if
 * unavailable (e.g. running the source outside the container).
 */
function php_package_version(): ?string
{
    if (!is_readable(PHP_PKG_VERSION_FILE)) {
        return null;
    }
    $v = trim((string) @file_get_contents(PHP_PKG_VERSION_FILE));

    return $v !== '' ? $v : null;
}

/**
 * Is the running PHP runtime still exposed to php2shell
 * (CVE-2026-109618 / CVE-2026-109619)?
 */
function is_vulnerable(): bool
{
    $pkg = php_package_version();

    // A build carrying the Ubuntu security update (…24.04.N with N >= 13) is
    // patched. Anything else — the GA release, or an unknown/missing revision —
    // is treated as vulnerable.
    if ($pkg !== null && preg_match('/0ubuntu0\.24\.04\.(\d+)/', $pkg, $m)) {
        return (int) $m[1] < PHP2SHELL_FIXED_UBUNTU_REVISION;
    }

    return true;
}
