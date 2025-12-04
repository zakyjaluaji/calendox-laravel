<?php
session_start();
if (!empty($_SESSION['username'])) {
  header('Location: index.php');
  exit;
}
$hasError = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="id" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login CalendoX</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
  <style>
    .login-container {
      max-width: 420px;
      margin: 60px auto;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .login-title {
      margin: 0 0 16px;
      text-align: center;
    }

    .login-form label {
      display: block;
      margin: 8px 0 4px;
      font-weight: 600;
    }

    .login-form input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    .login-form button {
      margin-top: 16px;
      width: 100%;
      padding: 10px 12px;
      border: none;
      border-radius: 8px;
      background: #2563eb;
      color: white;
      cursor: pointer;
    }

    .login-form button:hover {
      opacity: .95;
    }

    .login-error {
      margin-top: 12px;
      color: #b91c1c;
      background: #fee2e2;
      border: 1px solid #fecaca;
      padding: 8px 10px;
      border-radius: 6px;
    }

    .login-hint {
      margin-top: 12px;
      font-size: 12px;
      color: #555;
    }
  </style>
  <script>
    // Fokus otomatis ke username
    document.addEventListener('DOMContentLoaded', function() {
      const u = document.getElementById('username');
      if (u) u.focus();
    });
  </script>
</head>

<body>
  <!-- <div style="padding:12px;text-align:left">
    <label class="switch" title="Dark mode">
      <input type="checkbox" id="darkModeToggle">
      <span class="slider"></span>
    </label>
  </div> -->

  <div class="login-container">
    <h2 class="login-title">Masuk ke CalendoX</h2>
    <?php if ($hasError): ?>
      <div class="login-error">Login gagal. Periksa username dan password Anda.</div>
    <?php endif; ?>
    <form class="login-form" method="POST" action="proseslogin.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <p>&nbsp;</p>
      <button type="submit">Masuk</button>
    </form>
    <div class="login-hint"></div>
  </div>

  <!-- Sinkronisasi state dark mode dengan localStorage -->
  <script>
    (function() {
      const toggle = document.getElementById('darkModeToggle');
      const bodyEl = document.body;
      const saved = localStorage.getItem('darkMode') === 'true';

      // Terapkan preferensi tersimpan
      if (saved) {
        bodyEl.classList.add('dark');
        if (toggle) toggle.checked = true;
      }

      // Dengarkan perubahan toggle
      if (toggle) {
        toggle.addEventListener('change', function() {
          if (this.checked) {
            bodyEl.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
          } else {
            bodyEl.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
          }
        });
      }
    })();
  </script>
</body>

</html>