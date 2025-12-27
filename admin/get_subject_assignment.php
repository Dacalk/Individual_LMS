<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if (!isset($_GET['class_subject_id'])) {
        echo json_encode(['success' => false, 'message' => 'Class subject ID required']);
        exit();
    }

    $class_subject_id = (int)$_GET['class_subject_id'];

    // Get subject assignment details
    $query = "SELECT cs.class_subject_id, cs.teacher_id,
              s.subject_name, s.subject_code,
              c.class_name, c.section
              FROM class_subjects cs
              JOIN subjects s ON cs.subject_id = s.subject_id
              JOIN classes c ON cs.class_id = c.class_id
              WHERE cs.class_subject_id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $class_subject_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($assignment = $result->fetch_assoc()) {
        $class_name = $assignment['class_name'];
        if (!empty($assignment['section'])) {
            $class_name .= ' - ' . $assignment['section'];
        }
        $assignment['class_name'] = $class_name;
        
        echo json_encode([
            'success' => true,
            'assignment' => $assignment
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Assignment not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>