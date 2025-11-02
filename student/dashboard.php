<?php
require_once '../config/config.php';

if (!hasRole('student')) {
    redirect('index.php');
}

$page_title = 'Student Dashboard';

// Get student information
$student_query = $conn->prepare("SELECT s.*, c.class_name, c.section 
                                 FROM students s 
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

// Get statistics
$stats = [
    'attendance_percentage' => 0,
    'total_subjects' => 0,
    'books_issued' => 0,
];

// Calculate attendance percentage
$attendance_query = $conn->prepare("SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
    FROM attendance WHERE student_id = ?");
$attendance_query->bind_param("i", $student_id);
$attendance_query->execute();
$att_result = $attendance_query->get_result();
if ($att_row = $att_result->fetch_assoc()) {
    if ($att_row['total_days'] > 0) {
        $stats['attendance_percentage'] = round(($att_row['present_days'] / $att_row['total_days']) * 100, 1);
    }
}

// Count subjects
if ($student['class_id']) {
    $subject_query = $conn->prepare("SELECT COUNT(*) as count FROM class_subjects WHERE class_id = ?");
    $subject_query->bind_param("i", $student['class_id']);
    $subject_query->execute();
    $subject_result = $subject_query->get_result();
    if ($subj_row = $subject_result->fetch_assoc()) {
        $stats['total_subjects'] = $subj_row['count'];
    }
}

// Count issued books
$books_query = $conn->prepare("SELECT COUNT(*) as count FROM library_transactions WHERE user_id = ? AND status = 'issued'");
$books_query->bind_param("i", $_SESSION['user_id']);
$books_query->execute();
$books_result = $books_query->get_result();
if ($book_row = $books_result->fetch_assoc()) {
    $stats['books_issued'] = $book_row['count'];
}

// Get recent grades
$grades_query = $conn->prepare("SELECT g.*, e.exam_name, s.subject_name, e.total_marks 
                                FROM grades g
                                JOIN exams e ON g.exam_id = e.exam_id
                                JOIN subjects s ON e.subject_id = s.subject_id
                                WHERE g.student_id = ?
                                ORDER BY g.created_at DESC LIMIT 5");
$grades_query->bind_param("i", $student_id);
$grades_query->execute();
$recent_grades = $grades_query->get_result();

// Get today's timetable
$today_day = date('l');
$today_schedule = null;
if ($student['class_id']) {
    $schedule_query = $conn->prepare("SELECT t.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, tp.period_name, tp.start_time, tp.end_time
                                     FROM timetable t
                                     JOIN subjects s ON t.subject_id = s.subject_id
                                     JOIN teachers te ON t.teacher_id = te.teacher_id
                                     JOIN users u ON te.user_id = u.user_id
                                     JOIN time_periods tp ON t.period_id = tp.period_id
                                     WHERE t.class_id = ? AND t.day_of_week = ?
                                     ORDER BY tp.start_time ASC");
    $schedule_query->bind_param("is", $student['class_id'], $today_day);
    $schedule_query->execute();
    $today_schedule = $schedule_query->get_result();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-student.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Welcome Message -->
            <div class="card" style="background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); color: white; margin-bottom: 24px;">
                <div class="card-body" style="padding: 30px;">
                    <h2 style="margin: 0 0 10px 0;">Hello, <?php echo $_SESSION['first_name']; ?>! 🎓</h2>
                    <p style="margin: 0; opacity: 0.9;">
                        Class: <?php echo $student['class_name'] ? $student['class_name'] . ' - ' . $student['section'] : 'Not Assigned'; ?> | 
                        Admission No: <?php echo $student['admission_number']; ?>
                    </p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['attendance_percentage']; ?>%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_subjects']; ?></h3>
                        <p>Total Subjects</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['books_issued']; ?></h3>
                        <p>Books Issued</p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Schedule -->
            <?php if ($today_schedule && $today_schedule->num_rows > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-day"></i> Today's Schedule - <?php echo date('l'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($schedule = $today_schedule->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></strong>
                                        - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                    </td>
                                    <td><?php echo $schedule['subject_name']; ?></td>
                                    <td><?php echo $schedule['teacher_name']; ?></td>
                                    <td><?php echo $schedule['room_number']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recent Grades -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Recent Exam Results</h3>
                    <a href="grades.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_grades->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Total Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($grade = $recent_grades->fetch_assoc()): 
                                    $percentage = ($grade['marks_obtained'] / $grade['total_marks']) * 100;
                                    $badge_class = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo $grade['exam_name']; ?></td>
                                    <td><?php echo $grade['subject_name']; ?></td>
                                    <td><strong><?php echo $grade['marks_obtained']; ?></strong></td>
                                    <td><?php echo $grade['total_marks']; ?></td>
                                    <td><?php echo round($percentage, 1); ?>%</td>
                                    <td>
                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                            <?php echo $grade['grade'] ?: 'N/A'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No exam results available yet
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Links</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="timetable.php" class="btn btn-primary" style="padding: 15px;">
                            <i class="fas fa-calendar-alt"></i> View Timetable
                        </a>
                        <a href="attendance.php" class="btn btn-success" style="padding: 15px;">
                            <i class="fas fa-user-check"></i> View Attendance
                        </a>
                        <a href="grades.php" class="btn btn-warning" style="padding: 15px;">
                            <i class="fas fa-trophy"></i> View Grades
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>




