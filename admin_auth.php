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

function checkAdminPermissions($userId) {
    global $conn;

    $stmt = $conn->prepare("SELECT dodawanie, edytowanie, usuwanie FROM Permisje_AP WHERE id_uzytkownika = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $permissions = $result->fetch_assoc();
        return [
            'dodawanie' => (bool)$permissions['dodawanie'],
            'edytowanie' => (bool)$permissions['edytowanie'],
            'usuwanie' => (bool)$permissions['usuwanie']
        ];
    }

    return [
        'dodawanie' => false,
        'edytowanie' => false,
        'usuwanie' => false
    ];
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(false, 'Nieprawidłowa metoda żądania');
        }

        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            sendResponse(false, 'Login i hasło są wymagane');
        }

        $stmt = $conn->prepare("SELECT id, login, haslo, stanowisko FROM Admin_panel WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, 'Nieprawidłowy login lub hasło');
        }

        $admin = $result->fetch_assoc();

        // In production, use password_hash() and password_verify()
        // For now, assuming plain text passwords (CHANGE THIS IN PRODUCTION!)
        if ($password !== $admin['haslo']) {
            sendResponse(false, 'Nieprawidłowy login lub hasło');
        }

        // Login successful
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_login'] = $admin['login'];
        $_SESSION['admin_stanowisko'] = $admin['stanowisko'];

        $permissions = checkAdminPermissions($admin['id']);

        sendResponse(true, 'Zalogowano pomyślnie', [
            'admin' => [
                'id' => $admin['id'],
                'login' => $admin['login'],
                'stanowisko' => $admin['stanowisko']
            ],
            'permissions' => $permissions
        ]);
        break;

    case 'logout':
        session_destroy();
        sendResponse(true, 'Wylogowano pomyślnie');
        break;

    case 'check_session':
        if (isset($_SESSION['admin_id'])) {
            $permissions = checkAdminPermissions($_SESSION['admin_id']);

            sendResponse(true, 'Sesja aktywna', [
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'login' => $_SESSION['admin_login'],
                    'stanowisko' => $_SESSION['admin_stanowisko']
                ],
                'permissions' => $permissions
            ]);
        } else {
            sendResponse(false, 'Brak aktywnej sesji');
        }
        break;

    case 'check_permission':
        if (!isset($_SESSION['admin_id'])) {
            sendResponse(false, 'Nie jesteś zalogowany');
        }

        $requiredPermission = $_GET['permission'] ?? '';
        if (empty($requiredPermission)) {
            sendResponse(false, 'Nie podano wymaganego uprawnienia');
        }

        $permissions = checkAdminPermissions($_SESSION['admin_id']);

        if (!isset($permissions[$requiredPermission]) || !$permissions[$requiredPermission]) {
            sendResponse(false, 'Brak uprawnień do wykonania tej operacji');
        }

        sendResponse(true, 'Uprawnienie potwierdzone');
        break;

    default:
        sendResponse(false, 'Nieznana akcja');
}
?>
