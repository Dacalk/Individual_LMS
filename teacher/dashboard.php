<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'Teacher Dashboard';

// Get teacher ID
$teacher_stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$teacher_stmt->bind_param("i", $_SESSION['user_id']);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    redirect('logout.php');
}

$teacher_id = (int)$teacher['teacher_id'];

// Get statistics
$stats = [
    'total_classes' => 0,
    'total_students' => 0,
    'today_classes' => 0,
];

// Count assigned classes (either as subject teacher or class teacher)
$classes_count_stmt = $conn->prepare("SELECT COUNT(DISTINCT c.class_id) AS count
                                      FROM classes c
                                      LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                                      WHERE c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL");
$classes_count_stmt->bind_param("ii", $teacher_id, $teacher_id);
$classes_count_stmt->execute();
$classes_count_result = $classes_count_stmt->get_result();
if ($row = $classes_count_result->fetch_assoc()) {
    $stats['total_classes'] = (int)$row['count'];
}

// Count total students across those classes
$students_count_stmt = $conn->prepare("SELECT COUNT(DISTINCT st.student_id) AS count
                                        FROM students st
                                        JOIN classes c ON st.class_id = c.class_id
                                        LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                                        WHERE c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL");
$students_count_stmt->bind_param("ii", $teacher_id, $teacher_id);
$students_count_stmt->execute();
$students_count_result = $students_count_stmt->get_result();
if ($row = $students_count_result->fetch_assoc()) {
    $stats['total_students'] = (int)$row['count'];
}

// Count today's classes
$today_day = date('l');
$today_classes_stmt = $conn->prepare("SELECT COUNT(*) as count FROM timetable WHERE teacher_id = ? AND day_of_week = ?");
$today_classes_stmt->bind_param("is", $teacher_id, $today_day);
$today_classes_stmt->execute();
$today_classes_result = $today_classes_stmt->get_result();
if ($row = $today_classes_result->fetch_assoc()) {
    $stats['today_classes'] = (int)$row['count'];
}

// Get today's schedule
$today_schedule_stmt = $conn->prepare("SELECT t.*, c.class_name, c.section, sub.subject_name, tp.period_name, tp.start_time, tp.end_time, t.room_number 
                                FROM timetable t
                                JOIN classes c ON t.class_id = c.class_id
                                JOIN subjects sub ON t.subject_id = sub.subject_id
                                JOIN time_periods tp ON t.period_id = tp.period_id
                                WHERE t.teacher_id = ? AND t.day_of_week = ?
                                ORDER BY tp.start_time ASC");
$today_schedule_stmt->bind_param("is", $teacher_id, $today_day);
$today_schedule_stmt->execute();
$today_schedule = $today_schedule_stmt->get_result();

// Get assigned classes (subject teacher or class teacher)
$classes_stmt = $conn->prepare("SELECT c.class_id, c.class_name, c.section, c.room_number, c.class_numeric, c.academic_year,
                                COUNT(DISTINCT st.student_id) AS student_count,
                                (c.class_teacher_id = ?) AS is_class_teacher,
                                MAX(CASE WHEN cs.teacher_id IS NOT NULL THEN 1 ELSE 0 END) AS teaches_subject
                            FROM classes c
                            LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                            LEFT JOIN students st ON c.class_id = st.class_id
                            WHERE c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL
                            GROUP BY c.class_id, c.class_name, c.section, c.room_number, c.class_numeric, c.academic_year, c.class_teacher_id
                            ORDER BY c.class_numeric ASC, c.section ASC, c.class_name ASC");
$classes_stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$classes_stmt->execute();
$my_classes = $classes_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Welcome Message -->
            <div class="card" style="background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%); color: var(--text-dark); margin-bottom: 24px;">
                <div class="card-body" style="padding: 30px;">
                    <h2 style="margin: 0 0 10px 0;">Welcome back, <?php echo $_SESSION['first_name']; ?>! 👋</h2>
                    <p style="margin: 0; opacity: 0.9;">Today is <?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_classes']; ?></h3>
                        <p>My Classes</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['today_classes']; ?></h3>
                        <p>Today's Classes</p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Schedule -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-day"></i> Today's Schedule - <?php echo date('l'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($today_schedule->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Room</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($schedule = $today_schedule->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></strong>
                                        - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                    </td>
                                    <td><?php echo $schedule['class_name']; ?></td>
                                    <td><?php echo $schedule['subject_name']; ?></td>
                                    <td><?php echo $schedule['room_number']; ?></td>
                                    <td>
                                        <a href="attendance.php?class_id=<?php echo $schedule['class_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-check"></i> Take Attendance
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No classes scheduled for today
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- My Classes Overview -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chalkboard-teacher"></i> My Classes</h3>
                    <a href="classes.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($my_classes->num_rows > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                        <?php while ($class = $my_classes->fetch_assoc()):
                            $is_class_teacher = !empty($class['is_class_teacher']);
                            $teaches_subject = !empty($class['teaches_subject']);
                        ?>
                        <div class="card" style="border: 2px solid var(--border-color); margin-bottom: 0;">
                            <div class="card-body">
                                <h4 style="color: var(--primary-color); margin-bottom: 10px;">
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                    <?php if (!empty($class['section'])): ?>
                                        <small style="color: var(--text-light);">&nbsp;-&nbsp;<?php echo htmlspecialchars($class['section']); ?></small>
                                    <?php endif; ?>
                                </h4>
                                <?php if ($is_class_teacher): ?>
                                <span class="badge badge-success" style="font-size: 11px; margin-bottom: 8px; display: inline-block;">
                                    <i class="fas fa-star"></i> Class Teacher
                                </span>
                                <?php endif; ?>
                                <p style="color: var(--text-light); margin: 5px 0;">
                                    <i class="fas fa-users"></i> <?php echo (int)$class['student_count']; ?> Students
                                </p>
                                <p style="color: var(--text-light); margin: 5px 0;">
                                    <i class="fas fa-door-open"></i> Room <?php echo $class['room_number'] ? htmlspecialchars($class['room_number']) : 'N/A'; ?>
                                </p>
                                <?php if (!$teaches_subject): ?>
                                <p style="color: var(--warning-color); font-size: 12px; margin: 5px 0;">
                                    <i class="fas fa-info-circle"></i> No subjects assigned yet.
                                </p>
                                <?php endif; ?>
                                <div style="margin-top: 15px; display: flex; gap: 10px;">
                                    <a href="classes.php?class_id=<?php echo (int)$class['class_id']; ?>" class="btn btn-sm btn-primary" style="flex: 1;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No classes assigned yet
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>




