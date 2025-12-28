<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Parent Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Edit parent
            $user_id = (int)$_POST['user_id'];
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            
            // Update user
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ? AND role = 'parent'");
            $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Parent updated successfully!';
            } else {
                $error = 'Error updating parent account.';
            }
        } elseif ($_POST['action'] === 'add') {
            // Add new parent
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $plain_password = generateRandomPassword(8); // Generate random 8-char password
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            $child_student_id = !empty($_POST['child_student_id']) ? (int)$_POST['child_student_id'] : NULL;
            
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, phone, address) VALUES (?, ?, ?, 'parent', ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $username, $email, $hashed_password, $first_name, $last_name, $phone, $address);
                
                    if ($stmt->execute()) {
                        $user_id = $conn->insert_id;
                        
                        // Link child if provided (parent_id in students table is user_id, not a separate parent_id)
                        if ($child_student_id) {
                            $link_stmt = $conn->prepare("UPDATE students SET parent_id = ? WHERE student_id = ?");
                            $link_stmt->bind_param("ii", $user_id, $child_student_id);
                            $link_stmt->execute();
                            $link_stmt->close();
                        }
                        
                        // Set session variables for credential modal
                        $_SESSION['show_credentials'] = true;
                        $_SESSION['credential_username'] = $username;
                        $_SESSION['credential_password'] = $plain_password;
                        $_SESSION['credential_email'] = $email;
                        $_SESSION['credential_name'] = "$first_name $last_name";
                        $_SESSION['credential_type'] = 'Parent';
                        
                        // Redirect to refresh page and show modal
                        header("Location: parents.php?added=1");
                        exit();
                    } else {
                    $error = 'Error creating user account.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $user_id = (int)$_POST['user_id'];
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'parent'");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success = 'Parent deleted successfully!';
            } else {
                $error = 'Error deleting parent.';
            }
        }
    }
}

// Get all parents
$parents_query = "SELECT u.*, GROUP_CONCAT(CONCAT(su.first_name, ' ', su.last_name) SEPARATOR ', ') as children_names
                  FROM users u 
                  LEFT JOIN students s ON s.parent_id = u.user_id
                  LEFT JOIN users su ON s.user_id = su.user_id
                  WHERE u.role = 'parent'
                  GROUP BY u.user_id
                  ORDER BY u.user_id DESC";
$parents = $conn->query($parents_query);

// Get students without parents for linking
$students_without_parents = $conn->query("SELECT s.student_id, s.admission_number, u.first_name, u.last_name 
                                          FROM students s 
                                          JOIN users u ON s.user_id = u.user_id 
                                          WHERE (s.parent_id IS NULL OR s.parent_id = 0) AND u.status = 'active'
                                          ORDER BY u.first_name ASC");

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
                    <h3><i class="fas fa-users"></i> Parents List</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addParentModal')">
                        <i class="fas fa-plus"></i> Add New Parent
                    </button>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search parents..." onkeyup="searchTable('searchInput', 'parentsTable')">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="parentsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Children</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($parents->num_rows > 0): ?>
                                    <?php while ($parent = $parents->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $parent['first_name'] . ' ' . $parent['last_name']; ?></strong></td>
                                        <td><?php echo $parent['email']; ?></td>
                                        <td><?php echo $parent['phone']; ?></td>
                                        <td><?php echo $parent['children_names'] ?? '<span class="badge badge-warning">No children linked</span>'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $parent['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($parent['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewParent(<?php echo $parent['user_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editParent(<?php echo $parent['user_id']; ?>)" title="Edit Parent">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this parent?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $parent['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Parent">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No parents found</td>
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

<!-- Add Parent Modal -->
<div id="addParentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Parent</h3>
            <button class="modal-close" onclick="hideModal('addParentModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> A random secure password will be automatically generated and displayed on screen after creation.
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email * (Credentials will be sent here)</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Link to Child (Optional)</label>
                    <select name="child_student_id" class="form-control">
                        <option value="">Select Student</option>
                        <?php 
                        $students_without_parents->data_seek(0);
                        while ($student = $students_without_parents->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo $student['first_name'] . ' ' . $student['last_name']; ?> (<?php echo $student['admission_number']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addParentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Parent & Send Credentials</button>
            </div>
        </form>
    </div>
</div>

<!-- Credential Display Modal -->
<?php if ($show_credential_modal): ?>
<div id="credentialModal" class="credential-modal" style="display: flex;">
    <div class="credential-modal-content">
        <div class="credential-modal-header">
            <i class="fas fa-key" style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;"></i>
            <h2 style="margin: 0; color: white;">Login Credentials Created</h2>
            <p style="margin: 10px 0 0 0; color: #fef3c7;">For: <strong><?php echo $cred_name; ?></strong> (<?php echo $cred_type; ?>)</p>
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
                    <li>Share them with the parent (verbally, note, message, etc.)</li>
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
    background: linear-gradient(135deg, #f59e0b, #d97706);
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
    background: #fffbeb;
    border: 2px solid #fef3c7;
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
    border: 2px solid #f59e0b;
}

.credential-value code {
    flex: 1;
    font-size: 18px;
    font-weight: 700;
    color: #d97706;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

.copy-btn {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.copy-btn:hover {
    background: #d97706;
    transform: scale(1.05);
}

.copy-btn:active {
    transform: scale(0.95);
}

.credential-instructions {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 20px;
    color: #92400e;
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
        btn.style.background = '#ca8a04';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#f59e0b';
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

<!-- Edit Parent Modal -->
<div id="editParentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Parent</h3>
            <button class="modal-close" onclick="hideModal('editParentModal')">&times;</button>
        </div>
        <form method="POST" id="editParentForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
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
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('editParentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Parent</button>
            </div>
        </form>
    </div>
</div>

<!-- View Parent Modal -->
<div id="viewParentModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
            <h3><i class="fas fa-users"></i> Parent Details</h3>
            <button class="modal-close" onclick="hideModal('viewParentModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="parentDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #f59e0b;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading parent details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewParentModal')">Close</button>
        </div>
    </div>
</div>

<script>
function editParent(userId) {
    // Fetch parent details and populate edit form
    fetch('get_parent_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const parent = data.parent;
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_first_name').value = parent.first_name || '';
                document.getElementById('edit_last_name').value = parent.last_name || '';
                document.getElementById('edit_email').value = parent.email || '';
                document.getElementById('edit_phone').value = parent.phone || '';
                document.getElementById('edit_address').value = parent.address || '';
                
                showModal('editParentModal');
            } else {
                alert('Error loading parent details');
            }
        })
        .catch(error => {
            alert('Failed to load parent details');
        });
}

function viewParent(userId) {
    showModal('viewParentModal');
    
    // Fetch parent details via AJAX
    fetch('get_parent_details.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const parent = data.parent;
                document.getElementById('parentDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: #f59e0b; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-user"></i> Personal Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Full Name:</th><td>${parent.first_name} ${parent.last_name}</td></tr>
                                <tr><th>Email:</th><td>${parent.email}</td></tr>
                                <tr><th>Username:</th><td>${parent.username}</td></tr>
                                <tr><th>Phone:</th><td>${parent.phone || 'N/A'}</td></tr>
                                <tr><th>Address:</th><td>${parent.address || 'No address provided'}</td></tr>
                                <tr><th>Status:</th><td><span class="badge badge-${parent.status === 'active' ? 'success' : 'danger'}">${parent.status}</span></td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="color: #d97706; border-bottom: 2px solid #d97706; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Registration Info
                            </h4>
                            <table class="info-table">
                                <tr><th>Registered On:</th><td>${parent.created_at || 'N/A'}</td></tr>
                                <tr><th>Total Children:</th><td>${parent.children ? parent.children.length : 0}</td></tr>
                            </table>
                        </div>
                    </div>
                    ${parent.children && parent.children.length > 0 ? `
                    <div style="margin-top: 20px;">
                        <h4 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fas fa-child"></i> Children (${parent.children.length})
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                            ${parent.children.map(child => `
                                <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                                    <p style="margin: 0; font-weight: 600; color: #065f46;">${child.name}</p>
                                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #047857;">
                                        <i class="fas fa-id-badge"></i> ${child.admission_number}
                                    </p>
                                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #047857;">
                                        <i class="fas fa-school"></i> ${child.class_name || 'Not Assigned'}
                                    </p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : '<div style="margin-top: 20px; text-align: center; padding: 20px; background: #fef3c7; border-radius: 8px;"><i class="fas fa-info-circle"></i> No children assigned to this parent</div>'}
                `;
            } else {
                document.getElementById('parentDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 20px; color: var(--text-dark);">Error loading parent details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('parentDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                    <p style="margin-top: 20px; color: var(--text-dark);">Failed to load parent details</p>
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


