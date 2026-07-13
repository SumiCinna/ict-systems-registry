<?php
session_start();

// One-time flash message support (e.g. after a failed submit redirect).
$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old']);

// CSRF token
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
<title>Create Account — ICT Systems Registry</title>
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

  <!-- Letterhead -->
  <div class="w-full max-w-xl mb-6 text-center">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">ICT Systems &amp; Projects Registry</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Create your account</h1>
    <div class="ledger-rule mt-4"></div>
  </div>

  <div class="w-full max-w-xl bg-white border border-ledger-line shadow-sm">

    <div class="px-8 pt-7 pb-2">
      <p class="text-sm text-ledger-muted">
        Register with your basic information below. Fields marked
        <span class="text-ledger-gold font-semibold">*</span> are required.
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mx-8 mt-4 border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3" role="alert">
        <p class="font-semibold mb-1">Please correct the following:</p>
        <ul class="list-disc list-inside space-y-0.5">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="register_process.php" method="POST" id="registerForm" class="px-8 py-7 space-y-6" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <!-- Name -->
      <fieldset>
        <legend class="field-label mb-3">Full name <span class="text-ledger-gold">*</span></legend>
        <div class="grid grid-cols-1 sm:grid-cols-[2fr_2fr_1fr] gap-4">
          <div>
            <label for="last_name" class="field-sublabel">Last name</label>
            <input type="text" id="last_name" name="last_name" required maxlength="100"
                   value="<?= old('last_name', $old) ?>"
                   class="ledger-input" placeholder="Dela Cruz" autocomplete="family-name">
            <p class="field-error" data-error-for="last_name"></p>
          </div>
          <div>
            <label for="first_name" class="field-sublabel">First name</label>
            <input type="text" id="first_name" name="first_name" required maxlength="100"
                   value="<?= old('first_name', $old) ?>"
                   class="ledger-input" placeholder="Juan" autocomplete="given-name">
            <p class="field-error" data-error-for="first_name"></p>
          </div>
          <div>
            <label for="middle_initial" class="field-sublabel">M.I.</label>
            <input type="text" id="middle_initial" name="middle_initial" maxlength="5"
                   value="<?= old('middle_initial', $old) ?>"
                   class="ledger-input" placeholder="S." autocomplete="additional-name">
            <p class="field-error" data-error-for="middle_initial"></p>
          </div>
        </div>
      </fieldset>

      <!-- Email -->
      <div>
        <label for="email" class="field-label">Email address <span class="text-ledger-gold">*</span></label>
        <input type="email" id="email" name="email" required maxlength="191"
               value="<?= old('email', $old) ?>"
               class="ledger-input mt-2" placeholder="you@agency.gov.ph" autocomplete="email">
        <p class="field-error" data-error-for="email"></p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="field-label">Password <span class="text-ledger-gold">*</span></label>
        <div class="relative mt-2">
          <input type="password" id="password" name="password" required
                 class="ledger-input pr-11" placeholder="Enter password" autocomplete="new-password">
          <button type="button" class="eye-toggle" data-target="password" aria-label="Show password">
            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-closed hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.2 13.2 0 0 1-3.1 3.9M6.6 6.6C3.7 8.4 1.5 12 1.5 12s3.5 7 10.5 7a10.4 10.4 0 0 0 4.6-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
          </button>
        </div>

        <!-- Real-time requirement checklist -->
        <ul id="passwordChecklist" class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
          <li data-rule="length" class="rule-item"><span class="rule-box"></span>8–20 characters</li>
          <li data-rule="upper" class="rule-item"><span class="rule-box"></span>1 uppercase letter</li>
          <li data-rule="lower" class="rule-item"><span class="rule-box"></span>1 lowercase letter</li>
          <li data-rule="number" class="rule-item"><span class="rule-box"></span>1 number</li>
        </ul>
      </div>

      <!-- Confirm password -->
      <div>
        <label for="confirm_password" class="field-label">Confirm password <span class="text-ledger-gold">*</span></label>
        <div class="relative mt-2">
          <input type="password" id="confirm_password" name="confirm_password" required
                 class="ledger-input pr-11" placeholder="Re-enter password" autocomplete="new-password">
          <button type="button" class="eye-toggle" data-target="confirm_password" aria-label="Show password">
            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-closed hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.2 13.2 0 0 1-3.1 3.9M6.6 6.6C3.7 8.4 1.5 12 1.5 12s3.5 7 10.5 7a10.4 10.4 0 0 0 4.6-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
          </button>
        </div>
        <p id="matchMessage" class="mt-2 text-xs hidden"></p>
      </div>

      <button type="submit" id="submitBtn"
              class="w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
              disabled>
        CREATE ACCOUNT
      </button>

      <p class="text-center text-xs text-ledger-muted">
        Already registered? <a href="login.php" class="text-ledger-steel underline hover:text-ledger-navy">Sign in</a>
      </p>
    </form>
  </div>

  <p class="text-[11px] text-ledger-muted mt-6 tracking-wide">ICT SYSTEMS &amp; PROJECTS REGISTRY</p>
</div>

<script src="assets/js/validation.js"></script>
</body>
</html>