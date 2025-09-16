<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once('db.php');

// Configuration
const VIDEO_LIBRARY_ID = '474299';
const TOKEN_SECURITY_KEY = '30dc103f-8a76-4b1c-8a99-5f8c246b799c';
const TOKEN_EXPIRATION_MINUTES = 1;

function generateVideoToken($videoId, $expirationTimestamp) {
    $tokenData = TOKEN_SECURITY_KEY . $videoId . $expirationTimestamp;
    return hash('sha256', $tokenData);
}

function generateEmbedUrl($videoId) {
    // Set expiration to 1 minute from now
    $expiration = time() + (TOKEN_EXPIRATION_MINUTES * 60);

    // Generate secure token
    $token = generateVideoToken($videoId, $expiration);

    // Build embed URL
    $embedUrl = sprintf(
        'https://iframe.mediadelivery.net/embed/%s/%s?token=%s&expires=%d',
        VIDEO_LIBRARY_ID,
        $videoId,
        $token,
        $expiration
    );

    return [
        'embedUrl' => $embedUrl,
        'videoId' => $videoId,
        'token' => $token,
        'expires' => $expiration,
        'expiresAt' => date('Y-m-d H:i:s', $expiration)
    ];
}

try {
    // Get movie ID from request
    $movieId = $_GET['movieId'] ?? $_POST['movieId'] ?? $_GET['id'] ?? null;

    if (!$movieId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Movie ID is required',
            'debug' => [
                'GET' => $_GET,
                'POST' => $_POST
            ]
        ]);
        exit;
    }

    // Get video_id from database
    $stmt = $conn->prepare("SELECT video_id, tytul FROM Filmy WHERE id = ?");
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($movie = $result->fetch_assoc()) {
        $videoId = $movie['video_id'];
        $movieTitle = $movie['tytul'];

        if (!$videoId) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Video ID not found for this movie',
                'debug' => [
                    'movieId' => $movieId,
                    'movieTitle' => $movieTitle,
                    'videoId' => $videoId
                ]
            ]);
            exit;
        }

        // Generate embed URL with token
        $embedData = generateEmbedUrl($videoId);

        echo json_encode([
            'success' => true,
            'movieId' => $movieId,
            'movieTitle' => $movieTitle,
            'videoId' => $videoId,
            'embedUrl' => $embedData['embedUrl'],
            'token' => $embedData['token'],
            'expires' => $embedData['expires'],
            'expiresAt' => $embedData['expiresAt']
        ]);

    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Movie not found',
            'debug' => [
                'movieId' => $movieId,
                'query' => "SELECT video_id, tytul FROM Filmy WHERE id = $movieId"
            ]
        ]);
    }

} catch (Exception $e) {
    error_log("Video token generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'debug' => [
            'movieId' => $movieId ?? 'not set',
            'exception' => $e->getMessage()
        ]
    ]);
}
