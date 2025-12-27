<?php
require_once '../config/config.php';

if (!hasRole('parent')) {
    redirect('index.php');
}

$page_title = 'My Children';

// Get all children linked to this parent
$children_query = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.date_of_birth, u.gender,
                  c.class_name, c.section, c.academic_year
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN classes c ON s.class_id = c.class_id
                  WHERE s.parent_id = " . $_SESSION['user_id'] . "
                  ORDER BY c.class_numeric ASC, u.first_name ASC";
$children_result = $conn->query($children_query);

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-parent.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-child"></i> My Children</h3>
                </div>
                <div class="card-body">
                    <?php if ($children_result->num_rows > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php while ($child = $children_result->fetch_assoc()): ?>
                            <div class="card" style="border: 2px solid var(--border-color);">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: white;">
                                    <h3 style="margin: 0; color: white;">
                                        <i class="fas fa-user-graduate"></i> 
                                        <?php echo $child['first_name'] . ' ' . $child['last_name']; ?>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <table class="info-table" style="width: 100%;">
                                        <tr>
                                            <th>Admission Number:</th>
                                            <td><strong><?php echo $child['admission_number']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Class:</th>
                                            <td>
                                                <?php if ($child['class_name']): ?>
                                                    <span class="badge badge-primary">
                                                        <?php echo $child['class_name'] . ($child['section'] ? ' - ' . $child['section'] : ''); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if ($child['roll_number']): ?>
                                        <tr>
                                            <th>Roll Number:</th>
                                            <td><?php echo $child['roll_number']; ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?php echo $child['email']; ?></td>
                                        </tr>
                                        <?php if ($child['phone']): ?>
                                        <tr>
                                            <th>Phone:</th>
                                            <td><?php echo $child['phone']; ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($child['date_of_birth']): ?>
                                        <tr>
                                            <th>Date of Birth:</th>
                                            <td><?php echo formatDate($child['date_of_birth']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($child['gender']): ?>
                                        <tr>
                                            <th>Gender:</th>
                                            <td><?php echo ucfirst($child['gender']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                    
                                    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                                        <a href="attendance.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-check"></i> Attendance
                                        </a>
                                        <a href="grades.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-trophy"></i> Grades
                                        </a>
                                        <a href="timetable.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-calendar-alt"></i> Timetable
                                        </a>
                                        <!-- Fees button removed -->
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                            <i class="fas fa-info-circle" style="font-size: 64px; display: block; margin-bottom: 20px; opacity: 0.3;"></i>
                            <h3 style="margin-bottom: 10px;">No Children Linked</h3>
                            <p>Please contact the school administration to link your child's account to your parent account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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


