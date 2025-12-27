<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $parents_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'parent' AND status = 'active' ORDER BY first_name ASC";
    $result = $conn->query($parents_query);
    
    $parents = [];
    while ($row = $result->fetch_assoc()) {
        $parents[] = $row;
    }
    
    echo json_encode(['success' => true, 'parents' => $parents]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>




