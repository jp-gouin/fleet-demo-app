<?php
/**
 * Demo configuration.
 *
 * The vulnerability is tied to the PHP runtime version, not a manual flag. This
 * demo tracks php2shell (CVE-2026-109618 / CVE-2026-109619), the critical
 * unauthenticated PHP RCE fixed on 2026-10-21. A build is vulnerable until its
 * PHP branch reaches the patched release:
 *
 *   8.2.x -> fixed in 8.2.35
 *   8.3.x -> fixed in 8.3.36
 *   8.4.x -> fixed in 8.4.27
 *   8.5.x -> fixed in 8.5.12
 *   (EOL branches < 8.2 got no official release -> treated as vulnerable)
 *
 * The "fix PR" is therefore a one-line Dockerfile change: bump the base image
 * from php:8.3.35-apache to php:8.3.36-apache. On the next deploy the app detects
 * the patched runtime, switches to the hardened code path, and the site banner
 * flips from VULNERABLE to SECURE automatically.
 */

define('APP_NAME', 'NetScope Diagnostics');
define('APP_VERSION', '1.0.0');

/**
 * Is the running PHP runtime still exposed to php2shell
 * (CVE-2026-109618 / CVE-2026-109619)?
 */
function is_vulnerable(): bool
{
    // First patched release per supported branch.
    $fixed = [
        '8.2' => '8.2.35',
        '8.3' => '8.3.36',
        '8.4' => '8.4.27',
        '8.5' => '8.5.12',
    ];

    $branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

    if (isset($fixed[$branch])) {
        return version_compare(PHP_VERSION, $fixed[$branch], '<');
    }

    // Branches without an entry: anything older than 8.2 is EOL with no official
    // fix (vulnerable); anything newer than the known-affected branches is
    // assumed patched.
    return version_compare(PHP_VERSION, '8.2.0', '<');
}
