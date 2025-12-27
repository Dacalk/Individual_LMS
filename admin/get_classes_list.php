<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Get all active classes
    $query = "SELECT c.class_id, c.class_name, c.section, c.academic_year
              FROM classes c
              WHERE c.status = 'active'
              ORDER BY c.class_numeric ASC, c.section ASC";
    $result = $conn->query($query);

    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    echo json_encode([
        'success' => true,
        'classes' => $classes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


