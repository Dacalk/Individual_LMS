<?php
require_once '../config/config.php';
require_once '../includes/quiz_helpers.php';

if (!hasRole('student')) {
    redirect('index.php');
}

$page_title = 'MCQ Quizzes';
$error = '';
$success = '';

ensureQuizTables($conn);

// Fetch student data
$student_stmt = $conn->prepare("SELECT s.student_id, s.class_id, c.class_name, c.section
                                FROM students s
                                LEFT JOIN classes c ON s.class_id = c.class_id
                                WHERE s.user_id = ?");
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    redirect('logout.php');
}

$student_id = (int)$student['student_id'];
$student_class_id = $student['class_id'] ? (int)$student['class_id'] : null;

if (isset($_GET['status']) && $_GET['status'] === 'completed') {
    $success = 'Your quiz submission has been recorded.';
}

// Fetch quizzes available to the student
$quiz_stmt = $conn->prepare("SELECT q.quiz_id, q.title, q.class_id, q.created_at,
                                   c.class_name, c.section,
                                   COUNT(DISTINCT qq.question_id) AS question_count,
                                   a.attempt_id, a.is_correct, a.attempted_at,
                                   SUM(CASE WHEN r.is_correct = 1 THEN 1 ELSE 0 END) AS correct_count
                            FROM mcq_quizzes q
                            LEFT JOIN classes c ON q.class_id = c.class_id
                            LEFT JOIN mcq_quiz_questions qq ON q.quiz_id = qq.quiz_id
                            LEFT JOIN mcq_quiz_attempts a ON q.quiz_id = a.quiz_id AND a.student_id = ?
                            LEFT JOIN mcq_quiz_responses r ON a.attempt_id = r.attempt_id
                            WHERE q.status = 'published' AND (q.class_id IS NULL OR q.class_id = ?)
                            GROUP BY q.quiz_id, c.class_name, c.section, a.attempt_id, a.is_correct, a.attempted_at
                            HAVING question_count > 0
                            ORDER BY q.created_at DESC");
$quiz_stmt->bind_param("ii", $student_id, $student_class_id);
$quiz_stmt->execute();
$quizzes = $quiz_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-student.php'; ?>
    
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
                    <h3><i class="fas fa-question-circle"></i> MCQ Quizzes</h3>
                </div>
                <div class="card-body">
                    <?php if ($quizzes->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Class</th>
                                    <th>Questions</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($quiz = $quizzes->fetch_assoc()):
                                    $question_count = (int)$quiz['question_count'];
                                    $attempted = !empty($quiz['attempt_id']);
                                    $correct_count = (int)($quiz['correct_count'] ?? 0);
                                    $attempt_status = 'Not Attempted';
                                    $badge_class = 'secondary';
                                    if ($attempted) {
                                        $attempt_status = $correct_count . ' / ' . $question_count . ' correct';
                                        $badge_class = $correct_count === $question_count ? 'success' : 'warning';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                                        <br><small style="color: var(--text-light);">Created: <?php echo date('d M Y', strtotime($quiz['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($quiz['class_id']): ?>
                                            <?php echo htmlspecialchars($quiz['class_name'] . (!empty($quiz['section']) ? ' - ' . $quiz['section'] : '')); ?>
                                        <?php else: ?>
                                            <em>General</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo $question_count; ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo $badge_class; ?>"><?php echo $attempt_status; ?></span>
                                        <?php if ($attempted): ?>
                                            <br><small>Attempted on <?php echo date('d M Y, h:i A', strtotime($quiz['attempted_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="quiz_view.php?quiz_id=<?php echo (int)$quiz['quiz_id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-arrow-right"></i> <?php echo $attempted ? 'View Result' : 'Take Quiz'; ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        No quizzes available right now. Please check back later.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.empty-state {
    text-align: center;
    color: var(--text-light);
    padding: 40px 20px;
    font-size: 16px;
}

.empty-state i {
    display: block;
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.table .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
</style>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/config.php';
require_once '../includes/quiz_helpers.php';

if (!hasRole('student')) {
    redirect('index.php');
}

$page_title = 'MCQ Quizzes';
$error = '';
$success = '';

// Fetch student data
$student_stmt = $conn->prepare("SELECT s.student_id, s.class_id, c.class_name, c.section
                                FROM students s
                                LEFT JOIN classes c ON s.class_id = c.class_id
                                WHERE s.user_id = ?");
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    redirect('logout.php');
}

$student_id = (int)$student['student_id'];
$student_class_id = $student['class_id'] ? (int)$student['class_id'] : null;

ensureQuizTables($conn);

// Handle quiz attempts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attempt') {
    $quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
    $selected_option = $_POST['selected_option'] ?? '';

    if (!$quiz_id || !in_array($selected_option, ['A', 'B', 'C', 'D'], true)) {
        $error = 'Please select a valid answer.';
    } else {
        // Verify quiz is available to student
        $quiz_check_stmt = $conn->prepare("SELECT correct_option
                                           FROM mcq_quizzes
                                           WHERE quiz_id = ? AND status = 'published' AND (class_id IS NULL OR class_id = ?)");
        $quiz_check_stmt->bind_param("ii", $quiz_id, $student_class_id);
        $quiz_check_stmt->execute();
        $quiz_check_result = $quiz_check_stmt->get_result();

        if ($quiz = $quiz_check_result->fetch_assoc()) {
            $is_correct = ($quiz['correct_option'] === $selected_option) ? 1 : 0;

            $attempt_stmt = $conn->prepare("INSERT INTO mcq_quiz_attempts (quiz_id, student_id, selected_option, is_correct)
                                             VALUES (?, ?, ?, ?)
                                             ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option), is_correct = VALUES(is_correct), attempted_at = CURRENT_TIMESTAMP");
            $attempt_stmt->bind_param("iisi", $quiz_id, $student_id, $selected_option, $is_correct);

            if ($attempt_stmt->execute()) {
                $success = 'Your response has been recorded.';
            } else {
                $error = 'Unable to submit your answer. Please try again.';
            }
        } else {
            $error = 'This quiz is no longer available.';
        }
    }
}

// Fetch available quizzes and attempts
$quiz_stmt = $conn->prepare("SELECT q.quiz_id, q.title, q.question, q.option_a, q.option_b, q.option_c, q.option_d,
                                    q.correct_option, q.created_at, q.class_id,
                                    c.class_name, c.section,
                                    a.selected_option, a.is_correct, a.attempted_at
                             FROM mcq_quizzes q
                             LEFT JOIN classes c ON q.class_id = c.class_id
                             LEFT JOIN mcq_quiz_attempts a ON q.quiz_id = a.quiz_id AND a.student_id = ?
                             WHERE q.status = 'published' AND (q.class_id IS NULL OR q.class_id = ?)
                             ORDER BY q.created_at DESC");
$quiz_stmt->bind_param("ii", $student_id, $student_class_id);
$quiz_stmt->execute();
$quizzes = $quiz_stmt->get_result();

$quiz_list = [];
while ($row = $quizzes->fetch_assoc()) {
    $quiz_list[] = $row;
}

$total_quizzes = count($quiz_list);
$attempted_quizzes = 0;
$correct_answers = 0;

foreach ($quiz_list as $quiz) {
    if ($quiz['selected_option']) {
        $attempted_quizzes++;
        if ((int)$quiz['is_correct'] === 1) {
            $correct_answers++;
        }
    }
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-student.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $total_quizzes; ?></h3>
                        <p>Available Quizzes</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $attempted_quizzes; ?></h3>
                        <p>Attempted</p>
                    </div>
                </div>
                
                <div class="dashboard-card info">
                    <div class="card-icon info">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $correct_answers; ?></h3>
                        <p>Correct Answers</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Quizzes</h3>
                    <?php if ($student_class_id): ?>
                    <span class="badge badge-primary">Class: <?php echo htmlspecialchars($student['class_name'] . (!empty($student['section']) ? ' - ' . $student['section'] : '')); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($total_quizzes > 0): ?>
                        <div class="quiz-grid">
                            <?php foreach ($quiz_list as $quiz):
                                $attempted = !empty($quiz['selected_option']);
                                $is_correct = (int)$quiz['is_correct'] === 1;
                            ?>
                            <div class="quiz-card <?php echo $attempted ? ($is_correct ? 'quiz-correct' : 'quiz-attempted') : ''; ?>">
                                <div class="quiz-header">
                                    <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                    <small>
                                        <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($quiz['created_at'])); ?>
                                        <?php if ($quiz['class_id']): ?>
                                            &nbsp;•&nbsp; <i class="fas fa-users"></i> <?php echo htmlspecialchars($quiz['class_name'] . (!empty($quiz['section']) ? ' - ' . $quiz['section'] : '')); ?>
                                        <?php else: ?>
                                            &nbsp;•&nbsp; <i class="fas fa-globe"></i> General
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <p class="quiz-question"><?php echo nl2br(htmlspecialchars($quiz['question'])); ?></p>

                                <form method="POST" class="quiz-options">
                                    <input type="hidden" name="action" value="attempt">
                                    <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz['quiz_id']; ?>">

                                    <?php
                                    $options = [
                                        'A' => $quiz['option_a'],
                                        'B' => $quiz['option_b'],
                                        'C' => $quiz['option_c'],
                                        'D' => $quiz['option_d'],
                                    ];
                                    foreach ($options as $key => $text):
                                        $is_selected = $attempted && $quiz['selected_option'] === $key;
                                        $is_correct_option = $quiz['correct_option'] === $key;
                                    ?>
                                    <label class="quiz-option <?php echo $attempted ? ($is_correct_option ? 'option-correct' : ($is_selected ? 'option-selected' : 'disabled')) : ''; ?>">
                                        <input type="radio" name="selected_option" value="<?php echo $key; ?>" <?php echo $attempted ? 'disabled' : ''; ?> <?php echo $is_selected ? 'checked' : ''; ?>>
                                        <span class="option-key"><?php echo $key; ?></span>
                                        <span class="option-text"><?php echo htmlspecialchars($text); ?></span>
                                    </label>
                                    <?php endforeach; ?>

                                    <div class="quiz-actions">
                                        <?php if ($attempted): ?>
                                            <span class="badge badge-<?php echo $is_correct ? 'success' : 'danger'; ?>">
                                                <?php echo $is_correct ? 'Correct' : 'Incorrect'; ?>
                                            </span>
                                            <small>Attempted on <?php echo date('d M Y, h:i A', strtotime($quiz['attempted_at'])); ?></small>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Submit Answer</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                            No quizzes available at the moment. Please check back later.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.quiz-card {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    background: #ffffff;
    transition: box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.quiz-card:hover {
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.12);
}

.quiz-card.quiz-attempted {
    border-color: #fbbf24;
    background: #fffbeb;
}

.quiz-card.quiz-correct {
    border-color: #34d399;
    background: #ecfdf3;
}

.quiz-header h4 {
    margin: 0 0 5px 0;
    color: var(--primary-color);
}

.quiz-header small {
    color: var(--text-light);
}

.quiz-question {
    margin: 0;
    color: var(--text-dark);
    font-weight: 500;
}

.quiz-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quiz-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    transition: background 0.2s, border-color 0.2s;
    cursor: pointer;
    background: #f9fafb;
}

.quiz-option:hover {
    border-color: var(--primary-color);
}

.quiz-option input {
    margin: 0;
}

.option-key {
    font-weight: 700;
    color: var(--primary-color);
}

.option-text {
    color: var(--text-dark);
}

.quiz-option.disabled {
    cursor: default;
    opacity: 0.7;
}

.quiz-option.option-selected {
    border-color: #fbbf24;
    background: #fef3c7;
}

.quiz-option.option-correct {
    border-color: #34d399;
    background: #d1fae5;
}

.quiz-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
}

.quiz-actions small {
    color: var(--text-light);
}

@media (max-width: 768px) {
    .quiz-grid {
        grid-template-columns: 1fr;
    }

    .quiz-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

