<?php
if (!function_exists('ensureQuizTables')) {
    function ensureQuizTables(mysqli $conn): void
    {
        $createQuizzes = "CREATE TABLE IF NOT EXISTS mcq_quizzes (
            quiz_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            class_id INT NULL,
            created_by INT NOT NULL,
            creator_role ENUM('admin','teacher') NOT NULL,
            status ENUM('draft','published') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_class (class_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $createQuestions = "CREATE TABLE IF NOT EXISTS mcq_quiz_questions (
            question_id INT PRIMARY KEY AUTO_INCREMENT,
            quiz_id INT NOT NULL,
            question TEXT NOT NULL,
            option_a VARCHAR(255) NOT NULL,
            option_b VARCHAR(255) NOT NULL,
            option_c VARCHAR(255) NOT NULL,
            option_d VARCHAR(255) NOT NULL,
            correct_option ENUM('A','B','C','D') NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES mcq_quizzes(quiz_id) ON DELETE CASCADE,
            INDEX idx_quiz (quiz_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $createAttempts = "CREATE TABLE IF NOT EXISTS mcq_quiz_attempts (
            attempt_id INT PRIMARY KEY AUTO_INCREMENT,
            quiz_id INT NOT NULL,
            student_id INT NOT NULL,
            selected_option ENUM('A','B','C','D') NOT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES mcq_quizzes(quiz_id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            UNIQUE KEY unique_quiz_student (quiz_id, student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $createResponses = "CREATE TABLE IF NOT EXISTS mcq_quiz_responses (
            response_id INT PRIMARY KEY AUTO_INCREMENT,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_option ENUM('A','B','C','D') NOT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            FOREIGN KEY (attempt_id) REFERENCES mcq_quiz_attempts(attempt_id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES mcq_quiz_questions(question_id) ON DELETE CASCADE,
            UNIQUE KEY unique_attempt_question (attempt_id, question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $conn->query($createQuizzes);
        $conn->query($createQuestions);
        $conn->query($createAttempts);
        $conn->query($createResponses);

        // Drop old single-question columns if they still exist
        $legacyColumns = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'];
        foreach ($legacyColumns as $column) {
            $check = $conn->query("SHOW COLUMNS FROM mcq_quizzes LIKE '" . $conn->real_escape_string($column) . "'");
            if ($check && $check->num_rows > 0) {
                $conn->query("ALTER TABLE mcq_quizzes DROP COLUMN `{$column}`");
            }
        }
    }
}

