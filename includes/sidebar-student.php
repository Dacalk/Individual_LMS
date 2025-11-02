<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> LearnX</h2>
        <p>Student Panel</p>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>student/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>student/timetable.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i>
                <span>My Timetable</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>student/attendance.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                <span>Attendance</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>student/grades.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Grades/Results</span>
            </a>
        </li>
 
        <li>
            <a href="<?php echo SITE_URL; ?>student/quizzes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'quizzes.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>MCQ Quizzes</span>
            </a>
        </li>

        <li>
            <a href="<?php echo SITE_URL; ?>student/library.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-reader"></i>
                <span>Library</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>student/messages.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
        </li>
    </ul>
</div>




