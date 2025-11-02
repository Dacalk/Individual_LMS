<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Class Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Edit class
            $class_id = (int)$_POST['class_id'];
            $class_name = sanitize($_POST['class_name']);
            $class_numeric = (int)$_POST['class_numeric'];
            $section = sanitize($_POST['section']);
            $room_number = sanitize($_POST['room_number']);
            $capacity = (int)$_POST['capacity'];
            $academic_year = sanitize($_POST['academic_year']);
            $class_teacher_id = !empty($_POST['class_teacher_id']) ? (int)$_POST['class_teacher_id'] : NULL;
            $status = sanitize($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE classes SET class_name = ?, class_numeric = ?, section = ?, room_number = ?, capacity = ?, academic_year = ?, class_teacher_id = ?, status = ? WHERE class_id = ?");
            $stmt->bind_param("sissisisi", $class_name, $class_numeric, $section, $room_number, $capacity, $academic_year, $class_teacher_id, $status, $class_id);
            
            if ($stmt->execute()) {
                $success = 'Class updated successfully!';
            } else {
                $error = 'Error updating class.';
            }
        } elseif ($_POST['action'] === 'add') {
            $class_name = sanitize($_POST['class_name']);
            $class_numeric = (int)$_POST['class_numeric'];
            $section = sanitize($_POST['section']);
            $room_number = sanitize($_POST['room_number']);
            $capacity = (int)$_POST['capacity'];
            $academic_year = sanitize($_POST['academic_year']);
            $class_teacher_id = !empty($_POST['class_teacher_id']) ? (int)$_POST['class_teacher_id'] : NULL;
            
            $stmt = $conn->prepare("INSERT INTO classes (class_name, class_numeric, section, room_number, capacity, academic_year, class_teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissisi", $class_name, $class_numeric, $section, $room_number, $capacity, $academic_year, $class_teacher_id);
            
            if ($stmt->execute()) {
                $success = 'Class added successfully!';
            } else {
                $error = 'Error adding class.';
            }
        } elseif ($_POST['action'] === 'delete') {
            $class_id = (int)$_POST['class_id'];
            $stmt = $conn->prepare("UPDATE classes SET status = 'inactive' WHERE class_id = ?");
            $stmt->bind_param("i", $class_id);
            if ($stmt->execute()) {
                $success = 'Class deactivated successfully!';
            } else {
                $error = 'Error deactivating class.';
            }
        }
    }
}

// Get all classes
$classes_query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.class_id) as student_count
                  FROM classes c 
                  LEFT JOIN teachers t ON c.class_teacher_id = t.teacher_id
                  LEFT JOIN users u ON t.user_id = u.user_id
                  ORDER BY c.class_numeric ASC, c.section ASC";
$classes = $conn->query($classes_query);

// Get teachers for dropdown
$teachers = $conn->query("SELECT t.teacher_id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.user_id WHERE u.status = 'active'");

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
                    <h3><i class="fas fa-school"></i> Classes List</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addClassModal')">
                        <i class="fas fa-plus"></i> Add New Class
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Room No.</th>
                                    <th>Capacity</th>
                                    <th>Students</th>
                                    <th>Class Teacher</th>
                                    <th>Academic Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($classes->num_rows > 0): ?>
                                    <?php while ($class = $classes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $class['class_name']; ?></strong></td>
                                        <td><?php echo $class['section']; ?></td>
                                        <td><?php echo $class['room_number']; ?></td>
                                        <td><?php echo $class['capacity']; ?></td>
                                        <td><span class="badge badge-primary"><?php echo $class['student_count']; ?> Students</span></td>
                                        <td><?php echo $class['teacher_name'] ?? 'Not Assigned'; ?></td>
                                        <td><?php echo $class['academic_year']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $class['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($class['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewClass(<?php echo $class['class_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editClass(<?php echo $class['class_id']; ?>)" title="Edit Class">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to deactivate this class?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Deactivate Class">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No classes found</td>
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

<!-- View Class Modal -->
<div id="viewClassModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
            <h3><i class="fas fa-school"></i> Class Details</h3>
            <button class="modal-close" onclick="hideModal('viewClassModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="classDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #6366f1;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading class details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewClassModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div id="editClassModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Class</h3>
            <button class="modal-close" onclick="hideModal('editClassModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="class_id" id="edit_class_id">
                
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="class_name" id="edit_class_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class Number *</label>
                    <input type="number" name="class_numeric" id="edit_class_numeric" class="form-control" min="1" max="13" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Section *</label>
                    <input type="text" name="section" id="edit_section" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" id="edit_room_number" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Academic Year *</label>
                    <input type="text" name="academic_year" id="edit_academic_year" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class Teacher</label>
                    <select name="class_teacher_id" id="edit_class_teacher_id" class="form-control">
                        <option value="">Select Class Teacher</option>
                        <?php 
                        $teachers->data_seek(0); // Reset pointer
                        while ($teacher = $teachers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>">
                                <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('editClassModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Class</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Class Modal -->
<div id="addClassModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Class</h3>
            <button class="modal-close" onclick="hideModal('addClassModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="class_name" class="form-control" placeholder="e.g., Class 10" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class Number *</label>
                    <input type="number" name="class_numeric" class="form-control" min="1" max="12" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Section *</label>
                    <input type="text" name="section" class="form-control" placeholder="e.g., A, B, C" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g., Room 101">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="40" min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Academic Year *</label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class Teacher</label>
                    <select name="class_teacher_id" class="form-control">
                        <option value="">Select Class Teacher</option>
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>">
                                <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addClassModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Class</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewClass(classId) {
    showModal('viewClassModal');
    
    // Fetch class details via AJAX
    fetch('get_class_details.php?class_id=' + classId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const classData = data.class;
                
                // Fetch teacher name and student count
                fetch('get_class_full_details.php?class_id=' + classId)
                    .then(response => response.json())
                    .then(fullData => {
                        if (fullData.success) {
                            const teacherName = fullData.teacher_name || 'Not Assigned';
                            const studentCount = fullData.student_count || 0;
                            const students = fullData.students || [];
                            
                            let studentsHtml = '';
                            if (students.length > 0) {
                                studentsHtml = '<ul style="list-style: none; padding: 0; margin: 10px 0;">';
                                students.forEach(student => {
                                    studentsHtml += `<li style="padding: 8px; background: #f0f9ff; margin: 5px 0; border-radius: 4px; border-left: 3px solid #6366f1;">
                                        <i class="fas fa-user-graduate"></i> ${student.name} 
                                        ${student.roll_number ? '(Roll: ' + student.roll_number + ')' : ''}
                                    </li>`;
                                });
                                studentsHtml += '</ul>';
                            } else {
                                studentsHtml = '<p style="color: var(--text-light); padding: 20px; text-align: center; background: #f3f4f6; border-radius: 8px;">No students assigned to this class</p>';
                            }
                            
                            document.getElementById('classDetailsContent').innerHTML = `
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div>
                                        <h4 style="color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 15px;">
                                            <i class="fas fa-info-circle"></i> Class Information
                                        </h4>
                                        <table class="info-table">
                                            <tr><th>Class Name:</th><td><strong>${classData.class_name}</strong></td></tr>
                                            <tr><th>Class Number:</th><td>${classData.class_numeric}</td></tr>
                                            <tr><th>Section:</th><td>${classData.section}</td></tr>
                                            <tr><th>Room Number:</th><td>${classData.room_number || 'Not Assigned'}</td></tr>
                                            <tr><th>Status:</th><td><span class="badge badge-${classData.status === 'active' ? 'success' : 'danger'}">${classData.status}</span></td></tr>
                                        </table>
                                    </div>
                                    <div>
                                        <h4 style="color: #4f46e5; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 15px;">
                                            <i class="fas fa-users-cog"></i> Additional Details
                                        </h4>
                                        <table class="info-table">
                                            <tr><th>Capacity:</th><td>${classData.capacity} students</td></tr>
                                            <tr><th>Current Students:</th><td><span class="badge badge-primary">${studentCount}</span></td></tr>
                                            <tr><th>Academic Year:</th><td>${classData.academic_year}</td></tr>
                                            <tr><th>Class Teacher:</th><td>${teacherName}</td></tr>
                                            <tr><th>Created:</th><td>${classData.created_at || 'N/A'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div style="margin-top: 20px;">
                                    <h4 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 15px;">
                                        <i class="fas fa-user-graduate"></i> Students in this Class (${studentCount})
                                    </h4>
                                    ${studentsHtml}
                                </div>
                            `;
                        }
                    });
            } else {
                document.getElementById('classDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 20px; color: var(--text-dark);">Error loading class details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('classDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                    <p style="margin-top: 20px; color: var(--text-dark);">Failed to load class details</p>
                </div>
            `;
        });
}

function editClass(classId) {
    // Fetch class data and populate form
    fetch('get_class_details.php?class_id=' + classId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const classData = data.class;
                document.getElementById('edit_class_id').value = classData.class_id;
                document.getElementById('edit_class_name').value = classData.class_name;
                document.getElementById('edit_class_numeric').value = classData.class_numeric;
                document.getElementById('edit_section').value = classData.section;
                document.getElementById('edit_room_number').value = classData.room_number || '';
                document.getElementById('edit_capacity').value = classData.capacity;
                document.getElementById('edit_academic_year').value = classData.academic_year;
                document.getElementById('edit_class_teacher_id').value = classData.class_teacher_id || '';
                document.getElementById('edit_status').value = classData.status;
                
                showModal('editClassModal');
            } else {
                alert('Error loading class details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load class details');
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

