<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'Parent Dashboard';

// Get children information
$children_query = "SELECT s.*, u.first_name, u.last_name, c.class_name, c.section 
                   FROM students s 
                   JOIN users u ON s.user_id = u.user_id
                   LEFT JOIN classes c ON s.class_id = c.class_id
                   WHERE s.parent_id = " . $_SESSION['user_id'];
$children_result = $conn->query($children_query);

// Get statistics for all children
$total_children = $children_result->num_rows;
$children_data = [];

while ($child = $children_result->fetch_assoc()) {
    // Get attendance percentage
    $attendance_query = "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
        FROM attendance WHERE student_id = " . $child['student_id'];
    $att_result = $conn->query($attendance_query);
    $att_data = $att_result->fetch_assoc();
    $child['attendance_percentage'] = 0;
    if ($att_data['total_days'] > 0) {
        $child['attendance_percentage'] = round(($att_data['present_days'] / $att_data['total_days']) * 100, 1);
    }
    
    // Get recent exam average
    $grades_query = "SELECT AVG((g.marks_obtained / e.total_marks) * 100) as avg_percentage
                     FROM grades g
                     JOIN exams e ON g.exam_id = e.exam_id
                     WHERE g.student_id = " . $child['student_id'];
    $grades_result = $conn->query($grades_query);
    $grades_data = $grades_result->fetch_assoc();
    $child['average_grade'] = $grades_data['avg_percentage'] ? round($grades_data['avg_percentage'], 1) : 0;
    
    // Pending fees removed from dashboard (fees functionality disabled)
    
    $children_data[] = $child;
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-parent.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Welcome Message -->
            <div class="card" style="background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%); color: var(--text-dark); margin-bottom: 24px;">
                <div class="card-body" style="padding: 30px;">
                    <h2 style="margin: 0 0 10px 0;">Welcome, <?php echo $_SESSION['first_name']; ?>! 👋</h2>
                    <p style="margin: 0; opacity: 0.9;">Monitor your child's academic progress and stay connected with the school</p>
                </div>
            </div>
            
            <!-- Children Overview -->
            <?php if ($total_children > 0): ?>
                <?php foreach ($children_data as $child): ?>
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: white;">
                        <h3 style="margin: 0;">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo $child['first_name'] . ' ' . $child['last_name']; ?>
                        </h3>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 14px;">
                            <?php echo $child['class_name'] ? $child['class_name'] . ' - ' . $child['section'] : 'Not Assigned'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="dashboard-card primary">
                                <div class="card-icon primary">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="card-content">
                                    <h3><?php echo $child['attendance_percentage']; ?>%</h3>
                                    <p>Attendance</p>
                                </div>
                            </div>
                            
                            <div class="dashboard-card success">
                                <div class="card-icon success">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-content">
                                    <h3><?php echo $child['average_grade']; ?>%</h3>
                                    <p>Average Grade</p>
                                </div>
                            </div>
                            
                            <!-- Fees display removed -->
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="attendance.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-check"></i> View Attendance
                            </a>
                            <a href="grades.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-trophy"></i> View Grades
                            </a>
                            <a href="timetable.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-calendar-alt"></i> View Timetable
                            </a>
                            <!-- Pay Fees button removed -->
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <p style="text-align: center; color: var(--text-light); padding: 60px 20px;">
                        <i class="fas fa-info-circle" style="font-size: 64px; display: block; margin-bottom: 20px;"></i>
                        <strong style="font-size: 20px; display: block; margin-bottom: 10px;">No children linked to your account</strong>
                        Please contact the school administration to link your child's account.
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="messages.php" class="btn btn-primary" style="padding: 15px;">
                            <i class="fas fa-envelope"></i> Message Teachers
                        </a>
                        <a href="notifications.php" class="btn btn-info" style="padding: 15px;">
                            <i class="fas fa-bell"></i> View Notifications
                        </a>
                        <!-- Fee Payments quick action removed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>









