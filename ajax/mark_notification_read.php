<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? (int)$data['notification_id'] : 0;

if ($notification_id > 0) {
    // Check if already read
    $check = $conn->prepare("SELECT read_id FROM notification_reads WHERE notification_id = ? AND user_id = ?");
    $check->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Mark as read
        $stmt = $conn->prepare("INSERT INTO notification_reads (notification_id, user_id, read_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error marking notification']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Already read']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
}
?>







