<header class="navbar" role="banner">
        <div class="navbar-container">
            <a href="index.php" class="logo">
                <img src="https://www.wfo.com.pl/wp-content/uploads/2015/11/wfo-100x33.png" alt="Logo WFO - Strona główna" class="logo-icon" style="width: 120px; height: 60px;">
            </a>

            <nav class="nav-links" role="navigation" aria-label="Nawigacja główna">
                <a href="index.php" class="nav-link active">Strona Główna</a>
                <a href="Filmy.php" class="nav-link">Filmy</a>
                <a href="Kategorie.php" class="nav-link">Kategorie</a>
                <a href="Lista_do_obejrzenia.php" class="nav-link">Lista do obejrzenia</a>
                <a href="biuro_licencyjne.php" class="nav-link">Biuro Licencyjne</a>
            </nav>

            <div class="nav-actions">
                <button class="icon-button search-toggle" aria-label="Otwórz wyszukiwarkę">
                    <i data-lucide="search"></i>
                </button>

                <button class="icon-button menu-toggle" aria-label="Otwórz menu mobilne" aria-expanded="false">
                    <i data-lucide="menu"></i>
                </button>

               <div class="user-info">
                    <i data-lucide="user-circle"></i>
                    <span class="user-login"></span>
                    <button class="btn btn-secondary login-button" onclick="window.location.href='Logowanie.html'">Zaloguj się</button>
                    <button id="logoutBtn" class="btn btn-secondary" style="display: none;">Wyloguj</button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" role="navigation" aria-label="Menu mobilne">
            <nav class="mobile-nav" role="menu">
                <a href="index.php" class="mobile-nav-link">
                    <i data-lucide="home"></i>
                    <span>Strona Główna</span>
                </a>
                <a href="Filmy.php" class="mobile-nav-link">
                    <i data-lucide="film"></i>
                    <span>Filmy</span>
                </a>
                <a href="Kategorie.php" class="mobile-nav-link">
                    <i data-lucide="list"></i>
                    <span>Kategorie</span>
                </a>
                <a href="Lista_do_obejrzenia.php" class="mobile-nav-link">
                    <i data-lucide="bookmark"></i>
                    <span>Lista do obejrzenia</span>
                </a>
                <a href="biuro_licencyjne.php" class="mobile-nav-link">
                    <i data-lucide="briefcase"></i>
                    <span>Biuro Licencyjne</span>
                </a>
            </nav>
        </div>
    </header>