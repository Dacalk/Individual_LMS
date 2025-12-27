<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if (!isset($_GET['subject_id'])) {
        echo json_encode(['success' => false, 'message' => 'Subject ID required']);
        exit();
    }

    $subject_id = (int)$_GET['subject_id'];

    // Get all assignments for this subject
    $query = "SELECT cs.class_subject_id, cs.teacher_id, cs.academic_year,
              c.class_id, c.class_name, c.section,
              s.subject_name, s.subject_code,
              u.first_name, u.last_name, t.employee_id
              FROM class_subjects cs
              JOIN classes c ON cs.class_id = c.class_id
              JOIN subjects s ON cs.subject_id = s.subject_id
              LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
              LEFT JOIN users u ON t.user_id = u.user_id
              WHERE cs.subject_id = ?
              ORDER BY c.class_numeric ASC, c.section ASC, cs.academic_year DESC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $class_name = $row['class_name'];
        if (!empty($row['section'])) {
            $class_name .= ' - ' . $row['section'];
        }
        
        $teacher_name = null;
        if ($row['teacher_id'] && $row['first_name']) {
            $teacher_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
            if ($row['employee_id']) {
                $teacher_name .= ' (' . htmlspecialchars($row['employee_id']) . ')';
            }
        }
        
        $assignments[] = [
            'class_subject_id' => (int)$row['class_subject_id'],
            'class_id' => (int)$row['class_id'],
            'class_name' => htmlspecialchars($class_name),
            'academic_year' => htmlspecialchars($row['academic_year']),
            'teacher_id' => $row['teacher_id'] ? (int)$row['teacher_id'] : null,
            'teacher_name' => $teacher_name,
            'subject_name' => htmlspecialchars($row['subject_name']),
            'subject_code' => htmlspecialchars($row['subject_code'])
        ];
    }

    echo json_encode([
        'success' => true,
        'assignments' => $assignments
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


