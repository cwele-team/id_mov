<!DOCTYPE html>
    <html lang="pl">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Odtwarzacz - WFO</title>
        <meta name="description" content="Odtwarzacz filmów WFO - oglądaj filmy archiwalne w wysokiej jakości">
        <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>" />
        <link
          rel="icon"
          type="image/png"
          href="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png"
        />
        <script src="https://unpkg.com/lucide@latest" defer></script>
      </head>
      <body class="player-page">
        <!-- Skip to main content link for screen readers -->
        <a href="#main-content" class="skip-link">Przejdź do odtwarzacza</a>

        <!-- Overlay -->
        <div class="overlay-dark"></div>

        <!-- Navbar -->
        <?php include 'navbar.php'; ?>

        <!-- Video Player Section -->
        <main id="main-content" class="player-container" role="main">
          <section class="video-player" aria-labelledby="video-title">
            <div id="moviePlayer" style="width: 100%; height: 400px; background: #000; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff;">
              <div style="text-align: center;">
                <i data-lucide="play-circle" style="width: 64px; height: 64px; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="opacity: 0.7;">Inicjalizacja odtwarzacza...</p>
              </div>
            </div>
          </section>
          <section class="movie-info" aria-labelledby="video-title">
            <div class="movie-info-header">
              <h1 id="movieTitle" class="featured-title" tabindex="0"></h1>
              <div class="movie-meta">
                <span id="movieRating" class="rating"></span>
                <span id="movieYear"></span>
                <span id="movieGenre"></span>
                <span id="movieAuthors" class="authors"></span>
              </div>
            </div>
            <p id="movieDescription" class="movie-description"></p>
            <!-- Moved button here -->
          </section>
        </main>

        <!-- Footer -->
        <?php include 'footer.php'; ?>

        <!-- JavaScript -->
        <script src="shared.js?v=<?= filemtime('shared.js') ?>"></script>
        <script src="app.js?v=<?= filemtime('app.js') ?>"></script>
        <script src="player.js?v=<?= filemtime('player.js') ?>"></script>
      </body>
    </html>
