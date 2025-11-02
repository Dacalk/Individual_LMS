<?php
require_once '../config/config.php';

if (!hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Timetable Management';
$error = '';
$success = '';

// Get current academic year
$current_year = date('Y');
$academic_year = isset($_GET['year']) ? sanitize($_GET['year']) : $current_year;
$term = isset($_GET['term']) ? sanitize($_GET['term']) : '1';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $class_id = (int)$_POST['class_id'];
            $day_of_week = sanitize($_POST['day_of_week']);
            $period_id = (int)$_POST['period_id'];
            $subject_id = (int)$_POST['subject_id'];
            $teacher_id = (int)$_POST['teacher_id'];
            $room_number = sanitize($_POST['room_number']);
            $year = sanitize($_POST['academic_year']);
            $term_val = sanitize($_POST['term']);
            $notes = sanitize($_POST['notes']);
            
            // Check for conflicts
            $conflict_check = $conn->prepare("SELECT timetable_id FROM timetable WHERE class_id = ? AND day_of_week = ? AND period_id = ? AND academic_year = ? AND term = ?");
            $conflict_check->bind_param("isiss", $class_id, $day_of_week, $period_id, $year, $term_val);
            $conflict_check->execute();
            
            if ($conflict_check->get_result()->num_rows > 0) {
                $error = 'A timetable entry already exists for this class, day, and period!';
            } else {
                $stmt = $conn->prepare("INSERT INTO timetable (class_id, day_of_week, period_id, subject_id, teacher_id, room_number, academic_year, term, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isisissss", $class_id, $day_of_week, $period_id, $subject_id, $teacher_id, $room_number, $year, $term_val, $notes);
                
                if ($stmt->execute()) {
                    $success = 'Timetable entry added successfully!';
                } else {
                    $error = 'Error adding timetable entry.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $timetable_id = (int)$_POST['timetable_id'];
            $stmt = $conn->prepare("DELETE FROM timetable WHERE timetable_id = ?");
            $stmt->bind_param("i", $timetable_id);
            
            if ($stmt->execute()) {
                $success = 'Timetable entry deleted successfully!';
            } else {
                $error = 'Error deleting timetable entry.';
            }
        } elseif ($_POST['action'] === 'bulk_add') {
            // Bulk add timetable for entire week
            $class_id = (int)$_POST['bulk_class_id'];
            $year = sanitize($_POST['bulk_academic_year']);
            $term_val = sanitize($_POST['bulk_term']);
            
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $added_count = 0;
            
            foreach ($days as $day) {
                if (isset($_POST['bulk_' . $day])) {
                    foreach ($_POST['bulk_' . $day] as $period_id => $data) {
                        if (!empty($data['subject_id']) && !empty($data['teacher_id'])) {
                            $subject_id = (int)$data['subject_id'];
                            $teacher_id = (int)$data['teacher_id'];
                            $room = sanitize($data['room'] ?? '');
                            
                            // Check if exists
                            $check = $conn->prepare("SELECT timetable_id FROM timetable WHERE class_id = ? AND day_of_week = ? AND period_id = ? AND academic_year = ? AND term = ?");
                            $check->bind_param("isiss", $class_id, $day, $period_id, $year, $term_val);
                            $check->execute();
                            
                            if ($check->get_result()->num_rows == 0) {
                                $stmt = $conn->prepare("INSERT INTO timetable (class_id, day_of_week, period_id, subject_id, teacher_id, room_number, academic_year, term) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isisissss", $class_id, $day, $period_id, $subject_id, $teacher_id, $room, $year, $term_val);
                                if ($stmt->execute()) {
                                    $added_count++;
                                }
                            }
                        }
                    }
                }
            }
            
            $success = "Added $added_count timetable entries successfully!";
        }
    }
}

// Get selected class for viewing
$selected_class = isset($_GET['class']) ? (int)$_GET['class'] : 0;

// Get all classes
$classes = $conn->query("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric ASC");

// Get all time periods
$periods = $conn->query("SELECT * FROM time_periods WHERE status = 'active' ORDER BY start_time ASC");

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects WHERE status = 'active' ORDER BY subject_name ASC");

// Get all teachers
$teachers = $conn->query("SELECT t.teacher_id, CONCAT(u.first_name, ' ', u.last_name) as teacher_name 
                          FROM teachers t 
                          JOIN users u ON t.user_id = u.user_id 
                          WHERE u.status = 'active' 
                          ORDER BY u.first_name ASC");

// Get timetable for selected class
$timetable_data = [];
if ($selected_class > 0) {
    $timetable_query = $conn->prepare("SELECT t.*, s.subject_name, s.subject_code, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, tp.period_number, tp.period_name, tp.start_time, tp.end_time, tp.is_break
                                       FROM timetable t
                                       JOIN subjects s ON t.subject_id = s.subject_id
                                       JOIN teachers te ON t.teacher_id = te.teacher_id
                                       JOIN users u ON te.user_id = u.user_id
                                       JOIN time_periods tp ON t.period_id = tp.period_id
                                       WHERE t.class_id = ? AND t.academic_year = ? AND t.term = ?
                                       ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tp.start_time");
    $timetable_query->bind_param("iss", $selected_class, $academic_year, $term);
    $timetable_query->execute();
    $result = $timetable_query->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $timetable_data[$row['day_of_week']][$row['period_id']] = $row;
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
            
            <!-- Filters -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 20px;">
                    <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                            <label class="form-label">Select Class</label>
                            <select name="class" class="form-control" onchange="this.form.submit()">
                                <option value="0">-- Select Class --</option>
                                <?php 
                                $classes->data_seek(0);
                                while ($class = $classes->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo $class['class_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0; flex: 1; min-width: 150px;">
                            <label class="form-label">Academic Year</label>
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <?php for ($y = $current_year - 1; $y <= $current_year + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $academic_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0; flex: 1; min-width: 120px;">
                            <label class="form-label">Term</label>
                            <select name="term" class="form-control" onchange="this.form.submit()">
                                <option value="1" <?php echo $term == '1' ? 'selected' : ''; ?>>Term 1</option>
                                <option value="2" <?php echo $term == '2' ? 'selected' : ''; ?>>Term 2</option>
                                <option value="3" <?php echo $term == '3' ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <?php if ($selected_class > 0): ?>
                        <button type="button" class="btn btn-primary" onclick="showModal('addEntryModal')">
                            <i class="fas fa-plus"></i> Add Entry
                        </button>
                        <button type="button" class="btn btn-success" onclick="showModal('bulkAddModal')">
                            <i class="fas fa-calendar-plus"></i> Bulk Add
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if ($selected_class > 0): ?>
            <!-- Timetable Display -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-week"></i> Weekly Timetable</h3>
                    <span class="badge badge-primary">Academic Year: <?php echo $academic_year; ?> | Term: <?php echo $term; ?></span>
                </div>
                <div class="card-body">
                    <div class="timetable-container">
                        <table class="timetable-table">
                            <thead>
                                <tr>
                                    <th class="period-column">Period</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $periods->data_seek(0);
                                while ($period = $periods->fetch_assoc()):
                                    if ($period['is_break']):
                                ?>
                                <tr class="break-row">
                                    <td class="period-cell">
                                        <strong><?php echo $period['period_name']; ?></strong><br>
                                        <small><?php echo date('g:i A', strtotime($period['start_time'])); ?> - <?php echo date('g:i A', strtotime($period['end_time'])); ?></small>
                                    </td>
                                    <td colspan="5" class="break-cell">
                                        <i class="fas fa-coffee"></i> <?php echo $period['period_name']; ?> (<?php echo $period['break_duration']; ?> mins)
                                    </td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td class="period-cell">
                                        <strong><?php echo $period['period_name']; ?></strong><br>
                                        <small><?php echo date('g:i A', strtotime($period['start_time'])); ?> - <?php echo date('g:i A', strtotime($period['end_time'])); ?></small>
                                    </td>
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                    foreach ($days as $day):
                                        $entry = $timetable_data[$day][$period['period_id']] ?? null;
                                    ?>
                                    <td class="subject-cell <?php echo $entry ? 'has-subject' : 'empty-cell'; ?>">
                                        <?php if ($entry): ?>
                                            <div class="subject-info">
                                                <strong><?php echo $entry['subject_name']; ?></strong><br>
                                                <small><i class="fas fa-user"></i> <?php echo $entry['teacher_name']; ?></small><br>
                                                <?php if ($entry['room_number']): ?>
                                                    <small><i class="fas fa-door-open"></i> <?php echo $entry['room_number']; ?></small>
                                                <?php endif; ?>
                                                <div class="subject-actions">
                                                    <button onclick="viewTimetableEntry(<?php echo $entry['timetable_id']; ?>)" class="btn-view" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entry?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="timetable_id" value="<?php echo $entry['timetable_id']; ?>">
                                                        <button type="submit" class="btn-delete" title="Delete Entry">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-slot">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php 
                                    endif;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-calendar-alt" style="font-size: 64px; color: var(--medium-gray); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-light); margin: 0;">Please select a class to view/manage timetable</h3>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Entry Modal -->
<div id="addEntryModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add Timetable Entry</h3>
            <button class="modal-close" onclick="hideModal('addEntryModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                <input type="hidden" name="academic_year" value="<?php echo $academic_year; ?>">
                <input type="hidden" name="term" value="<?php echo $term; ?>">
                
                <div class="form-group">
                    <label class="form-label">Day of Week *</label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Period *</label>
                    <select name="period_id" class="form-control" required>
                        <option value="">Select Period</option>
                        <?php 
                        $periods->data_seek(0);
                        while ($period = $periods->fetch_assoc()): 
                            if (!$period['is_break']):
                        ?>
                            <option value="<?php echo $period['period_id']; ?>">
                                <?php echo $period['period_name']; ?> (<?php echo date('g:i A', strtotime($period['start_time'])); ?> - <?php echo date('g:i A', strtotime($period['end_time'])); ?>)
                            </option>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject *</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        <?php 
                        $subjects->data_seek(0);
                        while ($subject = $subjects->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $subject['subject_id']; ?>"><?php echo $subject['subject_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teacher *</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">Select Teacher</option>
                        <?php 
                        $teachers->data_seek(0);
                        while ($teacher = $teachers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['teacher_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g., Room 101">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addEntryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Entry</button>
            </div>
        </form>
    </div>
</div>

<style>
.timetable-container {
    overflow-x: auto;
}

.timetable-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.timetable-table th,
.timetable-table td {
    border: 1px solid var(--border-color);
    padding: 12px;
    text-align: center;
}

.timetable-table thead th {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
    font-weight: 600;
    padding: 15px;
}

.period-column {
    width: 150px;
}

.period-cell {
    background: var(--light-gray);
    font-weight: 600;
    vertical-align: middle;
}

.period-cell small {
    color: var(--text-light);
    font-weight: normal;
}

.break-row {
    background: #fef3c7;
}

.break-cell {
    text-align: center;
    font-weight: 600;
    color: #92400e;
    padding: 15px;
}

.subject-cell {
    vertical-align: top;
    position: relative;
    min-height: 80px;
}

.subject-cell.has-subject {
    background: #f0f9ff;
    cursor: pointer;
    transition: all 0.2s;
}

.subject-cell.has-subject:hover {
    background: #e0f2fe;
    transform: scale(1.02);
}

.subject-cell.empty-cell {
    background: #f9fafb;
}

.empty-slot {
    color: var(--medium-gray);
    font-size: 18px;
}

.subject-info {
    text-align: left;
}

.subject-info strong {
    color: var(--primary-color);
    font-size: 14px;
}

.subject-info small {
    display: block;
    color: var(--text-light);
    margin-top: 3px;
    font-size: 12px;
}

.subject-actions {
    position: absolute;
    top: 5px;
    right: 5px;
}

.btn-view {
    background: var(--info-color);
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    opacity: 0.7;
    transition: all 0.2s;
    margin-right: 5px;
}

.btn-view:hover {
    opacity: 1;
    transform: scale(1.1);
}

.btn-delete {
    background: var(--danger-color);
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    opacity: 0.7;
    transition: all 0.2s;
}

.btn-delete:hover {
    opacity: 1;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .timetable-container {
        overflow-x: scroll;
    }
}

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
</style>

<!-- View Timetable Entry Modal -->
<div id="viewTimetableModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
            <h3><i class="fas fa-calendar-alt"></i> Timetable Entry Details</h3>
            <button class="modal-close" onclick="hideModal('viewTimetableModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="timetableDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #6366f1;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading timetable details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewTimetableModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewTimetableEntry(timetableId) {
    showModal('viewTimetableModal');
    
    // Fetch timetable entry details via AJAX
    fetch('get_timetable_details.php?timetable_id=' + timetableId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const entry = data.timetable;
                document.getElementById('timetableDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4 style="color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-clock"></i> Schedule Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Day:</th><td><strong>${entry.day_of_week}</strong></td></tr>
                                <tr><th>Period:</th><td>Period ${entry.period_number} - ${entry.period_name}</td></tr>
                                <tr><th>Time:</th><td>${entry.start_time_formatted} - ${entry.end_time_formatted}</td></tr>
                                <tr><th>Academic Year:</th><td>${entry.academic_year}</td></tr>
                                <tr><th>Term:</th><td>Term ${entry.term}</td></tr>
                                ${entry.room_number ? `<tr><th>Room:</th><td><strong>${entry.room_number}</strong></td></tr>` : ''}
                            </table>
                        </div>
                        <div>
                            <h4 style="color: #8b5cf6; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-graduation-cap"></i> Class Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Class:</th><td><strong>${entry.class_name}</strong></td></tr>
                                ${entry.section ? `<tr><th>Section:</th><td>${entry.section}</td></tr>` : ''}
                                ${entry.class_room ? `<tr><th>Class Room:</th><td>${entry.class_room}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-book"></i> Subject Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Subject Name:</th><td><strong>${entry.subject_name}</strong></td></tr>
                                <tr><th>Subject Code:</th><td>${entry.subject_code}</td></tr>
                                <tr><th>Credit Hours:</th><td>${entry.credit_hours} hours</td></tr>
                                ${entry.subject_description ? `<tr><th>Description:</th><td>${entry.subject_description}</td></tr>` : ''}
                            </table>
                        </div>
                        <div>
                            <h4 style="color: #f59e0b; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; margin-bottom: 15px;">
                                <i class="fas fa-chalkboard-teacher"></i> Teacher Information
                            </h4>
                            <table class="info-table">
                                <tr><th>Teacher Name:</th><td><strong>${entry.teacher_name}</strong></td></tr>
                                <tr><th>Employee ID:</th><td>${entry.employee_id}</td></tr>
                                ${entry.department ? `<tr><th>Department:</th><td>${entry.department}</td></tr>` : ''}
                                ${entry.teacher_email ? `<tr><th>Email:</th><td>${entry.teacher_email}</td></tr>` : ''}
                                ${entry.teacher_phone ? `<tr><th>Phone:</th><td>${entry.teacher_phone}</td></tr>`