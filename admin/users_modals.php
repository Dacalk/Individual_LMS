<?php
// Get classes and parents for dropdowns
$classes->data_seek(0);
$parents->data_seek(0);
?>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <button class="modal-close" onclick="hideModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" id="addUserForm">
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">User Role *</label>
                    <select name="role" id="userRole" class="form-control" required onchange="toggleRoleFields()">
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="librarian">Librarian</option>
                    </select>
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color); border-top: 2px solid var(--border-color); padding-top: 15px;">Account Information</h4>
                
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
                    <strong>Note:</strong> A random secure password will be automatically generated and displayed after creation.
                </div>
                
                <h4 style="margin: 20px 0 15px; color: var(--primary-color); border-top: 2px solid var(--border-color); padding-top: 15px;">Personal Information</h4>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                
                <!-- Student-specific fields -->
                <div id="studentFields" style="display: none;">
                    <h4 style="margin: 20px 0 15px; color: var(--primary-color); border-top: 2px solid var(--border-color); padding-top: 15px;">Student Information</h4>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Admission Date *</label>
                            <input type="date" name="admission_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Class *</label>
                            <select name="class_id" class="form-control">
                                <option value="">Select Class</option>
                                <?php 
                                $classes->data_seek(0);
                                while ($class = $classes->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name'] . ($class['section'] ? ' - ' . $class['section'] : '')); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Roll Number</label>
                        <input type="text" name="roll_number" class="form-control">
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Parent</label>
                            <select name="parent_id" class="form-control">
                                <option value="">Select Parent (Optional)</option>
                                <?php 
                                $parents->data_seek(0);
                                while ($parent = $parents->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $parent['user_id']; ?>">
                                        <?php echo htmlspecialchars($parent['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
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
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control" placeholder="Phone number">
                    </div>
                </div>
                
                <!-- Teacher-specific fields -->
                <div id="teacherFields" style="display: none;">
                    <h4 style="margin: 20px 0 15px; color: var(--primary-color); border-top: 2px solid var(--border-color); padding-top: 15px;">Teacher Information</h4>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Joining Date *</label>
                            <input type="date" name="joining_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g., Mathematics, Science">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" class="form-control" placeholder="e.g., M.Sc., B.Ed.">
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" placeholder="e.g., Physics, Chemistry">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit User</h3>
            <button class="modal-close" onclick="hideModal('editUserModal')">&times;</button>
        </div>
        <div class="modal-body" id="editUserContent" style="max-height: 70vh; overflow-y: auto;">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color);"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading user details...</p>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> User Details</h3>
            <button class="modal-close" onclick="hideModal('viewUserModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewUserContent" style="max-height: 70vh; overflow-y: auto;">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color);"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading user details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewUserModal')">Close</button>
        </div>
    </div>
</div>

<!-- Credentials Modal -->
<?php if ($show_credentials && !empty($new_credentials)): ?>
<div id="credentialsModal" class="modal show">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
            <h3><i class="fas fa-key"></i> User Credentials</h3>
            <button class="modal-close" onclick="hideModal('credentialsModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong><?php echo htmlspecialchars($new_credentials['role']); ?> added successfully!</strong>
            </div>
            
            <div style="background: #F3F4F6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($new_credentials['name']); ?>
                </h4>
                
                <table class="info-table">
                    <?php if (!empty($new_credentials['special_id'])): ?>
                    <tr>
                        <td><strong><?php echo $new_credentials['role'] === 'Student' ? 'Admission Number' : 'Employee ID'; ?>:</strong></td>
                        <td><?php echo htmlspecialchars($new_credentials['special_id']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><code style="background: white; padding: 5px 10px; border-radius: 4px; font-size: 16px;"><?php echo htmlspecialchars($new_credentials['username']); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Password:</strong></td>
                        <td>
                            <code id="passwordDisplay" style="background: white; padding: 5px 10px; border-radius: 4px; font-size: 16px; font-weight: bold; color: #059669;"><?php echo htmlspecialchars($new_credentials['password']); ?></code>
                            <button onclick="copyPassword()" class="btn btn-sm btn-outline" style="margin-left: 10px;">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($new_credentials['email']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Important:</strong> Please save these credentials securely. The password cannot be retrieved later.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="hideModal('credentialsModal')">
                <i class="fas fa-check"></i> Got it!
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Show Add User Modal
function showAddModal() {
    // Reset form
    document.getElementById('addUserForm').reset();
    document.getElementById('studentFields').style.display = 'none';
    document.getElementById('teacherFields').style.display = 'none';
    showModal('addUserModal');
}

// Toggle role-specific fields
function toggleRoleFields() {
    const role = document.getElementById('userRole').value;
    document.getElementById('studentFields').style.display = role === 'student' ? 'block' : 'none';
    document.getElementById('teacherFields').style.display = role === 'teacher' ? 'block' : 'none';
}

// View User Details
function viewUser(userId, tab) {
    showModal('viewUserModal');
    
    fetch('get_user_details.php?user_id=' + userId + '&user_type=' + tab)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                let html = '<table class="info-table">';
                
                html += '<tr><td>Name:</td><td><strong>' + user.first_name + ' ' + user.last_name + '</strong></td></tr>';
                html += '<tr><td>Username:</td><td>' + user.username + '</td></tr>';
                html += '<tr><td>Email:</td><td>' + user.email + '</td></tr>';
                html += '<tr><td>Phone:</td><td>' + (user.phone || 'N/A') + '</td></tr>';
                html += '<tr><td>Role:</td><td><span class="badge badge-primary">' + user.role.charAt(0).toUpperCase() + user.role.slice(1) + '</span></td></tr>';
                html += '<tr><td>Status:</td><td><span class="badge badge-' + (user.status === 'active' ? 'success' : 'danger') + '">' + user.status.charAt(0).toUpperCase() + user.status.slice(1) + '</span></td></tr>';
                
                if (user.date_of_birth) {
                    html += '<tr><td>Date of Birth:</td><td>' + user.date_of_birth + '</td></tr>';
                }
                if (user.gender) {
                    html += '<tr><td>Gender:</td><td>' + user.gender.charAt(0).toUpperCase() + user.gender.slice(1) + '</td></tr>';
                }
                if (user.address) {
                    html += '<tr><td>Address:</td><td>' + user.address + '</td></tr>';
                }
                
                // Role-specific fields
                if (tab === 'students' && user.admission_number) {
                    html += '<tr><td>Admission Number:</td><td><strong>' + user.admission_number + '</strong></td></tr>';
                    if (user.class_name) html += '<tr><td>Class:</td><td>' + user.class_name + '</td></tr>';
                    if (user.roll_number) html += '<tr><td>Roll Number:</td><td>' + user.roll_number + '</td></tr>';
                }
                
                if (tab === 'teachers' && user.employee_id) {
                    html += '<tr><td>Employee ID:</td><td><strong>' + user.employee_id + '</strong></td></tr>';
                    if (user.department) html += '<tr><td>Department:</td><td>' + user.department + '</td></tr>';
                    if (user.experience_years !== null) html += '<tr><td>Experience:</td><td>' + user.experience_years + ' years</td></tr>';
                }
                
                html += '</table>';
                document.getElementById('viewUserContent').innerHTML = html;
            } else {
                document.getElementById('viewUserContent').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Error loading user details') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('viewUserContent').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        });
}

// Edit User
function editUser(userId, tab) {
    showModal('editUserModal');
    
    fetch('get_user_details.php?user_id=' + userId + '&user_type=' + tab)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                // Redirect to edit form or load edit form via AJAX
                // For now, we'll create a simple edit form
                let html = '<form method="POST">';
                html += '<input type="hidden" name="action" value="edit">';
                html += '<input type="hidden" name="user_id" value="' + user.user_id + '">';
                html += '<input type="hidden" name="role" value="' + user.role + '">';
                
                html += '<div class="form-group"><label class="form-label">Username *</label>';
                html += '<input type="text" name="username" class="form-control" value="' + user.username + '" required></div>';
                
                html += '<div class="form-group"><label class="form-label">Email *</label>';
                html += '<input type="email" name="email" class="form-control" value="' + user.email + '" required></div>';
                
                html += '<div class="form-group"><label class="form-label">First Name *</label>';
                html += '<input type="text" name="first_name" class="form-control" value="' + user.first_name + '" required></div>';
                
                html += '<div class="form-group"><label class="form-label">Last Name *</label>';
                html += '<input type="text" name="last_name" class="form-control" value="' + user.last_name + '" required></div>';
                
                html += '<div class="form-group"><label class="form-label">Phone</label>';
                html += '<input type="text" name="phone" class="form-control" value="' + (user.phone || '') + '"></div>';
                
                html += '<div class="form-group"><label class="form-label">Status</label>';
                html += '<select name="status" class="form-control">';
                html += '<option value="active"' + (user.status === 'active' ? ' selected' : '') + '>Active</option>';
                html += '<option value="inactive"' + (user.status === 'inactive' ? ' selected' : '') + '>Inactive</option>';
                html += '<option value="suspended"' + (user.status === 'suspended' ? ' selected' : '') + '>Suspended</option>';
                html += '</select></div>';
                
                html += '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Leave password blank to keep current password</div>';
                html += '<div class="form-group"><label class="form-label">New Password (Optional)</label>';
                html += '<input type="password" name="new_password" class="form-control" placeholder="Enter new password (min 6 characters)"></div>';
                
                // Add role-specific fields for edit
                if (user.role === 'student' && user.student_id) {
                    html += '<input type="hidden" name="student_id" value="' + user.student_id + '">';
                    html += '<h4 style="margin: 20px 0 15px; color: var(--primary-color); border-top: 2px solid var(--border-color); padding-top: 15px;">Student Information</h4>';
                    html += '<div class="form-group"><label class="form-label">Admission Date</label>';
                    html += '<input type="date" name="admission_date" class="form-control" value="' + (user.admission_date || '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Class</label>';
                    html += '<select name="class_id" class="form-control" id="editClassId">';
                    html += '<option value="">Select Class</option>';
                    // Classes will be populated via separate AJAX call if needed, or use existing data
                    html += '</select></div>';
                    html += '<div class="form-group"><label class="form-label">Roll Number</label>';
                    html += '<input type="text" name="roll_number" class="form-control" value="' + (user.roll_number || '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Parent *</label>';
                    html += '<select name="parent_id" class="form-control" id="editParentId">';
                    html += '<option value="">-- No Parent --</option>';
                    // Parents will be loaded via AJAX
                    html += '</select></div>';
                    html += '<div class="form-group"><label class="form-label">Blood Group</label>';
                    html += '<select name="blood_group" class="form-control">';
                    html += '<option value="">Select Blood Group</option>';
                    html += '<option value="A+"' + (user.blood_group === 'A+' ? ' selected' : '') + '>A+</option>';
                    html += '<option value="A-"' + (user.blood_group === 'A-' ? ' selected' : '') + '>A-</option>';
                    html += '<option value="B+"' + (user.blood_group === 'B+' ? ' selected' : '') + '>B+</option>';
                    html += '<option value="B-"' + (user.blood_group === 'B-' ? ' selected' : '') + '>B-</option>';
                    html += '<option value="AB+"' + (user.blood_group === 'AB+' ? ' selected' : '') + '>AB+</option>';
                    html += '<option value="AB-"' + (user.blood_group === 'AB-' ? ' selected' : '') + '>AB-</option>';
                    html += '<option value="O+"' + (user.blood_group === 'O+' ? ' selected' : '') + '>O+</option>';
                    html += '<option value="O-"' + (user.blood_group === 'O-' ? ' selected' : '') + '>O-</option>';
                    html += '</select></div>';
                    html += '<div class="form-group"><label class="form-label">Emergency Contact</label>';
                    html += '<input type="text" name="emergency_contact" class="form-control" value="' + (user.emergency_contact || '') + '"></div>';
                    
                    // Load classes and parents dropdowns
                    loadEditDropdowns(user.class_id, user.parent_id);
                }
                
                if (user.role === 'teacher' && user.teacher_id) {
                    html += '<input type="hidden" name="teacher_id" value="' + user.teacher_id + '">';
                    html += '<div class="form-group"><label class="form-label">Joining Date</label>';
                    html += '<input type="date" name="joining_date" class="form-control" value="' + (user.joining_date ? user.joining_date.split(' ')[0] : '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Department</label>';
                    html += '<input type="text" name="department" class="form-control" value="' + (user.department || '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Qualification</label>';
                    html += '<input type="text" name="qualification" class="form-control" value="' + (user.qualification || '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Specialization</label>';
                    html += '<input type="text" name="specialization" class="form-control" value="' + (user.specialization || '') + '"></div>';
                    html += '<div class="form-group"><label class="form-label">Experience Years</label>';
                    html += '<input type="number" name="experience_years" class="form-control" value="' + (user.experience_years || 0) + '"></div>';
                }
                
                html += '<div class="modal-footer" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">';
                html += '<button type="button" class="btn btn-outline" onclick="hideModal(\'editUserModal\')">Cancel</button>';
                html += '<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>';
                html += '</div>';
                html += '</form>';
                
                document.getElementById('editUserContent').innerHTML = html;
            } else {
                document.getElementById('editUserContent').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Error loading user details') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('editUserContent').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        });
}

// Copy password to clipboard
function copyPassword() {
    const password = document.getElementById('passwordDisplay').textContent;
    navigator.clipboard.writeText(password).then(function() {
        alert('Password copied to clipboard!');
    }, function() {
        alert('Failed to copy password. Please copy manually.');
    });
}

// Load classes and parents for edit form
function loadEditDropdowns(selectedClassId, selectedParentId) {
    // Load classes
    fetch('get_classes.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const classSelect = document.getElementById('editClassId');
                if (classSelect) {
                    data.classes.forEach(cls => {
                        const option = document.createElement('option');
                        option.value = cls.class_id;
                        option.textContent = cls.class_name + (cls.section ? ' - ' + cls.section : '');
                        if (cls.class_id == selectedClassId) {
                            option.selected = true;
                        }
                        classSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading classes:', error));
    
    // Load parents
    fetch('get_parents.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const parentSelect = document.getElementById('editParentId');
                if (parentSelect) {
                    data.parents.forEach(parent => {
                        const option = document.createElement('option');
                        option.value = parent.user_id;
                        option.textContent = parent.name;
                        if (parent.user_id == selectedParentId) {
                            option.selected = true;
                        }
                        parentSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading parents:', error));
}

// View Parent's Children
function viewParentChildren(parentId) {
    showModal('viewParentChildrenModal');
    
    fetch('get_parent_children.php?parent_id=' + parentId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.children.length > 0) {
                let html = '<table class="table">';
                html += '<thead><tr><th>Admission No</th><th>Name</th><th>Class</th><th>Roll No</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                data.children.forEach(child => {
                    html += '<tr>';
                    html += '<td><strong>' + child.admission_number + '</strong></td>';
                    html += '<td>' + child.name + '</td>';
                    html += '<td>' + (child.class_name || 'N/A') + (child.section ? ' - ' + child.section : '') + '</td>';
                    html += '<td>' + (child.roll_number || 'N/A') + '</td>';
                    html += '<td>';
                    html += '<button class="btn btn-sm btn-info" onclick="viewUser(' + child.user_id + ', \'students\')" title="View Student">';
                    html += '<i class="fas fa-eye"></i></button> ';
                    html += '<button class="btn btn-sm btn-warning" onclick="editUser(' + child.user_id + ', \'students\')" title="Edit Student">';
                    html += '<i class="fas fa-edit"></i></button>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                document.getElementById('parentChildrenContent').innerHTML = html;
            } else {
                document.getElementById('parentChildrenContent').innerHTML = 
                    '<div class="alert alert-info" style="text-align: center; padding: 40px;">' +
                    '<i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 20px;"></i>' +
                    '<h4>No Children Assigned</h4>' +
                    '<p>This parent does not have any children assigned yet.</p>' +
                    '</div>';
            }
        })
        .catch(error => {
            document.getElementById('parentChildrenContent').innerHTML = 
                '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        });
}
</script>

<!-- View Parent Children Modal -->
<div id="viewParentChildrenModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-child"></i> Parent's Children</h3>
            <button class="modal-close" onclick="hideModal('viewParentChildrenModal')">&times;</button>
        </div>
        <div class="modal-body" id="parentChildrenContent" style="max-height: 70vh; overflow-y: auto;">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color);"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading children...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewParentChildrenModal')">Close</button>
        </div>
    </div>
</div>


