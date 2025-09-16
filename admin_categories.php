<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

function sendResponse($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ] + $data);
    exit;
}

function checkAdminSession() {
    if (!isset($_SESSION['admin_id'])) {
        sendResponse(false, 'Nie jesteś zalogowany jako administrator');
    }
    return $_SESSION['admin_id'];
}

function checkAdminPermission($permission) {
    global $conn;

    $adminId = checkAdminSession();

    $stmt = $conn->prepare("SELECT {$permission} FROM Permisje_AP WHERE id_uzytkownika = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse(false, 'Brak uprawnień administratora');
    }

    $permissions = $result->fetch_assoc();
    if (!$permissions[$permission]) {
        sendResponse(false, 'Brak uprawnień do wykonania tej operacji');
    }

    return true;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_categories':
        // Categories can be viewed by any logged-in admin
        checkAdminSession();

        $query = "SELECT * FROM Kategorie ORDER BY kategoria ASC";
        $result = $conn->query($query);
        $categories = [];

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        sendResponse(true, '', ['categories' => $categories]);
        break;

    case 'add_category':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('dodawanie');

        $categoryName = $_POST['kategoria'] ?? '';
        if (empty($categoryName)) {
            sendResponse(false, 'Nazwa kategorii jest wymagana');
        }

        // Check if category already exists
        $stmt = $conn->prepare("SELECT id FROM Kategorie WHERE kategoria = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            sendResponse(false, 'Kategoria o tej nazwie już istnieje');
        }

        // Add new category
        $stmt = $conn->prepare("INSERT INTO Kategorie (kategoria) VALUES (?)");
        $stmt->bind_param("s", $categoryName);

        if ($stmt->execute()) {
            sendResponse(true, 'Kategoria została dodana pomyślnie');
        } else {
            sendResponse(false, 'Błąd podczas dodawania kategorii: ' . $conn->error);
        }
        break;

    case 'delete_category':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('usuwanie');

        $categoryId = $_POST['id'] ?? '';
        if (empty($categoryId)) {
            sendResponse(false, 'ID kategorii jest wymagane');
        }

        // Check if category is used by any movies
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Filmy WHERE kategoria = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count > 0) {
            sendResponse(false, "Nie można usunąć kategorii. Jest używana przez {$count} filmów.");
        }

        // Delete category
        $stmt = $conn->prepare("DELETE FROM Kategorie WHERE id = ?");
        $stmt->bind_param("i", $categoryId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(true, 'Kategoria została usunięta pomyślnie');
            } else {
                sendResponse(false, 'Kategoria nie została znaleziona');
            }
        } else {
            sendResponse(false, 'Błąd podczas usuwania kategorii: ' . $conn->error);
        }
        break;

    case 'get_licenses':
        // Licenses can be viewed by any logged-in admin
        checkAdminSession();

        $query = "SELECT * FROM Licencje ORDER BY typ ASC";
        $result = $conn->query($query);
        $licenses = [];

        while ($row = $result->fetch_assoc()) {
            $licenses[] = $row;
        }

        sendResponse(true, '', ['licenses' => $licenses]);
        break;

    case 'add_license':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('dodawanie');

        $licenseType = $_POST['typ'] ?? '';
        $licensePrice = $_POST['cena'] ?? '';

        if (empty($licenseType)) {
            sendResponse(false, 'Typ licencji jest wymagany');
        }

        if (empty($licensePrice) || !is_numeric($licensePrice)) {
            sendResponse(false, 'Cena licencji musi być liczbą');
        }

        // Check if license type already exists
        $stmt = $conn->prepare("SELECT id FROM Licencje WHERE typ = ?");
        $stmt->bind_param("s", $licenseType);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            sendResponse(false, 'Licencja o tym typie już istnieje');
        }

        // Add new license
        $stmt = $conn->prepare("INSERT INTO Licencje (typ, cena) VALUES (?, ?)");
        $stmt->bind_param("sd", $licenseType, $licensePrice);

        if ($stmt->execute()) {
            sendResponse(true, 'Licencja została dodana pomyślnie');
        } else {
            sendResponse(false, 'Błąd podczas dodawania licencji: ' . $conn->error);
        }
        break;

    case 'delete_license':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('usuwanie');

        $licenseId = $_POST['id'] ?? '';
        if (empty($licenseId)) {
            sendResponse(false, 'ID licencji jest wymagane');
        }

        // Check if license is used by any movies
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Filmy WHERE typ_licencji = ?");
        $stmt->bind_param("i", $licenseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count > 0) {
            sendResponse(false, "Nie można usunąć licencji. Jest używana przez {$count} filmów.");
        }

        // Delete license
        $stmt = $conn->prepare("DELETE FROM Licencje WHERE id = ?");
        $stmt->bind_param("i", $licenseId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(true, 'Licencja została usunięta pomyślnie');
            } else {
                sendResponse(false, 'Licencja nie została znaleziona');
            }
        } else {
            sendResponse(false, 'Błąd podczas usuwania licencji: ' . $conn->error);
        }
        break;

    case 'update_license':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('edytowanie');

        $licenseId = $_POST['id'] ?? '';
        $licenseType = $_POST['typ'] ?? '';
        $licensePrice = $_POST['cena'] ?? '';

        if (empty($licenseId)) {
            sendResponse(false, 'ID licencji jest wymagane');
        }

        if (empty($licenseType)) {
            sendResponse(false, 'Typ licencji jest wymagany');
        }

        if (empty($licensePrice) || !is_numeric($licensePrice)) {
            sendResponse(false, 'Cena licencji musi być liczbą');
        }

        // Update license
        $stmt = $conn->prepare("UPDATE Licencje SET typ = ?, cena = ? WHERE id = ?");
        $stmt->bind_param("sdi", $licenseType, $licensePrice, $licenseId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(true, 'Licencja została zaktualizowana pomyślnie');
            } else {
                sendResponse(false, 'Licencja nie została znaleziona lub nie dokonano zmian');
            }
        } else {
            sendResponse(false, 'Błąd podczas aktualizacji licencji: ' . $conn->error);
        }
        break;

    default:
        sendResponse(false, 'Nieznana akcja');
}
?>
