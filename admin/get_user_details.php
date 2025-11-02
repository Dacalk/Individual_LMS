<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    if (!isset($_GET['user_id']) || !isset($_GET['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'User ID and type required']);
        exit();
    }
    
    $user_id = (int)$_GET['user_id'];
    $user_type = sanitize($_GET['user_type']);
    
    $query = "";
    
    if ($user_type === 'students') {
        $query = "SELECT u.*, s.student_id, s.admission_number, s.admission_date, s.class_id, s.section, 
                  s.roll_number, s.parent_id, s.blood_group, s.emergency_contact,
                  c.class_name, CONCAT(p.first_name, ' ', p.last_name) as parent_name
                  FROM users u 
                  LEFT JOIN students s ON u.user_id = s.user_id 
                  LEFT JOIN classes c ON s.class_id = c.class_id
                  LEFT JOIN users p ON s.parent_id = p.user_id
                  WHERE u.user_id = ? AND u.role = 'student'";
    } elseif ($user_type === 'teachers') {
        $query = "SELECT u.*, t.teacher_id, t.employee_id, t.joining_date, t.qualification, 
                  t.specialization, t.experience_years, t.department 
                  FROM users u 
                  LEFT JOIN teachers t ON u.user_id = t.user_id 
                  WHERE u.user_id = ? AND u.role = 'teacher'";
    } elseif ($user_type === 'parents') {
        $query = "SELECT u.*, COUNT(s.student_id) as children_count 
                  FROM users u 
                  LEFT JOIN students s ON u.user_id = s.parent_id 
                  WHERE u.user_id = ? AND u.role = 'parent' 
                  GROUP BY u.user_id";
    } elseif ($user_type === 'librarians') {
        $query = "SELECT u.* FROM users u WHERE u.user_id = ? AND u.role = 'librarian'";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user type']);
        exit();
    }
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Format dates
        if ($user['created_at']) {
            $user['created_at'] = date('F j, Y, g:i a', strtotime($user['created_at']));
        }
        if (isset($user['joining_date']) && $user['joining_date']) {
            $user['joining_date'] = date('F j, Y', strtotime($user['joining_date']));
        }
        if (isset($user['admission_date']) && $user['admission_date']) {
            $user['admission_date'] = date('Y-m-d', strtotime($user['admission_date']));
        }
        if ($user['date_of_birth']) {
            $user['date_of_birth'] = date('Y-m-d', strtotime($user['date_of_birth']));
        }
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

