<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'Messages';
$error = '';
$success = '';

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $subject, $message);
    
    if ($stmt->execute()) {
        $success = 'Message sent successfully!';
    } else {
        $error = 'Error sending message.';
    }
}

// Handle mark as read
if (isset($_GET['read']) && $_GET['read']) {
    $message_id = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
    $stmt->execute();
}

// Get inbox messages
$inbox_query = "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) as sender_name, u.role as sender_role
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.receiver_id = ?
                ORDER BY m.created_at DESC";
$inbox_stmt = $conn->prepare($inbox_query);
$inbox_stmt->bind_param("i", $_SESSION['user_id']);
$inbox_stmt->execute();
$inbox_messages = $inbox_stmt->get_result();

// Get sent messages
$sent_query = "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) as receiver_name, u.role as receiver_role
               FROM messages m
               JOIN users u ON m.receiver_id = u.user_id
               WHERE m.sender_id = ?
               ORDER BY m.created_at DESC";
$sent_stmt = $conn->prepare($sent_query);
$sent_stmt->bind_param("i", $_SESSION['user_id']);
$sent_stmt->execute();
$sent_messages = $sent_stmt->get_result();

// Get all users for new message
$users = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) as name, role, email FROM users WHERE user_id != {$_SESSION['user_id']} AND status = 'active' ORDER BY role, first_name ASC");

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>
    
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
                    <h3><i class="fas fa-envelope"></i> Messages</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('newMessageModal')">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
                <div class="card-body">
                    <!-- Tab Navigation -->
                    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color);">
                        <button class="tab-button active" onclick="switchTab('inbox')" id="inboxTab" style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-weight: 600; border-bottom: 3px solid var(--success-color); color: var(--success-color);">
                            <i class="fas fa-inbox"></i> Inbox
                        </button>
                        <button class="tab-button" onclick="switchTab('sent')" id="sentTab" style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-weight: 600; color: var(--text-light);">
                            <i class="fas fa-paper-plane"></i> Sent
                        </button>
                    </div>
                    
                    <!-- Inbox Tab -->
                    <div id="inboxContent" class="tab-content">
                        <?php if ($inbox_messages->num_rows > 0): ?>
                            <div class="messages-list">
                                <?php while ($msg = $inbox_messages->fetch_assoc()): ?>
                                    <div class="message-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>" onclick="viewMessage(<?php echo $msg['message_id']; ?>)">
                                        <div class="message-avatar">
                                            <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-header">
                                                <strong><?php echo $msg['sender_name']; ?></strong>
                                                <span class="badge badge-info"><?php echo ucfirst($msg['sender_role']); ?></span>
                                                <?php if (!$msg['is_read']): ?>
                                                    <span class="badge badge-danger">New</span>
                                                <?php endif; ?>
                                                <span class="message-time"><?php echo formatDateTime($msg['created_at']); ?></span>
                                            </div>
                                            <div class="message-subject"><?php echo $msg['subject']; ?></div>
                                            <div class="message-preview"><?php echo substr($msg['message'], 0, 100); ?>...</div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                                <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <p>No messages in inbox</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sent Tab -->
                    <div id="sentContent" class="tab-content" style="display: none;">
                        <?php if ($sent_messages->num_rows > 0): ?>
                            <div class="messages-list">
                                <?php while ($msg = $sent_messages->fetch_assoc()): ?>
                                    <div class="message-item">
                                        <div class="message-avatar">
                                            <?php echo strtoupper(substr($msg['receiver_name'], 0, 1)); ?>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-header">
                                                <strong>To: <?php echo $msg['receiver_name']; ?></strong>
                                                <span class="badge badge-info"><?php echo ucfirst($msg['receiver_role']); ?></span>
                                                <span class="message-time"><?php echo formatDateTime($msg['created_at']); ?></span>
                                            </div>
                                            <div class="message-subject"><?php echo $msg['subject']; ?></div>
                                            <div class="message-preview"><?php echo substr($msg['message'], 0, 100); ?>...</div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                                <i class="fas fa-paper-plane" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <p>No sent messages</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div id="newMessageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-envelope"></i> New Message</h3>
            <button class="modal-close" onclick="hideModal('newMessageModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">To: *</label>
                    <select name="receiver_id" class="form-control" required>
                        <option value="">Select Recipient</option>
                        <?php 
                        $current_role = '';
                        while ($user = $users->fetch_assoc()): 
                            if ($current_role != $user['role']) {
                                if ($current_role != '') echo '</optgroup>';
                                echo '<optgroup label="' . ucfirst($user['role']) . 's">';
                                $current_role = $user['role'];
                            }
                        ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo $user['name']; ?> (<?php echo $user['email']; ?>)
                            </option>
                        <?php endwhile; ?>
                        <?php if ($current_role != '') echo '</optgroup>'; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject: *</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message: *</label>
                    <textarea name="message" class="form-control" rows="6" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('newMessageModal')">Cancel</button>
                <button type="submit" name="send_message" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.messages-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.message-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.message-item:hover {
    background: var(--light-gray);
    border-color: var(--success-color);
}

.message-item.unread {
    background: rgba(16, 185, 129, 0.05);
    border-left: 4px solid var(--success-color);
    font-weight: 600;
}

.message-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--success-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
}

.message-content {
    flex: 1;
}

.message-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.message-time {
    margin-left: auto;
    font-size: 12px;
    color: var(--text-light);
}

.message-subject {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.message-preview {
    font-size: 14px;
    color: var(--text-light);
}

.tab-button:hover {
    background: var(--light-gray);
}

.tab-button.active {
    border-bottom: 3px solid var(--success-color) !important;
    color: var(--success-color) !important;
}
</style>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.getElementById('inboxContent').style.display = 'none';
    document.getElementById('sentContent').style.display = 'none';
    
    // Remove active class from all buttons
    document.getElementById('inboxTab').classList.remove('active');
    document.getElementById('sentTab').classList.remove('active');
    document.getElementById('inboxTab').style.borderBottom = 'none';
    document.getElementById('sentTab').style.borderBottom = 'none';
    document.getElementById('inboxTab').style.color = 'var(--text-light)';
    document.getElementById('sentTab').style.color = 'var(--text-light)';
    
    // Show selected tab
    if (tab === 'inbox') {
        document.getElementById('inboxContent').style.display = 'block';
        document.getElementById('inboxTab').classList.add('active');
        document.getElementById('inboxTab').style.borderBottom = '3px solid var(--success-color)';
        document.getElementById('inboxTab').style.color = 'var(--success-color)';
    } else {
        document.getElementById('sentContent').style.display = 'block';
        document.getElementById('sentTab').classList.add('active');
        document.getElementById('sentTab').style.borderBottom = '3px solid var(--success-color)';
        document.getElementById('sentTab').style.color = 'var(--success-color)';
    }
}

function viewMessage(messageId) {
    window.location.href = '?read=' + messageId;
}
</script>

<?php include '../includes/footer.php'; ?>





