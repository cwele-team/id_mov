<?php
// functions.php - funkcje pomocnicze do wyświetlania płatności

/**
 * Konwertuje typ płatności z PayByLink na przyjazną nazwę po polsku
 */
function getPaymentMethodName($paymentType) {
    $methods = [
        'TRANSFER' => 'Przelew bankowy',
        'CARD' => 'Karta płatnicza',
        'BLIK' => 'BLIK',
        'GOOGLE_PAY' => 'Google Pay',
        'APPLE_PAY' => 'Apple Pay',
        'PAYPAL' => 'PayPal',
        'ONLINE' => 'Płatność online',
        'OTHER' => 'Inna metoda'
    ];
    
    return $methods[$paymentType] ?? 'Nieznana metoda';
}

/**
 * Zwraca ikonę CSS lub emoji dla metody płatności
 */
function getPaymentMethodIcon($paymentType) {
    $icons = [
        'TRANSFER' => '🏦',
        'CARD' => '💳',
        'BLIK' => '📱',
        'GOOGLE_PAY' => '🟢',
        'APPLE_PAY' => '🍎',
        'PAYPAL' => '🅿️',
        'ONLINE' => '💻',
        'OTHER' => '💰'
    ];
    
    return $icons[$paymentType] ?? '💰';
}

/**
 * Konwertuje status płatności na przyjazną nazwę po polsku
 */
function getPaymentStatusName($status) {
    $statuses = [
        'PAID' => 'Opłacone',
        'PENDING' => 'Oczekuje',
        'FAILED' => 'Nieudane',
        'CANCELLED' => 'Anulowane',
        'REFUNDED' => 'Zwrócone',
        'UNKNOWN' => 'Nieznany'
    ];
    
    return $statuses[$status] ?? 'Nieznany';
}

/**
 * Zwraca klasę CSS dla statusu płatności
 */
function getPaymentStatusClass($status) {
    $classes = [
        'PAID' => 'status-paid',
        'PENDING' => 'status-pending', 
        'FAILED' => 'status-failed',
        'CANCELLED' => 'status-cancelled',
        'REFUNDED' => 'status-refunded',
        'UNKNOWN' => 'status-unknown'
    ];
    
    return $classes[$status] ?? 'status-unknown';
}

/**
 * Zwraca emoji dla statusu płatności
 */
function getPaymentStatusIcon($status) {
    $icons = [
        'PAID' => '✅',
        'PENDING' => '⏳',
        'FAILED' => '❌',
        'CANCELLED' => '🚫',
        'REFUNDED' => '↩️',
        'UNKNOWN' => '❓'
    ];
    
    return $icons[$status] ?? '❓';
}

/**
 * Pobiera historię płatności użytkownika
 */
function getUserPaymentHistory($userId, $conn) {
    $sql = "SELECT p.*, f.tytul as film_title, s.data_rozpoczecia, s.data_wygasniecia 
            FROM Platnosci p 
            LEFT JOIN Filmy f ON p.id_filmu = f.id 
            LEFT JOIN Subskrybcje s ON p.id = s.id_platnosci 
            WHERE p.id_uzytkownika = ? 
            ORDER BY p.id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Sprawdza czy użytkownik ma aktywną subskrypcję dla filmu
 */
function hasActiveSubscription($userId, $movieId, $conn) {
    $sql = "SELECT COUNT(*) as count FROM Subskrybcje s
            JOIN Platnosci p ON s.id_platnosci = p.id
            WHERE s.id_uzytkownika = ? AND s.id_filmu = ? 
            AND s.data_wygasniecia >= CURDATE()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $movieId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] > 0;
}
?>

<!-- Przykład użycia w HTML -->
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Historia płatności</title>
    <style>
        .payment-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e3f2fd;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .payment-status {
            float: right;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-paid { background: #4caf50; color: white; }
        .status-pending { background: #ff9800; color: white; }
        .status-failed { background: #f44336; color: white; }
        .status-cancelled { background: #9e9e9e; color: white; }
        .status-refunded { background: #2196f3; color: white; }
        .status-unknown { background: #607d8b; color: white; }
    </style>
</head>
<body>

<?php
// Przykład wyświetlania historii płatności
include 'functions.php';

// Połączenie z bazą danych
$conn = new mysqli("192.168.1.1", "f22305", "ItrjzveHI8", "db_f22305");
$conn->set_charset("utf8");

// Pobierz ID użytkownika z sesji
$sessionData = json_decode($_COOKIE['wfo_session'] ?? '{}', true);
$userId = $sessionData['id'] ?? 0;

if ($userId > 0) {
    $payments = getUserPaymentHistory($userId, $conn);
    
    echo "<h2>Historia płatności</h2>";
    
    foreach ($payments as $payment) {
        $methodName = getPaymentMethodName($payment['typ_platnosci']);
        $methodIcon = getPaymentMethodIcon($payment['typ_platnosci']);
        
        $statusName = getPaymentStatusName($payment['status'] ?? 'UNKNOWN');
        $statusIcon = getPaymentStatusIcon($payment['status'] ?? 'UNKNOWN');
        $statusClass = getPaymentStatusClass($payment['status'] ?? 'UNKNOWN');
        
        echo "<div class='payment-item'>";
        echo "<h3>{$payment['film_title']}</h3>";
        echo "<div class='payment-method'>";
        echo "<span>{$methodIcon}</span>";
        echo "<span>{$methodName}</span>";
        echo "</div>";
        echo "<div class='payment-status {$statusClass}'>";
        echo "<span>{$statusIcon}</span>";
        echo "<span>{$statusName}</span>";
        echo "</div>";
        echo "<div style='clear: both;'></div>";
        echo "<p><strong>ID transakcji:</strong> {$payment['id_transakcji']}</p>";
        
        if ($payment['data_rozpoczecia']) {
            echo "<p><strong>Dostęp od:</strong> {$payment['data_rozpoczecia']} do {$payment['data_wygasniecia']}</p>";
        }
        
        echo "</div>";
    }
}
?>

</body>
</html>
