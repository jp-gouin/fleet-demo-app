<?php
/**
 * Demo configuration.
 *
 * The vulnerability is tied to the PHP runtime version, not a manual flag:
 *   - PHP 8.0.x  -> vulnerable (CVE-2024-DEMO: OS command injection, CWE-78)
 *   - any other  -> secure
 *
 * The "fix PR" is therefore a one-line Dockerfile change: bump the base image
 * off php:8.0 (e.g. php:8.3-apache). On the next deploy the app detects the new
 * runtime, switches to the hardened code path, and the site banner flips from
 * VULNERABLE to SECURE automatically.
 */

define('APP_NAME', 'NetScope Diagnostics');
define('APP_VERSION', '1.0.0');

/**
 * The app is vulnerable only on PHP 8.0.x. Upgrading the runtime remediates it.
 */
function is_vulnerable(): bool
{
    return PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 0;
}
