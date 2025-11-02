<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if (!isset($_GET['class_id'])) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        exit();
    }

    $class_id = (int)$_GET['class_id'];

    // Get class details
    $query = "SELECT * FROM classes WHERE class_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($class = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'class' => $class
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Class not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>





