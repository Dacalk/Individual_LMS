<?php
require_once '../config/config.php';
require_once '../config/email.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Student Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Edit student
            $user_id = (int)$_POST['user_id'];
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $dob = sanitize($_POST['date_of_birth']);
            $gender = sanitize($_POST['gender']);
            $address = sanitize($_POST['address']);
            $admission_number = sanitize($_POST['admission_number']);
            $admission_date = sanitize($_POST['admission_date']);
            $class_id = sanitize($_POST['class_id']);
            $section = sanitize($_POST['section']);
            $roll_number = sanitize($_POST['roll_number']);
            $blood_group = sanitize($_POST['blood_group']);
            $emergency_contact = sanitize($_POST['emergency_contact']);
            
            // Update user
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, address = ? WHERE user_id = ? AND role = 'student'");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $phone, $dob, $gender, $address, $user_id);
            
            if ($stmt->execute()) {
                // Update student details
                $student_stmt = $conn->prepare("UPDATE students SET admission_number = ?, admission_date = ?, class_id = ?, section = ?, roll_number = ?, blood_group = ?, emergency_contact = ? WHERE user_id = ?");
                $student_stmt->bind_param("ssissssi", $admission_number, $admission_date, $class_id, $section, $roll_number, $blood_group, $emergency_contact, $user_id);
                
                if ($student_stmt->execute()) {
                    $success = 'Student updated successfully!';
                } else {
                    $error = 'Error updating student details.';
                }
            } else {
                $error = 'Error updating user account.';
            }
        } elseif ($_POST['action'] === 'add') {
            // Add new student
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
            $admission_number = sanitize($_POST['admission_number']);
            $admission_date = sanitize($_POST['admission_date']);
            $class_id = sanitize($_POST['class_id']);
            $section = sanitize($_POST['section']);
            $roll_number = sanitize($_POST['roll_number']);
            $blood_group = sanitize($_POST['blood_group']);
            $emergency_contact = sanitize($_POST['emergency_contact']);
            
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, phone, date_of_birth, gender, address) VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $username, $email, $password, $first_name, $last_name, $phone, $dob, $gender, $address);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert student details
                    $student_stmt = $conn->prepare("INSERT INTO students (user_id, admission_number, admission_date, class_id, section, roll_number, blood_group, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $student_stmt->bind_param("isssisss", $user_id, $admission_number, $admission_date, $class_id, $section, $roll_number, $blood_group, $emergency_contact);
                    
                    if ($student_stmt->execute()) {
                        // Set session variables for credential modal
                        $_SESSION['show_credentials'] = true;
                        $_SESSION['credential_username'] = $username;
                        $_SESSION['credential_password'] = $plain_password;
                        $_SESSION['credential_email'] = $email;
                        $_SESSION['credential_name'] = "$first_name $last_name";
                        $_SESSION['credential_type'] = 'Student';
                        
                        // Redirect to refresh page and show modal
                        header("Location: students.php?added=1");
                        exit();
                    } else {
                        $error = 'Error adding student details.';
                    }
                } else {
                    $error = 'Error creating user account.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $user_id = (int)$_POST['user_id'];
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success = 'Student deleted successfully!';
            } else {
                $error = 'Error deleting student.';
            }
        }
    }
}

// Get all students
$students_query = "SELECT u.*, s.*, c.class_name, c.section as class_section 
                   FROM students s 
                   JOIN users u ON s.user_id = u.user_id 
                   LEFT JOIN classes c ON s.class_id = c.class_id 
                   ORDER BY s.student_id DESC";
$students = $conn->query($students_query);

// Get classes for dropdown
$classes = $conn->query("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric ASC");

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
                    <h3><i class="fas fa-user-graduate"></i> Students List</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addStudentModal')">
                        <i class="fas fa-plus"></i> Add New Student
                    </button>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search students..." onkeyup="searchTable('searchInput', 'studentsTable')">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Admission No.</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $student['admission_number']; ?></td>
                                        <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                        <td><?php echo $student['class_name'] ? $student['class_name'] . ' - ' . $student['section'] : 'Not Assigned'; ?></td>
                                        <td><?php echo $student['email']; ?></td>
                                        <td><?php echo $student['phone']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $student['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['user_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editStudent(<?php echo $student['user_id']; ?>)" title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this student?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No students found</td>
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

<!-- Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
            <button class="modal-close" onclick="hideModal('addStudentModal')">&times;</button>
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
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Academic Information</h4>
                <div class="form-group">
                    <label class="form-label">Admission Number *</label>
                    <input type="text" name="admission_number" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Admission Date *</label>
                    <input type="date" name="admission_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class *</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php 
                        $classes->data_seek(0); // Reset pointer
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo $class['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" placeholder="e.g., A, B, C">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Roll Number</label>
                    <input type="text" name="roll_number" class="form-control">
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Medical Information</h4>
                <div class="form-group">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Credential Display Modal -->
<?php if ($show_credential_modal): ?>
<div id="credentialModal" class="credential-modal" style="display: flex;">
    <div class="credential-modal-content">
        <div class="credential-modal-header">
            <i class="fas fa-key" style="font-size: 48px; color: #0284c7; margin-bottom: 15px;"></i>
            <h2 style="margin: 0; color: #0369a1;">Login Credentials Created</h2>
            <p style="margin: 10px 0 0 0; color: #64748b;">For: <strong><?php echo $cred_name; ?></strong> (<?php echo $cred_type; ?>)</p>
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
                    <li>Share them with the student (verbally, note, message, etc.)</li>
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
    background: linear-gradient(135deg, #0284c7, #0369a1);
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
    background: #f8fafc;
    border: 2px solid #e2e8f0;
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
    border: 2px solid #0284c7;
}

.credential-value code {
    flex: 1;
    font-size: 18px;
    font-weight: 700;
    color: #0369a1;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

.copy-btn {
    background: #0284c7;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.copy-btn:hover {
    background: #0369a1;
    transform: scale(1.05);
}

.copy-btn:active {
    transform: scale(0.95);
}

.credential-instructions {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 20px;
    color: #075985;
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
        // Show copied feedback
        const btn = element.nextElementSibling;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#16a34a';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#0284c7';
        }, 2000);
    });
}

function closeCredentialModal() {
    document.getElementById('credentialModal').style.display = 'none';
}

// Enable close button only when checkbox is checked
document.getElementById('confirmCopied').addEventListener('change', function() {
    document.getElementById('closeModalBtn').disabled = !this.checked;
});

// Prevent closing modal by clicking outside
document.getElementById('credentialModal').addEventListener('click', function(e) {
    if (e.target === this) {
        // Shake animation when trying to click outside
        const content = this.querySelector('.credential-modal-content');
        content.style.animation = 'shake 0.5s';
        setTimeout(() => {
            content.style.animation = 'slideDown 0.3s ease';
        }, 500);
    }
});

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
</script>
<?php endif; ?>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Student</h3>
            <button class="modal-close" onclick="hideModal('editStudentModal')">&times;</button>
        </div>
        <form method="POST" id="editStudentForm">
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
                    <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Academic Information</h4>
                <div class="form-group">
                    <label class="form-label">Admission Number *</label>
                    <input type="text" name="admission_number" id="edit_admission_number" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Admission Date *</label>
                    <input type="date" name="admission_date" id="edit_admission_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class</label>
                    <select name="class_id" id="edit_class_id" class="form-control">
                        <option value="">Select Class</option>
                        <?php 
                        $classes->data_seek(0); // Reset pointer
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo $class['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" id="edit_section" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Roll Number</label>
                    <input type="text" name="roll_number" id="edit_roll_number" class="form-control">
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color);">Medical Information</h4>
                <div class="form-group">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" id="edit_blood_group" class="form-control">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" name="emergency_contact" id="edit_emergency_contact" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('editStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- View Student Modal -->
<div id="viewStudentModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-graduate"></i> Student Details</h3>
            <button class="modal-close" onclick="hideModal('viewStudentModal')">&times;</button>
        </div>
        <div class="modal-body" id="studentDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color);"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading student details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewStudentModal')">Close</button>
        </div>
    </div>
</div>

<script>
function editStudent(userId) {
    // Fetch student details and populate edit form
    fetch('get_student_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_first_name').value = student.first_name || '';
                document.getElementById('edit_last_name').value = student.last_name || '';
                document.getElementById('edit_email').value = student.email || '';
                document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
                document.getElementById('edit_gender').value = student.gender || '';
                document.getElementById('edit_phone').value = student.phone || '';
                document.getElementById('edit_address').value = student.address || '';
                document.getElementById('edit_admission_number').value = student.admission_number || '';
                document.getElementById('edit_admission_date').value = student.admission_date || '';
                document.getElementById('edit_class_id').value = student.class_id || '';
                document.getElementById('edit_section').value = student.section || '';
                document.getElementById('edit_roll_number').value = student.roll_number || '';
                document.getElementById('edit_blood_group').value = student.blood_group || '';
                document.getElementById('edit_emergency_contact').value = student.emergency_contact || '';
                
                showModal('editStudentModal');
            } else {
                alert('Error loading student details');
            }
        })
        .catch(error => {
            alert('Failed to load student details');
        });
}

function viewStudent(userId) {
    showModal('viewStudentModal');
    
    // Fetch student details via AJAX
    fetch('get_student_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                document.getElementById('studentDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: var(--primary-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-user"></i> Personal Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Full Name:</th><td>${student.first_name} ${student.last_name}</td></tr>
                                <tr><th>Email:</th><td>${student.email}</td></tr>
                                <tr><th>Username:</th><td>${student.username}</td></tr>
                                <tr><th>Date of Birth:</th><td>${student.date_of_birth || 'N/A'}</td></tr>
                                <tr><th>Gender:</th><td>${student.gender || 'N/A'}</td></tr>
                                <tr><th>Phone:</th><td>${student.phone || 'N/A'}</td></tr>
                                <tr><th>Address:</th><td>${student.address || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="color: var(--success-color); border-bottom: 2px solid var(--success-color); padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-graduation-cap"></i> Academic Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Admission No:</th><td><strong>${student.admission_number}</strong></td></tr>
                                <tr><th>Admission Date:</th><td>${student.admission_date || 'N/A'}</td></tr>
                                <tr><th>Class:</th><td>${student.class_name ? student.class_name + (student.section ? ' - ' + student.section : '') : 'Not Assigned'}</td></tr>
                                <tr><th>Roll Number:</th><td>${student.roll_number || 'N/A'}</td></tr>
                                <tr><th>Blood Group:</th><td>${student.blood_group || 'N/A'}</td></tr>
                                <tr><th>Emergency Contact:</th><td>${student.emergency_contact || 'N/A'}</td></tr>
                                <tr><th>Status:</th><td><span class="badge badge-${student.status === 'active' ? 'success' : 'danger'}">${student.status}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    ${student.parent_name ? `
                    <div style="margin-top: 20px;">
                        <h4 style="color: var(--info-color); border-bottom: 2px solid var(--info-color); padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fas fa-users"></i> Parent/Guardian
                        </h4>
                        <table class="info-table">
                            <tr><th>Parent Name:</th><td>${student.parent_name}</td></tr>
                            <tr><th>Parent Email:</th><td>${student.parent_email}</td></tr>
                            <tr><th>Parent Contact:</th><td>${student.parent_contact || 'N/A'}</td></tr>
                        </table>
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('studentDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: var(--danger-color);"></i>
                        <p style="margin-top: 20px; color: var(--text-dark);">Error loading student details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('studentDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--warning-color);"></i>
                    <p style="margin-top: 20px; color: var(--text-dark);">Failed to load student details</p>
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



