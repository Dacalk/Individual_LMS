<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if (!isset($_GET['timetable_id'])) {
        echo json_encode(['success' => false, 'message' => 'Timetable ID required']);
        exit();
    }

    $timetable_id = (int)$_GET['timetable_id'];

    // Get timetable entry details with related information
    $query = "SELECT t.*, 
              s.subject_name, s.subject_code, s.description as subject_description, s.credit_hours,
              c.class_name, c.section, c.room_number as class_room,
              CONCAT(u.first_name, ' ', u.last_name) as teacher_name, 
              u.email as teacher_email, u.phone as teacher_phone,
              te.employee_id, te.department,
              tp.period_number, tp.period_name, tp.start_time, tp.end_time, tp.is_break
              FROM timetable t
              JOIN subjects s ON t.subject_id = s.subject_id
              JOIN classes c ON t.class_id = c.class_id
              JOIN teachers te ON t.teacher_id = te.teacher_id
              JOIN users u ON te.user_id = u.user_id
              JOIN time_periods tp ON t.period_id = tp.period_id
              WHERE t.timetable_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $timetable_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($timetable = $result->fetch_assoc()) {
        // Format times
        if ($timetable['start_time']) {
            $timetable['start_time_formatted'] = date('g:i A', strtotime($timetable['start_time']));
        }
        if ($timetable['end_time']) {
            $timetable['end_time_formatted'] = date('g:i A', strtotime($timetable['end_time']));
        }
        
        echo json_encode([
            'success' => true,
            'timetable' => $timetable
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Timetable entry not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


