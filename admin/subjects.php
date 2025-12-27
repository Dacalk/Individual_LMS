<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Subject Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Edit subject
            $subject_id = (int)$_POST['subject_id'];
            $subject_name = sanitize($_POST['subject_name']);
            $subject_code = sanitize($_POST['subject_code']);
            $description = sanitize($_POST['description']);
            $credit_hours = (int)$_POST['credit_hours'];
            $status = sanitize($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, description = ?, credit_hours = ?, status = ? WHERE subject_id = ?");
            $stmt->bind_param("sssisi", $subject_name, $subject_code, $description, $credit_hours, $status, $subject_id);
            
            if ($stmt->execute()) {
                $success = 'Subject updated successfully!';
            } else {
                $error = 'Error updating subject. Subject code may already exist.';
            }
        } elseif ($_POST['action'] === 'add') {
            $subject_name = sanitize($_POST['subject_name']);
            $subject_code = sanitize($_POST['subject_code']);
            $description = sanitize($_POST['description']);
            $credit_hours = (int)$_POST['credit_hours'];
            
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, description, credit_hours) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $subject_name, $subject_code, $description, $credit_hours);
            
            if ($stmt->execute()) {
                $success = 'Subject added successfully!';
            } else {
                $error = 'Error adding subject. Subject code may already exist.';
            }
        } elseif ($_POST['action'] === 'delete') {
            $subject_id = (int)$_POST['subject_id'];
            
            // Check if subject is assigned to any classes
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM class_subjects WHERE subject_id = ?");
            $check_stmt->bind_param("i", $subject_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            
            if ($check_result['count'] > 0) {
                $error = 'Cannot delete this subject as it is assigned to classes. Please remove it from all classes first.';
            } else {
                $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
                $stmt->bind_param("i", $subject_id);
                if ($stmt->execute()) {
                    $success = 'Subject deleted successfully!';
                } else {
                    $error = 'Error deleting subject.';
                }
            }
        } elseif ($_POST['action'] === 'assign_subject') {
            $subject_id = (int)$_POST['subject_id'];
            $class_id = (int)$_POST['class_id'];
            $teacher_id = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : NULL;
            $academic_year = sanitize($_POST['academic_year']);
            
            // Check if subject is already assigned to this class for this academic year
            $check_stmt = $conn->prepare("SELECT class_subject_id FROM class_subjects WHERE class_id = ? AND subject_id = ? AND academic_year = ?");
            $check_stmt->bind_param("iis", $class_id, $subject_id, $academic_year);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'This subject is already assigned to this class for the selected academic year.';
            } else {
                $stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, teacher_id, academic_year) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $class_id, $subject_id, $teacher_id, $academic_year);
                
                if ($stmt->execute()) {
                    $success = 'Subject assigned to class successfully!';
                } else {
                    $error = 'Error assigning subject to class.';
                }
            }
        } elseif ($_POST['action'] === 'update_assignment') {
            $class_subject_id = (int)$_POST['class_subject_id'];
            $teacher_id = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : NULL;
            
            $stmt = $conn->prepare("UPDATE class_subjects SET teacher_id = ? WHERE class_subject_id = ?");
            $stmt->bind_param("ii", $teacher_id, $class_subject_id);
            
            if ($stmt->execute()) {
                $success = 'Teacher assignment updated successfully!';
            } else {
                $error = 'Error updating teacher assignment.';
            }
        } elseif ($_POST['action'] === 'remove_assignment') {
            $class_subject_id = (int)$_POST['class_subject_id'];
            
            $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_subject_id = ?");
            $stmt->bind_param("i", $class_subject_id);
            
            if ($stmt->execute()) {
                $success = 'Subject assignment removed successfully!';
            } else {
                $error = 'Error removing subject assignment.';
            }
        }
    }
}

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name ASC");

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
                    <h3><i class="fas fa-book"></i> Subjects List</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addSubjectModal')">
                        <i class="fas fa-plus"></i> Add New Subject
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Description</th>
                                    <th>Credit Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($subjects->num_rows > 0): ?>
                                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $subject['subject_code']; ?></strong></td>
                                        <td><?php echo $subject['subject_name']; ?></td>
                                        <td><?php echo $subject['description']; ?></td>
                                        <td><?php echo $subject['credit_hours']; ?> hours</td>
                                        <td>
                                            <span class="badge badge-<?php echo $subject['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($subject['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewSubject(<?php echo $subject['subject_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editSubject(<?php echo $subject['subject_id']; ?>)" title="Edit Subject">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to DELETE this subject permanently? This action cannot be undone.')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Subject">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No subjects found</td>
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

<!-- Edit Subject Modal -->
<div id="editSubjectModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Subject</h3>
            <button class="modal-close" onclick="hideModal('editSubjectModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Tab Navigation -->
            <div class="tab-navigation" style="margin-bottom: 20px;">
                <button type="button" class="tab-btn active" onclick="switchTab('subject-details', event)">Subject Details</button>
                <button type="button" class="tab-btn" onclick="switchTab('teacher-assignments', event)">Teacher Assignments</button>
            </div>

            <!-- Subject Details Tab -->
            <div id="subject-details-tab" class="tab-content active">
                <form method="POST" id="editSubjectForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    
                    <div class="form-group">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subject Code *</label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Credit Hours *</label>
                        <input type="number" name="credit_hours" id="edit_credit_hours" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Teacher Assignments Tab -->
            <div id="teacher-assignments-tab" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary btn-sm" onclick="showAssignToClassModal(document.getElementById('edit_subject_id').value)" id="assignToClassBtn">
                        <i class="fas fa-plus"></i> Assign to Class
                    </button>
                </div>
                
                <div id="assignmentsList">
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #059669;"></i>
                        <p style="margin-top: 20px; color: var(--text-light);">Loading assignments...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('editSubjectModal')">Cancel</button>
            <button type="submit" class="btn btn-primary" form="editSubjectForm">Update Subject</button>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addSubjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Subject</h3>
            <button class="modal-close" onclick="hideModal('addSubjectModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">Subject Name *</label>
                    <input type="text" name="subject_name" class="form-control" placeholder="e.g., Mathematics" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject Code *</label>
                    <input type="text" name="subject_code" class="form-control" placeholder="e.g., MATH101" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the subject"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Credit Hours *</label>
                    <input type="number" name="credit_hours" class="form-control" value="5" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addSubjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- View Subject Details Modal -->
<div id="viewSubjectModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
            <h3><i class="fas fa-book"></i> Subject Details</h3>
            <button class="modal-close" onclick="hideModal('viewSubjectModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="subjectDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #6366f1;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading subject details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewSubjectModal')">Close</button>
        </div>
    </div>
</div>

<script>
function editSubject(subjectId) {
    // Fetch subject data and populate form
    fetch('get_subject_details.php?subject_id=' + subjectId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const subject = data.subject;
                document.getElementById('edit_subject_id').value = subject.subject_id;
                document.getElementById('edit_subject_name').value = subject.subject_name;
                document.getElementById('edit_subject_code').value = subject.subject_code;
                document.getElementById('edit_description').value = subject.description || '';
                document.getElementById('edit_credit_hours').value = subject.credit_hours;
                document.getElementById('edit_status').value = subject.status;
                
                // Reset to first tab
                switchTab('subject-details');
                
                showModal('editSubjectModal');
                
                // Load assignments for the teacher assignments tab
                loadSubjectAssignments(subjectId);
            } else {
                alert('Error loading subject details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load subject details');
        });
}

function viewSubject(subjectId) {
    showModal('viewSubjectModal');
    
    // Fetch subject details via AJAX
    fetch('get_subject_details.php?subject_id=' + subjectId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const subject = data.subject;
                let classesHtml = '';
                if (subject.assigned_classes && subject.assigned_classes.length > 0) {
                    classesHtml = subject.assigned_classes.map(cls => 
                        `<tr>
                            <td>${cls.class_name}</td>
                            <td>${cls.academic_year}</td>
                            <td><span class="badge badge-info">${cls.student_count} students</span></td>
                        </tr>`
                    ).join('');
                } else {
                    classesHtml = '<tr><td colspan="3" class="text-center">No classes assigned</td></tr>';
                }
                
                let teachersHtml = '';
                if (subject.teachers && subject.teachers.length > 0) {
                    teachersHtml = subject.teachers.map(teacher => 
                        `<tr>
                            <td>${teacher.name}</td>
                            <td>${teacher.employee_id}</td>
                            <td>${teacher.email}</td>
                            <td>${teacher.academic_year}</td>
                            <td>${teacher.classes || 'N/A'}</td>
                        </tr>`
                    ).join('');
                } else {
                    teachersHtml = '<tr><td colspan="5" class="text-center">No teachers assigned</td></tr>';
                }
                
                document.getElementById('subjectDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4 style="color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Subject Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Subject Name:</th><td><strong>${subject.subject_name}</strong></td></tr>
                                <tr><th>Subject Code:</th><td><strong>${subject.subject_code}</strong></td></tr>
                                <tr><th>Credit Hours:</th><td>${subject.credit_hours} hours</td></tr>
                                <tr><th>Status:</th><td><span class="badge badge-${subject.status === 'active' ? 'success' : 'danger'}">${subject.status.charAt(0).toUpperCase() + subject.status.slice(1)}</span></td></tr>
                                <tr><th>Created:</th><td>${subject.created_at ? new Date(subject.created_at).toLocaleDateString() : 'N/A'}</td></tr>
                            </table>
                            ${subject.description ? `
                            <div style="margin-top: 15px;">
                                <h5 style="color: #6366f1; margin-bottom: 10px;">Description:</h5>
                                <p style="color: var(--text-light); line-height: 1.6;">${subject.description}</p>
                            </div>
                            ` : ''}
                        </div>
                        <div>
                            <h4 style="color: #8b5cf6; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-chart-bar"></i> Statistics
                            </h4>
                            <table class="info-table">
                                <tr><th>Total Classes:</th><td><span class="badge badge-primary">${subject.class_count || 0}</span></td></tr>
                                <tr><th>Total Students:</th><td><span class="badge badge-success">${subject.total_students || 0}</span></td></tr>
                                <tr><th>Total Teachers:</th><td><span class="badge badge-info">${subject.teacher_count || 0}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    ${subject.assigned_classes && subject.assigned_classes.length > 0 ? `
                    <div style="margin-top: 20px;">
                        <h4 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fas fa-chalkboard"></i> Assigned Classes (${subject.class_count || 0})
                        </h4>
                        <div class="table-responsive">
                            <table class="table" style="margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Academic Year</th>
                                        <th>Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${classesHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}
                    ${subject.teachers && subject.teachers.length > 0 ? `
                    <div style="margin-top: 20px;">
                        <h4 style="color: #f59e0b; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fas fa-chalkboard-teacher"></i> Assigned Teachers (${subject.teacher_count || 0})
                        </h4>
                        <div class="table-responsive">
                            <table class="table" style="margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th>Teacher Name</th>
                                        <th>Employee ID</th>
                                        <th>Email</th>
                                        <th>Academic Year</th>
                                        <th>Classes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${teachersHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('subjectDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 20px; color: var(--text-light);">${data.message || 'Failed to load subject details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('subjectDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                    <p style="margin-top: 20px; color: var(--text-light);">Failed to load subject details. Please try again.</p>
                </div>
            `;
        });
}

function showAssignToClassModal(subjectId) {
    // Create a modal for assigning subject to class
    const modalHtml = `
        <div id="assignToClassModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-plus"></i> Assign Subject to Class</h3>
                    <button class="modal-close" onclick="hideAssignToClassModal()">&times;</button>
                </div>
                <form method="POST" onsubmit="return assignSubjectToClass(event, ${subjectId})">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_subject">
                        <input type="hidden" name="subject_id" value="${subjectId}">
                        
                        <div class="form-group">
                            <label class="form-label">Class *</label>
                            <select name="class_id" class="form-control" required>
                                <option value="">Select Class</option>
                                <!-- Classes will be loaded here -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-control">
                                <option value="">Select Teacher (Optional)</option>
                                <!-- Teachers will be loaded here -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Academic Year *</label>
                            <input type="text" name="academic_year" class="form-control" value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="hideAssignToClassModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Subject</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    loadClassesAndTeachersForModal();
}

function loadClassesAndTeachersForModal() {
    // Load classes
    fetch('get_classes_list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const classSelect = document.querySelector('#assignToClassModal select[name="class_id"]');
                data.classes.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.class_id;
                    option.textContent = `${cls.class_name}${cls.section ? ' - ' + cls.section : ''} (${cls.academic_year})`;
                    classSelect.appendChild(option);
                });
            }
        });
    
    // Load teachers
    fetch('get_teachers_list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const teacherSelect = document.querySelector('#assignToClassModal select[name="teacher_id"]');
                data.teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.teacher_id;
                    option.textContent = `${teacher.first_name} ${teacher.last_name} (${teacher.employee_id})`;
                    teacherSelect.appendChild(option);
                });
            }
        });
}

function hideAssignToClassModal() {
    const modal = document.getElementById('assignToClassModal');
    if (modal) {
        modal.remove();
    }
}

function assignSubjectToClass(event, subjectId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        hideAssignToClassModal();
        loadSubjectAssignments(subjectId);
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error assigning subject to class');
    });
    
    return false;
}

function editTeacherAssignment(subjectId, classSubjectId) {
    // Fetch current assignment data
    fetch(`get_subject_assignment.php?class_subject_id=${classSubjectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const assignment = data.assignment;
                const modalHtml = `
                    <div id="editTeacherModal" class="modal" style="display: block;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-edit"></i> Assign Teacher</h3>
                                <button class="modal-close" onclick="hideEditTeacherModal()">&times;</button>
                            </div>
                            <form method="POST" onsubmit="return updateTeacherAssignment(event, ${subjectId})">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update_assignment">
                                    <input type="hidden" name="class_subject_id" value="${classSubjectId}">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Subject</label>
                                        <input type="text" class="form-control" value="${assignment.subject_name} (${assignment.subject_code})" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Class</label>
                                        <input type="text" class="form-control" value="${assignment.class_name}" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Teacher</label>
                                        <select name="teacher_id" class="form-control">
                                            <option value="">No Teacher Assigned</option>
                                            <!-- Teachers will be loaded here -->
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" onclick="hideEditTeacherModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Assignment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Load teachers and set current selection
                fetch('get_teachers_list.php')
                    .then(response => response.json())
                    .then(teacherData => {
                        if (teacherData.success) {
                            const teacherSelect = document.querySelector('#editTeacherModal select[name="teacher_id"]');
                            teacherData.teachers.forEach(teacher => {
                                const option = document.createElement('option');
                                option.value = teacher.teacher_id;
                                option.textContent = `${teacher.first_name} ${teacher.last_name} (${teacher.employee_id})`;
                                if (assignment.teacher_id == teacher.teacher_id) {
                                    option.selected = true;
                                }
                                teacherSelect.appendChild(option);
                            });
                        }
                    });
            }
        });
}

function hideEditTeacherModal() {
    const modal = document.getElementById('editTeacherModal');
    if (modal) {
        modal.remove();
    }
}

function updateTeacherAssignment(event, subjectId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        hideEditTeacherModal();
        loadSubjectAssignments(subjectId);
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating teacher assignment');
    });
}

function manageAssignments(subjectId) {
    // Show modal and load assignments for this subject
    document.getElementById('assignmentSubjectName').textContent = 'Subject ' + subjectId;
    showModal('manageAssignmentsModal');
    
    // Load assignments content
    loadSubjectAssignments(subjectId);
}

function loadSubjectAssignments(subjectId) {
    document.getElementById('assignmentsList').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #059669;"></i>
            <p style="margin-top: 20px; color: var(--text-light);">Loading assignments...</p>
        </div>
    `;
    
    // Fetch subject assignments data
    fetch('get_subject_assignments.php?subject_id=' + subjectId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySubjectAssignments(data, subjectId);
            } else {
                document.getElementById('assignmentsList').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                        <p style="margin-top: 20px; color: var(--text-dark);">Failed to load assignments</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('assignmentsList').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                    <p style="margin-top: 20px; color: var(--text-dark);">Error loading assignments</p>
                </div>
            `;
        });
}

function displaySubjectAssignments(data, subjectId) {
    let html = `
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (data.assignments && data.assignments.length > 0) {
        data.assignments.forEach(assignment => {
            const teacherName = assignment.teacher_name || 'Not Assigned';
            html += `
                <tr>
                    <td><strong>${assignment.class_name}</strong></td>
                    <td>${assignment.academic_year}</td>
                    <td>${teacherName}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editTeacherAssignment(${subjectId}, ${assignment.class_subject_id})" title="Edit Teacher">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="removeAssignment(${subjectId}, ${assignment.class_subject_id}, '${assignment.class_name}')" title="Remove Assignment">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr>
                <td colspan="4" class="text-center">No assignments found for this subject</td>
            </tr>
        `;
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('assignmentsList').innerHTML = html;
}

function switchTab(tabName, event) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remove active class from all tab buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to the clicked button if event is provided
    if (event && event.target) {
        event.target.classList.add('active');
    } else {
        // Find the button that corresponds to this tab
        const tabButtons = document.querySelectorAll('.tab-btn');
        for (let btn of tabButtons) {
            if (btn.onclick && btn.onclick.toString().includes(tabName)) {
                btn.classList.add('active');
                break;
            }
        }
    }
}

function removeAssignment(subjectId, classSubjectId, className) {
    if (!confirm(`Are you sure you want to remove this assignment for ${className}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove_assignment');
    formData.append('class_subject_id', classSubjectId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        loadSubjectAssignments(subjectId);
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing assignment');
    });
}

function showSuccessMessage(message) {
    // Create a temporary success message
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success';
    successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
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
    padding: 10px 15px 10px 0;
    color: var(--text-dark);
}

/* Tab Styles */
.tab-navigation {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 20px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 12px 24px;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-light);
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-btn:hover {
    color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>

<?php include '../includes/footer.php'; ?>




