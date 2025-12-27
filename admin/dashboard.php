<?php
require_once '../config/config.php';

// Check if user is admin
if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Admin Dashboard';
$error = '';
$success = '';

// Handle notification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
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

// Get statistics
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_classes' => 0,
    'total_subjects' => 0,
];

// Count students
$result = $conn->query("SELECT COUNT(*) as count FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['total_students'] = $row['count'];
}

// Count teachers
$result = $conn->query("SELECT COUNT(*) as count FROM teachers t JOIN users u ON t.user_id = u.user_id WHERE u.status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['total_teachers'] = $row['count'];
}

// Count classes
$result = $conn->query("SELECT COUNT(*) as count FROM classes WHERE status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['total_classes'] = $row['count'];
}

// Count subjects
$result = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['total_subjects'] = $row['count'];
}

// Get recent activities
$recent_students = $conn->query("SELECT u.first_name, u.last_name, s.admission_number, s.admission_date 
                                  FROM students s 
                                  JOIN users u ON s.user_id = u.user_id 
                                  WHERE u.status = 'active'
                                  ORDER BY s.student_id DESC LIMIT 5");

// Get today's attendance summary
$today = date('Y-m-d');
$attendance_query = "SELECT 
    COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
    COUNT(*) as total
    FROM attendance WHERE attendance_date = '$today'";
$attendance_result = $conn->query($attendance_query);
$attendance_stats = $attendance_result->fetch_assoc();

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
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_teachers']; ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_classes']; ?></h3>
                        <p>Total Classes</p>
                    </div>
                </div>
                
                <div class="dashboard-card danger">
                    <div class="card-icon danger">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_subjects']; ?></h3>
                        <p>Total Subjects</p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Attendance Summary -->
            <?php if ($attendance_stats && $attendance_stats['total'] > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Today's Attendance Summary</h3>
                    <span class="badge badge-info"><?php echo formatDate($today); ?></span>
                </div>
                <div class="card-body">
                    <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="attendance-stat-card attendance-present">
                            <div class="attendance-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3><?php echo $attendance_stats['present']; ?></h3>
                            <p>Present</p>
                        </div>
                        <div class="attendance-stat-card attendance-absent">
                            <div class="attendance-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h3><?php echo $attendance_stats['absent']; ?></h3>
                            <p>Absent</p>
                        </div>
                        <div class="attendance-stat-card attendance-late">
                            <div class="attendance-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3><?php echo $attendance_stats['late']; ?></h3>
                            <p>Late</p>
                        </div>
                        <div class="attendance-stat-card attendance-total">
                            <div class="attendance-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3><?php echo $attendance_stats['total']; ?></h3>
                            <p>Total</p>
                        </div>
                    </div>
                </div>
                
                <style>
                .attendance-stat-card {
                    text-align: center;
                    padding: 25px 20px;
                    border-radius: 16px;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    border: 2px solid transparent;
                }
                
                .attendance-stat-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, currentColor, transparent);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .attendance-stat-card:hover::before {
                    opacity: 1;
                }
                
                .attendance-stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
                    border-color: currentColor;
                }
                
                .attendance-stat-card .attendance-icon {
                    width: 50px;
                    height: 50px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 15px;
                    font-size: 24px;
                    color: white;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                }
                
                .attendance-stat-card h3 {
                    font-size: 32px;
                    font-weight: 800;
                    margin: 0 0 8px 0;
                    line-height: 1;
                    letter-spacing: -1px;
                }
                
                .attendance-stat-card p {
                    margin: 0;
                    font-weight: 600;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .attendance-present {
                    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                    color: #065f46;
                    border-color: rgba(5, 95, 70, 0.2);
                }
                
                .attendance-present .attendance-icon {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                }
                
                .attendance-present h3 {
                    color: #065f46;
                }
                
                .attendance-absent {
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    color: #991b1b;
                    border-color: rgba(153, 27, 27, 0.2);
                }
                
                .attendance-absent .attendance-icon {
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                }
                
                .attendance-absent h3 {
                    color: #991b1b;
                }
                
                .attendance-late {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    color: #92400e;
                    border-color: rgba(146, 64, 14, 0.2);
                }
                
                .attendance-late .attendance-icon {
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                }
                
                .attendance-late h3 {
                    color: #92400e;
                }
                
                .attendance-total {
                    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                    color: #3730a3;
                    border-color: rgba(55, 48, 163, 0.2);
                }
                
                .attendance-total .attendance-icon {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                
                .attendance-total h3 {
                    color: #3730a3;
                }
                
                @media (max-width: 768px) {
                    .attendance-stat-card {
                        padding: 20px 15px;
                    }
                    
                    .attendance-stat-card h3 {
                        font-size: 24px;
                    }
                    
                    .attendance-stat-card .attendance-icon {
                        width: 40px;
                        height: 40px;
                        font-size: 20px;
                        margin-bottom: 12px;
                    }
                }
                </style>
            </div>
            <?php endif; ?>
            
            <!-- Recent Students -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Recently Admitted Students</h3>
                    <a href="students.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_students->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Admission No.</th>
                                    <th>Student Name</th>
                                    <th>Admission Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $recent_students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $student['admission_number']; ?></td>
                                    <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                    <td><?php echo formatDate($student['admission_date']); ?></td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 20px;">
                        <i class="fas fa-info-circle"></i> No recent admissions
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div id="sendNotificationModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
            <h3><i class="fas fa-bullhorn"></i> Send Notification</h3>
            <button class="modal-close" onclick="hideModal('sendNotificationModal')" style="color: white;">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="send_notification">
                
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> Send notifications to all users or specific groups
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notification Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., School Holiday Announcement" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Enter your notification message here..." required></textarea>
                    <small style="color: var(--text-light);">This message will be visible to all selected users</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notification Type *</label>
                    <select name="notification_type" class="form-control" required>
                        <option value="general">📢 General Announcement</option>
                        <option value="academic">📚 Academic</option>
                        <option value="exam">📝 Exam Related</option>
                        <option value="event">🎉 Event</option>
                        <option value="urgent">⚠️ Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target Audience *</label>
                    <select name="target_role" class="form-control" id="target_role" onchange="toggleClassSelector()" required>
                        <option value="">👥 All Users (Everyone)</option>
                        <option value="student">🎓 Students Only</option>
                        <option value="teacher">👨‍🏫 Teachers Only</option>
                        <option value="parent">👪 Parents Only</option>
                    </select>
                </div>
                
                <div class="form-group" id="class_selector_group" style="display: none;">
                    <label class="form-label">Target Specific Class (Optional)</label>
                    <select name="target_class_id" class="form-control">
                        <option value="">All Classes</option>
                        <?php 
                        $classes_result = $conn->query("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric ASC");
                        while ($class = $classes_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo $class['class_name'] . ' - ' . $class['section']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small style="color: var(--text-light);">Leave as "All Classes" to send to all students/teachers</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expires In (Days)</label>
                    <input type="number" name="expires_days" class="form-control" value="30" min="0" placeholder="30">
                    <small style="color: var(--text-light);">Set to 0 for no expiration</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('sendNotificationModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleClassSelector() {
    const targetRole = document.getElementById('target_role').value;
    const classSelector = document.getElementById('class_selector_group');
    
    // Show class selector only for students and teachers
    if (targetRole === 'student' || targetRole === 'teacher') {
        classSelector.style.display = 'block';
    } else {
        classSelector.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>





