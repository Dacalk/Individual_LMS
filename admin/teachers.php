<?php
require_once '../config/config.php';
require_once '../config/email.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Teacher Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Edit teacher
            $user_id = (int)$_POST['user_id'];
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $dob = sanitize($_POST['date_of_birth']);
            $gender = sanitize($_POST['gender']);
            $address = sanitize($_POST['address']);
            $employee_id = sanitize($_POST['employee_id']);
            $joining_date = sanitize($_POST['joining_date']);
            $qualification = sanitize($_POST['qualification']);
            $specialization = sanitize($_POST['specialization']);
            $experience_years = (int)$_POST['experience_years'];
            $department = sanitize($_POST['department']);
            
            // Update user
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, address = ? WHERE user_id = ? AND role = 'teacher'");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $phone, $dob, $gender, $address, $user_id);
            
            if ($stmt->execute()) {
                // Update teacher details
                $teacher_stmt = $conn->prepare("UPDATE teachers SET employee_id = ?, joining_date = ?, qualification = ?, specialization = ?, experience_years = ?, department = ? WHERE user_id = ?");
                $teacher_stmt->bind_param("ssssisi", $employee_id, $joining_date, $qualification, $specialization, $experience_years, $department, $user_id);
                
                if ($teacher_stmt->execute()) {
                    $success = 'Teacher updated successfully!';
                } else {
                    $error = 'Error updating teacher details.';
                }
            } else {
                $error = 'Error updating user account.';
            }
        } elseif ($_POST['action'] === 'add') {
            // Add new teacher
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $plain_password = generateRandomPassword(8); // Generate random 8-char password
            $password = password_hash($plain_password, PASSWORD_DEFAULT);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $phone = sanitize($_POST['phone']);
            $dob = sanitize($_POST['date_of_birth']);
            $gender = sanitize($_POST['gender']);
            $address = sanitize($_POST['address']);
            $employee_id = sanitize($_POST['employee_id']);
            $joining_date = sanitize($_POST['joining_date']);
            $qualification = sanitize($_POST['qualification']);
            $specialization = sanitize($_POST['specialization']);
            $experience_years = (int)$_POST['experience_years'];
            $department = sanitize($_POST['department']);
            
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, phone, date_of_birth, gender, address) VALUES (?, ?, ?, 'teacher', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $username, $email, $password, $first_name, $last_name, $phone, $dob, $gender, $address);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert teacher details
                    $teacher_stmt = $conn->prepare("INSERT INTO teachers (user_id, employee_id, joining_date, qualification, specialization, experience_years, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $teacher_stmt->bind_param("issssis", $user_id, $employee_id, $joining_date, $qualification, $specialization, $experience_years, $department);
                    
                    if ($teacher_stmt->execute()) {
                        // Set session variables for credential modal
                        $_SESSION['show_credentials'] = true;
                        $_SESSION['credential_username'] = $username;
                        $_SESSION['credential_password'] = $plain_password;
                        $_SESSION['credential_email'] = $email;
                        $_SESSION['credential_name'] = "$first_name $last_name";
                        $_SESSION['credential_type'] = 'Teacher';
                        
                        // Redirect to refresh page and show modal
                        header("Location: teachers.php?added=1");
                        exit();
                    } else {
                        $error = 'Error adding teacher details.';
                    }
                } else {
                    $error = 'Error creating user account.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $user_id = (int)$_POST['user_id'];
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'teacher'");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success = 'Teacher deleted successfully!';
            } else {
                $error = 'Error deleting teacher.';
            }
        }
    }
}

// Get all teachers
$teachers_query = "SELECT u.*, t.* 
                   FROM teachers t 
                   JOIN users u ON t.user_id = u.user_id 
                   ORDER BY t.teacher_id DESC";
$teachers = $conn->query($teachers_query);

include '../includes/header.php';

// Check if we need to show credential modal
$show_credential_modal = false;
if (isset($_SESSION['show_credentials']) && $_SESSION['show_credentials']) {
    $show_credential_modal = true;
    $cred_username = $_SESSION['credential_username'];
    $cred_password = $_SESSION['credential_password'];
    $cred_email = $_SESSION['credential_email'];
    $cred_name = $_SESSION['credential_name'];
    $cred_type = $_SESSION['credential_type'];
    
    // Clear session variables
    unset($_SESSION['show_credentials']);
    unset($_SESSION['credential_username']);
    unset($_SESSION['credential_password']);
    unset($_SESSION['credential_email']);
    unset($_SESSION['credential_name']);
    unset($_SESSION['credential_type']);
}
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
                    <h3><i class="fas fa-chalkboard-teacher"></i> Teachers List</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addTeacherModal')">
                        <i class="fas fa-plus"></i> Add New Teacher
                    </button>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search teachers..." onkeyup="searchTable('searchInput', 'teachersTable')">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="teachersTable">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Specialization</th>
                                    <th>Experience</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($teachers->num_rows > 0): ?>
                                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $teacher['employee_id']; ?></td>
                                        <td><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                        <td><?php echo $teacher['email']; ?></td>
                                        <td><?php echo $teacher['phone']; ?></td>
                                        <td><?php echo $teacher['specialization']; ?></td>
                                        <td><?php echo $teacher['experience_years']; ?> years</td>
                                        <td>
                                            <span class="badge badge-<?php echo $teacher['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($teacher['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewTeacher(<?php echo $teacher['user_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editTeacher(<?php echo $teacher['user_id']; ?>)" title="Edit Teacher">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this teacher?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $teacher['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Teacher">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No teachers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Teacher Modal -->
<div id="addTeacherModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Teacher</h3>
            <button class="modal-close" onclick="hideModal('addTeacherModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <h4 style="margin-bottom: 15px; color: var(--primary-color);">Account Information</h4>
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> A random secure password will be automatically generated and displayed on screen after creation.
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Personal Information</h4>
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" name="date_of_birth" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Professional Information</h4>
                <div class="form-group">
                    <label class="form-label">Employee ID *</label>
                    <input type="text" name="employee_id" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Joining Date *</label>
                    <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g., B.Ed, M.Ed">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" placeholder="e.g., Mathematics, Science">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g., Science Department">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Teacher</button>
            </div>
        </form>
    </div>
</div>

<!-- Credential Display Modal -->
<?php if ($show_credential_modal): ?>
<div id="credentialModal" class="credential-modal" style="display: flex;">
    <div class="credential-modal-content">
        <div class="credential-modal-header">
            <i class="fas fa-key" style="font-size: 48px; color: #16a34a; margin-bottom: 15px;"></i>
            <h2 style="margin: 0; color: white;">Login Credentials Created</h2>
            <p style="margin: 10px 0 0 0; color: #dcfce7;">For: <strong><?php echo $cred_name; ?></strong> (<?php echo $cred_type; ?>)</p>
        </div>
        
        <div class="credential-modal-body">
            <div class="credential-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>IMPORTANT:</strong> Copy these credentials now. They will not be shown again.
            </div>
            
            <div class="credential-box">
                <div class="credential-item">
                    <label>Username</label>
                    <div class="credential-value">
                        <code id="username-value"><?php echo $cred_username; ?></code>
                        <button class="copy-btn" onclick="copyToClipboard('username-value')" title="Copy username">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="credential-item">
                    <label>Password</label>
                    <div class="credential-value">
                        <code id="password-value"><?php echo $cred_password; ?></code>
                        <button class="copy-btn" onclick="copyToClipboard('password-value')" title="Copy password">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="credential-item">
                    <label>Email</label>
                    <div class="credential-value">
                        <code id="email-value"><?php echo $cred_email; ?></code>
                        <button class="copy-btn" onclick="copyToClipboard('email-value')" title="Copy email">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="credential-instructions">
                <p><i class="fas fa-info-circle"></i> <strong>Next Steps:</strong></p>
                <ol>
                    <li>Copy or write down these credentials</li>
                    <li>Share them with the teacher (verbally, note, message, etc.)</li>
                    <li>Advise them to change password after first login</li>
                </ol>
            </div>
        </div>
        
        <div class="credential-modal-footer">
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; cursor: pointer;">
                <input type="checkbox" id="confirmCopied" style="width: 20px; height: 20px;">
                <span>I have copied/saved these credentials</span>
            </label>
            <button class="btn btn-primary btn-lg" onclick="closeCredentialModal()" id="closeModalBtn" disabled>
                <i class="fas fa-check"></i> Close
            </button>
        </div>
    </div>
</div>

<style>
.credential-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.credential-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.credential-modal-header {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: white;
    padding: 40px 30px;
    text-align: center;
    border-radius: 16px 16px 0 0;
}

.credential-modal-body {
    padding: 30px;
}

.credential-warning {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #92400e;
}

.credential-warning i {
    font-size: 24px;
    color: #f59e0b;
}

.credential-box {
    background: #f0fdf4;
    border: 2px solid #dcfce7;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.credential-item {
    margin-bottom: 20px;
}

.credential-item:last-child {
    margin-bottom: 0;
}

.credential-item label {
    display: block;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.credential-value {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 12px 15px;
    border-radius: 8px;
    border: 2px solid #16a34a;
}

.credential-value code {
    flex: 1;
    font-size: 18px;
    font-weight: 700;
    color: #15803d;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

.copy-btn {
    background: #16a34a;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.copy-btn:hover {
    background: #15803d;
    transform: scale(1.05);
}

.copy-btn:active {
    transform: scale(0.95);
}

.credential-instructions {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 20px;
    color: #166534;
}

.credential-instructions p {
    margin: 0 0 10px 0;
    font-weight: 600;
}

.credential-instructions ol {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.credential-instructions li {
    margin: 8px 0;
}

.credential-modal-footer {
    padding: 20px 30px 30px;
    text-align: center;
}

.credential-modal-footer button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .credential-modal-content {
        max-width: 95%;
    }
    
    .credential-value code {
        font-size: 14px;
    }
}
</style>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const btn = element.nextElementSibling;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#059669';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#16a34a';
        }, 2000);
    });
}

function closeCredentialModal() {
    document.getElementById('credentialModal').style.display = 'none';
}

document.getElementById('confirmCopied').addEventListener('change', function() {
    document.getElementById('closeModalBtn').disabled = !this.checked;
});

document.getElementById('credentialModal').addEventListener('click', function(e) {
    if (e.target === this) {
        const content = this.querySelector('.credential-modal-content');
        content.style.animation = 'shake 0.5s';
        setTimeout(() => {
            content.style.animation = 'slideDown 0.3s ease';
        }, 500);
    }
});
</script>
<?php endif; ?>

<!-- Edit Teacher Modal -->
<div id="editTeacherModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Teacher</h3>
            <button class="modal-close" onclick="hideModal('editTeacherModal')">&times;</button>
        </div>
        <form method="POST" id="editTeacherForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <h4 style="margin-bottom: 15px; color: var(--primary-color);">Personal Information</h4>
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" id="edit_gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Professional Information</h4>
                <div class="form-group">
                    <label class="form-label">Employee ID *</label>
                    <input type="text" name="employee_id" id="edit_employee_id" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Joining Date *</label>
                    <input type="date" name="joining_date" id="edit_joining_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" id="edit_qualification" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" id="edit_specialization" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" id="edit_experience_years" class="form-control" min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" id="edit_department" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('editTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Teacher</button>
            </div>
        </form>
    </div>
</div>

<!-- View Teacher Modal -->
<div id="viewTeacherModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #16a34a 0%, #059669 100%); color: white;">
            <h3><i class="fas fa-chalkboard-teacher"></i> Teacher Details</h3>
            <button class="modal-close" onclick="hideModal('viewTeacherModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="teacherDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #16a34a;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading teacher details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewTeacherModal')">Close</button>
        </div>
    </div>
</div>

<script>
function editTeacher(userId) {
    // Fetch teacher details and populate edit form
    fetch('get_teacher_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const teacher = data.teacher;
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_first_name').value = teacher.first_name || '';
                document.getElementById('edit_last_name').value = teacher.last_name || '';
                document.getElementById('edit_email').value = teacher.email || '';
                document.getElementById('edit_date_of_birth').value = teacher.date_of_birth || '';
                document.getElementById('edit_gender').value = teacher.gender || '';
                document.getElementById('edit_phone').value = teacher.phone || '';
                document.getElementById('edit_address').value = teacher.address || '';
                document.getElementById('edit_employee_id').value = teacher.employee_id || '';
                document.getElementById('edit_joining_date').value = teacher.joining_date || '';
                document.getElementById('edit_qualification').value = teacher.qualification || '';
                document.getElementById('edit_specialization').value = teacher.specialization || '';
                document.getElementById('edit_experience_years').value = teacher.experience_years || 0;
                document.getElementById('edit_department').value = teacher.department || '';
                
                showModal('editTeacherModal');
            } else {
                alert('Error loading teacher details');
            }
        })
        .catch(error => {
            alert('Failed to load teacher details');
        });
}

function viewTeacher(userId) {
    showModal('viewTeacherModal');
    
    // Fetch teacher details via AJAX
    fetch('get_teacher_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const teacher = data.teacher;
                document.getElementById('teacherDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: #16a34a; border-bottom: 2px solid #16a34a; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-user"></i> Personal Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Full Name:</th><td>${teacher.first_name} ${teacher.last_name}</td></tr>
                                <tr><th>Email:</th><td>${teacher.email}</td></tr>
                                <tr><th>Username:</th><td>${teacher.username}</td></tr>
                                <tr><th>Phone:</th><td>${teacher.phone || 'N/A'}</td></tr>
                                <tr><th>Date of Birth:</th><td>${teacher.date_of_birth || 'N/A'}</td></tr>
                                <tr><th>Gender:</th><td>${teacher.gender || 'N/A'}</td></tr>
                                <tr><th>Address:</th><td>${teacher.address || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="color: #059669; border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-briefcase"></i> Professional Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Employee ID:</th><td><strong>${teacher.employee_id || 'N/A'}</strong></td></tr>
                                <tr><th>Qualification:</th><td>${teacher.qualification || 'N/A'}</td></tr>
                                <tr><th>Specialization:</th><td>${teacher.specialization || 'N/A'}</td></tr>
                                <tr><th>Experience:</th><td>${teacher.experience_years ? teacher.experience_years + ' years' : 'N/A'}</td></tr>
                                <tr><th>Joining Date:</th><td>${teacher.joining_date || 'N/A'}</td></tr>
                                <tr><th>Department:</th><td>${teacher.department || 'N/A'}</td></tr>
                                <tr><th>Status:</th><td><span class="badge badge-${teacher.status === 'active' ? 'success' : 'danger'}">${teacher.status}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    ${teacher.assigned_classes ? `
                    <div style="margin-top: 20px;">
                        <h4 style="color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fas fa-chalkboard"></i> Assigned Classes/Subjects
                        </h4>
                        <p>${teacher.assigned_classes}</p>
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('teacherDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 20px; color: var(--text-dark);">Error loading teacher details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('teacherDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                    <p style="margin-top: 20px; color: var(--text-dark);">Failed to load teacher details</p>
                </div>
            `;
        });
}
</script>

<style>
.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr {
    border-bottom: 1px solid var(--border-color);
}

.info-table th {
    text-align: left;
    padding: 10px 15px 10px 0;
    font-weight: 600;
    color: var(--text-light);
    width: 40%;
}

.info-table td {
    padding: 10px 0;
    color: var(--text-dark);
}
</style>

<?php include '../includes/footer.php'; ?>



