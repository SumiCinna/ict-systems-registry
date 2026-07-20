<?php
session_start();

$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['login_errors'], $_SESSION['login_old'], $_SESSION['flash_success']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function old(string $key, array $old): string
{
    return htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — ICT Systems Registry</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ledger: {
            navy: '#0B2340',
            steel: '#1B4B72',
            gold: '#C9A227',
            paper: '#F7F5EF',
            line: '#D9D3C3',
            ink: '#28313A',
            muted: '#5B6B79',
          }
        },
        fontFamily: {
          display: ['Georgia', 'Cambria', 'Times New Roman', 'serif'],
          body: ['"Inter"', 'system-ui', 'sans-serif'],
        }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-ledger-paper font-body text-ledger-ink min-h-screen">

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">

  <div class="w-full max-w-md mb-6 text-center">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">ICT Systems &amp; Projects Registry</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Sign in to your account</h1>
    <div class="ledger-rule mt-4"></div>
  </div>

  <div class="w-full max-w-md bg-white border border-ledger-line shadow-sm">

    <div class="px-10 pt-8 pb-2">
      <p class="text-sm text-ledger-muted">
        Welcome back. Enter your credentials below to continue.
      </p>
    </div>

    <?php if ($success): ?>
      <div class="mx-10 mt-4 border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3" role="status">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="mx-10 mt-4 border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3" role="alert">
        <p class="font-semibold mb-1">Please correct the following:</p>
        <ul class="list-disc list-inside space-y-0.5">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="login_process.php" method="POST" id="loginForm" class="px-10 py-8 space-y-7" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div>
        <label for="email" class="field-label">Email or Username <span class="text-ledger-gold">*</span></label>
        <input type="text" id="email" name="email" required maxlength="191"
               value="<?= old('email', $old) ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel" placeholder="you@agency.gov.ph" autocomplete="username">
        <p class="field-error" data-error-for="email"></p>
      </div>

      <div>
        <label for="password" class="field-label">Password <span class="text-ledger-gold">*</span></label>
        <div class="relative mt-2">
          <input type="password" id="password" name="password" required
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 pr-11 bg-white focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel" placeholder="Enter password" autocomplete="current-password">
          <button type="button" class="eye-toggle absolute z-10 flex items-center px-3 text-ledger-muted hover:text-ledger-navy focus:outline-none" data-target="password" aria-label="Show password">
            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-closed hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.2 13.2 0 0 1-3.1 3.9M6.6 6.6C3.7 8.4 1.5 12 1.5 12s3.5 7 10.5 7a10.4 10.4 0 0 0 4.6-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
          </button>
        </div>
        <p class="field-error" data-error-for="password"></p>
      </div>

      <button type="submit" id="submitBtn"
              class="w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
              disabled>
        SIGN IN
      </button>

      <p class="text-center text-xs text-ledger-muted">
        No account yet? <a href="register.php" class="text-ledger-steel underline hover:text-ledger-navy">Create one</a>
      </p>
    </form>
  </div>

  <p class="text-[11px] text-ledger-muted mt-6 tracking-wide">ICT SYSTEMS &amp; PROJECTS REGISTRY</p>
</div>

<script src="assets/js/login.js"></script>
</body>
</html>