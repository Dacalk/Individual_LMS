<?php
require_once '../config/config.php';

if (!hasRole('student')) {
    redirect('index.php');
}

$page_title = 'My Grades';

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
$stats = [
    'total_exams' => 0,
    'average_percentage' => 0,
    'highest_marks' => 0,
    'total_subjects' => 0
];

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
            
            <!-- Performance Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Performance Overview</h3>
                </div>
                <div class="card-body">
                    <?php if ($stats['average_percentage'] > 0): ?>
                    <div style="padding: 30px; text-align: center;">
                        <div style="display: inline-block; position: relative; width: 200px; height: 200px;">
                            <svg viewBox="0 0 200 200" style="transform: rotate(-90deg);">
                                <circle cx="100" cy="100" r="80" fill="none" stroke="#E5E7EB" stroke-width="20"/>
                                <circle cx="100" cy="100" r="80" fill="none" stroke="var(--primary-color)" stroke-width="20" 
                                        stroke-dasharray="<?php echo ($stats['average_percentage'] / 100) * 502.4; ?> 502.4"/>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 36px; font-weight: bold; color: var(--primary-color);">
                                <?php echo $stats['average_percentage']; ?>%
                            </div>
                        </div>
                        <div style="margin-top: 20px; font-size: 18px; color: var(--text-dark); font-weight: 600;">
                            Overall Performance
                        </div>
                        <div style="margin-top: 10px; color: var(--text-light);">
                            <?php 
                            if ($stats['average_percentage'] >= 90) echo "Excellent Performance! 🌟";
                            elseif ($stats['average_percentage'] >= 75) echo "Great Work! Keep it up! 👍";
                            elseif ($stats['average_percentage'] >= 60) echo "Good Effort! Room for improvement 💪";
                            else echo "Need More Focus! You can do better 📚";
                            ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        No performance data available yet
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Grades Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Exam Results</h3>
                </div>
                <div class="card-body">
                    <?php if ($grades->num_rows > 0): ?>
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
            
            <!-- Grading Scale -->
            <div class="card" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);">
                <div class="card-body" style="padding: 25px;">
                    <h4 style="color: #92400E; margin-bottom: 15px;">
                        <i class="fas fa-graduation-cap"></i> Grading Scale
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px;">
                            <strong style="color: #065F46;">A+ (90-100%)</strong>
                            <div style="color: var(--text-light); font-size: 12px;">Excellent</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px;">
                            <strong style="color: #1E40AF;">A (75-89%)</strong>
                            <div style="color: var(--text-light); font-size: 12px;">Very Good</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px;">
                            <strong style="color: #92400E;">B (60-74%)</strong>
                            <div style="color: var(--text-light); font-size: 12px;">Good</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px;">
                            <strong style="color: #991B1B;">C (50-59%)</strong>
                            <div style="color: var(--text-light); font-size: 12px;">Average</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: white; border-radius: 8px;">
                            <strong style="color: #7C2D12;">F (<50%)</strong>
                            <div style="color: var(--text-light); font-size: 12px;">Fail</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>







