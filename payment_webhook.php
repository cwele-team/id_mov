<?php
// payment_webhook.php - zabezpieczony przed duplikatami

header('Content-Type: text/plain; charset=utf-8');

// === Konfiguracja ===
$secret = 'Tp5Ji6Sw3Md8Yh3P@9I@9Rz2Mo8Fk5Of';
$algo = 'sha256';
$logFile = __DIR__ . '/debug_notify.log';

// === Połączenie z bazą danych ===
$conn = new mysqli("192.168.1.1", "f22305", "ItrjzveHI8", "db_f22305");
if ($conn->connect_error) {
    logDebug("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo "Database error";
    exit;
}
$conn->set_charset("utf8");

// === Funkcja logowania ===
function logDebug($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND | LOCK_EX);
}

// === Funkcja określania statusu ===
function getPaymentStatus($data) {
    if (isset($data['status'])) {
        switch ($data['status']) {
            case 'COMPLETED':
            case 'SUCCESS':
                return 'PAID';
            case 'PENDING':
                return 'PENDING';
            case 'FAILED':
            case 'CANCELLED':
                return 'FAILED';
            default:
                return 'UNKNOWN';
        }
    }
    return 'PAID'; // PayByLink wywołuje webhook tylko przy udanych płatnościach
}

try {
    // === 1. Pobierz surowe dane POST ===
    $raw = file_get_contents('php://input');
    logDebug("=== WEBHOOK START (Attempt: " . ($_POST['notificationAttempt'] ?? 'unknown') . ") ===");
    logDebug("Raw POST: " . $raw);

    // === 2. Dekoduj JSON ===
    $data = json_decode($raw, true);
    if (!$data) {
        logDebug("Błędne dane JSON: " . json_last_error_msg());
        http_response_code(400);
        echo "Błędne dane";
        exit;
    }

    $notificationAttempt = $data['notificationAttempt'] ?? 1;
    logDebug("Processing notification attempt: $notificationAttempt");

    // === 3. Formatowanie kwoty i budowa stringa do podpisu ===
    $amountPaidFormatted = number_format((float)$data['amountPaid'], 2, '.', '');
    $signString = sprintf(
        "%s|%s|%s|%s|%s|%d|%s|%d",
        $secret,
        $data['transactionId'],
        $data['control'],
        $data['email'],
        $amountPaidFormatted,
        $data['notificationAttempt'],
        $data['paymentType'],
        $data['apiVersion']
    );
    $signatureCalc = hash($algo, $signString);

    // === 4. Weryfikacja podpisu ===
    if (!hash_equals($signatureCalc, $data['signature'])) {
        logDebug("Nieprawidłowy podpis!");
        http_response_code(403);
        echo "Nieprawidłowy podpis";
        exit;
    }
    logDebug("Signature verification: OK");

    // === 5. Parsowanie pola control ===
    $controlParts = explode('-', $data['control']);
    if (count($controlParts) !== 2) {
        logDebug("Nieprawidłowy format control: " . $data['control']);
        http_response_code(400);
        echo "Invalid control data";
        exit;
    }
    $userId = intval($controlParts[0]);
    $movieId = intval($controlParts[1]);

    // === 6. Sprawdź film ===
    $stmt = $conn->prepare("SELECT typ_licencji FROM Filmy WHERE id = ?");
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$movie = $result->fetch_assoc()) {
        logDebug("Movie not found: $movieId");
        http_response_code(400);
        echo "Movie not found";
        exit;
    }

    // === 7. Sprawdź czy płatność już istnieje - szybkie sprawdzenie ===
    $stmt = $conn->prepare("SELECT id, status FROM Platnosci WHERE id_transakcji = ? LIMIT 1");
    $stmt->bind_param("s", $data['transactionId']);
    $stmt->execute();
    $existingPayment = $stmt->get_result()->fetch_assoc();
    
    if ($existingPayment) {
        logDebug("Payment already exists (ID: {$existingPayment['id']}) - returning OK immediately");
        http_response_code(200);
        echo "OK";
        exit;
    }

    // === 8. Określ status i dane płatności ===
    $paymentStatus = getPaymentStatus($data);
    $paymentType = $data['paymentType'] ?? 'online';

    // === 9. Użyj INSERT IGNORE aby uniknąć duplikatów ===
    $stmt = $conn->prepare("INSERT IGNORE INTO Platnosci (id_filmu, id_uzytkownika, id_transakcji, typ_platnosci, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        logDebug("Prepare failed: " . $conn->error);
        http_response_code(500);
        echo "Database error";
        exit;
    }
    
    $stmt->bind_param("iisss", $movieId, $userId, $data['transactionId'], $paymentType, $paymentStatus);
    
    if (!$stmt->execute()) {
        logDebug("Execute failed: " . $stmt->error);
        http_response_code(500);
        echo "Database error";
        exit;
    }

    $paymentId = $conn->insert_id;
    
    if ($paymentId > 0) {
        // Nowy rekord został dodany
        logDebug("NEW payment saved with ID: $paymentId");
        
        // === 10. Dodaj subskrypcję tylko dla nowych płatności ===
        if ($paymentStatus === 'PAID') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 month'));

            // Użyj INSERT IGNORE także dla subskrypcji
            $stmt = $conn->prepare("INSERT IGNORE INTO Subskrybcje (id_uzytkownika, id_filmu, id_platnosci, data_rozpoczecia, data_wygasniecia) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiiss", $userId, $movieId, $paymentId, $startDate, $endDate);
                if ($stmt->execute()) {
                    if ($conn->insert_id > 0) {
                        logDebug("NEW subscription added");
                    } else {
                        logDebug("Subscription already exists (INSERT IGNORE)");
                    }
                } else {
                    logDebug("Subscription insert failed: " . $stmt->error);
                }
            }
        }
    } else {
        // Rekord już istniał (INSERT IGNORE nie dodał nowego)
        logDebug("Payment already existed (INSERT IGNORE prevented duplicate)");
    }

    // === 11. Zawsze odpowiedz OK ===
    http_response_code(200);
    echo "OK";
    logDebug("Response: OK (attempt $notificationAttempt)");

} catch (Exception $e) {
    logDebug("EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo "Server error";
} catch (Error $e) {
    logDebug("FATAL ERROR: " . $e->getMessage());
    http_response_code(500);
    echo "Server error";
}

logDebug("=== WEBHOOK END ===");
?>
