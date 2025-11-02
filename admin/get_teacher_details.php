<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

require_once '../config/config.php';

header('Content-Type: application/json');

try {
    // Check if session is active
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session not active. Please login again.']);
        exit();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
        exit();
    }

    // Check if user_id parameter is provided
    if (!isset($_GET['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID parameter is required']);
        exit();
    }

    $user_id = (int)$_GET['user_id'];
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit();
    }

    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Get teacher details
    $query = "SELECT 
                t.*,
                u.first_name, u.last_name, u.email, u.username, u.status, u.phone, u.date_of_birth, u.gender, u.address
              FROM teachers t
              JOIN users u ON t.user_id = u.user_id
              WHERE t.user_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($teacher = $result->fetch_assoc()) {
        // Initialize assigned_classes as empty
        $teacher['assigned_classes'] = null;
        
        // Get assigned classes and subjects (if teacher_id exists)
        if (isset($teacher['teacher_id']) && !empty($teacher['teacher_id'])) {
            $classes_query = "SELECT DISTINCT c.class_name, c.section, s.subject_name
                              FROM class_subjects cs
                              JOIN classes c ON cs.class_id = c.class_id
                              JOIN subjects s ON cs.subject_id = s.subject_id
                              WHERE cs.teacher_id = ?
                              ORDER BY c.class_name, s.subject_name";
            
            $classes_stmt = $conn->prepare($classes_query);
            if ($classes_stmt) {
                $classes_stmt->bind_param("i", $teacher['teacher_id']);
                $classes_stmt->execute();
                $classes_result = $classes_stmt->get_result();
                
                $assigned_list = [];
                while ($class = $classes_result->fetch_assoc()) {
                    $class_full_name = $class['class_name'];
                    if (!empty($class['section'])) {
                        $class_full_name .= ' - ' . $class['section'];
                    }
                    $assigned_list[] = htmlspecialchars($class_full_name) . ': ' . htmlspecialchars($class['subject_name']);
                }
                
                if (count($assigned_list) > 0) {
                    $teacher['assigned_classes'] = implode('<br>', $assigned_list);
                }
                $classes_stmt->close();
            }
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'teacher' => $teacher
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt->close();
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found for user ID: ' . $user_id
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>

