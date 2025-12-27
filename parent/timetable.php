<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'Children Timetable';

// Get parent's children
$children_query = "SELECT s.student_id, s.class_id, CONCAT(u.first_name, ' ', u.last_name) as name, c.class_name, c.section
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN classes c ON s.class_id = c.class_id
                  WHERE s.parent_id = " . $_SESSION['user_id'] . "
                  ORDER BY c.class_numeric ASC, u.first_name ASC";
$children_result = $conn->query($children_query);

// Get selected student or default to first child
$selected_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$selected_student = null;
$class_id = null;

if ($selected_student_id > 0) {
    $check_query = $conn->prepare("SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as name, c.class_name, c.section, c.class_id
                                   FROM students s 
                                   JOIN users u ON s.user_id = u.user_id
                                   LEFT JOIN classes c ON s.class_id = c.class_id
                                   WHERE s.student_id = ? AND s.parent_id = ?");
    $check_query->bind_param("ii", $selected_student_id, $_SESSION['user_id']);
    $check_query->execute();
    $selected_student = $check_query->get_result()->fetch_assoc();
    if ($selected_student) {
        $class_id = $selected_student['class_id'];
    }
}

// If no student selected or invalid, use first child
if (!$selected_student && $children_result->num_rows > 0) {
    $children_result->data_seek(0);
    $first_child = $children_result->fetch_assoc();
    $selected_student_id = $first_child['student_id'];
    $selected_student = $first_child;
    $class_id = $first_child['class_id'];
}

$current_year = date('Y');
$term = '1';

// Get all time periods
$periods = $conn->query("SELECT * FROM time_periods WHERE status = 'active' ORDER BY start_time ASC");
if (!$periods) {
    $error = 'Time periods table not found. Please contact administrator.';
    $periods = $conn->query("SELECT 1 WHERE 1=0");
}

// Get timetable for selected student's class
$timetable_data = [];
if ($class_id) {
    $timetable_query = $conn->prepare("SELECT t.*, s.subject_name, s.subject_code, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, tp.period_number, tp.period_name, tp.start_time, tp.end_time, tp.is_break
                                       FROM timetable t
                                       JOIN subjects s ON t.subject_id = s.subject_id
                                       JOIN teachers te ON t.teacher_id = te.teacher_id
                                       JOIN users u ON te.user_id = u.user_id
                                       JOIN time_periods tp ON t.period_id = tp.period_id
                                       WHERE t.class_id = ? AND t.academic_year = ? AND t.term = ?
                                       ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tp.start_time");
    $timetable_query->bind_param("iss", $class_id, $current_year, $term);
    $timetable_query->execute();
    $result = $timetable_query->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $timetable_data[$row['day_of_week']][$row['period_id']] = $row;
    }
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-parent.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Student Selector -->
            <?php if ($children_result->num_rows > 1): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <label class="form-label"><strong>Select Child:</strong></label>
                    <select class="form-control" onchange="window.location.href='timetable.php?student_id=' + this.value" style="max-width: 400px;">
                        <?php 
                        $children_result->data_seek(0);
                        while ($child = $children_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $child['student_id']; ?>" <?php echo ($selected_student_id == $child['student_id']) ? 'selected' : ''; ?>>
                                <?php echo $child['name'] . ' - ' . ($child['class_name'] ? $child['class_name'] . ($child['section'] ? ' - ' . $child['section'] : '') : 'Not Assigned'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($selected_student): ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);">
                    <div class="card-body">
                        <h3 style="margin: 0; color: var(--primary-color);">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo $selected_student['name']; ?>
                            <?php if ($selected_student['class_name']): ?>
                                <span class="badge badge-primary" style="margin-left: 10px;">
                                    <?php echo $selected_student['class_name'] . ($selected_student['section'] ? ' - ' . $selected_student['section'] : ''); ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-week"></i> Weekly Timetable</h3>
                        <div>
                            <?php if ($selected_student['class_name']): ?>
                                <span class="badge badge-primary"><?php echo $selected_student['class_name']; ?></span>
                            <?php endif; ?>
                            <span class="badge badge-success">Academic Year: <?php echo $current_year; ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($class_id): ?>
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
                        <?php else: ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                            No class assigned to this student
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p style="text-align: center; color: var(--text-light); padding: 60px 20px;">
                            <i class="fas fa-info-circle" style="font-size: 64px; display: block; margin-bottom: 20px; opacity: 0.3;"></i>
                            <strong style="font-size: 20px; display: block; margin-bottom: 10px;">No children linked to your account</strong>
                            Please contact the school administration to link your child's account.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
    min-height: 80px;
}

.subject-cell.has-subject {
    background: #f0f9ff;
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

@media (max-width: 768px) {
    .timetable-container {
        overflow-x: scroll;
    }
}
</style>

<?php include '../includes/footer.php'; ?>


