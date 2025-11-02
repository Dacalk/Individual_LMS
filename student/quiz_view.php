<?php
require_once '../config/config.php';
require_once '../includes/quiz_helpers.php';

if (!hasRole('student')) {
    redirect('index.php');
}

ensureQuizTables($conn);

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id <= 0) {
    redirect('quizzes.php');
}

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

$quiz_stmt = $conn->prepare("SELECT q.quiz_id, q.title, q.class_id, q.created_at,
                                     c.class_name, c.section
                              FROM mcq_quizzes q
                              LEFT JOIN classes c ON q.class_id = c.class_id
                              WHERE q.quiz_id = ? AND q.status = 'published' AND (q.class_id IS NULL OR q.class_id = ?)");
$quiz_stmt->bind_param("ii", $quiz_id, $student_class_id);
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
$questions_result = $questions_stmt->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

if (empty($questions)) {
    redirect('quizzes.php');
}

$attempt_stmt = $conn->prepare("SELECT attempt_id, is_correct, attempted_at
                                 FROM mcq_quiz_attempts
                                 WHERE quiz_id = ? AND student_id = ?");
$attempt_stmt->bind_param("ii", $quiz_id, $student_id);
$attempt_stmt->execute();
$attempt_result = $attempt_stmt->get_result();
$attempt = $attempt_result->fetch_assoc();

$responses_map = [];
$score = 0;

if ($attempt) {
    $response_stmt = $conn->prepare("SELECT question_id, selected_option, is_correct
                                      FROM mcq_quiz_responses
                                      WHERE attempt_id = ?");
    $response_stmt->bind_param("i", $attempt['attempt_id']);
    $response_stmt->execute();
    $response_result = $response_stmt->get_result();
    while ($row = $response_result->fetch_assoc()) {
        $responses_map[(int)$row['question_id']] = $row;
        if ((int)$row['is_correct'] === 1) {
            $score++;
        }
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $allowed_options = ['A', 'B', 'C', 'D'];

    if (!is_array($answers) || count($answers) < count($questions)) {
        $error = 'Please answer every question before submitting.';
    } else {
        $conn->begin_transaction();
        try {
            // Fetch or create attempt row
            $attempt_id = null;
            if ($attempt) {
                $attempt_id = (int)$attempt['attempt_id'];
                $update_attempt = $conn->prepare("UPDATE mcq_quiz_attempts SET attempted_at = CURRENT_TIMESTAMP, is_correct = 0 WHERE attempt_id = ?");
                $update_attempt->bind_param("i", $attempt_id);
                $update_attempt->execute();
            } else {
                // Determine placeholder selected option for schema compatibility
                $first_option = reset($answers);
                if (!in_array($first_option, $allowed_options, true)) {
                    $first_option = 'A';
                }
                $create_attempt = $conn->prepare("INSERT INTO mcq_quiz_attempts (quiz_id, student_id, selected_option, is_correct)
                                                   VALUES (?, ?, ?, 0)");
                $create_attempt->bind_param("iis", $quiz_id, $student_id, $first_option);
                $create_attempt->execute();
                $attempt_id = $conn->insert_id;
                if ($attempt_id <= 0) {
                    throw new Exception('Unable to create attempt.');
                }
                $attempt = ['attempt_id' => $attempt_id];
            }

            $delete_responses = $conn->prepare("DELETE FROM mcq_quiz_responses WHERE attempt_id = ?");
            $delete_responses->bind_param("i", $attempt_id);
            $delete_responses->execute();

            $insert_response = $conn->prepare("INSERT INTO mcq_quiz_responses (attempt_id, question_id, selected_option, is_correct)
                                               VALUES (?, ?, ?, ?)");

            $correct_answers = 0;
            foreach ($questions as $index => $question) {
                $question_id = (int)$question['question_id'];
                $selected = isset($answers[$question_id]) ? strtoupper($answers[$question_id]) : '';

                if (!in_array($selected, $allowed_options, true)) {
                    throw new Exception('Invalid answer submitted.');
                }

                $is_correct = $selected === $question['correct_option'] ? 1 : 0;
                if ($is_correct) {
                    $correct_answers++;
                }

                $insert_response->bind_param("iisi", $attempt_id, $question_id, $selected, $is_correct);
                $insert_response->execute();
            }

            $final_status = $correct_answers === count($questions) ? 1 : 0;
            $final_option = reset($answers);
            if (!in_array($final_option, $allowed_options, true)) {
                $final_option = 'A';
            }
            $finalize_attempt = $conn->prepare("UPDATE mcq_quiz_attempts
                                                SET selected_option = ?, is_correct = ?, attempted_at = CURRENT_TIMESTAMP
                                                WHERE attempt_id = ?");
            $finalize_attempt->bind_param("sii", $final_option, $final_status, $attempt_id);
            $finalize_attempt->execute();

            $conn->commit();

            redirect('student/quizzes.php?status=completed');
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'We could not submit your answers. Please try again.';
        }
    }
}

// Refresh responses after potential submission without redirect (e.g. validation error)
if ($attempt) {
    $responses_map = [];
    $score = 0;
    $response_stmt = $conn->prepare("SELECT question_id, selected_option, is_correct
                                      FROM mcq_quiz_responses
                                      WHERE attempt_id = ?");
    $response_stmt->bind_param("i", $attempt['attempt_id']);
    $response_stmt->execute();
    $response_result = $response_stmt->get_result();
    while ($row = $response_result->fetch_assoc()) {
        $responses_map[(int)$row['question_id']] = $row;
        if ((int)$row['is_correct'] === 1) {
            $score++;
        }
    }
}

$question_total = count($questions);
$attempted = !empty($attempt);

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-student.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content">
            <?php if ($attempted): ?>
                <a href="quizzes.php" class="btn btn-light" style="margin-bottom: 15px;" onclick="window.close(); return false;"><i class="fas fa-arrow-left"></i> Close Quiz</a>
            <?php else: ?>
                <div style="margin-bottom: 15px; padding: 15px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Once you start this quiz, you cannot go back. Please ensure you are ready before proceeding.
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-check"></i> <?php echo htmlspecialchars($quiz['title']); ?></h3>
                </div>
                <div class="card-body">
                    <div class="quiz-meta">
                        <div><strong>Questions:</strong> <?php echo $question_total; ?></div>
                        <div><strong>Class:</strong>
                            <?php if ($quiz['class_id']): ?>
                                <?php echo htmlspecialchars($quiz['class_name'] . (!empty($quiz['section']) ? ' - ' . $quiz['section'] : '')); ?>
                            <?php else: ?>
                                General
                            <?php endif; ?>
                        </div>
                        <?php if ($attempted && $question_total > 0): ?>
                            <div><strong>Your Score:</strong> <?php echo $score; ?> / <?php echo $question_total; ?></div>
                            <div><strong>Last Attempt:</strong> <?php echo date('d M Y, h:i A', strtotime($attempt['attempted_at'] ?? 'now')); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="margin-top: 15px;"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="quiz-attempt-form">
                        <?php foreach ($questions as $index => $question):
                            $question_id = (int)$question['question_id'];
                            $response = $responses_map[$question_id] ?? null;
                            $selected_option = $response['selected_option'] ?? '';
                            $correct_option = $question['correct_option'];
                        ?>
                        <div class="quiz-question">
                            <div class="question-header">
                                <span class="question-number">Question <?php echo $index + 1; ?></span>
                                <?php if ($attempted): ?>
                                    <span class="question-status <?php echo ($response && (int)$response['is_correct'] === 1) ? 'correct' : 'incorrect'; ?>">
                                        <?php echo ($response && (int)$response['is_correct'] === 1) ? 'Correct' : 'Incorrect'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question'])); ?></p>
                            <div class="question-options">
                                <?php
                                    $options = [
                                        'A' => $question['option_a'],
                                        'B' => $question['option_b'],
                                        'C' => $question['option_c'],
                                        'D' => $question['option_d'],
                                    ];
                                    foreach ($options as $option_key => $option_text):
                                        $is_selected = $selected_option === $option_key;
                                        $is_correct_option = $correct_option === $option_key;
                                        $state_class = '';
                                        if ($attempted) {
                                            if ($is_correct_option) {
                                                $state_class = 'is-correct';
                                            } elseif ($is_selected && !$is_correct_option) {
                                                $state_class = 'is-wrong';
                                            }
                                        }
                                ?>
                                <label class="quiz-option <?php echo $state_class; ?>">
                                    <input type="radio" name="answers[<?php echo $question_id; ?>]" value="<?php echo $option_key; ?>" <?php echo $is_selected ? 'checked' : ''; ?> required>
                                    <span class="option-key"><?php echo $option_key; ?>.</span>
                                    <span class="option-text"><?php echo htmlspecialchars($option_text); ?></span>
                                    <?php if ($attempted && $is_correct_option): ?>
                                        <span class="option-indicator"><i class="fas fa-check"></i></span>
                                    <?php elseif ($attempted && $is_selected && !$is_correct_option): ?>
                                        <span class="option-indicator"><i class="fas fa-times"></i></span>
                                    <?php endif; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="attempt-actions">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Submit Answers</button>
                            <?php if ($attempted): ?>
                                <p class="attempt-note">Submitting again will update your last attempt.</p>
                            <?php else: ?>
                                <p class="attempt-note">Make sure to review your answers before submitting.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quiz-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 15px;
}

.quiz-question {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 18px;
    margin-bottom: 20px;
    background: #f9fafb;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.question-number {
    font-weight: 600;
    color: var(--primary-color);
}

.question-status {
    font-size: 13px;
    padding: 4px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.question-status.correct {
    background: rgba(46, 213, 115, 0.15);
    color: #1c7c4c;
}

.question-status.incorrect {
    background: rgba(255, 99, 132, 0.15);
    color: #c0392b;
}

.question-text {
    margin-bottom: 15px;
    font-size: 15px;
    color: var(--text-color);
}

.question-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

@media (max-width: 768px) {
    .question-options {
        grid-template-columns: 1fr;
    }
}

.quiz-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    position: relative;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.quiz-option input[type="radio"] {
    accent-color: var(--primary-color);
}

.quiz-option .option-key {
    font-weight: 600;
    color: var(--primary-color);
}

.quiz-option .option-indicator {
    position: absolute;
    right: 12px;
    font-size: 16px;
}

.quiz-option.is-correct {
    border-color: #2ecc71;
    background: rgba(46, 204, 113, 0.1);
}

.quiz-option.is-wrong {
    border-color: #e74c3c;
    background: rgba(231, 76, 60, 0.08);
}

.attempt-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
    margin-top: 10px;
}

.attempt-note {
    font-size: 13px;
    color: var(--text-light);
}
</style>

<script>
// Prevent back navigation during quiz (only if not already attempted)
<?php if (!$attempted): ?>
(function() {
    // Disable browser back button
    history.pushState(null, null, location.href);
    window.onpopstate = function(event) {
        history.pushState(null, null, location.href);
        alert('You cannot go back during the quiz. Please complete the quiz or close this window.');
    };
    
    // Prevent keyboard shortcuts for back navigation
    document.addEventListener('keydown', function(e) {
        // Prevent Alt+Left Arrow (back)
        if (e.altKey && e.keyCode === 37) {
            e.preventDefault();
            alert('Browser navigation is disabled during the quiz.');
        }
    });
})();
<?php endif; ?>

// Warn when closing window/tab if quiz is in progress (only for new attempts)
<?php if (!$attempted): ?>
window.addEventListener('beforeunload', function(e) {
    var form = document.querySelector('.quiz-attempt-form');
    if (form) {
        var radios = form.querySelectorAll('input[type="radio"]:checked');
        if (radios.length > 0) {
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your progress may be lost.';
            return e.returnValue;
        }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>

