<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> LearnX</h2>
        <p>Parent Panel</p>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>parent/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>parent/children.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'children.php' ? 'active' : ''; ?>">
                <i class="fas fa-child"></i>
                <span>My Children</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>parent/attendance.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                <span>Attendance</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>parent/grades.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Academic Results</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>parent/timetable.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Timetable</span>
            </a>
        </li>
        
        
        
        <li>
            <a href="<?php echo SITE_URL; ?>parent/notifications.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
    </ul>
</div>




