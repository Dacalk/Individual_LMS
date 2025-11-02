<?php
require_once '../config/config.php';
require_once '../includes/quiz_helpers.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

ensureQuizTables($conn);

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id <= 0) {
    redirect('quizzes.php');
}

$quiz_stmt = $conn->prepare("SELECT q.quiz_id, q.title, q.status, q.created_at, q.class_id,
                                     c.class_name, c.section,
                                     COUNT(DISTINCT qq.question_id) AS question_count
                              FROM mcq_quizzes q
                              LEFT JOIN classes c ON q.class_id = c.class_id
                              LEFT JOIN mcq_quiz_questions qq ON q.quiz_id = qq.quiz_id
                              WHERE q.quiz_id = ? AND q.created_by = ?
                              GROUP BY q.quiz_id, c.class_name, c.section");
$quiz_stmt->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz = $quiz_result->fetch_assoc();

if (!$quiz) {
    redirect('quizzes.php');
}

$questions_stmt = $conn->prepare("SELECT question_id, question, option_a, option_b, option_c, option_d, correct_option, sort_order
                                   FROM mcq_quiz_questions
                                   WHERE quiz_id = ?
                                   ORDER BY sort_order ASC, question_id ASC");
$questions_stmt->bind_param("i", $quiz_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content">
            <a href="quizzes.php" class="btn btn-light" style="margin-bottom: 15px;"><i class="fas fa-arrow-left"></i> Back to My Quizzes</a>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-eye"></i> Quiz Preview</h3>
                </div>
                <div class="card-body">
                    <div class="quiz-details">
                        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                        <div class="details-grid">
                            <div><strong>Status:</strong> <?php echo ucfirst($quiz['status']); ?></div>
                            <div><strong>Questions:</strong> <?php echo (int)$quiz['question_count']; ?></div>
                            <div><strong>Created:</strong> <?php echo date('d M Y, h:i A', strtotime($quiz['created_at'])); ?></div>
                            <div><strong>Class:</strong>
                                <?php if ($quiz['class_id']): ?>
                                    <?php echo htmlspecialchars($quiz['class_name'] . (!empty($quiz['section']) ? ' - ' . $quiz['section'] : '')); ?>
                                <?php else: ?>
                                    General
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="questions-wrapper">
                        <?php if ($questions->num_rows > 0):
                            $index = 1;
                            while ($question = $questions->fetch_assoc()):
                                $options = [
                                    'A' => $question['option_a'],
                                    'B' => $question['option_b'],
                                    'C' => $question['option_c'],
                                    'D' => $question['option_d'],
                                ];
                        ?>
                        <div class="question-card">
                            <div class="question-title">
                                <span class="badge badge-primary">Q<?php echo $index++; ?></span>
                                <p><?php echo nl2br(htmlspecialchars($question['question'])); ?></p>
                            </div>
                            <div class="question-options">
                                <?php foreach ($options as $key => $text): ?>
                                <div class="option <?php echo $question['correct_option'] === $key ? 'correct' : ''; ?>">
                                    <span class="option-key"><?php echo $key; ?>.</span>
                                    <span class="option-text"><?php echo htmlspecialchars($text); ?></span>
                                    <?php if ($question['correct_option'] === $key): ?>
                                        <span class="option-indicator"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                            <p>No questions found for this quiz.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quiz-details h2 {
    margin-bottom: 15px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 25px;
}

.question-card {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 18px;
    margin-bottom: 18px;
    background: #f9fafb;
}

.question-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.question-title p {
    margin: 0;
    font-weight: 600;
}

.question-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.option {
    position: relative;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.option.correct {
    border-color: #2ecc71;
    background: rgba(46, 204, 113, 0.12);
}

.option-key {
    font-weight: 600;
    color: var(--primary-color);
}

.option-indicator {
    position: absolute;
    right: 12px;
    color: #1e8449;
}

@media (max-width: 768px) {
    .question-options {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>


