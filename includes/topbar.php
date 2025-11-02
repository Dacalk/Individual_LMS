<?php
// Get unread notifications count and recent notifications
$notification_count = 0;
$recent_notifications = [];

$notif_query = "SELECT n.*, nr.read_at 
                FROM notifications n 
                LEFT JOIN notification_reads nr ON n.notification_id = nr.notification_id AND nr.user_id = ?
                WHERE n.is_active = 1 
                AND (n.target_role IS NULL OR n.target_role = ? OR n.target_role = 'all')
                AND (n.expires_at IS NULL OR n.expires_at > NOW())
                ORDER BY n.created_at DESC
                LIMIT 10";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['role']);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

while ($notif = $notif_result->fetch_assoc()) {
    $recent_notifications[] = $notif;
    if (!$notif['read_at']) {
        $notification_count++;
    }
}

// Get unread messages count
$message_count = 0;
$msg_query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$msg_stmt = $conn->prepare($msg_query);
$msg_stmt->bind_param("i", $_SESSION['user_id']);
$msg_stmt->execute();
$msg_result = $msg_stmt->get_result();
if ($msg_row = $msg_result->fetch_assoc()) {
    $message_count = $msg_row['count'];
}

?>
<div class="header">
    <div class="header-left">
        <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
    </div>
    
    <div class="header-right">
        <!-- Notifications Icon with Popup -->
        <div class="notification-icon" onclick="toggleNotificationPopup()" style="cursor: pointer; position: relative;" id="notificationIcon">
            <i class="fas fa-bell"></i>
            <?php if ($notification_count > 0): ?>
                <span class="notification-badge"><?php echo $notification_count > 9 ? '9+' : $notification_count; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Messages Icon -->
        <div class="notification-icon" onclick="window.location.href='<?php echo SITE_URL . $_SESSION['role']; ?>/messages.php'" style="cursor: pointer; position: relative;">
            <i class="fas fa-envelope"></i>
            <?php if ($message_count > 0): ?>
                <span class="notification-badge"><?php echo $message_count > 9 ? '9+' : $message_count; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- User Profile with Dropdown -->
        <div class="user-profile" onclick="toggleUserDropdown()" style="cursor: pointer; position: relative;" id="userProfile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
            </div>
            <i class="fas fa-chevron-down" style="margin-left: 8px; font-size: 12px;"></i>
        </div>
    </div>
</div>

<!-- Notification Popup -->
<div id="notificationPopup" class="dropdown-popup notification-popup" style="display: none;">
    <div class="popup-header">
        <h3><i class="fas fa-bell"></i> Notifications</h3>
        <span class="badge badge-primary"><?php echo $notification_count; ?> unread</span>
    </div>
    <div class="popup-body">
        <?php if (count($recent_notifications) > 0): ?>
            <?php foreach ($recent_notifications as $notif): ?>
                <div class="notification-item <?php echo !$notif['read_at'] ? 'unread' : ''; ?>" onclick="markNotificationRead(<?php echo $notif['notification_id']; ?>)">
                    <div class="notification-icon-small <?php echo $notif['notification_type']; ?>">
                        <i class="fas fa-<?php 
                            echo $notif['notification_type'] == 'urgent' ? 'exclamation-circle' : 
                                 ($notif['notification_type'] == 'exam' ? 'clipboard-list' : 
                                 ($notif['notification_type'] == 'academic' ? 'book' : 'info-circle')); 
                        ?>"></i>
                    </div>
                    <div class="notification-content">
                        <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                        <p><?php echo htmlspecialchars(substr($notif['message'], 0, 100)); ?><?php echo strlen($notif['message']) > 100 ? '...' : ''; ?></p>
                        <span class="notification-time"><?php echo formatDateTime($notif['created_at']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-light);">
                <i class="fas fa-bell-slash" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                <p>No notifications</p>
            </div>
        <?php endif; ?>
    </div>
    <?php if (count($recent_notifications) > 0): ?>
    <div class="popup-footer">
        <a href="<?php echo SITE_URL . $_SESSION['role']; ?>/notifications.php" class="view-all-link">View All Notifications</a>
    </div>
    <?php endif; ?>
</div>

<!-- User Dropdown -->
<div id="userDropdown" class="dropdown-popup user-dropdown" style="display: none;">
    <div class="user-dropdown-header">
        <div class="user-avatar-large">
            <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
        </div>
        <div>
            <h3><?php echo $_SESSION['full_name']; ?></h3>
            <p><?php echo ucfirst($_SESSION['role']); ?></p>
            <small><?php echo $_SESSION['username']; ?></small>
        </div>
    </div>
    <div class="user-dropdown-menu">
        <?php if (!hasRole('librarian')): ?>
        <a href="<?php echo SITE_URL . $_SESSION['role']; ?>/profile.php" class="dropdown-menu-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        <hr style="margin: 10px 0; border: none; border-top: 1px solid var(--border-color);">
        <?php endif; ?>
        <a href="<?php echo SITE_URL; ?>logout.php" class="dropdown-menu-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
.dropdown-popup {
    position: fixed;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    max-width: 400px;
    width: 90vw;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.notification-popup {
    top: 70px;
    right: 200px;
}

.user-dropdown {
    top: 70px;
    right: 30px;
    max-width: 300px;
}

.popup-header {
    padding: 20px;
    border-bottom: 2px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
}

.popup-header h3 {
    margin: 0;
    font-size: 18px;
    color: white;
}

.popup-body {
    overflow-y: auto;
    max-height: 500px;
    flex: 1;
}

.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    gap: 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: var(--light-gray);
}

.notification-item.unread {
    background: rgba(79, 70, 229, 0.05);
    border-left: 3px solid var(--primary-color);
}

.notification-icon-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.notification-icon-small.urgent {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

.notification-icon-small.exam {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.notification-icon-small.academic {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info-color);
}

.notification-icon-small.general {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.notification-content {
    flex: 1;
}

.notification-content h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
}

.notification-content p {
    margin: 0 0 5px 0;
    font-size: 13px;
    color: var(--text-light);
    line-height: 1.4;
}

.notification-time {
    font-size: 11px;
    color: var(--medium-gray);
}

.popup-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    text-align: center;
    background: var(--light-gray);
}

.view-all-link {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 14px;
}

.user-dropdown-header {
    padding: 25px 20px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
    display: flex;
    gap: 15px;
    align-items: center;
}

.user-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
}

.user-dropdown-header h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    color: white;
}

.user-dropdown-header p {
    margin: 0 0 3px 0;
    font-size: 13px;
    opacity: 0.9;
}

.user-dropdown-header small {
    font-size: 12px;
    opacity: 0.7;
}

.user-dropdown-menu {
    padding: 10px;
}

.dropdown-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    border-radius: 8px;
    color: var(--text-dark);
    transition: all 0.2s;
    font-size: 14px;
}

.dropdown-menu-item:hover {
    background: var(--light-gray);
}

.dropdown-menu-item.logout {
    color: var(--danger-color);
}

.dropdown-menu-item.logout:hover {
    background: rgba(239, 68, 68, 0.1);
}

.dropdown-menu-item i {
    width: 20px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .notification-popup {
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .user-dropdown {
        right: 10px;
    }
}
</style>

<script>
function toggleNotificationPopup() {
    const popup = document.getElementById('notificationPopup');
    const userDropdown = document.getElementById('userDropdown');
    
    // Close user dropdown if open
    if (userDropdown.style.display === 'flex') {
        userDropdown.style.display = 'none';
    }
    
    // Toggle notification popup
    if (popup.style.display === 'none' || popup.style.display === '') {
        popup.style.display = 'flex';
    } else {
        popup.style.display = 'none';
    }
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const notifPopup = document.getElementById('notificationPopup');
    
    // Close notification popup if open
    if (notifPopup.style.display === 'flex') {
        notifPopup.style.display = 'none';
    }
    
    // Toggle user dropdown
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'flex';
    } else {
        dropdown.style.display = 'none';
    }
}

function markNotificationRead(notificationId) {
    // Mark as read via AJAX
    fetch('<?php echo SITE_URL; ?>ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const notifIcon = document.getElementById('notificationIcon');
    const notifPopup = document.getElementById('notificationPopup');
    const userProfile = document.getElementById('userProfile');
    const userDropdown = document.getElementById('userDropdown');
    
    // Close notification popup
    if (!notifIcon.contains(event.target) && !notifPopup.contains(event.target)) {
        notifPopup.style.display = 'none';
    }
    
    // Close user dropdown
    if (!userProfile.contains(event.target) && !userDropdown.contains(event.target)) {
        userDropdown.style.display = 'none';
    }
});
</script>



