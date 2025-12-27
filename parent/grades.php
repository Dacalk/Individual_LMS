<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'Children Academic Results';

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

$grades = null;
$stats = [
    'total_exams' => 0,
    'average_percentage' => 0,
    'highest_marks' => 0,
    'total_subjects' => 0
];

if ($selected_student) {
    $student_id = $selected_student['student_id'];
    
    // Get all grades
    $grades_query = $conn->prepare("SELECT g.*, e.exam_name, e.exam_type, e.total_marks, e.exam_date, s.subject_name, s.subject_code
                                    FROM grades g
                                    JOIN exams e ON g.exam_id = e.exam_id
                                    JOIN subjects s ON e.subject_id = s.subject_id
                                    WHERE g.student_id = ?
                                    ORDER BY e.exam_date DESC, s.subject_name ASC");
    $grades_query->bind_param("i", $student_id);
    $grades_query->execute();
    $grades = $grades_query->get_result();
    
    // Calculate overall statistics
    $stats_query = $conn->prepare("SELECT 
        COUNT(DISTINCT g.exam_id) as total_exams,
        COUNT(DISTINCT e.subject_id) as total_subjects,
        AVG((g.marks_obtained / e.total_marks) * 100) as avg_percentage,
        MAX((g.marks_obtained / e.total_marks) * 100) as highest_percentage
        FROM grades g
        JOIN exams e ON g.exam_id = e.exam_id
        WHERE g.student_id = ?");
    $stats_query->bind_param("i", $student_id);
    $stats_query->execute();
    $stats_result = $stats_query->get_result();
    if ($stats_row = $stats_result->fetch_assoc()) {
        $stats['total_exams'] = $stats_row['total_exams'];
        $stats['total_subjects'] = $stats_row['total_subjects'];
        $stats['average_percentage'] = round($stats_row['avg_percentage'], 1);
        $stats['highest_marks'] = round($stats_row['highest_percentage'], 1);
    }
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
                    <select class="form-control" onchange="window.location.href='grades.php?student_id=' + this.value" style="max-width: 400px;">
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
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['total_exams']; ?></h3>
                            <p>Total Exams</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card success">
                        <div class="card-icon success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['average_percentage']; ?>%</h3>
                            <p>Average Percentage</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card warning">
                        <div class="card-icon warning">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['highest_marks']; ?>%</h3>
                            <p>Highest Score</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card info">
                        <div class="card-icon info">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $stats['total_subjects']; ?></h3>
                            <p>Subjects</p>
                        </div>
                    </div>
                </div>
                
                <!-- Grades Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Exam Results</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($grades && $grades->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($grade = $grades->fetch_assoc()): 
                                        $percentage = ($grade['marks_obtained'] / $grade['total_marks']) * 100;
                                        $badge_class = $percentage >= 90 ? 'success' : 
                                                      ($percentage >= 75 ? 'info' : 
                                                      ($percentage >= 50 ? 'warning' : 'danger'));
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($grade['exam_date']); ?></td>
                                        <td>
                                            <strong><?php echo $grade['exam_name']; ?></strong>
                                            <br><small style="color: var(--text-light);"><?php echo ucfirst($grade['exam_type']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $grade['subject_name']; ?>
                                            <br><small style="color: var(--text-light);"><?php echo $grade['subject_code']; ?></small>
                                        </td>
                                        <td><strong style="font-size: 18px; color: var(--primary-color);"><?php echo $grade['marks_obtained']; ?></strong></td>
                                        <td><?php echo $grade['total_marks']; ?></td>
                                        <td><strong><?php echo round($percentage, 1); ?>%</strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $badge_class; ?>" style="font-size: 14px;">
                                                <?php echo $grade['grade'] ?: 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $grade['remarks'] ?? '-'; ?></td>
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


