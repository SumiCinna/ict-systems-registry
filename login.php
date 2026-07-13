<?php
session_start();
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — ICT Systems Registry</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#F7F5EF] font-body min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-md text-center">
    <?php if ($success): ?>
      <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
    <h1 class="font-display text-2xl text-[#0B2340] mb-2">Sign in</h1>
    <p class="text-sm text-[#5B6B79]">This is a placeholder — the login screen is not built yet. Ask me to build it next when you're ready.</p>
  </div>
</body>
</html>