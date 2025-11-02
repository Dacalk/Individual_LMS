<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> LearnX</h2>
        <p>Teacher Panel</p>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>teacher/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>teacher/classes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard"></i>
                <span>My Classes</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>teacher/timetable.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i>
                <span>My Timetable</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>teacher/attendance.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                <span>Attendance</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>teacher/grades.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Grades/Marks</span>
            </a>
        </li>

        <li>
            <a href="<?php echo SITE_URL; ?>teacher/library.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-reader"></i>
                <span>Library</span>
            </a>
        </li>

        <li>
            <a href="<?php echo SITE_URL; ?>teacher/quizzes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'quizzes.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>MCQ Quizzes</span>
            </a>
        </li>
    </ul>
</div>




