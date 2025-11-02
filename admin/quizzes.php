<?php
require_once '../config/config.php';
require_once '../includes/quiz_helpers.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'MCQ Quizzes Management';
$error = '';
$success = '';

ensureQuizTables($conn);

// Fetch classes
$classes_stmt = $conn->prepare("SELECT class_id, class_name, section, class_numeric
                                FROM classes
                                WHERE status = 'active'
                                ORDER BY class_numeric ASC, section ASC, class_name ASC");
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $title = sanitize($_POST['title'] ?? '');
        $status_value = $_POST['status'] ?? 'draft';
        $class_id = isset($_POST['class_id']) && $_POST['class_id'] !== '' ? (int)$_POST['class_id'] : null;
        $class_id_param = $class_id ?? 0;

        $raw_questions = $_POST['questions'] ?? [];
        $raw_option_a = $_POST['option_a'] ?? [];
        $raw_option_b = $_POST['option_b'] ?? [];
        $raw_option_c = $_POST['option_c'] ?? [];
        $raw_option_d = $_POST['option_d'] ?? [];
        $raw_correct = $_POST['correct_option'] ?? [];

        $question_data = [];
        $rows = count($raw_questions);

        for ($i = 0; $i < $rows; $i++) {
            $question_text = trim($raw_questions[$i] ?? '');
            $optionA = trim($raw_option_a[$i] ?? '');
            $optionB = trim($raw_option_b[$i] ?? '');
            $optionC = trim($raw_option_c[$i] ?? '');
            $optionD = trim($raw_option_d[$i] ?? '');
            $correct = $raw_correct[$i] ?? '';

            if ($question_text === '' && $optionA === '' && $optionB === '' && $optionC === '' && $optionD === '' && $correct === '') {
                continue;
            }

            if ($question_text === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '') {
                $error = 'Please provide question text and all four options for each question.';
                break;
            }

            if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                $error = 'Please select the correct option for each question.';
                break;
            }

            $question_data[] = [
                'question' => sanitize($question_text),
                'option_a' => sanitize($optionA),
                'option_b' => sanitize($optionB),
                'option_c' => sanitize($optionC),
                'option_d' => sanitize($optionD),
                'correct'  => $correct
            ];
        }

        if (!$error && empty($question_data)) {
            $error = 'Add at least one question to the quiz.';
        }

        if (!$error && $title === '') {
            $error = 'Quiz title is required.';
        }

        if (!$error && !in_array($status_value, ['draft', 'published'], true)) {
            $error = 'Invalid quiz status selected.';
        }

        if (!$error && $class_id !== null && !in_array($class_id, array_column($classes, 'class_id'))) {
            $error = 'Selected class is invalid.';
        }

        if (!$error) {
            $conn->begin_transaction();
            try {
                $quiz_stmt = $conn->prepare("INSERT INTO mcq_quizzes (title, class_id, created_by, creator_role, status)
                                             VALUES (?, NULLIF(?,0), ?, 'admin', ?)");
                $quiz_stmt->bind_param(
                    "siis",
                    $title,
                    $class_id_param,
                    $_SESSION['user_id'],
                    $status_value
                );

                if (!$quiz_stmt->execute()) {
                    throw new Exception('Failed to create quiz');
                }

                $quiz_id = $conn->insert_id;

                $question_stmt = $conn->prepare("INSERT INTO mcq_quiz_questions
                    (quiz_id, question, option_a, option_b, option_c, option_d, correct_option, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($question_data as $index => $item) {
                    $sort_order = $index + 1;
                    $question_stmt->bind_param(
                        "issssssi",
                        $quiz_id,
                        $item['question'],
                        $item['option_a'],
                        $item['option_b'],
                        $item['option_c'],
                        $item['option_d'],
                        $item['correct'],
                        $sort_order
                    );

                    if (!$question_stmt->execute()) {
                        throw new Exception('Failed to create quiz question');
                    }
                }

                $conn->commit();
                $success = 'Quiz created successfully with ' . count($question_data) . ' question(s).';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Failed to create quiz. Please try again.';
            }
        }

    } elseif ($action === 'toggle_status' && isset($_POST['quiz_id'])) {
        $quiz_id = (int)$_POST['quiz_id'];
        $new_status = $_POST['new_status'] === 'published' ? 'published' : 'draft';

        $toggle_stmt = $conn->prepare("UPDATE mcq_quizzes SET status = ? WHERE quiz_id = ?");
        $toggle_stmt->bind_param("si", $new_status, $quiz_id);

        if ($toggle_stmt->execute()) {
            $success = 'Quiz status updated.';
        } else {
            $error = 'Unable to update quiz status.';
        }

    } elseif ($action === 'delete' && isset($_POST['quiz_id'])) {
        $quiz_id = (int)$_POST['quiz_id'];
        $delete_stmt = $conn->prepare("DELETE FROM mcq_quizzes WHERE quiz_id = ?");
        $delete_stmt->bind_param("i", $quiz_id);

        if ($delete_stmt->execute()) {
            $success = 'Quiz deleted successfully.';
        } else {
            $error = 'Unable to delete quiz.';
        }
    }
}

// Fetch quizzes with counts
$quiz_list_stmt = $conn->prepare("SELECT q.*, c.class_name, c.section, u.first_name, u.last_name,
                                  COUNT(DISTINCT qq.question_id) AS question_count,
                                  COUNT(DISTINCT a.attempt_id) AS attempt_count,
                                  SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) AS perfect_count
                                  FROM mcq_quizzes q
                                  LEFT JOIN classes c ON q.class_id = c.class_id
                                  LEFT JOIN users u ON q.created_by = u.user_id
                                  LEFT JOIN mcq_quiz_questions qq ON q.quiz_id = qq.quiz_id
                                  LEFT JOIN mcq_quiz_attempts a ON q.quiz_id = a.quiz_id
                                  GROUP BY q.quiz_id, c.class_name, c.section, u.first_name, u.last_name
                                  ORDER BY q.created_at DESC");
$quiz_list_stmt->execute();
$quizzes = $quiz_list_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-admin.php'; ?>
    
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
                    <h3><i class="fas fa-plus-circle"></i> Create MCQ Quiz</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="quiz-form" id="createQuizForm">
                        <input type="hidden" name="action" value="create">

                        <div class="form-group">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Class (optional)</label>
                            <select name="class_id" class="form-control">
                                <option value="">-- General / All Classes --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo (int)$class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name'] . (!empty($class['section']) ? ' - ' . $class['section'] : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="questions-header">
                            <h4><i class="fas fa-question-circle"></i> Questions</h4>
                            <button type="button" class="btn btn-outline" onclick="addQuestionRow()"><i class="fas fa-plus"></i> Add Question</button>
                        </div>

                        <div id="questionsContainer" class="question-list">
                            <div class="question-card" data-question-index="1">
                                <div class="question-card-header">
                                    <h5>Question <span class="question-number">1</span></h5>
                                    <button type="button" class="btn btn-sm btn-danger remove-question" onclick="removeQuestionRow(this)" style="display: none;"><i class="fas fa-trash"></i></button>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Question *</label>
                                    <textarea name="questions[]" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="options-grid">
                                    <div class="form-group">
                                        <label class="form-label">Option A *</label>
                                        <input type="text" name="option_a[]" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Option B *</label>
                                        <input type="text" name="option_b[]" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Option C *</label>
                                        <input type="text" name="option_c[]" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Option D *</label>
                                        <input type="text" name="option_d[]" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Correct Option *</label>
                                    <select name="correct_option[]" class="form-control" required>
                                        <option value="">Select correct answer</option>
                                        <option value="A">Option A</option>
                                        <option value="B">Option B</option>
                                        <option value="C">Option C</option>
                                        <option value="D">Option D</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="published">Publish</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Quiz</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Quizzes</h3>
                </div>
                <div class="card-body">
                    <?php if ($quizzes->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                                        <br><small style="color: var(--text-light);">Created by <?php echo htmlspecialchars(($quiz['first_name'] ?? '') . ' ' . ($quiz['last_name'] ?? '')); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($quiz['class_id']): ?>
                                            <?php echo htmlspecialchars($quiz['class_name'] . (!empty($quiz['section']) ? ' - ' . $quiz['section'] : '')); ?>
                                        <?php else: ?>
                                            <em>General</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo (int)$quiz['question_count']; ?></span></td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo (int)$quiz['attempt_count']; ?> Attempts</span>
                                        <br><small><?php echo (int)$quiz['perfect_count']; ?> perfect score(s)</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $quiz['status'] === 'published' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($quiz['status']); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($quiz['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="quiz_view.php?quiz_id=<?php echo (int)$quiz['quiz_id']; ?>" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-eye"></i> Preview</a>
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz['quiz_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $quiz['status'] === 'published' ? 'draft' : 'published'; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">
                                                    <i class="fas fa-sync"></i> <?php echo $quiz['status'] === 'published' ? 'Mark Draft' : 'Publish'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this quiz?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz['quiz_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No quizzes created yet.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="questionTemplate">
    <div class="question-card" data-question-index="">
        <div class="question-card-header">
            <h5>Question <span class="question-number"></span></h5>
            <button type="button" class="btn btn-sm btn-danger remove-question" onclick="removeQuestionRow(this)"><i class="fas fa-trash"></i></button>
        </div>
        <div class="form-group">
            <label class="form-label">Question *</label>
            <textarea name="questions[]" class="form-control" rows="2" required></textarea>
        </div>
        <div class="options-grid">
            <div class="form-group">
                <label class="form-label">Option A *</label>
                <input type="text" name="option_a[]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option B *</label>
                <input type="text" name="option_b[]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option C *</label>
                <input type="text" name="option_c[]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option D *</label>
                <input type="text" name="option_d[]" class="form-control" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Correct Option *</label>
            <select name="correct_option[]" class="form-control" required>
                <option value="">Select correct answer</option>
                <option value="A">Option A</option>
                <option value="B">Option B</option>
                <option value="C">Option C</option>
                <option value="D">Option D</option>
            </select>
        </div>
    </div>
</template>

<style>
.quiz-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
}

.quiz-form .form-group {
    display: flex;
    flex-direction: column;
}

.questions-header {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-list {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.question-card {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 15px;
    background: #f9fafb;
}

.question-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.form-actions {
    grid-column: 1 / -1;
}

.table-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .quiz-form {
        grid-template-columns: 1fr;
    }

    .options-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function renumberQuestionCards() {
    const cards = document.querySelectorAll('#questionsContainer .question-card');
    cards.forEach((card, index) => {
        const number = index + 1;
        card.dataset.questionIndex = number;
        const numberSpan = card.querySelector('.question-number');
        if (numberSpan) {
            numberSpan.textContent = number;
        }
        const removeButton = card.querySelector('.remove-question');
        if (removeButton) {
            removeButton.style.display = cards.length > 1 ? 'inline-flex' : 'none';
        }
    });
}

function addQuestionRow() {
    const template = document.getElementById('questionTemplate');
    const container = document.getElementById('questionsContainer');
    const clone = template.content.cloneNode(true);
    container.appendChild(clone);
    renumberQuestionCards();
}

function removeQuestionRow(button) {
    const card = button.closest('.question-card');
    if (!card) return;
    card.remove();
    renumberQuestionCards();
}

document.addEventListener('DOMContentLoaded', function() {
    renumberQuestionCards();
});
</script>

<?php include '../includes/footer.php'; ?>


