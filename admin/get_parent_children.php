<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    if (!isset($_GET['parent_id'])) {
        echo json_encode(['success' => false, 'message' => 'Parent ID required']);
        exit();
    }
    
    $parent_id = (int)$_GET['parent_id'];
    
    $query = "SELECT s.student_id, s.admission_number, s.roll_number, 
              u.user_id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as name,
              c.class_name, c.section
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.class_id = c.class_id
              WHERE s.parent_id = ? AND u.status = 'active'
              ORDER BY u.first_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $children = [];
    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }
    
    echo json_encode(['success' => true, 'children' => $children]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>




