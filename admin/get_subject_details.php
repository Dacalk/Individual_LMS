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

    // Get subject details
    $query = "SELECT * FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($subject = $result->fetch_assoc()) {
        // Get classes assigned to this subject
        $classes_query = "SELECT DISTINCT c.class_id, c.class_name, c.section, cs.academic_year,
                         COUNT(DISTINCT s.student_id) as student_count
                         FROM class_subjects cs
                         JOIN classes c ON cs.class_id = c.class_id
                         LEFT JOIN students s ON c.class_id = s.class_id
                         WHERE cs.subject_id = ?
                         GROUP BY c.class_id, c.class_name, c.section, cs.academic_year
                         ORDER BY c.class_name, cs.academic_year";
        $classes_stmt = $conn->prepare($classes_query);
        $classes_stmt->bind_param("i", $subject_id);
        $classes_stmt->execute();
        $classes_result = $classes_stmt->get_result();
        
        $classes_list = [];
        $total_students = 0;
        while ($class = $classes_result->fetch_assoc()) {
            $class_full_name = $class['class_name'];
            if (!empty($class['section'])) {
                $class_full_name .= ' - ' . $class['section'];
            }
            $classes_list[] = [
                'class_name' => htmlspecialchars($class_full_name),
                'academic_year' => htmlspecialchars($class['academic_year']),
                'student_count' => (int)$class['student_count']
            ];
            $total_students += (int)$class['student_count'];
        }
        $classes_stmt->close();
        $subject['assigned_classes'] = $classes_list;
        $subject['total_students'] = $total_students;
        $subject['class_count'] = count($classes_list);
        
        // Get teachers teaching this subject
        $teachers_query = "SELECT DISTINCT t.teacher_id, u.first_name, u.last_name, u.email, 
                          t.employee_id, cs.academic_year,
                          GROUP_CONCAT(DISTINCT CONCAT(c.class_name, IF(c.section != '', CONCAT(' - ', c.section), '')) SEPARATOR ', ') as classes
                          FROM class_subjects cs
                          JOIN teachers t ON cs.teacher_id = t.teacher_id
                          JOIN users u ON t.user_id = u.user_id
                          JOIN classes c ON cs.class_id = c.class_id
                          WHERE cs.subject_id = ?
                          GROUP BY t.teacher_id, u.first_name, u.last_name, u.email, t.employee_id, cs.academic_year
                          ORDER BY u.first_name, u.last_name";
        $teachers_stmt = $conn->prepare($teachers_query);
        $teachers_stmt->bind_param("i", $subject_id);
        $teachers_stmt->execute();
        $teachers_result = $teachers_stmt->get_result();
        
        $teachers_list = [];
        while ($teacher = $teachers_result->fetch_assoc()) {
            $teachers_list[] = [
                'name' => htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']),
                'employee_id' => htmlspecialchars($teacher['employee_id']),
                'email' => htmlspecialchars($teacher['email']),
                'academic_year' => htmlspecialchars($teacher['academic_year']),
                'classes' => htmlspecialchars($teacher['classes'])
            ];
        }
        $teachers_stmt->close();
        $subject['teachers'] = $teachers_list;
        $subject['teacher_count'] = count($teachers_list);
        
        echo json_encode([
            'success' => true,
            'subject' => $subject
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Subject not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


