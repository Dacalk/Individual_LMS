-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 08:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `learnx_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `class_id`, `attendance_date`, `status`, `marked_by`, `remarks`, `created_at`) VALUES
(1, 1, 3, '2025-12-26', 'present', 11, '', '2025-12-26 15:47:30');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_numeric` int(11) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `class_teacher_id` int(11) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT 40,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `class_numeric`, `section`, `class_teacher_id`, `room_number`, `capacity`, `academic_year`, `status`, `created_at`) VALUES
(1, 'Class 10', 10, 'A', NULL, NULL, 40, '2024-2025', 'active', '2025-12-19 17:31:00'),
(2, 'Class 9', 9, 'A', NULL, NULL, 40, '2024-2025', 'active', '2025-12-19 17:31:00'),
(3, 'Class 8', 8, 'A', NULL, NULL, 40, '2024-2025', 'active', '2025-12-19 17:31:00'),
(4, 'Class 8', 8, 'B', 1, 'Room 627', 40, '2025-2026', 'active', '2025-12-19 18:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `class_subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`class_subject_id`, `class_id`, `subject_id`, `teacher_id`, `academic_year`) VALUES
(1, 3, 5, 1, '2025-2026');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_type` enum('midterm','final','unit_test','quarterly','annual') NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_marks` int(11) NOT NULL,
  `passing_marks` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `exam_name`, `exam_type`, `class_id`, `subject_id`, `exam_date`, `start_time`, `end_time`, `total_marks`, `passing_marks`, `academic_year`, `status`, `created_at`) VALUES
(1, '1st Term', 'unit_test', 3, 5, '2025-12-26', '08:00:00', '09:00:00', 100, 40, '2025', 'scheduled', '2025-12-26 15:38:21'),
(2, '1st Term', 'quarterly', 3, 5, '2025-12-26', '08:00:00', '11:00:00', 100, 40, '2025', 'scheduled', '2025-12-26 15:38:40'),
(3, 'Test 1', 'midterm', 3, 5, '2025-12-28', '08:00:00', '09:00:00', 100, 40, '2025', 'scheduled', '2025-12-26 16:23:04'),
(4, 'Test 1', 'midterm', 3, 5, '2025-12-28', '08:00:00', '09:00:00', 100, 40, '2025-2026', 'scheduled', '2025-12-26 16:26:58'),
(5, '1st Term', 'unit_test', 3, 5, '2025-12-26', '08:00:00', '09:00:00', 100, 40, '2025-2026', 'scheduled', '2025-12-26 16:27:43'),
(6, '1st Term', 'unit_test', 3, 5, '2025-12-26', '08:00:00', '09:00:00', 100, 40, '2025-2026', 'scheduled', '2025-12-26 16:35:05'),
(7, '1st Term', 'unit_test', 3, 5, '2025-12-26', '08:00:00', '09:00:00', 100, 40, '2025-2026', 'scheduled', '2025-12-26 16:37:51'),
(8, '2 nd', 'unit_test', 3, 5, '2025-12-26', '08:00:00', '09:00:00', 100, 40, '2025-2026', 'scheduled', '2025-12-26 16:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `entered_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `student_id`, `exam_id`, `marks_obtained`, `grade`, `remarks`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 50.00, 'C', '', 11, '2025-12-26 15:47:14', '2025-12-26 15:47:14'),
(2, 1, 8, 40.00, 'F', '', 11, '2025-12-26 16:49:50', '2025-12-26 16:49:56'),
(4, 1, 7, 40.00, 'F', '', 11, '2025-12-26 16:50:40', '2025-12-26 16:58:35');

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available_quantity` int(11) DEFAULT 1,
  `shelf_location` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','lost','damaged') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_books`
--

INSERT INTO `library_books` (`book_id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `quantity`, `available_quantity`, `shelf_location`, `description`, `cover_image`, `status`, `created_at`, `updated_at`) VALUES
(1, '2005', 'Sherlock Holmes', 'Arthur Conel Doyle', 'EA', 1995, 'Fiction', 2, 2, 'A-001', 'Unknown', NULL, 'active', '2025-12-26 08:11:02', '2025-12-27 02:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `library_transactions`
--

CREATE TABLE `library_transactions` (
  `transaction_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('issued','returned','overdue','lost') DEFAULT 'issued',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `returned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_transactions`
--

INSERT INTO `library_transactions` (`transaction_id`, `book_id`, `user_id`, `issue_date`, `due_date`, `return_date`, `status`, `fine_amount`, `remarks`, `issued_by`, `returned_to`, `created_at`) VALUES
(1, 1, 11, '2025-12-27', '2026-01-10', '2025-12-27', 'returned', 0.00, NULL, 13, NULL, '2025-12-27 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_quizzes`
--

CREATE TABLE `mcq_quizzes` (
  `quiz_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `creator_role` enum('admin','teacher') NOT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mcq_quizzes`
--

INSERT INTO `mcq_quizzes` (`quiz_id`, `title`, `class_id`, `created_by`, `creator_role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Test', NULL, 3, 'admin', 'published', '2025-12-20 06:28:05', '2025-12-20 06:28:05'),
(2, 'Test', 3, 11, 'teacher', 'published', '2025-12-26 17:05:22', '2025-12-26 17:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_quiz_attempts`
--

CREATE TABLE `mcq_quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `selected_option` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mcq_quiz_attempts`
--

INSERT INTO `mcq_quiz_attempts` (`attempt_id`, `quiz_id`, `student_id`, `selected_option`, `is_correct`, `attempted_at`) VALUES
(1, 1, 1, 'C', 0, '2025-12-26 15:49:23'),
(2, 2, 1, 'A', 1, '2025-12-26 17:05:53');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_quiz_questions`
--

CREATE TABLE `mcq_quiz_questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mcq_quiz_questions`
--

INSERT INTO `mcq_quiz_questions` (`question_id`, `quiz_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'What is Age?', '1', '2', '3', '4', 'B', 1, '2025-12-20 06:28:05', '2025-12-20 06:28:05'),
(2, 2, 'What if', 'D', 'A', 'C', 'A', 'A', 1, '2025-12-26 17:05:22', '2025-12-26 17:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_quiz_responses`
--

CREATE TABLE `mcq_quiz_responses` (
  `response_id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mcq_quiz_responses`
--

INSERT INTO `mcq_quiz_responses` (`response_id`, `attempt_id`, `question_id`, `selected_option`, `is_correct`) VALUES
(2, 1, 1, 'C', 0),
(3, 2, 2, 'A', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `parent_message_id`, `created_at`, `read_at`) VALUES
(1, 4, 3, 'Test', 'Test', 1, NULL, '2025-12-19 18:13:42', '2025-12-22 02:49:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('general','academic','exam','fee','event','urgent') NOT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `target_class_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `read_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `admission_date` date NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `roll_number` varchar(20) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `admission_number`, `admission_date`, `class_id`, `section`, `roll_number`, `parent_id`, `blood_group`, `emergency_contact`, `medical_conditions`) VALUES
(1, 4, 'STU00004', '2025-12-18', 3, NULL, '001', 12, 'A+', '0766646354', NULL),
(3, 8, 'STU00008', '2025-10-20', 4, NULL, '002', NULL, 'A-', '0789833468', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `description`, `credit_hours`, `status`, `created_at`) VALUES
(1, 'Mathematics', 'MATH101', 'Basic and Advanced Mathematics', 5, 'active', '2025-12-19 17:31:00'),
(2, 'Science', 'SCI101', 'General Science', 5, 'active', '2025-12-19 17:31:00'),
(3, 'English', 'ENG101', 'English Language and Literature', 4, 'active', '2025-12-19 17:31:00'),
(4, 'Social Studies', 'SS101', 'History, Geography, Civics', 4, 'active', '2025-12-19 17:31:00'),
(5, 'Computer Science', 'CS101', 'Introduction to Computing', 3, 'active', '2025-12-19 17:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `joining_date` date NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `salary` decimal(10,2) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `employee_id`, `joining_date`, `qualification`, `specialization`, `experience_years`, `salary`, `department`) VALUES
(1, 11, 'EMP00011', '0000-00-00', 'Bsc.(Hons) Computer Science', '0', 10, NULL, 'Mathematics');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_id` int(11) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `term` varchar(10) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_periods`
--

CREATE TABLE `time_periods` (
  `period_id` int(11) NOT NULL,
  `period_number` int(11) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_break` tinyint(1) DEFAULT 0,
  `break_duration` int(11) DEFAULT 0 COMMENT 'Duration in minutes for breaks',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_periods`
--

INSERT INTO `time_periods` (`period_id`, `period_number`, `period_name`, `start_time`, `end_time`, `is_break`, `break_duration`, `status`, `created_at`) VALUES
(1, 1, 'Period 1', '08:30:00', '09:15:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(2, 2, 'Period 2', '09:15:00', '10:00:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(3, 3, 'Period 3', '10:00:00', '10:45:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(4, 4, 'Break', '10:45:00', '11:00:00', 1, 15, 'active', '2025-12-19 18:02:53'),
(5, 5, 'Period 4', '11:00:00', '11:45:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(6, 6, 'Period 5', '11:45:00', '12:30:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(7, 7, 'Lunch Break', '12:30:00', '13:00:00', 1, 30, 'active', '2025-12-19 18:02:53'),
(8, 8, 'Period 6', '13:00:00', '13:45:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(9, 9, 'Period 7', '13:45:00', '14:30:00', 0, 0, 'active', '2025-12-19 18:02:53'),
(10, 10, 'Period 8', '14:30:00', '15:15:00', 0, 0, 'active', '2025-12-19 18:02:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent','librarian') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `first_name`, `last_name`, `phone`, `address`, `date_of_birth`, `gender`, `profile_picture`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(3, 'admin', 'admin@learnx.com', '$2y$10$BFk3N7TlyqAADHGkzROUcOrtib5ZU0CrrMo6ilN/og5TfArTG38Pm', 'admin', 'Admin', 'User', '1234567890', NULL, NULL, 'other', NULL, 'active', '2025-12-19 17:50:55', '2025-12-27 02:31:48', '2025-12-27 02:31:48'),
(4, 'charitha', 'charithadissanayaka5290@gmail.com', '$2y$10$Q1dlsUm0KD1pG2w2KSKG4eVZeoDoQtCKKZnkFe0Sqy6akJppxX692', 'student', 'Charitha', 'Dissanayaka', '0773028797', '', '0000-00-00', '', NULL, 'active', '2025-12-19 18:11:54', '2025-12-27 12:55:00', '2025-12-27 12:55:00'),
(8, 'John', 'John@example.com', '$2y$10$2xP1CTZ94X0Oh2llaeFQce6hD1aBOODEnQsJFtR4tFD1gBdPup91m', 'student', 'John', 'Wick', '0756545254', '', '0000-00-00', '', NULL, 'active', '2025-12-19 18:21:20', '2025-12-20 06:45:55', '2025-12-19 18:21:55'),
(11, 'Damayanthi', 'Damayanthi@teacher.com', '$2y$10$vs8FmqDf0oOqvA.5xF/Z7e.reYvR9JEW1ijzI2eKHl4yf2uyRqupi', 'teacher', 'Damayanthi', 'Baskanayaka', '0745625458', '', '0000-00-00', '', NULL, 'active', '2025-12-20 06:35:52', '2025-12-27 02:14:11', '2025-12-27 02:14:11'),
(12, 'Sadmini', 'Sadamini@112.com', '$2y$10$kgfSi.58pbshXUfyUD.afuaOUtELa9xHUPHElAfyEHAuz4aVoerea', 'parent', 'Sadamini', 'Kumari', '0789833468', '', '0000-00-00', '', NULL, 'active', '2025-12-20 06:38:33', '2025-12-27 13:05:28', '2025-12-27 13:05:28'),
(13, 'Kamal', 'Kamal@test.com', '$2y$10$AmBmkJ2y2uLuZJVCX6Cgi.x3XEto61wDLqpNTPHpvO3JZi80z1Pu2', 'librarian', 'Kamal', 'Herath', '0773546254', '', '0000-00-00', '', NULL, 'active', '2025-12-20 06:55:18', '2025-12-27 12:57:49', '2025-12-27 12:57:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_date` (`attendance_date`),
  ADD KEY `idx_student_date` (`student_id`,`attendance_date`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `class_teacher_id` (`class_teacher_id`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`class_subject_id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_date` (`exam_date`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `unique_student_exam` (`student_id`,`exam_id`),
  ADD KEY `entered_by` (`entered_by`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_exam` (`exam_id`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `idx_isbn` (`isbn`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `library_transactions`
--
ALTER TABLE `library_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `returned_to` (`returned_to`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`issue_date`,`due_date`);

--
-- Indexes for table `mcq_quizzes`
--
ALTER TABLE `mcq_quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `mcq_quiz_attempts`
--
ALTER TABLE `mcq_quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD UNIQUE KEY `unique_quiz_student` (`quiz_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `mcq_quiz_questions`
--
ALTER TABLE `mcq_quiz_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_quiz` (`quiz_id`);

--
-- Indexes for table `mcq_quiz_responses`
--
ALTER TABLE `mcq_quiz_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD UNIQUE KEY `unique_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `parent_message_id` (`parent_message_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `target_class_id` (`target_class_id`),
  ADD KEY `idx_type` (`notification_type`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`read_id`),
  ADD UNIQUE KEY `unique_notification_user` (`notification_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_class` (`class_id`),
  ADD KEY `idx_admission` (`admission_number`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_code` (`subject_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_employee` (`employee_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_class_day` (`class_id`,`day_of_week`),
  ADD KEY `idx_teacher_day` (`teacher_id`,`day_of_week`),
  ADD KEY `idx_period` (`period_id`);

--
-- Indexes for table `time_periods`
--
ALTER TABLE `time_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `class_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `library_transactions`
--
ALTER TABLE `library_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mcq_quizzes`
--
ALTER TABLE `mcq_quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mcq_quiz_attempts`
--
ALTER TABLE `mcq_quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mcq_quiz_questions`
--
ALTER TABLE `mcq_quiz_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mcq_quiz_responses`
--
ALTER TABLE `mcq_quiz_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `read_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_periods`
--
ALTER TABLE `time_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`entered_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `library_transactions`
--
ALTER TABLE `library_transactions`
  ADD CONSTRAINT `library_transactions_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_transactions_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `library_transactions_ibfk_4` FOREIGN KEY (`returned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `mcq_quizzes`
--
ALTER TABLE `mcq_quizzes`
  ADD CONSTRAINT `mcq_quizzes_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mcq_quizzes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `mcq_quiz_attempts`
--
ALTER TABLE `mcq_quiz_attempts`
  ADD CONSTRAINT `mcq_quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `mcq_quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mcq_quiz_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `mcq_quiz_questions`
--
ALTER TABLE `mcq_quiz_questions`
  ADD CONSTRAINT `mcq_quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `mcq_quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `mcq_quiz_responses`
--
ALTER TABLE `mcq_quiz_responses`
  ADD CONSTRAINT `mcq_quiz_responses_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `mcq_quiz_attempts` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mcq_quiz_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `mcq_quiz_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`target_class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `notification_reads_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`period_id`) REFERENCES `time_periods` (`period_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
