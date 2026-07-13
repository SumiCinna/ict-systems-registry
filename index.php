<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ICT Systems Registry</title>
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

  <div class="w-full max-w-xl text-center">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">ICT Systems &amp; Projects Registry</p>
    <h1 class="font-display text-3xl md:text-4xl text-ledger-navy">Application Systems &amp; ICT Project Records</h1>
    <div class="ledger-rule mt-4 mb-6"></div>
    <p class="text-sm text-ledger-muted max-w-md mx-auto leading-relaxed">
      A single record of your agency's application systems and ICT projects —
      built from the inventory template, kept current, and ready to submit.
    </p>

    <div class="mt-9 flex flex-col sm:flex-row gap-3 justify-center">
      <a href="register.php"
         class="bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 px-8 hover:bg-ledger-steel transition-colors">
        CREATE ACCOUNT
      </a>
      <a href="login.php"
         class="border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 px-8 hover:bg-white transition-colors">
        SIGN IN
      </a>
    </div>
  </div>

  <p class="text-[11px] text-ledger-muted mt-14 tracking-wide">ICT SYSTEMS &amp; PROJECTS REGISTRY</p>
</div>

</body>
</html>