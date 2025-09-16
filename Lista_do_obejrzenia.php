<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista do obejrzenia - WFO</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime('styles.css') ?>">
    <link rel="icon" type="image/png" href="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="categories">
            <div class="category-row">
                <div class="category-header">
                    <h2>Twoja lista do obejrzenia</h2>
                </div>
                <div class="movie-row" id="watchlist">
                    <!-- Movies will be inserted here by JavaScript -->
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
