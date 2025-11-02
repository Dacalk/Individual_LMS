<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'My Classes';
$error = '';
$success = '';

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

// Fetch assigned classes
$classes_stmt = $conn->prepare("SELECT c.class_id, c.class_name, c.class_numeric, c.section, c.room_number, c.academic_year,
                                       COUNT(DISTINCT st.student_id) AS student_count,
                                       (c.class_teacher_id = ?) AS is_class_teacher,
                                       MAX(CASE WHEN cs.teacher_id IS NOT NULL THEN 1 ELSE 0 END) AS teaches_subject
                                FROM classes c
                                LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                                LEFT JOIN students st ON c.class_id = st.class_id
                                WHERE c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL
                                GROUP BY c.class_id, c.class_name, c.class_numeric, c.section, c.room_number, c.academic_year, c.class_teacher_id
                                ORDER BY c.class_numeric ASC, c.section ASC, c.class_name ASC");
$classes_stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if (!$selected_class && !empty($classes)) {
    $selected_class = (int)$classes[0]['class_id'];
}

$selected_class_allowed = false;
foreach ($classes as $class) {
    if ((int)$class['class_id'] === $selected_class) {
        $selected_class_allowed = true;
        break;
    }
}

if ($selected_class && !$selected_class_allowed) {
    $error = 'You are not assigned to this class.';
    $selected_class = 0;
}

$class_details = null;
$class_subjects = [];
$class_students = [];
$selected_class_student_count = 0;
$selected_class_info = null;
$selected_class_is_class_teacher = false;
$selected_class_teaches_subject = false;

if ($selected_class) {
    foreach ($classes as $class_row) {
        if ((int)$class_row['class_id'] === $selected_class) {
            $selected_class_info = $class_row;
            $selected_class_is_class_teacher = !empty($class_row['is_class_teacher']);
            $selected_class_teaches_subject = !empty($class_row['teaches_subject']);
            break;
        }
    }
}

if ($selected_class) {
    // Fetch class details
    $class_details_stmt = $conn->prepare("SELECT c.class_id, c.class_name, c.section, c.room_number, c.class_numeric, c.academic_year,
                                                CONCAT(u.first_name, ' ', u.last_name) AS class_teacher_name
                                         FROM classes c
                                         LEFT JOIN teachers ct ON c.class_teacher_id = ct.teacher_id
                                         LEFT JOIN users u ON ct.user_id = u.user_id
                                         WHERE c.class_id = ?");
    $class_details_stmt->bind_param("i", $selected_class);
    $class_details_stmt->execute();
    $class_details = $class_details_stmt->get_result()->fetch_assoc();

    // Fetch subjects assigned to this teacher for the class
    $subjects_stmt = $conn->prepare("SELECT sub.subject_id, sub.subject_name, sub.subject_code, cs.academic_year
                                     FROM class_subjects cs
                                     JOIN subjects sub ON cs.subject_id = sub.subject_id
                                     WHERE cs.teacher_id = ? AND cs.class_id = ?
                                     ORDER BY sub.subject_name ASC");
    $subjects_stmt->bind_param("ii", $teacher_id, $selected_class);
    $subjects_stmt->execute();
    $subjects_result = $subjects_stmt->get_result();
    while ($subject = $subjects_result->fetch_assoc()) {
        $class_subjects[] = $subject;
    }

    // Fetch students in class
    $students_stmt = $conn->prepare("SELECT st.student_id, st.roll_number, st.admission_number,
                                            u.first_name, u.last_name, u.email, u.phone
                                     FROM students st
                                     JOIN users u ON st.user_id = u.user_id
                                     WHERE st.class_id = ?
                                     ORDER BY st.roll_number ASC, u.first_name ASC");
    $students_stmt->bind_param("i", $selected_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    while ($student = $students_result->fetch_assoc()) {
        $class_students[] = $student;
    }

    $selected_class_student_count = count($class_students);
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

            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" class="class-select-form">
                        <div class="form-group" style="margin: 0; flex: 1;">
                            <label class="form-label">Select Class</label>
                            <select name="class_id" class="form-control" onchange="this.form.submit()" <?php echo empty($classes) ? 'disabled' : ''; ?>>
                                <?php if (empty($classes)): ?>
                                    <option value="">No classes assigned</option>
                                <?php else: ?>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                            <?php echo $class['class_name'] . (!empty($class['section']) ? ' - ' . $class['section'] : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($selected_class && $class_details): ?>
            <div class="class-overview-grid">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Class Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span>Class</span>
                                <strong>
                                    <?php echo htmlspecialchars($class_details['class_name']); ?><?php echo !empty($class_details['section']) ? ' - ' . htmlspecialchars($class_details['section']) : ''; ?>
                                </strong>
                                <?php if ($selected_class_is_class_teacher): ?>
                                <span class="badge badge-success" style="margin-top: 6px; display: inline-block; font-size: 11px;">
                                    <i class="fas fa-star"></i> You are the Class Teacher
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="summary-item">
                                <span>Academic Year</span>
                                <strong><?php echo htmlspecialchars($class_details['academic_year']); ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Students</span>
                                <strong><?php echo $selected_class_student_count; ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Subjects (You)</span>
                                <strong><?php echo count($class_subjects); ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Room</span>
                                <strong><?php echo $class_details['room_number'] ? htmlspecialchars($class_details['room_number']) : 'N/A'; ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Class Teacher</span>
                                <strong><?php echo $class_details['class_teacher_name'] ? htmlspecialchars($class_details['class_teacher_name']) : 'Not Assigned'; ?></strong>
                            </div>
                        </div>

                        <div class="summary-actions">
                            <a href="attendance.php?class_id=<?php echo $selected_class; ?>" class="btn btn-primary" style="flex: 1;"><i class="fas fa-user-check"></i> Take Attendance</a>
                            <?php if ($selected_class_teaches_subject): ?>
                            <a href="grades.php?class_id=<?php echo $selected_class; ?>" class="btn btn-success" style="flex: 1;"><i class="fas fa-trophy"></i> Manage Grades</a>
                            <?php else: ?>
                            <span class="btn btn-success disabled" style="opacity: 0.6; cursor: not-allowed; flex: 1;"><i class="fas fa-trophy"></i> Manage Grades</span>
                            <?php endif; ?>
                            <a href="timetable.php" class="btn btn-outline" style="flex: 1;"><i class="fas fa-calendar-week"></i> View Timetable</a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Subjects You Teach</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($class_subjects)): ?>
                        <div class="subjects-list">
                            <?php foreach ($class_subjects as $subject): ?>
                            <div class="subject-chip">
                                <div>
                                    <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                </div>
                                <a href="grades.php?class_id=<?php echo $selected_class; ?>&subject_id=<?php echo $subject['subject_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-pencil-alt"></i> Record Marks
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: var(--text-light);">No subjects assigned yet for this class. Please contact the administrator.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Students</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($class_students)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Student</th>
                                    <th>Admission No.</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['admission_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="attendance.php?class_id=<?php echo $selected_class; ?>" class="btn btn-sm btn-outline"><i class="fas fa-user-check"></i> Attendance</a>
                                            <a href="grades.php?class_id=<?php echo $selected_class; ?>" class="btn btn-sm btn-primary"><i class="fas fa-trophy"></i> Grades</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No students found for this class.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 64px; color: var(--medium-gray); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-light); margin: 0;">No classes assigned yet.</h3>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.class-select-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.class-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.summary-item {
    background: var(--light-gray);
    padding: 14px 16px;
    border-radius: 10px;
}

.summary-item span {
    display: block;
    color: var(--text-light);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-item strong {
    display: block;
    font-size: 18px;
    color: var(--text-dark);
    margin-top: 6px;
}

.summary-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.summary-actions .btn {
    flex: 1;
    min-width: 160px;
}

.subjects-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.subject-chip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--light-gray);
    padding: 14px 16px;
    border-radius: 10px;
}

.subject-chip strong {
    display: block;
    color: var(--primary-color);
    font-size: 15px;
}

.subject-chip small {
    color: var(--text-light);
    font-size: 12px;
}

.table-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

@media (max-width: 768px) {
    .class-select-form {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .summary-actions {
        flex-direction: column;
    }
    
    .summary-actions .btn {
        width: 100%;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

