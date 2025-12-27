# LearnX - School Management System

A comprehensive web-based School Management System built with PHP, MySQL, HTML, CSS, and JavaScript. LearnX streamlines administrative and academic processes for educational institutions, designed specifically for Grades 6-13 with support for Sri Lankan educational institutions.

## 📋 Table of Contents

- [Features](#features)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [User Roles](#user-roles)
- [Technologies Used](#technologies-used)
- [File Structure](#file-structure)

## 🎯 Features

### Core Functionalities

- **User Management**: Complete user management system for Admin, Teachers, Students, Parents, and Librarians
- **Student Management**: Admission, profile management, and academic tracking
- **Teacher Management**: Teacher assignment, class management, and performance tracking
- **Attendance System**: Daily attendance marking with reports and statistics
- **Grading System**: Exam creation, mark entry, automatic grade calculation, and report cards
- **Library Management**: Book catalog, issue/return tracking, overdue management, and fine calculation
- **Timetable Management**: Weekly schedule creation, teacher assignment, and room allocation
- **MCQ Quiz System**: Create quizzes, take quizzes, and view results with back navigation prevention
- **Messaging System**: Communication between users (Admin, Teachers, Students, Parents)
- **Notification System**: School-wide and role-based announcements
- **Responsive Design**: Mobile-friendly interface with modern UI

## 📁 Project Structure

```
LMS/
├── admin/              # Admin dashboard and management pages
├── teacher/            # Teacher dashboard and functionality
├── student/            # Student dashboard and pages
├── parent/             # Parent dashboard
├── librarian/          # Librarian dashboard and library management
├── config/             # Configuration files
│   ├── config.php     # Site configuration
│   ├── database.php    # Database connection
│   └── email.php       # Email configuration
├── includes/           # Reusable components
│   ├── header.php      # Page header
│   ├── footer.php     # Page footer
│   ├── topbar.php     # Top navigation bar
│   ├── sidebar-*.php  # Role-based sidebars
│   └── quiz_helpers.php # Quiz helper functions
├── assets/             # Static resources
│   ├── css/
│   │   └── style.css  # Main stylesheet
│   └── js/
│       └── script.js  # JavaScript functions
├── database/           # Database files
│   └── schema.sql     # Complete database schema
├── ajax/               # AJAX handlers
├── create_admin.php    # Script to create admin user
├── home.php            # Landing page
├── login.php           # Login page
├── index.php           # Main entry point
└── README.md           # This file
```

## 🔧 Requirements

- **Server**: Apache (XAMPP, WAMP, or similar)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher (or MariaDB 10.2+)
- **Web Browser**: Modern browser with JavaScript enabled
- **Extensions**: 
  - mysqli
  - mbstring
  - session

## 📦 Installation

### Step 1: Clone or Download

Download the project and place it in your web server's document root:
- **XAMPP**: `C:\xampp\htdocs\LMS\`
- **WAMP**: `C:\wamp64\www\LMS\`
- **Linux**: `/var/www/html/LMS/`

### Step 2: Database Configuration

1. Open `config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'learnx_db');
```

2. Create the database in phpMyAdmin or MySQL:

```sql
CREATE DATABASE learnx_db;
```

### Step 3: Site Configuration

1. Open `config/config.php` and update the site URL:

```php
define('SITE_URL', 'http://localhost/LMS/');
```

Update this to match your server path (e.g., `http://your-domain.com/LMS/` for production).

### Step 4: Import Database Schema

1. Open phpMyAdmin
2. Select the `learnx_db` database
3. Go to the "Import" tab
4. Choose `database/schema.sql` and click "Go"


### Step 5: Access the Application

1. Start your web server (Apache) and MySQL
2. Open your browser and navigate to: `http://localhost/LMS/`
3. You'll be redirected to the login page
4. Use your admin credentials to log in

## ⚙️ Configuration

### Database Settings

Edit `config/database.php`:
- `DB_HOST`: Database host (usually 'localhost')
- `DB_USER`: Database username
- `DB_PASS`: Database password
- `DB_NAME`: Database name (default: 'learnx_db')

### Site Settings

Edit `config/config.php`:
- `SITE_NAME`: Name of your school/application
- `SITE_URL`: Base URL of your application
- Timezone: Currently set to 'Asia/Kolkata' (update as needed)

### Email Configuration (Optional)

Edit `config/email.php` if you want to enable email notifications for password resets and other features.

## 💾 Database Setup

The database schema includes the following main tables:

- **users**: Unified user table for all roles
- **students**: Student-specific information
- **teachers**: Teacher-specific information
- **classes**: Class and section information
- **subjects**: Subject catalog
- **class_subjects**: Subject-to-class mapping
- **timetable**: Weekly schedule
- **time_periods**: Period definitions (8:30 AM - 2:00 PM schedule)
- **attendance**: Daily attendance records
- **exams**: Exam information
- **grades**: Student grades and marks
- **library_books**: Book catalog
- **library_transactions**: Book issue/return records
- **messages**: Internal messaging system
- **notifications**: System notifications
- **mcq_quizzes**: MCQ quiz definitions
- **mcq_quiz_questions**: Quiz questions
- **mcq_quiz_attempts**: Student quiz attempts
- **mcq_quiz_responses**: Individual question responses

## 🚀 Usage

### Admin Dashboard

1. **User Management**: Create and manage users (Students, Teachers, Parents, Librarians)
2. **Class Management**: Create classes, assign class teachers
3. **Subject Management**: Create subjects, assign to classes and teachers
4. **Timetable**: Create weekly timetables (8:30 AM - 2:00 PM, 8 periods + lunch break)
5. **Quizzes**: Create MCQ quizzes for students
6. **Notifications**: Send school-wide announcements

### Teacher Dashboard

1. **My Classes**: View assigned classes (as class teacher or subject teacher)
2. **Attendance**: Mark daily attendance for assigned classes
3. **Grades**: Enter marks for exams
4. **Timetable**: View personal timetable
5. **Quizzes**: Create and manage MCQ quizzes
6. **Messages**: Communicate with students and parents
7. **Library**: Browse library books

### Student Dashboard

1. **Profile**: View and edit personal information
2. **Attendance**: View attendance records and statistics
3. **Grades**: View exam results and report cards
4. **Timetable**: View class timetable
5. **Library**: Browse books, view issued books, and history
6. **Quizzes**: Take MCQ quizzes (opens in new tab, back navigation disabled)
7. **Messages**: Communicate with teachers
8. **Notifications**: View school announcements

### Parent Dashboard

1. **My Children**: View children's information
2. **Attendance**: Monitor children's attendance
3. **Academic Results**: View grades and exam results
4. **Timetable**: View children's timetables
5. **Messages**: Communicate with teachers

### Librarian Dashboard

1. **Books**: Manage book catalog (add, edit, delete books)
2. **Issue Books**: Issue books to students and teachers
3. **Return Books**: Process book returns
4. **Overdue**: View and manage overdue books
5. **Transactions**: View all library transactions
6. **Messages**: Communicate with users

## 👥 User Roles

### Admin
- Full system access
- User management
- System configuration
- Reports and analytics

### Teacher
- Class management
- Attendance marking
- Grade entry
- Quiz creation
- Communication with students/parents

### Student
- View academic information
- Take quizzes
- Browse library
- Communication

### Parent
- Monitor child's progress
- View attendance and grades
- Communication with teachers

### Librarian
- Library management
- Book issue/return
- Transaction management

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **UI Framework**: Custom CSS with modern design patterns
- **Icons**: Font Awesome 6.4.0
- **Security**: Prepared statements, password hashing, input sanitization

## 📂 File Structure Details

### Admin Pages (`admin/`)
- `dashboard.php` - Admin dashboard with statistics
- `users.php` - User management
- `students.php` - Student management
- `teachers.php` - Teacher management
- `classes.php` - Class management
- `subjects.php` - Subject management
- `timetable.php` - Timetable creation and management
- `quizzes.php` - Quiz management
- `messages.php` - Messaging system

### Teacher Pages (`teacher/`)
- `dashboard.php` - Teacher dashboard
- `classes.php` - View assigned classes
- `attendance.php` - Mark attendance
- `grades.php` - Enter grades
- `timetable.php` - View timetable
- `quizzes.php` - Create quizzes
- `messages.php` - Messaging

### Student Pages (`student/`)
- `dashboard.php` - Student dashboard
- `profile.php` - Profile management
- `attendance.php` - View attendance
- `grades.php` - View grades
- `timetable.php` - View timetable
- `library.php` - Library browsing
- `quizzes.php` - View and take quizzes
- `quiz_view.php` - Quiz taking page (opens in new tab)
- `messages.php` - Messaging

### Configuration Files (`config/`)
- `config.php` - Site configuration and helper functions
- `database.php` - Database connection
- `email.php` - Email configuration

### Database Files (`database/`)
- `schema.sql` - Complete database schema (includes all tables, views, and initial data)

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Input sanitization
- Session management
- Role-based access control
- CSRF protection (recommended for production)

## 📝 Notes

- Default timetable schedule: 8:30 AM - 2:00 PM with 8 periods and 30-minute lunch break
- Quiz pages open in new tabs and prevent back navigation during quiz attempts
- Library books can be browsed by students and teachers
- All user roles can access their profile and messaging (except librarian profile link)

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `config/database.php` credentials
   - Ensure MySQL service is running
   - Verify database exists

2. **Page Not Found**
   - Check `.htaccess` if using Apache
   - Verify `SITE_URL` in `config/config.php`
   - Check file permissions

3. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check file permissions on session directory

4. **CSS/JavaScript Not Loading**
   - Check `SITE_URL` configuration
   - Verify file paths in `includes/header.php`

## 📞 Support

For issues, questions, or contributions, please refer to the project repository or contact the development team.

## 📄 License

This project is proprietary software. All rights reserved.

---

**LearnX School Management System** - Empowering Educational Institutions
+
