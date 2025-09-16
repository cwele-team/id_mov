<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorie - WFO</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime('styles.css') ?>">
    <link rel="icon" type="image/png" href="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="category-container">
            <!-- Search Section -->


            <div class="category-filter">
                <div class="category-header">
                    <h2>Wybierz kategorię</h2>
                </div>
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <i data-lucide="search" class="search-icon"></i>
                        <input 
                            type="text" 
                            id="movie-search-input" 
                            class="search-input" 
                            placeholder="Wyszukaj filmy po tytule, kategorii, opisie..."
                            autocomplete="off"
                            aria-label="Wyszukaj filmy"
                        >
                        <button id="clear-search" class="clear-search-btn" aria-label="Wyczyść wyszukiwanie" style="display: none;">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <div id="search-suggestions"  style="display: none;" role="listbox" ></div>
                </div>
              
                <div class="category-tags" id="category-tags">
                    <!-- Category buttons will be inserted here by JavaScript -->
                </div>
            </div>
            <div class="movie-grid" id="movies-grid">
                <!-- Movies will be inserted here by JavaScript -->
            </div>
            <div class="no-results" id="no-results" style="display: none;">
                <div class="no-results-content">
                    <i data-lucide="search-x" class="no-results-icon"></i>
                    <h3>Brak wyników</h3>
                    <p>Nie znaleziono filmów spełniających kryteria wyszukiwania.</p>
                    <button id="reset-search" class="btn btn-primary">
                        <i data-lucide="refresh-cw"></i>
                        <span>Wyczyść filtry</span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="shared.js?v=<?= filemtime('shared.js') ?>"></script>
    <script src="app.js?v=<?= filemtime('app.js') ?>"></script>
</body>
</html>
