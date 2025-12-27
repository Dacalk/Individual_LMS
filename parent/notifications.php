<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'Notifications';

// Get parent's children and their class IDs
$children_query = "SELECT DISTINCT s.class_id FROM students s WHERE s.parent_id = " . $_SESSION['user_id'];
$children_result = $conn->query($children_query);
$class_ids = [];
while ($row = $children_result->fetch_assoc()) {
    if ($row['class_id']) {
        $class_ids[] = $row['class_id'];
    }
}

// Build query for notifications relevant to parent
$notifications_query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name, c.class_name
                        FROM notifications n
                        JOIN users u ON n.created_by = u.user_id
                        LEFT JOIN classes c ON n.target_class_id = c.class_id
                        WHERE n.is_active = 1
                        AND (n.target_role = 'parent' OR n.target_role = 'general' OR n.target_role IS NULL";
if (!empty($class_ids)) {
    $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $notifications_query .= " OR n.target_class_id IN ($placeholders)";
}
$notifications_query .= ")
                        AND (n.expires_at IS NULL OR n.expires_at > NOW())
                        ORDER BY n.created_at DESC";

if (!empty($class_ids)) {
    $stmt = $conn->prepare($notifications_query);
    $types = str_repeat('i', count($class_ids));
    $stmt->bind_param($types, ...$class_ids);
    $stmt->execute();
    $notifications = $stmt->get_result();
} else {
    $notifications = $conn->query($notifications_query);
}

// Mark notifications as read when viewed
if (isset($_GET['read']) && $_GET['read']) {
    $notification_id = (int)$_GET['read'];
    $read_query = $conn->prepare("INSERT INTO notification_reads (notification_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE read_at = NOW()");
    $read_query->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $read_query->execute();
}

// Get unread count
$unread_query = "SELECT COUNT(*) as unread_count
                 FROM notifications n
                 WHERE n.is_active = 1
                 AND (n.target_role = 'parent' OR n.target_role = 'general' OR n.target_role IS NULL";
if (!empty($class_ids)) {
    $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $unread_query .= " OR n.target_class_id IN ($placeholders)";
}
$unread_query .= ")
                 AND (n.expires_at IS NULL OR n.expires_at > NOW())
                 AND n.notification_id NOT IN (SELECT notification_id FROM notification_reads WHERE user_id = ?)";

if (!empty($class_ids)) {
    $unread_stmt = $conn->prepare($unread_query);
    $types = str_repeat('i', count($class_ids) + 1);
    $params = array_merge($class_ids, [$_SESSION['user_id']]);
    $unread_stmt->bind_param($types, ...$params);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result()->fetch_assoc();
} else {
    $unread_stmt = $conn->prepare($unread_query);
    $unread_stmt->bind_param("i", $_SESSION['user_id']);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result()->fetch_assoc();
}
$unread_count = $unread_result['unread_count'] ?? 0;

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-parent.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge badge-danger" style="font-size: 14px; padding: 6px 12px;">
                            <?php echo $unread_count; ?> Unread
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php while ($notif = $notifications->fetch_assoc()): 
                            // Check if read
                            $read_check = $conn->prepare("SELECT read_id FROM notification_reads WHERE notification_id = ? AND user_id = ?");
                            $read_check->bind_param("ii", $notif['notification_id'], $_SESSION['user_id']);
                            $read_check->execute();
                            $is_read = $read_check->get_result()->num_rows > 0;
                            
                            $type_colors = [
                                'urgent' => 'danger',
                                'exam' => 'warning',
                                'academic' => 'info',
                                'general' => 'primary',
                                'event' => 'success'
                            ];
                            $badge_color = $type_colors[$notif['notification_type']] ?? 'primary';
                        ?>
                        <div class="card" style="margin-bottom: 15px; border-left: 4px solid var(--<?php echo $badge_color; ?>-color); <?php echo !$is_read ? 'background: rgba(245, 158, 11, 0.05);' : ''; ?>">
                            <div class="card-body" style="padding: 15px;">
                                <div class="d-flex justify-between align-center" style="margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: var(--text-dark);">
                                        <?php if (!$is_read): ?>
                                            <span class="badge badge-danger" style="margin-right: 8px;">New</span>
                                        <?php endif; ?>
                                        <?php echo $notif['title']; ?>
                                    </h4>
                                    <span class="badge badge-<?php echo $badge_color; ?>">
                                        <?php echo ucfirst($notif['notification_type']); ?>
                                    </span>
                                </div>
                                <p style="margin: 10px 0; color: var(--text-light); line-height: 1.6;"><?php echo nl2br($notif['message']); ?></p>
                                <div style="display: flex; gap: 15px; font-size: 12px; color: var(--text-light); margin-top: 10px; flex-wrap: wrap;">
                                    <span><i class="fas fa-user"></i> <?php echo $notif['created_by_name']; ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo formatDateTime($notif['created_at']); ?></span>
                                    <?php if ($notif['class_name']): ?>
                                        <span><i class="fas fa-school"></i> <?php echo $notif['class_name']; ?></span>
                                    <?php endif; ?>
                                    <?php if (!$is_read): ?>
                                        <a href="?read=<?php echo $notif['notification_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                            <i class="fas fa-bell-slash" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                            <h3 style="margin-bottom: 10px;">No Notifications</h3>
                            <p>You don't have any notifications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

