<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'Children Attendance';

// Get parent's children
$children_query = "SELECT s.student_id, CONCAT(u.first_name, ' ', u.last_name) as name, c.class_name, c.section
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN classes c ON s.class_id = c.class_id
                  WHERE s.parent_id = " . $_SESSION['user_id'] . "
                  ORDER BY c.class_numeric ASC, u.first_name ASC";
$children_result = $conn->query($children_query);

// Get selected student or default to first child
$selected_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$selected_student = null;

if ($selected_student_id > 0) {
    $check_query = $conn->prepare("SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as name, c.class_name, c.section
                                   FROM students s 
                                   JOIN users u ON s.user_id = u.user_id
                                   LEFT JOIN classes c ON s.class_id = c.class_id
                                   WHERE s.student_id = ? AND s.parent_id = ?");
    $check_query->bind_param("ii", $selected_student_id, $_SESSION['user_id']);
    $check_query->execute();
    $selected_student = $check_query->get_result()->fetch_assoc();
}

// If no student selected or invalid, use first child
if (!$selected_student && $children_result->num_rows > 0) {
    $children_result->data_seek(0);
    $first_child = $children_result->fetch_assoc();
    $selected_student_id = $first_child['student_id'];
    $selected_student = $first_child;
    $selected_student['student_id'] = $first_child['student_id'];
}

$stats = [
    'total_days' => 0,
    'present_days' => 0,
    'absent_days' => 0,
    'late_days' => 0,
    'percentage' => 0
];

$attendance_records = null;

if ($selected_student) {
    $student_id = $selected_student['student_id'];
    
    // Get attendance statistics
    $stats_query = $conn->prepare("SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance WHERE student_id = ?");
    $stats_query->bind_param("i", $student_id);
    $stats_query->execute();
    $stats_result = $stats_query->get_result();
    if ($stats_row = $stats_result->fetch_assoc()) {
        $stats['total_days'] = (int)$stats_row['total_days'];
        $stats['present_days'] = (int)($stats_row['present_days'] ?? 0);
        $stats['absent_days'] = (int)($stats_row['absent_days'] ?? 0);
        $stats['late_days'] = (int)($stats_row['late_days'] ?? 0);
        if ($stats['total_days'] > 0) {
            $stats['percentage'] = round(($stats['present_days'] / $stats['total_days']) * 100, 1);
        }
    }
    
    // Get recent attendance (last 30 days)
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $attendance_query = $conn->prepare("SELECT * FROM attendance 
                                        WHERE student_id = ? 
                                        AND attendance_date >= ?
                                        ORDER BY attendance_date DESC");
    $attendance_query->bind_param("is", $student_id, $thirty_days_ago);
    $attendance_query->execute();
    $attendance_records = $attendance_query->get_result();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-parent.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Student Selector -->
            <?php if ($children_result->num_rows > 1): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <label class="form-label"><strong>Select Child:</strong></label>
                    <select class="form-control" onchange="window.location.href='attendance.php?student_id=' + this.value" style="max-width: 400px;">
                        <?php 
                        $children_result->data_seek(0);
                        while ($child = $children_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $child['student_id']; ?>" <?php echo ($selected_student_id == $child['student_id']) ? 'selected' : ''; ?>>
                                <?php echo $child['name'] . ' - ' . ($child['class_name'] ? $child['class_name'] . ($child['section'] ? ' - ' . $child['section'] : '') : 'Not Assigned'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($selected_student): ?>
                <div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);">
                    <div class="card-body">
                        <h3 style="margin: 0; color: var(--primary-color);">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo $selected_student['name']; ?>
                            <?php if ($selected_student['class_name']): ?>
                                <span class="badge badge-primary" style="margin-left: 10px;">
                                    <?php echo $selected_student['class_name'] . ($selected_student['section'] ? ' - ' . $selected_student['section'] : ''); ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="dashboard-cards">
                    <div class="dashboard-card primary">
                        <div class="card-icon primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['total_days']; ?></h3>
                            <p>Total Days</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card success">
                        <div class="card-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['present_days']; ?></h3>
                            <p>Present Days</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card danger">
                        <div class="card-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['absent_days']; ?></h3>
                            <p>Absent Days</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card warning">
                        <div class="card-icon warning">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['percentage']; ?>%</h3>
                            <p>Attendance Rate</p>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Recent Attendance (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($attendance_records && $attendance_records->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $attendance_records->fetch_assoc()): 
                                        $status_class = $record['status'] === 'present' ? 'success' : 
                                                       ($record['status'] === 'absent' ? 'danger' : 'warning');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo formatDate($record['attendance_date']); ?></strong></td>
                                        <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['remarks'] ?? '-'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                            No attendance records found for the last 30 days
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p style="text-align: center; color: var(--text-light); padding: 60px 20px;">
                            <i class="fas fa-info-circle" style="font-size: 64px; display: block; margin-bottom: 20px; opacity: 0.3;"></i>
                            <strong style="font-size: 20px; display: block; margin-bottom: 10px;">No children linked to your account</strong>
                            Please contact the school administration to link your child's account.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


