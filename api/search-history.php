<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../dbConnection.php';

$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $query = "SELECT search_term, searched_at FROM search_history WHERE user_id = ? ORDER BY searched_at DESC LIMIT 20";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                'term' => $row['search_term'],
                'date' => $row['searched_at']
            ];
        }

        echo json_encode(['history' => $history]);
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($method === 'POST') {
        $rawTerm = $_POST['term'] ?? '';
        $term = trim($rawTerm);

        if ($term === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Search term is required']);
            $conn->close();
            exit;
        }

        if (mb_strlen($term) > 80) {
            $term = mb_substr($term, 0, 80);
        }

        $query = "INSERT INTO search_history (user_id, search_term, searched_at) VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE searched_at = NOW()";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param('is', $user_id, $term);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Keep only latest 20 items per user.
        $trimQuery = "DELETE sh FROM search_history sh
                      LEFT JOIN (
                          SELECT search_id FROM search_history WHERE user_id = ? ORDER BY searched_at DESC LIMIT 20
                      ) keep_rows ON sh.search_id = keep_rows.search_id
                      WHERE sh.user_id = ? AND keep_rows.search_id IS NULL";
        $trimStmt = $conn->prepare($trimQuery);
        if ($trimStmt) {
            $trimStmt->bind_param('ii', $user_id, $user_id);
            $trimStmt->execute();
            $trimStmt->close();
        }

        $stmt->close();
        echo json_encode(['success' => true]);
        $conn->close();
        exit;
    }

    if ($method === 'DELETE') {
        $query = "DELETE FROM search_history WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $stmt->close();
        echo json_encode(['success' => true]);
        $conn->close();
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
