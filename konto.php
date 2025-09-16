<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Moje Konto</title>
  <link rel="stylesheet" href="style.css?v=<?= filemtime('styles.css') ?>" />
  <link rel="icon" type="image/png" href="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png">
  <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
  <!-- Navbar -->
  <?php include 'navbar.php'; ?>

  <!-- Main Content -->
  <main class="account-main">
    <h1 class="account-title">Bezpieczeństwo</h1>

    <div class="account-box">
      <!-- Hasło -->
      <a href="index.php" class="account-row border-bottom full-click">
        <div class="account-icon-text">
          <i data-lucide="lock" class="icon-secondary"></i>
          <div>
            <span class="row-title">Hasło</span>
          </div>
        </div>
        <i data-lucide="chevron-right" class="icon-secondary"></i>
      </a>

      <!-- E-mail -->
      <a href="index.php" class="account-row full-click">
        <div class="account-icon-text">
          <i data-lucide="mail" class="icon-secondary"></i>
          <div>
            <span class="row-title">E-mail</span>
            <p class="row-sub">tutaj@email.com</p>
          </div>
        </div>
        <i data-lucide="chevron-right" class="icon-secondary"></i>
      </a>
    </div>

    <!-- Logout Button -->
    <button class="btn btn-primary logout-button" style="margin-top: 2rem; width: 100%;">
      <i data-lucide="log-out"></i>
      <span>Wyloguj się</span>
    </button>
  </main>

  <!-- Footer -->
  <?php include 'footer.php'; ?>

  <script src="shared.js?v=<?= filemtime('shared.js') ?>" defer></script>
  <script src="app.js?v=<?= filemtime('app.js') ?>" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const logoutButton = document.querySelector('.logout-button');

      logoutButton.addEventListener('click', () => {
        // Clear session cookie
        document.cookie = 'wfo_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

        // Redirect to home page
        window.location.href = 'index.php';
      });
    });
  </script>
</body>
</html>
