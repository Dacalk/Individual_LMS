<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

function calculate_grade_letter(float $percentage): string
{
    if ($percentage >= 90) {
        return 'A+';
    }
    if ($percentage >= 75) {
        return 'A';
    }
    if ($percentage >= 60) {
        return 'B';
    }
    if ($percentage >= 50) {
        return 'C';
    }
    return 'F';
}

$page_title = 'Grades & Marks';
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

// Initial selections
$selected_class = 0;
$selected_subject = 0;
$selected_exam = 0;

// Handle GET parameters first (for redirects and initial page load)
if (isset($_GET['class_id'])) {
    $selected_class = (int)$_GET['class_id'];
}
if (isset($_GET['subject_id'])) {
    $selected_subject = (int)$_GET['subject_id'];
}
if (isset($_GET['exam_id'])) {
    $selected_exam = (int)$_GET['exam_id'];
}
if (isset($_GET['status']) && $_GET['status'] === 'created') {
    $success = 'Assessment created successfully! You can now record marks.';
}

// Handle POST parameters (but exam creation is handled separately below)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only set from POST if it's NOT the create_exam form
    if (!isset($_POST['action']) || $_POST['action'] !== 'create_exam') {
        if (isset($_POST['class_id'])) {
            $selected_class = (int)$_POST['class_id'];
        }
        if (isset($_POST['subject_id'])) {
            $selected_subject = (int)$_POST['subject_id'];
        }
        if (isset($_POST['exam_id'])) {
            $selected_exam = (int)$_POST['exam_id'];
        }
    }
}

// Fetch classes assigned to teacher
$classes_stmt = $conn->prepare("SELECT DISTINCT c.class_id, c.class_name, c.class_numeric, c.section, c.academic_year,
                                (c.class_teacher_id = ?) AS is_class_teacher
                                FROM classes c
                                LEFT JOIN class_subjects cs ON c.class_id = cs.class_id AND cs.teacher_id = ?
                                WHERE c.class_teacher_id = ? OR cs.teacher_id IS NOT NULL
                                ORDER BY c.class_numeric ASC, c.section ASC, c.class_name ASC");
$classes_stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

if (!$selected_class && !empty($classes)) {
    $selected_class = (int)$classes[0]['class_id'];
}

$class_ids = array_column($classes, 'class_id');
if ($selected_class && !in_array($selected_class, $class_ids, true)) {
    $error = 'You are not assigned to the selected class.';
    $selected_class = 0;
    $selected_subject = 0;
    $selected_exam = 0;
}

// Fetch subjects for selected class
$subjects = [];
if ($selected_class) {
    $subjects_stmt = $conn->prepare("SELECT sub.subject_id, sub.subject_name, sub.subject_code
                                     FROM class_subjects cs
                                     JOIN subjects sub ON cs.subject_id = sub.subject_id
                                     WHERE cs.teacher_id = ? AND cs.class_id = ?
                                     ORDER BY sub.subject_name ASC");
    $subjects_stmt->bind_param("ii", $teacher_id, $selected_class);
    $subjects_stmt->execute();
    $subjects_result = $subjects_stmt->get_result();
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

if (!$selected_subject && !empty($subjects)) {
    $selected_subject = (int)$subjects[0]['subject_id'];
}

$subject_ids = array_column($subjects, 'subject_id');
if ($selected_subject && !in_array($selected_subject, $subject_ids, true)) {
    $error = 'You are not assigned to the selected subject.';
    $selected_subject = 0;
    $selected_exam = 0;
}

// Handle exam creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_exam') {
    // Get class_id and subject_id from POST (form hidden fields)
    $form_class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $form_subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    
    // Use form values if available, otherwise use selected values
    $exam_class_id = $form_class_id > 0 ? $form_class_id : $selected_class;
    $exam_subject_id = $form_subject_id > 0 ? $form_subject_id : $selected_subject;
    
    if (!$exam_class_id || !$exam_subject_id) {
        $error = 'Please select a class and subject before creating an exam.';
    } else {
        // Verify teacher has access to this class/subject
        $verify_stmt = $conn->prepare("SELECT COUNT(*) as count FROM class_subjects 
                                      WHERE class_id = ? AND subject_id = ? AND teacher_id = ?");
        $verify_stmt->bind_param("iii", $exam_class_id, $exam_subject_id, $teacher_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result()->fetch_assoc();
        
        if ($verify_result['count'] == 0) {
            $error = 'You do not have permission to create an exam for this class and subject combination.';
        } else {
            $exam_name = sanitize($_POST['exam_name'] ?? '');
            $exam_type = sanitize($_POST['exam_type'] ?? 'unit_test');
            $exam_date = sanitize($_POST['exam_date'] ?? date('Y-m-d'));
            $start_time = sanitize($_POST['start_time'] ?? '08:00');
            $end_time = sanitize($_POST['end_time'] ?? '09:00');
            $total_marks = isset($_POST['total_marks']) ? (int)$_POST['total_marks'] : 100;
            $passing_marks = isset($_POST['passing_marks']) ? (int)$_POST['passing_marks'] : 40;

            if (empty(trim($exam_name))) {
                $error = 'Exam name is required.';
            } elseif ($total_marks <= 0) {
                $error = 'Total marks must be greater than zero.';
            } elseif ($passing_marks < 0 || $passing_marks > $total_marks) {
                $error = 'Passing marks must be between 0 and total marks.';
            } else {
                $academic_year = date('Y') . '-' . (date('Y') + 1);
                $exam_insert_stmt = $conn->prepare("INSERT INTO exams (exam_name, exam_type, class_id, subject_id, exam_date, start_time, end_time, total_marks, passing_marks, academic_year, status)
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                $start_time_sql = $start_time . ':00';
                $end_time_sql = $end_time . ':00';
                $exam_insert_stmt->bind_param(
                    "ssissssiis",
                    $exam_name,
                    $exam_type,
                    $exam_class_id,
                    $exam_subject_id,
                    $exam_date,
                    $start_time_sql,
                    $end_time_sql,
                    $total_marks,
                    $passing_marks,
                    $academic_year
                );

                if ($exam_insert_stmt->execute()) {
                    $new_exam_id = $exam_insert_stmt->insert_id;
                    $success = 'Assessment created successfully! You can now record marks.';
                    // Update selected values and redirect
                    $selected_class = $exam_class_id;
                    $selected_subject = $exam_subject_id;
                    $selected_exam = $new_exam_id;
                    // Redirect to refresh page with new exam selected
                    redirect('teacher/grades.php?class_id=' . $exam_class_id . '&subject_id=' . $exam_subject_id . '&exam_id=' . $new_exam_id . '&status=created');
                } else {
                    $error = 'Failed to create assessment. Database Error: ' . $exam_insert_stmt->error . ' | MySQL Error: ' . $conn->error;
                    // Log detailed error for debugging
                    error_log("Assessment creation failed - Teacher ID: $teacher_id, Class: $exam_class_id, Subject: $exam_subject_id, Error: " . $exam_insert_stmt->error);
                }
            }
        }
    }
}

// Fetch exams for selected class/subject
$exams = [];
if ($selected_class && $selected_subject) {
    $exams_stmt = $conn->prepare("SELECT exam_id, exam_name, exam_type, exam_date, total_marks, passing_marks
                                  FROM exams
                                  WHERE class_id = ? AND subject_id = ?
                                  ORDER BY exam_date DESC, exam_name ASC");
    $exams_stmt->bind_param("ii", $selected_class, $selected_subject);
    $exams_stmt->execute();
    $exams_result = $exams_stmt->get_result();
    while ($row = $exams_result->fetch_assoc()) {
        $exams[] = $row;
    }
}

if (!$selected_exam && !empty($exams)) {
    $selected_exam = (int)$exams[0]['exam_id'];
}

$exam_ids = array_column($exams, 'exam_id');
if ($selected_exam && !in_array($selected_exam, $exam_ids, true)) {
    $error = 'Selected exam not found.';
    $selected_exam = 0;
}

$selected_exam_details = null;
foreach ($exams as $exam) {
    if ((int)$exam['exam_id'] === $selected_exam) {
        $selected_exam_details = $exam;
        break;
    }
}

// Fetch students for grade entry
$students = [];
if ($selected_class) {
    $students_stmt = $conn->prepare("SELECT st.student_id, st.roll_number, st.admission_number,
                                            u.first_name, u.last_name
                                     FROM students st
                                     JOIN users u ON st.user_id = u.user_id
                                     WHERE st.class_id = ?
                                     ORDER BY st.roll_number ASC, u.first_name ASC");
    $students_stmt->bind_param("i", $selected_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch existing grades
$existing_grades = [];
if ($selected_exam) {
    $grades_stmt = $conn->prepare("SELECT student_id, marks_obtained, grade, remarks
                                   FROM grades
                                   WHERE exam_id = ?");
    $grades_stmt->bind_param("i", $selected_exam);
    $grades_stmt->execute();
    $grades_result = $grades_stmt->get_result();
    while ($row = $grades_result->fetch_assoc()) {
        $existing_grades[(int)$row['student_id']] = $row;
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_grades') {
    if (!$selected_exam_details) {
        $error = 'Please select a valid exam before saving grades.';
    } elseif (empty($_POST['marks']) || !is_array($_POST['marks'])) {
        $error = 'No marks provided.';
    } else {
        $total_marks_allowed = (float)$selected_exam_details['total_marks'];
        $marks = $_POST['marks'];
        $remarks_input = $_POST['remarks'] ?? [];
        $updated = 0;

        $grade_insert_stmt = $conn->prepare("INSERT INTO grades (student_id, exam_id, marks_obtained, grade, remarks, entered_by)
                                             VALUES (?, ?, ?, ?, ?, ?)
                                             ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained),
                                                                     grade = VALUES(grade),
                                                                     remarks = VALUES(remarks),
                                                                     entered_by = VALUES(entered_by),
                                                                     updated_at = NOW()");

        foreach ($students as $student) {
            $student_id = (int)$student['student_id'];
            if (!array_key_exists($student_id, $marks) || $marks[$student_id] === '') {
                continue;
            }

            $mark_value = (float)$marks[$student_id];
            if ($mark_value < 0 || $mark_value > $total_marks_allowed) {
                $error = 'Marks for student ' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . ' must be between 0 and ' . $total_marks_allowed . '.';
                break;
            }

            $percentage = $total_marks_allowed > 0 ? ($mark_value / $total_marks_allowed) * 100 : 0;
            $grade_letter = calculate_grade_letter($percentage);
            $remark = isset($remarks_input[$student_id]) ? sanitize($remarks_input[$student_id]) : '';

            $grade_insert_stmt->bind_param(
                "iisssi",
                $student_id,
                $selected_exam,
                $mark_value,
                $grade_letter,
                $remark,
                $_SESSION['user_id']
            );

            if ($grade_insert_stmt->execute()) {
                $updated++;
                $existing_grades[$student_id] = [
                    'marks_obtained' => $mark_value,
                    'grade' => $grade_letter,
                    'remarks' => $remark,
                ];
            }
        }

        if (!$error) {
            $success = $updated > 0 ? "Grades saved for {$updated} students." : 'No grades were updated.';
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'created' && !$success) {
    $success = 'Exam created successfully. You can now record marks.';
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
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Select Class, Subject & Exam</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="selection-grid">
                        <div class="form-group">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-control" required>
                                <?php if (empty($classes)): ?>
                                    <option value="">No classes assigned</option>
                                <?php else: ?>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . (!empty($class['section']) ? ' - ' . $class['section'] : '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-control" <?php echo empty($subjects) ? 'disabled' : ''; ?>>
                                <?php if (empty($subjects)): ?>
                                    <option value="">No subjects</option>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>" <?php echo $selected_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Exam</label>
                            <select name="exam_id" class="form-control" <?php echo empty($exams) ? 'disabled' : ''; ?>>
                                <?php if (empty($exams)): ?>
                                    <option value="">No exams</option>
                                <?php else: ?>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?php echo $exam['exam_id']; ?>" <?php echo $selected_exam == $exam['exam_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($exam['exam_name']); ?> (<?php echo formatDate($exam['exam_date']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> View Records</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($selected_class && $selected_subject): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Create Assessment</h3>
                </div>
                <div class="card-body">
                    <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'create_exam'): ?>
                        <div class="alert alert-danger" style="margin-bottom: 20px;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="create-exam-grid" id="createAssessmentForm" onsubmit="return validateAssessmentForm(event)">
                        <input type="hidden" name="action" value="create_exam">
                        <input type="hidden" name="class_id" id="form_class_id" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="subject_id" id="form_subject_id" value="<?php echo $selected_subject; ?>">

                        <div class="form-group">
                            <label class="form-label">Assessment Name *</label>
                            <input type="text" name="exam_name" id="exam_name" class="form-control" placeholder="e.g., Unit Test 1, Midterm Exam" required>
                            <small class="form-text" style="color: var(--text-light);">Enter a descriptive name for this assessment</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Assessment Type *</label>
                            <select name="exam_type" id="exam_type" class="form-control" required>
                                <option value="unit_test">Unit Test</option>
                                <option value="midterm">Midterm</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                                <option value="final">Final</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Assessment Date *</label>
                            <input type="date" name="exam_date" id="exam_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" value="08:00">
                        </div>

                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" value="09:00">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Total Marks *</label>
                            <input type="number" name="total_marks" id="total_marks" class="form-control" value="100" min="1" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Passing Marks *</label>
                            <input type="number" name="passing_marks" id="passing_marks" class="form-control" value="40" min="0" step="0.01" required>
                            <small class="form-text" style="color: var(--text-light);">Must be less than or equal to total marks</small>
                        </div>

                        <div class="form-actions" style="grid-column: 1 / -1; margin-top: 10px;">
                            <button type="submit" class="btn btn-primary" id="submitAssessmentBtn"><i class="fas fa-save"></i> Create Assessment</button>
                            <button type="reset" class="btn btn-outline"><i class="fas fa-redo"></i> Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select a class and subject from the dropdowns above to create an assessment.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($selected_exam && $selected_exam_details && !empty($students)): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-pencil-alt"></i> Record Marks</h3>
                        <p class="exam-meta">
                            <strong><?php echo htmlspecialchars($selected_exam_details['exam_name']); ?></strong>
                            &nbsp;•&nbsp; <?php echo formatDate($selected_exam_details['exam_date']); ?>
                            &nbsp;•&nbsp; Total Marks: <?php echo $selected_exam_details['total_marks']; ?>
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_grades">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $selected_subject; ?>">
                        <input type="hidden" name="exam_id" value="<?php echo $selected_exam; ?>">

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Student</th>
                                        <th>Marks Obtained (<?php echo $selected_exam_details['total_marks']; ?>)</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student):
                                        $student_id = (int)$student['student_id'];
                                        $existing = $existing_grades[$student_id] ?? null;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                                        <td style="max-width: 140px;">
                                            <input type="number"
                                                   name="marks[<?php echo $student_id; ?>]"
                                                   class="form-control"
                                                   step="0.01"
                                                   min="0"
                                                   max="<?php echo $selected_exam_details['total_marks']; ?>"
                                                   value="<?php echo $existing ? htmlspecialchars($existing['marks_obtained']) : ''; ?>"
                                                   placeholder="Enter marks">
                                        </td>
                                        <td>
                                            <?php if ($existing && isset($existing['marks_obtained'])):
                                                $percentage = $selected_exam_details['total_marks'] > 0
                                                    ? round(($existing['marks_obtained'] / $selected_exam_details['total_marks']) * 100, 1)
                                                    : 0;
                                                $grade_letter = $existing['grade'] ?? calculate_grade_letter($percentage);
                                            ?>
                                            <span class="badge badge-info" style="font-size: 14px;">
                                                <?php echo htmlspecialchars($grade_letter); ?>
                                            </span>
                                            <small style="display:block; color: var(--text-light);">(<?php echo $percentage; ?>%)</small>
                                            <?php else: ?>
                                            <span class="badge badge-secondary" style="font-size: 14px;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?php echo $student_id; ?>]" class="form-control" placeholder="Optional remarks" value="<?php echo $existing ? htmlspecialchars($existing['remarks']) : ''; ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Grades</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($selected_class && $selected_subject && empty($exams)): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 40px; color: var(--text-light);">
                    <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                    No exams found for this class and subject. Create an assessment above to start recording marks.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    align-items: end;
}

.selection-grid .form-actions {
    display: flex;
    align-items: center;
}

.create-exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    align-items: end;
}

.create-exam-grid .form-actions {
    display: flex;
    align-items: center;
}

.exam-meta {
    margin: 0;
    color: var(--text-light);
    font-size: 13px;
}

@media (max-width: 768px) {
    .selection-grid,
    .create-exam-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function validateAssessmentForm(event) {
    const form = document.getElementById('createAssessmentForm');
    if (!form) {
        console.error('Form not found');
        return true;
    }
    
    // Ensure hidden fields are set
    const classIdInput = document.getElementById('form_class_id');
    const subjectIdInput = document.getElementById('form_subject_id');
    
    if (!classIdInput || !classIdInput.value || classIdInput.value == '0') {
        alert('Please select a class first.');
        event.preventDefault();
        return false;
    }
    
    if (!subjectIdInput || !subjectIdInput.value || subjectIdInput.value == '0') {
        alert('Please select a subject first.');
        event.preventDefault();
        return false;
    }
    
    const examName = document.getElementById('exam_name').value.trim();
    const totalMarks = parseFloat(document.getElementById('total_marks').value);
    const passingMarks = parseFloat(document.getElementById('passing_marks').value);
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    // Validate exam name
    if (!examName) {
        alert('Please enter an assessment name.');
        document.getElementById('exam_name').focus();
        event.preventDefault();
        return false;
    }
    
    // Validate marks
    if (totalMarks <= 0 || isNaN(totalMarks)) {
        alert('Total marks must be greater than zero.');
        document.getElementById('total_marks').focus();
        event.preventDefault();
        return false;
    }
    
    if (passingMarks < 0 || passingMarks > totalMarks || isNaN(passingMarks)) {
        alert('Passing marks must be between 0 and total marks.');
        document.getElementById('passing_marks').focus();
        event.preventDefault();
        return false;
    }
    
    // Validate time
    if (startTime && endTime && startTime >= endTime) {
        alert('End time must be after start time.');
        document.getElementById('end_time').focus();
        event.preventDefault();
        return false;
    }
    
    // Show loading indicator
    const submitBtn = document.getElementById('submitAssessmentBtn');
    if (submitBtn) {
        const originalHTML = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        
        // Re-enable after 5 seconds in case of error
        setTimeout(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        }, 5000);
    }
    
    // Log form data for debugging
    console.log('Submitting assessment form:', {
        class_id: classIdInput.value,
        subject_id: subjectIdInput.value,
        exam_name: examName,
        total_marks: totalMarks,
        passing_marks: passingMarks
    });
    
    // Allow form submission
    return true;
}

// Auto-update passing marks max value when total marks changes
document.addEventListener('DOMContentLoaded', function() {
    const totalMarksInput = document.getElementById('total_marks');
    const passingMarksInput = document.getElementById('passing_marks');
    
    if (totalMarksInput && passingMarksInput) {
        totalMarksInput.addEventListener('input', function() {
            const maxMarks = parseFloat(this.value);
            if (maxMarks > 0 && !isNaN(maxMarks)) {
                passingMarksInput.setAttribute('max', maxMarks);
                const currentPassing = parseFloat(passingMarksInput.value);
                if (currentPassing > maxMarks) {
                    passingMarksInput.value = maxMarks;
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>

