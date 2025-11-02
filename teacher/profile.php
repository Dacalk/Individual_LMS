<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'My Profile';
$error = '';
$success = '';

// Get teacher user details
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    redirect('logout.php');
}

// Get teacher information
$teacher_query = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_query->bind_param("i", $user_id);
$teacher_query->execute();
$teacher_result = $teacher_query->get_result();
$teacher = $teacher_result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
    $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        $success = 'Profile updated successfully!';
        
        // Refresh user data
        $user_query->execute();
        $user_result = $user_query->get_result();
        $user = $user_result->fetch_assoc();
    } else {
        $error = 'Error updating profile.';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = 'New passwords do not match!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif (password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $pass_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($pass_stmt->execute()) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Error changing password.';
        }
    } else {
        $error = 'Current password is incorrect!';
    }
}

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
            
            <!-- Profile Information -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Profile Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                            </div>
                            
                            <?php if ($teacher): ?>
                            <div class="form-group">
                                <label class="form-label">Employee ID</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['employee_id'] ?? 'N/A'); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?>" disabled>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" value="<?php echo $user['date_of_birth'] ?? ''; ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['gender'] ?? 'N/A'); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; align-items: center; color: var(--text-light); font-size: 13px; margin-top: 15px;">
                            <i class="fas fa-info-circle"></i>
                            <span>Last Login: <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></span>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div style="max-width: 500px;">
                            <div class="form-group">
                                <label class="form-label">Current Password *</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password *</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                                <small style="color: var(--text-light);">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password *</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

