<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if (!isset($_GET['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }

    $user_id = (int)$_GET['user_id'];

    // Get student details with parent information
    // Note: parent_id in students table directly references users.user_id (no separate parents table)
    $query = "SELECT 
                s.*,
                u.first_name, u.last_name, u.email, u.username, u.status, u.phone, u.date_of_birth, u.gender, u.address,
                c.class_name, c.section,
                p_user.first_name as parent_first_name, p_user.last_name as parent_last_name,
                p_user.email as parent_email, p_user.phone as parent_contact
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.class_id = c.class_id
              LEFT JOIN users p_user ON s.parent_id = p_user.user_id
              WHERE s.user_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($student = $result->fetch_assoc()) {
        // Format parent name if exists
        if (!empty($student['parent_first_name'])) {
            $student['parent_name'] = $student['parent_first_name'] . ' ' . $student['parent_last_name'];
        }
        
        echo json_encode([
            'success' => true,
            'student' => $student
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

