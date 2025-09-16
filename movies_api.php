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

try {
    $searchTerm = $_GET['searchTerm'] ?? '';
    $limit = $_GET['limit'] ?? null; // For live search dropdown, e.g., limit=5
    $categoryId = $_GET['categoryId'] ?? null; // New: Filter by category ID

    $sql = "
        SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji,
               f.miniaturka_url, f.dostepne_jakosci, f.video_id, k.kategoria, f.tag,
               GROUP_CONCAT(DISTINCT CONCAT(a.imie, ' ', a.nazwisko) SEPARATOR ', ') as autorzy
        FROM Filmy f
        LEFT JOIN Kategorie k ON f.kategoria = k.id
        LEFT JOIN Autorzy a ON f.id = a.film_id
    ";

    $params = [];
    $types = "";
    $whereClauses = [];

    if ($categoryId !== null && is_numeric($categoryId)) {
        $whereClauses[] = "f.kategoria = ?";
        $params[] = (int)$categoryId;
        $types .= "i";
    }

    if (!empty($searchTerm)) {
        // Join Napisy table only if a search term is provided
        $sql .= " LEFT JOIN Napisy n ON f.id = n.film_id ";
        $whereClauses[] = " (f.tytul LIKE ? OR f.opis LIKE ? OR k.kategoria LIKE ? OR n.napisy LIKE ? OR f.tag LIKE ?) ";
        $searchTermWildcard = '%' . $searchTerm . '%';
        $params[] = $searchTermWildcard;
        $params[] = $searchTermWildcard;
        $params[] = $searchTermWildcard;
        $params[] = $searchTermWildcard;
        $params[] = $searchTermWildcard; // Add tag search term
        $types .= "sssss"; // Add 's' for the new tag parameter
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= "
        GROUP BY f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji,
                 f.miniaturka_url, f.dostepne_jakosci, f.video_id, k.kategoria, f.tag
        ORDER BY f.id DESC
    ";

    if ($limit !== null && is_numeric($limit)) {
        $sql .= " LIMIT ? ";
        $params[] = (int)$limit;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        // Use call_user_func_array for bind_param with dynamic parameters
        $bindParams = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], refValues($bindParams));
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $movies = [];
    while ($row = $result->fetch_assoc()) {
        // Konwertuj czas trwania z TIME na format "Xh Ym"
        $duration = $row['czas_trwania'];
        if ($duration) {
            $time = DateTime::createFromFormat('H:i:s', $duration);
            if ($time) {
                $hours = $time->format('G');
                $minutes = $time->format('i');
                $durationFormatted = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
            } else {
                $durationFormatted = 'N/A';
            }
        } else {
            $durationFormatted = 'N/A';
        }

        // Parsuj dostępne jakości
        $qualities = [];
        if ($row['dostepne_jakosci']) {
            $qualityList = explode(',', $row['dostepne_jakosci']);
            foreach ($qualityList as $quality) {
                $quality = trim($quality);
                // Placeholder URLs - w przyszłości można dodać rzeczywiste ścieżki do plików wideo
                $qualities[$quality] = "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4";
            }
        }

        // Jeśli brak jakości, dodaj domyślne
        if (empty($qualities)) {
            $qualities = [
                "1080p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "720p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "480p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "360p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4"
            ];
        }

        // Określ kategorię i gatunki
        $categoryString = $row['kategoria'] ?: 'Nieznana';
        // Split categories by comma and trim whitespace for each
        $categories = array_map('trim', explode(',', $categoryString));

        // Mapowanie kategorii na gatunki dla kompatybilności
        // Use the first category for genre mapping if multiple exist
        $primaryCategory = $categories[0] ?? 'Nieznana';
        $genreMapping = [
            'Dramat' => 'Dramat',
            'Fantasy' => 'Fantasy, Przygodowy',
            'Kryminał' => 'Kryminał, Thriller',
            'Akcja' => 'Akcja, Przygodowy',
            'Komedia' => 'Komedia',
            'Horror' => 'Horror, Thriller',
            'Sci-Fi' => 'Sci-Fi, Akcja',
            'Romans' => 'Romans, Dramat'
        ];

        $genre = $genreMapping[$primaryCategory] ?? $primaryCategory;

        $movies[] = [
            'id' => (int)$row['id'],
            'title' => $row['tytul'],
            'imageUrl' => $row['miniaturka_url'] ?: 'https://via.placeholder.com/300x450?text=Brak+obrazu',
            'year' => (string)$row['rok_produkcji'],
            'rating' => number_format((float)$row['ocena_sr'], 1) . '/5',
            'genre' => $genre,
            'description' => $row['opis'] ?: 'Brak opisu filmu.',
            'dateAdded' => date('Y-m-d'),
            'videoId' => $row['video_id'], // MediaDelivery video ID
            'videoSources' => $qualities,
            'duration' => $durationFormatted,
            'categories' => $categories, // Now an array of all categories
            'authors' => $row['autorzy'] ?: 'Nieznany',
            'tag' => $row['tag'] ?: '' // Include the tag
        ];
    }

    echo json_encode([
        'success' => true,
        'movies' => $movies,
        'count' => count($movies)
    ]);

} catch (Exception $e) {
    error_log("Movies API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'movies' => []
    ]);
}

// Helper function for bind_param with dynamic parameters
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>
