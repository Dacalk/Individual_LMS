<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'User Management';
$error = '';
$success = '';
$show_credentials = false;
$new_credentials = [];

// Get active tab from URL (default: students)
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'students';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // ADD USER
        if ($_POST['action'] === 'add') {
            $role = sanitize($_POST['role']);
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            $date_of_birth = sanitize($_POST['date_of_birth']);
            $gender = sanitize($_POST['gender']);
            $password = generateRandomPassword();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, phone, address, date_of_birth, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssssssssss", $username, $email, $hashed_password, $role, $first_name, $last_name, $phone, $address, $date_of_birth, $gender);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $special_id = '';
                
                // Insert role-specific data
                if ($role === 'teacher') {
                    $employee_id = 'EMP' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                    $special_id = $employee_id;
                    $joining_date = sanitize($_POST['joining_date']);
                    $qualification = sanitize($_POST['qualification']);
                    $specialization = sanitize($_POST['specialization']);
                    $experience_years = (int)$_POST['experience_years'];
                    $department = sanitize($_POST['department']);
                    
                    $teacher_stmt = $conn->prepare("INSERT INTO teachers (user_id, employee_id, joining_date, qualification, specialization, experience_years, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $teacher_stmt->bind_param("isssiss", $user_id, $employee_id, $joining_date, $qualification, $specialization, $experience_years, $department);
                    $teacher_stmt->execute();
                } elseif ($role === 'student') {
                    $admission_number = 'STU' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                    $special_id = $admission_number;
                    $admission_date = sanitize($_POST['admission_date']);
                    $class_id = (int)$_POST['class_id'];
                    $section = !empty($_POST['section']) ? sanitize($_POST['section']) : null;
                    $roll_number = sanitize($_POST['roll_number']);
                    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                    $blood_group = sanitize($_POST['blood_group']);
                    $emergency_contact = sanitize($_POST['emergency_contact']);
                    
                    $student_stmt = $conn->prepare("INSERT INTO students (user_id, admission_number, admission_date, class_id, section, roll_number, parent_id, blood_group, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $student_stmt->bind_param("isssissss", $user_id, $admission_number, $admission_date, $class_id, $section, $roll_number, $parent_id, $blood_group, $emergency_contact);
                    $student_stmt->execute();
                }
                
                // Store credentials to show in modal
                $show_credentials = true;
                $new_credentials = [
                    'role' => ucfirst($role),
                    'name' => $first_name . ' ' . $last_name,
                    'username' => $username,
                    'password' => $password,
                    'email' => $email,
                    'special_id' => $special_id
                ];
                
                $success = ucfirst($role) . " added successfully!";
            } else {
                $error = 'Error adding user. Username or email may already exist.';
            }
        }
        
        // EDIT USER
        elseif ($_POST['action'] === 'edit') {
            $user_id = (int)$_POST['user_id'];
            $role = sanitize($_POST['role']);
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
            $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
            $date_of_birth = isset($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : '';
            $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : '';
            $status = sanitize($_POST['status']);
            
            // Ensure username is unique
            $username_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $username_check->bind_param("si", $username, $user_id);
            $username_check->execute();
            $username_result = $username_check->get_result();
            if ($username_result->num_rows > 0) {
                $error = 'The username is already taken. Please choose a different username.';
                goto skip_edit;
            }
            
            // Check if password is being updated
            $update_password = false;
            $hashed_password = '';
            if (!empty($_POST['new_password'])) {
                $new_password = $_POST['new_password'];
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_password = true;
                } else {
                    $error = 'Password must be at least 6 characters long!';
                    goto skip_edit;
                }
            }
            
            // Build update query
            if ($update_password) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, status = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssssssi", $username, $email, $first_name, $last_name, $phone, $address, $date_of_birth, $gender, $status, $hashed_password, $user_id);
            } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, status = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssssi", $username, $email, $first_name, $last_name, $phone, $address, $date_of_birth, $gender, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                // Update role-specific data
                if ($role === 'teacher' && isset($_POST['teacher_id'])) {
                    $teacher_id = (int)$_POST['teacher_id'];
                    $joining_date = sanitize($_POST['joining_date']);
                    $qualification = sanitize($_POST['qualification']);
                    $specialization = sanitize($_POST['specialization']);
                    $experience_years = (int)$_POST['experience_years'];
                    $department = sanitize($_POST['department']);
                    
                    $teacher_stmt = $conn->prepare("UPDATE teachers SET joining_date = ?, qualification = ?, specialization = ?, experience_years = ?, department = ? WHERE teacher_id = ?");
                    $teacher_stmt->bind_param("sssisi", $joining_date, $qualification, $specialization, $experience_years, $department, $teacher_id);
                    $teacher_stmt->execute();
                } elseif ($role === 'student' && isset($_POST['student_id'])) {
                    $student_id = (int)$_POST['student_id'];
                    $admission_date = isset($_POST['admission_date']) ? sanitize($_POST['admission_date']) : '';
                    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
                    $section = isset($_POST['section']) ? sanitize($_POST['section']) : null;
                    $roll_number = isset($_POST['roll_number']) ? sanitize($_POST['roll_number']) : '';
                    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                    $blood_group = isset($_POST['blood_group']) ? sanitize($_POST['blood_group']) : '';
                    $emergency_contact = isset($_POST['emergency_contact']) ? sanitize($_POST['emergency_contact']) : '';
                    
                    $student_stmt = $conn->prepare("UPDATE students SET admission_date = ?, class_id = ?, section = ?, roll_number = ?, parent_id = ?, blood_group = ?, emergency_contact = ? WHERE student_id = ?");
                    $student_stmt->bind_param("sisssssi", $admission_date, $class_id, $section, $roll_number, $parent_id, $blood_group, $emergency_contact, $student_id);
                    $student_stmt->execute();
                }
                
                $success = ucfirst($role) . ' updated successfully!' . ($update_password ? ' Password has been updated.' : '');
            } else {
                $error = 'Error updating user.';
            }
            
            skip_edit:
        }
        
        // DELETE USER
        elseif ($_POST['action'] === 'delete') {
            $user_id = (int)$_POST['user_id'];
            
            // Delete user (CASCADE will handle related records in teachers/students tables)
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $success = 'User deleted successfully!';
            } else {
                $error = 'Error deleting user. User may have related data.';
            }
        }
        
        // ASSIGN PARENT TO STUDENT
        elseif ($_POST['action'] === 'assign_parent') {
            $student_id = (int)$_POST['student_id'];
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            
            $stmt = $conn->prepare("UPDATE students SET parent_id = ? WHERE student_id = ?");
            $stmt->bind_param("ii", $parent_id, $student_id);
            
            if ($stmt->execute()) {
                $success = 'Parent assigned successfully!';
            } else {
                $error = 'Error assigning parent.';
            }
        }
        
        // BULK ASSIGN PARENTS
        elseif ($_POST['action'] === 'bulk_assign_parents') {
            $assigned_count = 0;
            
            if (isset($_POST['assignments']) && is_array($_POST['assignments'])) {
                foreach ($_POST['assignments'] as $student_id => $parent_id) {
                    $student_id = (int)$student_id;
                    $parent_id = !empty($parent_id) ? (int)$parent_id : null;
                    
                    if ($student_id > 0) {
                        $stmt = $conn->prepare("UPDATE students SET parent_id = ? WHERE student_id = ?");
                        $stmt->bind_param("ii", $parent_id, $student_id);
                        if ($stmt->execute()) {
                            $assigned_count++;
                        }
                    }
                }
            }
            
            if ($assigned_count > 0) {
                $success = "Successfully assigned parents to $assigned_count student(s)!";
            } else {
                $error = 'No assignments were made.';
            }
        }
    }
}

// Fetch users based on active tab
$users_query = "";
if ($active_tab === 'students') {
    $users_query = "SELECT u.*, s.student_id, s.admission_number, s.admission_date, s.class_id, s.section, s.roll_number, s.blood_group, s.emergency_contact,
                    c.class_name, CONCAT(p.first_name, ' ', p.last_name) as parent_name
                    FROM users u 
                    LEFT JOIN students s ON u.user_id = s.user_id
                    LEFT JOIN classes c ON s.class_id = c.class_id
                    LEFT JOIN users p ON s.parent_id = p.user_id
                    WHERE u.role = 'student' 
                    ORDER BY u.created_at DESC";
} elseif ($active_tab === 'teachers') {
    $users_query = "SELECT u.*, t.teacher_id, t.employee_id, t.joining_date, t.qualification, t.specialization, t.experience_years, t.department 
                    FROM users u 
                    LEFT JOIN teachers t ON u.user_id = t.user_id 
                    WHERE u.role = 'teacher' 
                    ORDER BY u.created_at DESC";
} elseif ($active_tab === 'parents') {
    $users_query = "SELECT u.*, COUNT(s.student_id) as children_count 
                    FROM users u 
                    LEFT JOIN students s ON u.user_id = s.parent_id 
                    WHERE u.role = 'parent' 
                    GROUP BY u.user_id 
                    ORDER BY u.created_at DESC";
} elseif ($active_tab === 'librarians') {
    $users_query = "SELECT u.* 
                    FROM users u 
                    WHERE u.role = 'librarian' 
                    ORDER BY u.created_at DESC";
}

$users = $conn->query($users_query);

// Get classes for student form dropdown
$classes = $conn->query("SELECT class_id, class_name, section FROM classes WHERE status = 'active' ORDER BY class_numeric ASC");

// Get parents for student form dropdown
$parents = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'parent' AND status = 'active' ORDER BY first_name ASC");

// Get students without parents (for assign parents feature)
$students_without_parents = [];
if ($active_tab === 'students') {
    $students_no_parents_query = "SELECT s.student_id, s.admission_number, u.user_id, u.first_name, u.last_name, c.class_name
                                   FROM students s
                                   JOIN users u ON s.user_id = u.user_id
                                   LEFT JOIN classes c ON s.class_id = c.class_id
                                   WHERE (s.parent_id IS NULL OR s.parent_id = 0) AND u.status = 'active'
                                   ORDER BY u.first_name ASC";
    $result = $conn->query($students_no_parents_query);
    while ($row = $result->fetch_assoc()) {
        $students_without_parents[] = $row;
    }
}

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
            
            <?php if ($show_credentials): ?>
                <script>
                    // Auto-show credentials modal
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('credentialsModal');
                    });
                </script>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> User Management</h3>
                    <button class="btn btn-primary btn-sm" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>
                <div class="card-body">
                    <!-- Tabs -->
                    <div class="tabs">
                        <a href="?tab=students" class="tab-item <?php echo $active_tab === 'students' ? 'active' : ''; ?>">
                            <i class="fas fa-user-graduate"></i> Students
                        </a>
                        <a href="?tab=teachers" class="tab-item <?php echo $active_tab === 'teachers' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i> Teachers
                        </a>
                        <a href="?tab=parents" class="tab-item <?php echo $active_tab === 'parents' ? 'active' : ''; ?>">
                            <i class="fas fa-user-friends"></i> Parents
                        </a>
                        <a href="?tab=librarians" class="tab-item <?php echo $active_tab === 'librarians' ? 'active' : ''; ?>">
                            <i class="fas fa-book-reader"></i> Librarians
                        </a>
                    </div>
                    
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by name, email, or <?php echo $active_tab === 'students' ? 'admission number' : ($active_tab === 'teachers' ? 'employee ID' : 'username'); ?>..." onkeyup="searchTable('searchInput', 'usersTable')">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="usersTable">
                            <thead>
                                <tr>
                                    <?php if ($active_tab === 'students'): ?>
                                        <th>Admission No</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Class</th>
                                        <th>Roll No</th>
                                        <th>Parent</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    <?php elseif ($active_tab === 'teachers'): ?>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Department</th>
                                        <th>Experience</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    <?php elseif ($active_tab === 'parents'): ?>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Phone</th>
                                        <th>Children</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    <?php else: ?>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Phone</th>
                                        <th>Joined Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users->num_rows > 0): ?>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <?php if ($active_tab === 'students'): ?>
                                            <td><strong><?php echo htmlspecialchars($user['admission_number'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['class_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['roll_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (!empty($user['parent_name'])): ?>
                                                    <?php echo htmlspecialchars($user['parent_name']); ?>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">No Parent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <?php elseif ($active_tab === 'teachers'): ?>
                                            <td><strong><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['experience_years'] ?? '0'); ?> years</td>
                                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <?php elseif ($active_tab === 'parents'): ?>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $user['children_count']; ?> child(ren)</span>
                                                <?php if ($user['children_count'] > 0): ?>
                                                    <button class="btn btn-sm btn-info" onclick="viewParentChildren(<?php echo $user['user_id']; ?>)" title="View Children" style="margin-left: 5px;">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <?php endif; ?>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['user_id']; ?>, '<?php echo $active_tab; ?>')" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['user_id']; ?>, '<?php echo $active_tab; ?>')" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to permanently delete this user? This action cannot be undone!')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No users found</td>
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

<style>
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border-color);
}

.tab-item {
    padding: 12px 24px;
    text-decoration: none;
    color: var(--text-light);
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-item:hover {
    color: var(--primary-color);
    background: var(--light-gray);
}

.tab-item.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr {
    border-bottom: 1px solid var(--border-color);
}

.info-table tr:last-child {
    border-bottom: none;
}

.info-table td {
    padding: 12px 8px;
}

.info-table td:first-child {
    font-weight: 600;
    color: var(--text-dark);
    width: 40%;
}

.info-table td:last-child {
    color: var(--text-light);
}
</style>

<?php 
// Include modals (will be created next)
include 'users_modals.php'; 
include '../includes/footer.php'; 
?>

