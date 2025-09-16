<?php
// admin_payments.php - panel statystyk płatności

include 'functions.php';

// Połączenie z bazą danych
$conn = new mysqli("192.168.1.1", "f22305", "ItrjzveHI8", "db_f22305");
$conn->set_charset("utf8");

// Statystyki statusów płatności
$statusStats = $conn->query("
    SELECT status, COUNT(*) as count, SUM(6.00) as total_amount 
    FROM Platnosci 
    GROUP BY status 
    ORDER BY count DESC
")->fetch_all(MYSQLI_ASSOC);

// Statystyki metod płatności
$methodStats = $conn->query("
    SELECT typ_platnosci, COUNT(*) as count, SUM(6.00) as total_amount 
    FROM Platnosci 
    WHERE status = 'PAID'
    GROUP BY typ_platnosci 
    ORDER BY count DESC
")->fetch_all(MYSQLI_ASSOC);

// Ostatnie płatności
$recentPayments = $conn->query("
    SELECT p.*, f.tytul as film_title, u.login as user_login 
    FROM Platnosci p 
    LEFT JOIN Filmy f ON p.id_filmu = f.id 
    LEFT JOIN Uzytkownicy u ON p.id_uzytkownika = u.id 
    ORDER BY p.id DESC 
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administracyjny - Płatności</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        .stat-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
        .stat-item:last-child { border-bottom: none; }
        .payment-table { width: 100%; border-collapse: collapse; }
        .payment-table th, .payment-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .payment-table th { background: #f0f0f0; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-paid { background: #4caf50; color: white; }
        .status-pending { background: #ff9800; color: white; }
        .status-failed { background: #f44336; color: white; }
        .method-badge { padding: 4px 8px; background: #e3f2fd; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>

<h1>Panel Administracyjny - Płatności</h1>

<div class="stats-grid">
    <div class="stat-card">
        <h3>📊 Statusy Płatności</h3>
        <?php foreach ($statusStats as $stat): ?>
            <?php 
                $statusName = getPaymentStatusName($stat['status']);
                $statusIcon = getPaymentStatusIcon($stat['status']);
            ?>
            <div class="stat-item">
                <span><?= $statusIcon ?> <?= $statusName ?></span>
                <span><strong><?= $stat['count'] ?></strong> (<?= number_format($stat['total_amount'], 2) ?> zł)</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="stat-card">
        <h3>💳 Metody Płatności (Opłacone)</h3>
        <?php foreach ($methodStats as $stat): ?>
            <?php 
                $methodName = getPaymentMethodName($stat['typ_platnosci']);
                $methodIcon = getPaymentMethodIcon($stat['typ_platnosci']);
            ?>
            <div class="stat-item">
                <span><?= $methodIcon ?> <?= $methodName ?></span>
                <span><strong><?= $stat['count'] ?></strong> (<?= number_format($stat['total_amount'], 2) ?> zł)</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<h3>🕒 Ostatnie Płatności</h3>
<table class="payment-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Użytkownik</th>
            <th>Film</th>
            <th>Metoda</th>
            <th>Status</th>
            <th>ID Transakcji</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recentPayments as $payment): ?>
            <?php 
                $statusName = getPaymentStatusName($payment['status'] ?? 'UNKNOWN');
                $statusClass = getPaymentStatusClass($payment['status'] ?? 'UNKNOWN');
                $methodName = getPaymentMethodName($payment['typ_platnosci']);
            ?>
            <tr>
                <td><?= $payment['id'] ?></td>
                <td><?= htmlspecialchars($payment['user_login']) ?></td>
                <td><?= htmlspecialchars($payment['film_title']) ?></td>
                <td><span class="method-badge"><?= $methodName ?></span></td>
                <td><span class="status-badge <?= $statusClass ?>"><?= $statusName ?></span></td>
                <td><small><?= $payment['id_transakcji'] ?></small></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
