<?php
require_once '../config/config.php';

if (!hasRole('student')) {
    redirect('index.php');
}

$page_title = 'My Attendance';

// Get student information
$student_query = $conn->prepare("SELECT s.*, c.class_name FROM students s 
                                 LEFT JOIN classes c ON s.class_id = c.class_id 
                                 WHERE s.user_id = ?");
$student_query->bind_param("i", $_SESSION['user_id']);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    redirect('logout.php');
}

$student_id = $student['student_id'];

// Get attendance statistics
$stats = [
    'total_days' => 0,
    'present_days' => 0,
    'absent_days' => 0,
    'late_days' => 0,
    'percentage' => 0
];

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
    } else {
        $stats['percentage'] = 0;
    }
}

// Get monthly attendance (last 30 days)
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$attendance_query = $conn->prepare("SELECT * FROM attendance 
                                    WHERE student_id = ? 
                                    AND attendance_date >= ?
                                    ORDER BY attendance_date DESC");
$attendance_query->bind_param("is", $student_id, $thirty_days_ago);
$attendance_query->execute();
$attendance_records = $attendance_query->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-student.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
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
                        <h3><?php echo isset($stats['percentage']) ? $stats['percentage'] : 0; ?>%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Attendance Overview</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px;">
                        <div style="text-align: center; padding: 20px; background: #D1FAE5; border-radius: 12px;">
                            <div style="font-size: 42px; font-weight: bold; color: #065F46;"><?php echo $stats['present_days']; ?></div>
                            <div style="color: #065F46; font-weight: 600; margin-top: 8px;">Present</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #FEE2E2; border-radius: 12px;">
                            <div style="font-size: 42px; font-weight: bold; color: #991B1B;"><?php echo $stats['absent_days']; ?></div>
                            <div style="color: #991B1B; font-weight: 600; margin-top: 8px;">Absent</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #FEF3C7; border-radius: 12px;">
                            <div style="font-size: 42px; font-weight: bold; color: #92400E;"><?php echo $stats['late_days']; ?></div>
                            <div style="color: #92400E; font-weight: 600; margin-top: 8px;">Late</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Recent Attendance (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <?php if ($attendance_records->num_rows > 0): ?>
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
                        No attendance records found
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Attendance Guidelines -->
            <div class="card" style="background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);">
                <div class="card-body" style="padding: 25px;">
                    <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Attendance Guidelines
                    </h4>
                    <ul style="color: var(--text-dark); line-height: 2;">
                        <li>Minimum 75% attendance is required for appearing in exams</li>
                        <li>Contact your class teacher if there are any attendance discrepancies</li>
                        <li>Medical leave requires proper documentation</li>
                        <li>Attendance is marked daily for each class period</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive Styles */
@media (max-width: 768px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
    
    .dashboard-card {
        min-width: auto !important;
    }
    
    .card-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 20px !important;
    }
    
    .card-content h3 {
        font-size: 24px !important;
    }
    
    .card-content p {
        font-size: 12px !important;
    }
    
    .card-body > div[style*="grid"] {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
        padding: 15px !important;
    }
    
    .card-body > div[style*="grid"] > div {
        padding: 15px !important;
    }
    
    .card-body > div[style*="grid"] > div > div:first-child {
        font-size: 32px !important;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        font-size: 13px;
        min-width: 600px;
    }
    
    .table th,
    .table td {
        padding: 8px 6px !important;
    }
}

@media (max-width: 480px) {
    .dashboard-cards {
        grid-template-columns: 1fr !important;
    }
    
    .dashboard-card {
        flex-direction: row !important;
        align-items: center !important;
        padding: 15px !important;
    }
    
    .card-icon {
        margin-right: 15px !important;
        margin-bottom: 0 !important;
    }
    
    .card-content {
        text-align: left !important;
    }
    
    .card-header h3 {
        font-size: 18px !important;
    }
    
    .card-body > div[style*="grid"] > div > div:first-child {
        font-size: 28px !important;
    }
    
    .table {
        font-size: 12px;
        min-width: 500px;
    }
    
    .card[style*="gradient"] .card-body {
        padding: 20px !important;
    }
    
    .card[style*="gradient"] ul {
        font-size: 13px !important;
        line-height: 1.8 !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>







