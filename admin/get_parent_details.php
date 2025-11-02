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

    // Get parent details (parents are just users with role='parent')
    $query = "SELECT 
                u.user_id, u.first_name, u.last_name, u.email, u.username, u.status, 
                u.phone, u.address, u.created_at, u.date_of_birth, u.gender
              FROM users u
              WHERE u.user_id = ? AND u.role = 'parent'";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($parent = $result->fetch_assoc()) {
        // Get children information
        $children = [];
        $children_query = "SELECT s.admission_number, u.first_name, u.last_name, c.class_name, c.section
                           FROM students s
                           JOIN users u ON s.user_id = u.user_id
                           LEFT JOIN classes c ON s.class_id = c.class_id
                           WHERE s.parent_id = ?
                           ORDER BY u.first_name";
        
        $children_stmt = $conn->prepare($children_query);
        if ($children_stmt) {
            $children_stmt->bind_param("i", $user_id);
            $children_stmt->execute();
            $children_result = $children_stmt->get_result();
            
            while ($child = $children_result->fetch_assoc()) {
                $class_full_name = $child['class_name'];
                if (!empty($child['section'])) {
                    $class_full_name .= ' - ' . $child['section'];
                }
                if (empty($class_full_name)) {
                    $class_full_name = 'Not Assigned';
                }
                
                $children[] = [
                    'name' => $child['first_name'] . ' ' . $child['last_name'],
                    'admission_number' => $child['admission_number'],
                    'class_name' => $class_full_name
                ];
            }
            $children_stmt->close();
        }
        
        $parent['children'] = $children;
        
        echo json_encode([
            'success' => true,
            'parent' => $parent
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Parent not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

