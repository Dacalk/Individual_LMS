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

    // Get class with teacher name and student count
    $query = "SELECT c.*, 
              CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
              (SELECT COUNT(*) FROM students WHERE class_id = c.class_id) as student_count
              FROM classes c 
              LEFT JOIN teachers t ON c.class_teacher_id = t.teacher_id
              LEFT JOIN users u ON t.user_id = u.user_id
              WHERE c.class_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($class = $result->fetch_assoc()) {
        // Get students in this class
        $students_query = "SELECT s.student_id, s.roll_number, s.admission_number,
                          CONCAT(u.first_name, ' ', u.last_name) as name
                          FROM students s
                          JOIN users u ON s.user_id = u.user_id
                          WHERE s.class_id = ?
                          ORDER BY s.roll_number ASC, u.first_name ASC";
        
        $students_stmt = $conn->prepare($students_query);
        $students_stmt->bind_param("i", $class_id);
        $students_stmt->execute();
        $students_result = $students_stmt->get_result();
        
        $students = [];
        while ($student = $students_result->fetch_assoc()) {
            $students[] = $student;
        }
        
        echo json_encode([
            'success' => true,
            'teacher_name' => $class['teacher_name'],
            'student_count' => $class['student_count'],
            'students' => $students
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





