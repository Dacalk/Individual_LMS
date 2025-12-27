<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Notifications';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = sanitize($_POST['title']);
        $message = sanitize($_POST['message']);
        $notification_type = sanitize($_POST['notification_type']);
        $target_role = sanitize($_POST['target_role']);
        $target_class_id = !empty($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : NULL;
        $expires_days = (int)$_POST['expires_days'];
        $expires_at = $expires_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expires_days days")) : NULL;
        
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, notification_type, target_role, target_class_id, created_by, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiis", $title, $message, $notification_type, $target_role, $target_class_id, $_SESSION['user_id'], $expires_at);
        
        if ($stmt->execute()) {
            $success = 'Notification sent successfully!';
        } else {
            $error = 'Error sending notification.';
        }
    }
}

// Get all notifications
$notifications_query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name, c.class_name
                        FROM notifications n
                        JOIN users u ON n.created_by = u.user_id
                        LEFT JOIN classes c ON n.target_class_id = c.class_id
                        ORDER BY n.created_at DESC";
$notifications = $conn->query($notifications_query);

// Get classes for dropdown
$classes = $conn->query("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric ASC");

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
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addNotificationModal')">
                        <i class="fas fa-bullhorn"></i> Send Notification
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <div class="card" style="margin-bottom: 15px; border-left: 4px solid var(--primary-color);">
                            <div class="card-body" style="padding: 15px;">
                                <div class="d-flex justify-between align-center" style="margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: var(--text-dark);"><?php echo $notif['title']; ?></h4>
                                    <span class="badge badge-<?php echo $notif['notification_type'] === 'urgent' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($notif['notification_type']); ?>
                                    </span>
                                </div>
                                <p style="margin: 10px 0; color: var(--text-light);"><?php echo nl2br($notif['message']); ?></p>
                                <div style="display: flex; gap: 15px; font-size: 12px; color: var(--text-light); margin-top: 10px;">
                                    <span><i class="fas fa-user"></i> <?php echo $notif['created_by_name']; ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo formatDateTime($notif['created_at']); ?></span>
                                    <?php if ($notif['target_role']): ?>
                                        <span><i class="fas fa-users"></i> Target: <?php echo ucfirst($notif['target_role']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($notif['class_name']): ?>
                                        <span><i class="fas fa-school"></i> <?php echo $notif['class_name']; ?></span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fas fa-eye"></i> 
                                        <?php echo $notif['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                            No notifications yet
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Notification Modal -->
<div id="addNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-bullhorn"></i> Send Notification</h3>
            <button class="modal-close" onclick="hideModal('addNotificationModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Notification title" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Notification message" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type *</label>
                    <select name="notification_type" class="form-control" required>
                        <option value="general">General</option>
                        <option value="academic">Academic</option>
                        <option value="exam">Exam</option>
                        <option value="event">Event</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target Audience *</label>
                    <select name="target_role" class="form-control" required>
                        <option value="">All Users</option>
                        <option value="student">Students Only</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="parent">Parents Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target Class (Optional)</label>
                    <select name="target_class_id" class="form-control">
                        <option value="">All Classes</option>
                        <?php 
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo $class['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expires In (Days)</label>
                    <input type="number" name="expires_days" class="form-control" value="30" min="0" placeholder="0 for no expiry">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addNotificationModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>









