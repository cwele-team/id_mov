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

function validateMovieData($data, $isUpdate = false) {
    $errors = [];

    if (!$isUpdate || isset($data['tytul'])) {
        if (empty($data['tytul'])) {
            $errors[] = 'Tytuł jest wymagany';
        }
    }

    if (!$isUpdate || isset($data['opis'])) {
        if (empty($data['opis'])) {
            $errors[] = 'Opis jest wymagany';
        }
    }

    if (!$isUpdate || isset($data['kategoria'])) {
        if (empty($data['kategoria'])) {
            $errors[] = 'Kategoria jest wymagana';
        }
    }

    if (isset($data['rok_produkcji']) && !empty($data['rok_produkcji'])) {
        if (!is_numeric($data['rok_produkcji']) || $data['rok_produkcji'] > 2030) {
            $errors[] = 'Rok produkcji musi być liczbą nie większą niż 2030';
        }
    }

    return $errors;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_movies':
        checkAdminSession();

        $query = "SELECT f.*, k.kategoria as kategoria_nazwa
                  FROM Filmy f
                  LEFT JOIN Kategorie k ON f.kategoria = k.id
                  ORDER BY f.id DESC";

        $result = $conn->query($query);
        $movies = [];

        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        sendResponse(true, '', ['movies' => $movies]);
        break;

    case 'get_movie':
        checkAdminSession();

        $movieId = $_GET['id'] ?? '';
        if (empty($movieId)) {
            sendResponse(false, 'ID filmu jest wymagane');
        }

        $stmt = $conn->prepare("SELECT * FROM Filmy WHERE id = ?");
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, 'Film nie został znaleziony');
        }

        $movie = $result->fetch_assoc();
        sendResponse(true, '', ['movie' => $movie]);
        break;

    case 'add_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('dodawanie');

        $data = $_POST;
        $errors = validateMovieData($data);

        if (!empty($errors)) {
            sendResponse(false, implode(', ', $errors));
        }

        $stmt = $conn->prepare("INSERT INTO Filmy (tytul, opis, czas_trwania, rok_produkcji, kategoria, miniaturka_url, dostepne_jakosci, napisy, id_uzytkownika, typ_licencji) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssississi",
            $data['tytul'],
            $data['opis'],
            $data['czas_trwania'] ?? null,
            $data['rok_produkcji'] ?? null,
            $data['kategoria'],
            $data['miniaturka_url'] ?? null,
            $data['dostepne_jakosci'] ?? null,
            $data['napisy'] ?? null,
            $data['id_uzytkownika'],
            $data['typ_licencji'] ?? null
        );

        if ($stmt->execute()) {
            sendResponse(true, 'Film został dodany pomyślnie');
        } else {
            sendResponse(false, 'Błąd podczas dodawania filmu: ' . $conn->error);
        }
        break;

    case 'update_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('edytowanie');

        $movieId = $_POST['id'] ?? '';
        if (empty($movieId)) {
            sendResponse(false, 'ID filmu jest wymagane');
        }

        $data = $_POST;
        $errors = validateMovieData($data, true);

        if (!empty($errors)) {
            sendResponse(false, implode(', ', $errors));
        }

        // Build dynamic update query
        $updateFields = [];
        $params = [];
        $types = '';

        $allowedFields = ['tytul', 'opis', 'czas_trwania', 'rok_produkcji', 'kategoria', 'miniaturka_url', 'dostepne_jakosci', 'napisy', 'typ_licencji'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];

                if (in_array($field, ['rok_produkcji', 'kategoria', 'typ_licencji'])) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
            }
        }

        if (empty($updateFields)) {
            sendResponse(false, 'Brak danych do aktualizacji');
        }

        $params[] = $movieId;
        $types .= 'i';

        $query = "UPDATE Filmy SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            sendResponse(true, 'Film został zaktualizowany pomyślnie');
        } else {
            sendResponse(false, 'Błąd podczas aktualizacji filmu: ' . $conn->error);
        }
        break;

    case 'delete_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        checkAdminPermission('usuwanie');

        $movieId = $_POST['id'] ?? '';
        if (empty($movieId)) {
            sendResponse(false, 'ID filmu jest wymagane');
        }

        // First, delete related authors
        $stmt = $conn->prepare("DELETE FROM Autorzy WHERE film_id = ?");
        $stmt->bind_param("i", $movieId);
        $stmt->execute();

        // Then delete the movie
        $stmt = $conn->prepare("DELETE FROM Filmy WHERE id = ?");
        $stmt->bind_param("i", $movieId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(true, 'Film został usunięty pomyślnie');
            } else {
                sendResponse(false, 'Film nie został znaleziony');
            }
        } else {
            sendResponse(false, 'Błąd podczas usuwania filmu: ' . $conn->error);
        }
        break;

    case 'search_movies':
        checkAdminSession();

        $searchTerm = $_GET['search'] ?? '';
        if (empty($searchTerm)) {
            sendResponse(false, 'Fraza wyszukiwania jest wymagana');
        }

        $searchTerm = '%' . $searchTerm . '%';
        $stmt = $conn->prepare("SELECT f.*, k.kategoria as kategoria_nazwa
                                FROM Filmy f
                                LEFT JOIN Kategorie k ON f.kategoria = k.id
                                WHERE f.tytul LIKE ? OR f.opis LIKE ?
                                ORDER BY f.id DESC");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        sendResponse(true, '', ['movies' => $movies]);
        break;

    default:
        sendResponse(false, 'Nieznana akcja');
}
?>
