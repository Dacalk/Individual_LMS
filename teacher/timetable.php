<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'My Timetable';

// Get teacher ID
$teacher_query = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$teacher_query->bind_param("i", $_SESSION['user_id']);
$teacher_query->execute();
$teacher_result = $teacher_query->get_result();
$teacher_data = $teacher_result->fetch_assoc();

if (!$teacher_data) {
    redirect('dashboard.php');
}

$teacher_id = $teacher_data['teacher_id'];
$current_year = date('Y');
$term = '1'; // You can make this dynamic

// Get all time periods
$periods = $conn->query("SELECT * FROM time_periods WHERE status = 'active' ORDER BY start_time ASC");

// Get timetable for teacher
$timetable_data = [];
$timetable_query = $conn->prepare("SELECT t.*, s.subject_name, s.subject_code, c.class_name, tp.period_number, tp.period_name, tp.start_time, tp.end_time, tp.is_break
                                   FROM timetable t
                                   JOIN subjects s ON t.subject_id = s.subject_id
                                   JOIN classes c ON t.class_id = c.class_id
                                   JOIN time_periods tp ON t.period_id = tp.period_id
                                   WHERE t.teacher_id = ? AND t.academic_year = ? AND t.term = ?
                                   ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tp.start_time");
$timetable_query->bind_param("iss", $teacher_id, $current_year, $term);
$timetable_query->execute();
$result = $timetable_query->get_result();

while ($row = $result->fetch_assoc()) {
    $timetable_data[$row['day_of_week']][$row['period_id']] = $row;
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-week"></i> My Teaching Schedule</h3>
                    <span class="badge badge-success">Academic Year: <?php echo $current_year; ?> | Term: <?php echo $term; ?></span>
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
                                    <td class="subject-cell <?php echo $entry ? 'has-subject teacher-cell' : 'free-cell'; ?>">
                                        <?php if ($entry): ?>
                                            <div class="subject-info">
                                                <strong><?php echo $entry['subject_name']; ?></strong><br>
                                                <small><i class="fas fa-school"></i> <?php echo $entry['class_name']; ?></small><br>
                                                <?php if ($entry['room_number']): ?>
                                                    <small><i class="fas fa-door-open"></i> <?php echo $entry['room_number']; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="free-period">Free</span>
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
                    
                    <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div style="padding: 20px; background: var(--light-gray); border-radius: 8px;">
                            <h4><i class="fas fa-chalkboard-teacher"></i> Teaching Summary</h4>
                            <?php
                            // Count total classes
                            $total_classes = count(array_reduce($timetable_data, 'array_merge', []));
                            echo "<p style='margin: 10px 0 0 0;'><strong>Total Classes:</strong> $total_classes per week</p>";
                            ?>
                        </div>
                        
                        <div style="padding: 20px; background: var(--light-gray); border-radius: 8px;">
                            <h4><i class="fas fa-info-circle"></i> Reminders</h4>
                            <ul style="margin: 10px 0 0 20px;">
                                <li>Prepare lesson plans in advance</li>
                                <li>Check attendance for each class</li>
                                <li>Update gradebook regularly</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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

.subject-cell.teacher-cell {
    background: #dcfce7;
}

.subject-cell.free-cell {
    background: #f9fafb;
}

.free-period {
    color: var(--medium-gray);
    font-style: italic;
}

.subject-info {
    text-align: left;
}

.subject-info strong {
    color: #15803d;
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




