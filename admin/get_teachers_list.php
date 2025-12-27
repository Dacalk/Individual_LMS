<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Get all active teachers
    $query = "SELECT t.teacher_id, t.employee_id, u.first_name, u.last_name
              FROM teachers t
              JOIN users u ON t.user_id = u.user_id
              WHERE u.status = 'active'
              ORDER BY u.first_name ASC, u.last_name ASC";
    $result = $conn->query($query);

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

    echo json_encode([
        'success' => true,
        'teachers' => $teachers
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>