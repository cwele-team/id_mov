<?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Połączenie z bazą danych
    require_once 'db.php';

    // Check if user is logged in and session contains user ID
    if (!isset($_COOKIE['wfo_session'])) {
        // If user is not logged in, redirect to login page with movieId as returnUrl
        $movieId = isset($_POST['movieId']) ? intval($_POST['movieId']) : 0;
        $loginUrl = 'Logowanie.html?returnUrl=' . urlencode($_SERVER['REQUEST_URI'] . ($movieId ? '?movieId=' . $movieId : ''));
        header("Location: $loginUrl");
        exit();
    }

    // Get user ID from wfo_session cookie
    $sessionData = json_decode($_COOKIE['wfo_session'], true);
    if (!isset($sessionData['id'])) {
        // If user ID is not in session, redirect to login page
        $movieId = isset($_POST['movieId']) ? intval($_POST['movieId']) : 0;
        $loginUrl = 'Logowanie.html?returnUrl=' . urlencode($_SERVER['REQUEST_URI'] . ($movieId ? '?movieId=' . $movieId : ''));
        header("Location: $loginUrl");
        exit();
    }

    if (!isset($_POST['movieId'])) {
        die('Brak ID filmu');
    }

    $movieId = intval($_POST['movieId']);
    $userId = $sessionData['id'];
    
    // Debug logging
    error_log("Payment request - MovieID: $movieId, UserID: $userId");

    // Pobierz typ licencji z tabeli Filmy
    $stmtMovie = $conn->prepare("SELECT id, tytul, typ_licencji FROM Filmy WHERE id = ?");
    $stmtMovie->bind_param("i", $movieId);
    $stmtMovie->execute();
    $resultMovie = $stmtMovie->get_result();

    if (!$movie = $resultMovie->fetch_assoc()) {
        error_log("Movie not found for ID: $movieId");
        die('Film nie znaleziony. ID: ' . $movieId);
    }
    
    error_log("Found movie: " . $movie['tytul'] . " (ID: " . $movie['id'] . ")");
    
    $licenseTypeId = $movie['typ_licencji'];
    
    if (!$licenseTypeId) {
        error_log("No license type set for movie ID: $movieId");
        die('Film nie ma przypisanego typu licencji.');
    }

    // Pobierz cenę z tabeli Licencje na podstawie typu licencji
    $stmtLicense = $conn->prepare("SELECT cena FROM Licencje WHERE id = ?");
    $stmtLicense->bind_param("i", $licenseTypeId);
    $stmtLicense->execute();
    $resultLicense = $stmtLicense->get_result();

    if (!$license = $resultLicense->fetch_assoc()) {
        error_log("License type not found for ID: $licenseTypeId");
        die('Typ licencji nie znaleziony. ID: ' . $licenseTypeId);
    }
    
    $price = floatval($license['cena']);
    error_log("License price: $price");


    $shopId             = intval(2139);
    $description        = strval('WFO - Zakup Filmu ' . $movieId);
    $hash               = 'Tp5Ji6Sw3Md8Yh3P@9I@9Rz2Mo8Fk5Of';
    $notifyURL          = strval('https://frog02-32305.wykr.es/payment_webhook.php');
    $returnUrlSuccess   = strval('https://frog02-32305.wykr.es/player.php?id=' . $movieId);
    $control            = strval($userId . '-' . $movieId);
    // $price              = floatval(6.00); // Cena jest teraz pobierana dynamicznie z bazy danych

    // Budujemy string do podpisu
    $data   = $hash . "|" . $shopId . "|" . sprintf("%.2f", $price);

    if ($control != null && $control != "") {
        $data .= "|" . $control;
    }

    if ($description != null && $description != "") {
        $data .= "|" . $description;
    }

    if ($notifyURL != null && $notifyURL != "") {
        $data .= "|" . $notifyURL;
    }

    if ($returnUrlSuccess != null && $returnUrlSuccess != "") {
        $data .= "|" . $returnUrlSuccess;
    }

    // Obliczamy sygnaturę SHA256
    $signature = hash('sha256', $data);

    // Przygotowujemy dane do API
    $paybylinkData  = [
        'shopId'            => $shopId,
        'price'             => $price,
        'control'           => $control,
        'description'       => $description,
        'notifyURL'         => $notifyURL,
        'returnUrlSuccess'  => $returnUrlSuccess,
        'signature'         => $signature
    ];

    $data_string = json_encode($paybylinkData);

    // Wysyłamy zapytanie do API PayByLink
    $ch = curl_init('https://secure.pbl.pl/api/v1/transfer/generate');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ));

    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result);

    if(isset($result->transactionId) && !empty($result->transactionId)) {
        $payURL = 'https://secure.pbl.pl/transfer/' . $result->transactionId;
        // Przekierowanie do strony płatności
        header("Location: $payURL");
        exit();
    } else {
        echo 'Wystąpił błąd podczas generowania transakcji.';
    }

    ?>
