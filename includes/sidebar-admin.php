<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> LearnX</h2>
        <p>Admin Panel</p>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>admin/users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>admin/classes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : ''; ?>">
                <i class="fas fa-school"></i>
                <span>Classes</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>admin/subjects.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Subjects</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>admin/timetable.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i>
                <span>Timetable</span>
            </a>
        </li>
 
        <li>
            <a href="<?php echo SITE_URL; ?>admin/quizzes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'quizzes.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>MCQ Quizzes</span>
            </a>
        </li>

        <li>
            <a href="<?php echo SITE_URL; ?>librarian/books.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-reader"></i>
                <span>Library</span>
            </a>
        </li>
    </ul>
</div>


