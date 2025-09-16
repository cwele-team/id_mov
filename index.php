<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona główna - Wfo</title>
    <meta name="description" content="Strona filmowa WFO - oglądaj filmy archiwalne oraz sprzed dawnych lat, wykupuj licencje do filmów">
    <link rel="stylesheet" href="style.css?v=2.6">
    <link rel="icon" type="image/png" href="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png">

    <script src="smooth-scroll.js?v=2.6"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="lock.js?v=2.6"></script>
    <script src="lock2.js?v=2.6"></script>
    <script src="https://unpkg.com/lucide@latest" defer></script>

</head>
<body>
    <!-- Skip to main content link for screen readers -->
    <a href="#main-content" class="skip-link">Przejdź do głównej zawartości</a>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Featured Movie -->
        <section class="featured-movie" aria-labelledby="featured-title">
            <div class="featured-backdrop"></div>

            <div class="featured-content">
                <h1 id="featured-title" class="featured-title">Tytuł</h1>
                <div class="movie-meta">
                    <span class="rating">ocena</span>
                    <span class="year">rok produkcji</span>
                    <span class="duration">czas trwania</span>
                </div>
                <p class="movie-description">
                    Opis
                </p>
                <div class="action-buttons">
                    <a href="/movie/1" class="btn btn-primary">
                        <i data-lucide="play"></i>
                        <span>Wypożycz teraz!</span>
                    </a>
                    <button class="btn btn-secondary">
                        <i data-lucide="plus"></i>
                        <span>Dodaj do listy</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Movie Categories -->
        <section class="categories" aria-label="Kategorie filmów">
            <div class="category-row" role="region" aria-labelledby="watchlist-heading">
                <div class="category-header">
                    <h2 id="watchlist-heading">Twoja lista do obejrzenia</h2>
                    <a href="Lista_do_obejrzenia.php" class="view-all">Zobacz swoją liste</a>
                </div>
                <div class="movie-row" id="watchlist" role="list" aria-label="Filmy na liście do obejrzenia">
                    <!-- Movies will be inserted here by JavaScript -->
                </div>
            </div>

            <div class="category-row" role="region" aria-labelledby="recommended-heading">
                <div class="category-header">
                    <h2 id="recommended-heading">Polecane filmy</h2>
                    <a href="Kategorie.php" class="view-all">Zobacz wszystkie</a>
                </div>
                <div class="movie-row" id="recommended-movies" role="list" aria-label="Polecane filmy">
                    <!-- Movies will be inserted here by JavaScript -->
                </div>
            </div>

            <!-- Dynamic category sections will be populated by JavaScript -->
            <div id="dynamic-category-sections">
                <!-- Categories and movies will be inserted here by JavaScript -->
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="shared.js?v=2.6"></script>
    <script src="app.js?v=2.6"></script>
</body>
</html>
