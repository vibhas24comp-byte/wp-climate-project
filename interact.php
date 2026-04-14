<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$story_id = intval($_POST['story_id'] ?? 0);

if ($story_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid story ID']);
    exit();
}

try {
    if ($action === 'view') {
        $stmt = $conn->prepare("INSERT INTO views_history (user_id, story_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $story_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'like') {
        $stmt = $conn->prepare("INSERT IGNORE INTO likes (user_id, story_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $story_id);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);
        
    } elseif ($action === 'check_like') {
        $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND story_id = ?");
        $stmt->bind_param("ii", $user_id, $story_id);
        $stmt->execute();
        $is_liked = $stmt->get_result()->num_rows > 0;
        echo json_encode(['success' => true, 'liked' => $is_liked]);

    } elseif ($action === 'watch_later') {
        $stmt = $conn->prepare("INSERT IGNORE INTO watch_later (user_id, story_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $story_id);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);

    } elseif ($action === 'check_watch_later') {
        $stmt = $conn->prepare("SELECT id FROM watch_later WHERE user_id = ? AND story_id = ?");
        $stmt->bind_param("ii", $user_id, $story_id);
        $stmt->execute();
        $is_watch = $stmt->get_result()->num_rows > 0;
        echo json_encode(['success' => true, 'watch_later' => $is_watch]);

    } elseif ($action === 'add_comment') {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment !== '') {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, story_id, comment) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $story_id, $comment);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Empty comment']);
        }

    } elseif ($action === 'get_comments') {
        $stmt = $conn->prepare("SELECT c.comment, c.created_at, u.username, u.profile_icon FROM comments c JOIN users u ON c.user_id = u.id WHERE c.story_id = ? ORDER BY c.created_at DESC");
        $stmt->bind_param("i", $story_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $comments = [];
        while($row = $res->fetch_assoc()) {
            $comments[] = $row;
        }
        echo json_encode(['success' => true, 'comments' => $comments]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
