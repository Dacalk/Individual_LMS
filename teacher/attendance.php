<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'Attendance Management';
$error = '';
$success = '';

// Get teacher ID
$teacher_result = $conn->query("SELECT teacher_id FROM teachers WHERE user_id = " . $_SESSION['user_id']);
$teacher = $teacher_result->fetch_assoc();
$teacher_id = $teacher['teacher_id'];

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $class_id = (int)$_POST['class_id'];
    $attendance_date = sanitize($_POST['attendance_date']);
    
    // Check if attendance already marked for this date
    $check = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE class_id = $class_id AND attendance_date = '$attendance_date'");
    $check_row = $check->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $error = 'Attendance already marked for this date!';
    } else {
        $attendance_data = $_POST['attendance'];
        $success_count = 0;
        
        foreach ($attendance_data as $student_id => $status) {
            $student_id = (int)$student_id;
            $status = sanitize($status);
            $remarks = isset($_POST['remarks'][$student_id]) ? sanitize($_POST['remarks'][$student_id]) : '';
            
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissis", $student_id, $class_id, $attendance_date, $status, $_SESSION['user_id'], $remarks);
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        $success = "Attendance marked successfully for $success_count students!";
    }
}

// Get classes assigned to this teacher (as subject teacher or class teacher)
$classes_stmt = $conn->prepare("SELECT DISTINCT c.class_id, c.class_name, c.section, c.class_numeric
                             FROM classes c
                             LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                             WHERE c.status = 'active' AND (c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL)
                             ORDER BY c.class_numeric ASC, c.section ASC, c.class_name ASC");
$classes_stmt->bind_param("ii", $teacher_id, $teacher_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

$my_classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $my_classes[] = $row;
}

// Prepare class selection defaults
$students = null;
$selected_class = 0;
$selected_date = date('Y-m-d');
$allowed_class_ids = array_column($my_classes, 'class_id');

if (isset($_GET['class_id'])) {
    $selected_class = (int)$_GET['class_id'];
} elseif (!empty($my_classes)) {
    $selected_class = (int)$my_classes[0]['class_id'];
}

if ($selected_class && !in_array($selected_class, $allowed_class_ids, true)) {
    $error = 'You are not assigned to the selected class.';
    $selected_class = 0;
}

if ($selected_class) {
    if (isset($_GET['date'])) {
        $selected_date = sanitize($_GET['date']);
    }

    $students_stmt = $conn->prepare("SELECT s.*, u.first_name, u.last_name
                              FROM students s
                              JOIN users u ON s.user_id = u.user_id
                              WHERE s.class_id = ? AND u.status = 'active'
                              ORDER BY s.roll_number ASC, u.first_name ASC");
    $students_stmt->bind_param("i", $selected_class);
    $students_stmt->execute();
    $students = $students_stmt->get_result();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-check"></i> Mark Attendance</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-bar">
                        <div class="form-group" style="flex: 1; margin: 0;">
                            <select name="class_id" class="form-control" <?php echo empty($my_classes) ? 'disabled' : 'required'; ?> onchange="this.form.submit()">
                                <option value="">Select Class</option>
                                <?php foreach ($my_classes as $class): ?>
                                    <option value="<?php echo (int)$class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name'] . (!empty($class['section']) ? ' - ' . $class['section'] : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (empty($my_classes)): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 40px; color: var(--text-light);">
                    <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                    You have not been assigned to any classes yet. Please contact the administrator.
                </div>
            </div>
            <?php elseif ($students && $students->num_rows > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Student List - <?php echo date('d M Y', strtotime($selected_date)); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Student Name</th>
                                        <th style="text-align: center;">Present</th>
                                        <th style="text-align: center;">Absent</th>
                                        <th style="text-align: center;">Late</th>
                                        <th style="text-align: center;">Excused</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $student['roll_number'] ?? '-'; ?></td>
                                        <td><strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong></td>
                                        <td style="text-align: center;">
                                            <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="present" required>
                                        </td>
                                        <td style="text-align: center;">
                                            <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="absent">
                                        </td>
                                        <td style="text-align: center;">
                                            <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="late">
                                        </td>
                                        <td style="text-align: center;">
                                            <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" value="excused">
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?php echo $student['student_id']; ?>]" class="form-control" placeholder="Optional remarks">
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button type="submit" name="submit_attendance" class="btn btn-primary btn-block" style="max-width: 400px; margin: 0 auto;">
                                <i class="fas fa-check"></i> Submit Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($selected_class): ?>
            <div class="card">
                <div class="card-body">
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No students found in this class
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>







