<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('db.php');

try {
    // Pobierz wszystkie kategorie z bazy danych
    $stmt = $conn->prepare("SELECT id, kategoria FROM Kategorie ORDER BY kategoria ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['kategoria']
        ];
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);

} catch (Exception $e) {
    error_log("Categories API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'categories' => []
    ]);
}
?>
