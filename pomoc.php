<?php
require_once 'db.php'; // Połączenie z bazą danych

$categories = [];
$formMessage = '';
$messageType = '';
$showAlert = false; // Nowa zmienna do kontrolowania wyświetlania alertu

// Logowanie do pliku
function log_debug($msg) {
    file_put_contents('debug.log', $msg . PHP_EOL, FILE_APPEND);
}

// ====== POBIERANIE KATEGORII (dla wyświetlenia formularza) ======
try {
    $sql = "SELECT id, string_techniczna FROM Kategorie_problemow ORDER BY string_techniczna ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        $formMessage = 'Błąd podczas ładowania kategorii: ' . $conn->error;
        $messageType = 'error';
        log_debug("Błąd ładowania kategorii: " . $conn->error);
    }
} catch (Exception $e) {
    $formMessage = 'Wystąpił wyjątek podczas ładowania kategorii: ' . $e->getMessage();
    $messageType = 'error';
    log_debug("Wyjątek ładowania kategorii: " . $e->getMessage());
}

// ====== OBSŁUGA WYSYŁKI FORMULARZA (POST) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = $_POST['category'] ?? '';
    $email = $_POST['email'] ?? '';
    $description = $_POST['description'] ?? '';

    log_debug("=== NOWE ZGŁOSZENIE (POST) ===");
    log_debug("Kategoria: " . $categoryName);
    log_debug("Email: " . $email);
    log_debug("Opis: " . $description);

    // Walidacja
    if (empty($categoryName) || empty($email) || empty($description)) {
        $formMessage = 'Wszystkie pola są wymagane.';
        $messageType = 'error';
        log_debug("Walidacja nieudana: Puste pola.");
    } else {
        try {
            // Sprawdzenie czy kategoria istnieje i pobranie jej ID
            $stmt = $conn->prepare("SELECT id FROM Kategorie_problemow WHERE string_techniczna = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania SELECT: " . $conn->error);
            }

            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $result = $stmt->get_result();
            $categoryRow = $result->fetch_assoc();
            $stmt->close();

            if (!$categoryRow) {
                $formMessage = 'Wybrana kategoria nie istnieje.';
                $messageType = 'error';
                log_debug("Kategoria nie istnieje: " . $categoryName);
            } else {
                $categoryId = $categoryRow['id'];
                log_debug("ID kategorii: " . $categoryId);

                // Wstawienie zgłoszenia do tabeli Zgloszenia
                $stmt = $conn->prepare("INSERT INTO Zgloszenia (kat_problemu, email, opis) VALUES (?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Błąd przygotowania INSERT: " . $conn->error);
                }

                $stmt->bind_param("iss", $categoryId, $email, $description);
                $executed = $stmt->execute();
                $stmt->close();

                if ($executed) {
                    $formMessage = 'Zgłoszenie zostało wysłane pomyślnie.';
                    $messageType = 'success';
                    $showAlert = true; // Ustaw flagę na true, aby wyświetlić alert
                    log_debug("Zgłoszenie zapisane OK.");
                    // Opcjonalnie: wyczyść pola formularza po sukcesie
                    $_POST['category'] = ''; // Clear selected category
                    $_POST['email'] = '';
                    $_POST['description'] = '';
                } else {
                    throw new Exception("Błąd wykonania INSERT: " . $conn->error);
                }
            }
        } catch (Exception $e) {
            $formMessage = 'Błąd serwera: ' . $e->getMessage();
            $messageType = 'error';
            log_debug("BŁĄD: " . $e->getMessage());
        }
    }
    log_debug("=== KONIEC ZGŁOSZENIA ===\n");
}

$conn->close(); // Zamknij połączenie z bazą danych po wszystkich operacjach
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomoc - Wfo</title>
    <meta name="description" content="Strona pomocy WFO - zgłoś problem lub znajdź odpowiedzi na często zadawane pytania.">
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
    <main id="main-content" role="main" class="help-page-main">
        <div class="help-container">
            <div class="help-header">
                <i data-lucide="help-circle" class="help-logo"></i>
                <h1 class="help-title">Centrum Pomocy</h1>
                <p class="help-subtitle">Wypełnij poniższy formularz, aby zgłosić problem lub zadać pytanie</p>
            </div>

          
                <?php if (!empty($formMessage)): ?>
                    <div id="formMessage" class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert" aria-live="polite">
                        <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($formMessage); ?></span>
                    </div>
                <?php endif; ?>

                <form id="helpForm" class="help-form" action="pomoc.php" method="POST">
                    <div class="form-group">
                        <label for="categorySelect">Kategoria problemu</label>
                        <div class="input-icon">
                            <i data-lucide="tag"></i>
                            <select id="categorySelect" name="category" required>
                                <?php if (empty($categories)): ?>
                                    <option value="">Brak dostępnych kategorii</option>
                                <?php else: ?>
                                    <option value="">Wybierz kategorię problemu</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['string_techniczna']); ?>"
                                            <?php echo (isset($_POST['category']) && $_POST['category'] == $cat['string_techniczna']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['string_techniczna']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="emailInput">Adres e-mail</label>
                        <div class="input-icon">
                            <i data-lucide="mail"></i>
                            <input type="email" id="emailInput" name="email" placeholder="twoj.email@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descriptionTextarea">Opis problemu</label>
                        <div class="textarea-wrapper">
                            <i data-lucide="message-square" class="textarea-icon"></i>
                            <textarea id="descriptionTextarea" name="description" rows="6" placeholder="Szczegółowo opisz problem, z którym się zmagasz..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="help-submit">
                        <i data-lucide="send"></i>
                        <span>Wyślij zgłoszenie</span>
                    </button>
                </form>

                <div class="help-back">
                    <a href="index.php" class="back-link">
                        <i data-lucide="arrow-left"></i>
                        Powróć na stronę główną
                    </a>
                </div>
            
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="shared.js?v=2.6"></script>
    <script src="app.js?v=2.6"></script>

    <?php if ($showAlert): ?>
    <script>
        alert("Zgłoszenie zostało wysłane pomyślnie.");
        // Opcjonalnie, aby zapobiec ponownemu wyświetleniu alertu po odświeżeniu strony
        // Można przekierować użytkownika lub usunąć parametr z URL
        // window.location.href = window.location.pathname;
    </script>
    <?php endif; ?>
</body>
</html>
