<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/diagnostics.php';

$host   = $_GET['host'] ?? '';
$result = null;
if ($host !== '') {
    $result = run_ping($host);
}
$secure = !is_vulnerable();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(APP_NAME) ?> — CVE Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= $secure ? 'is-secure' : 'is-vuln' ?>">

<div class="scanlines"></div>

<header class="topbar">
  <div class="brand">
    <span class="logo">▞▚</span>
    <span class="name"><?= htmlspecialchars(APP_NAME) ?></span>
    <span class="ver">v<?= htmlspecialchars(APP_VERSION) ?></span>
  </div>
  <div class="status-pill" title="Live security status of this running build">
    <span class="dot"></span>
    <?= $secure ? 'SECURE' : 'VULNERABLE' ?>
  </div>
</header>

<main>
  <section class="hero">
    <p class="kicker">CVE-2026-109618 · CVE-2026-109619 · php2shell · PHP RCE</p>
    <h1>php2<span class="glitch" data-text="shell">shell</span></h1>
    <p class="lede">
      A live, self-contained lab for <strong>php2shell</strong> — the critical
      unauthenticated remote-code-execution flaw in the PHP runtime itself,
      patched on 2026-10-21. Security here is decided by the installed PHP
      package, not app code. This build ships Ubuntu's
      <strong class="inline-status">php8.3 <?= htmlspecialchars(php_package_version() ?? PHP_VERSION) ?></strong>,
      so it is currently
      <strong class="inline-status"><?= $secure ? 'secure' : 'vulnerable' ?></strong>.
    </p>
    <div class="deploy-meta">
      <span>host: <?= htmlspecialchars(gethostname() ?: 'unknown') ?></span>
      <span>php: <?= htmlspecialchars(PHP_VERSION) ?></span>
      <span>pkg: <?= htmlspecialchars(php_package_version() ?? 'unknown') ?></span>
      <span>build: <?= $secure ? 'remediated' : 'vulnerable' ?></span>
    </div>
  </section>

  <section class="panel">
    <h2>Network reachability check</h2>
    <p class="muted">Enter a host to ping from the server. (Demo endpoint.)</p>
    <form method="get" class="tool">
      <input type="text" name="host" value="<?= htmlspecialchars($host) ?>"
             placeholder="e.g. 127.0.0.1" autocomplete="off" spellcheck="false">
      <button type="submit">Run</button>
    </form>

    <?php if ($result !== null): ?>
      <div class="console">
        <div class="console-head">
          <span>$ <?= htmlspecialchars($result['command']) ?></span>
        </div>
        <?php if (!empty($result['blocked'])): ?>
          <pre class="blocked">⛔ <?= htmlspecialchars($result['blocked']) ?></pre>
        <?php else: ?>
          <pre><?= htmlspecialchars($result['output']) !== '' ? htmlspecialchars($result['output']) : '(no output)' ?></pre>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="grid">
    <article class="card <?= $secure ? '' : 'card-bad' ?>">
      <h3><?= $secure ? '🛡️ What the patch does' : '☠️ Why this is exploitable' ?></h3>
      <?php if ($secure): ?>
        <p>User input is validated against a strict hostname/IP allowlist and
           escaped with <code>escapeshellarg()</code> before it ever reaches a
           shell. Injection payloads are rejected before execution.</p>
        <p class="try">Try <code>127.0.0.1; id</code> — it is now refused.</p>
      <?php else: ?>
        <p>The host value is concatenated directly into a shell command:
           <code>"ping -c 1 " . $host</code>. Shell metacharacters are honored,
           so the attacker controls the command line.</p>
        <p class="try">Try <code>127.0.0.1; id</code> and watch a second command run.</p>
      <?php endif; ?>
    </article>

    <article class="card">
      <h3>The vulnerable line</h3>
      <pre class="snippet">$command = "ping -c 1 " . <span class="hl">$host</span>;
shell_exec($command);</pre>
      <p class="muted">Untrusted input → shell. That's the whole bug.</p>
    </article>

    <article class="card">
      <h3>The fix</h3>
      <pre class="snippet"># Dockerfile
-ARG PATCH_STATE=<span class="hl">vulnerable</span>
+ARG PATCH_STATE=<span class="hl">fixed</span></pre>
      <p class="muted">Pull Ubuntu's php2shell security update
      (8.3.6-0ubuntu0.24.04.13). The app detects the patched package and the
      banner turns green. Open the PR to ship it.</p>
    </article>
  </section>
</main>

<footer>
  <span><?= htmlspecialchars(APP_NAME) ?> · security training demo</span>
  <span>deploy with Fleet · patch with a PR</span>
</footer>

</body>
</html>
