<?php
require_once __DIR__ . '/config.php';

/**
 * Core demo logic: a "network reachability" tool that pings a host.
 *
 * The vulnerability class demonstrated here is CWE-78 (OS Command Injection),
 * the canonical "PHP to shell" bug. User input is concatenated straight into a
 * shell command, so a crafted host value lets an attacker chain arbitrary
 * commands (e.g. `127.0.0.1; id`) and get command execution on the container.
 *
 * The patched path validates the host against a strict allowlist pattern and
 * passes it through escapeshellarg(), removing the injection entirely.
 */

/**
 * @return array{ok:bool, output:string, command:string, blocked?:string}
 */
function run_ping(string $host): array
{
    if (!is_vulnerable()) {
        // --- Hardened path -------------------------------------------------
        // Only accept hostnames / IPv4 addresses. Reject anything else before
        // it can reach a shell. escapeshellarg() is belt-and-suspenders.
        if (!preg_match('/^[A-Za-z0-9.\-]{1,253}$/', $host)) {
            return [
                'ok'      => false,
                'output'  => '',
                'command' => '(rejected)',
                'blocked' => 'Input rejected: not a valid hostname or IP address.',
            ];
        }
        $safe    = escapeshellarg($host);
        $command = "ping -c 1 -W 1 $safe";
    } else {
        // --- Vulnerable path (CWE-78) --------------------------------------
        // Raw concatenation of untrusted input into a shell string.
        $command = "ping -c 1 -W 1 " . $host;
    }

    $output = @shell_exec($command . ' 2>&1');

    return [
        'ok'      => $output !== null,
        'output'  => (string) ($output ?? ''),
        'command' => $command,
    ];
}
