<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Overdue Books';
$error = '';
$success = '';

// Handle sending reminder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $user_id = (int)$_POST['user_id'];
    
    // Create a notification for the user
    $title = "Library Book Overdue Reminder";
    $message = "You have an overdue library book. Please return it as soon as possible to avoid additional fines.";
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (title, message, notification_type, target_role, created_by, is_active) VALUES (?, ?, 'urgent', NULL, ?, 1)");
    $notif_stmt->bind_param("ssi", $title, $message, $_SESSION['user_id']);
    
    if ($notif_stmt->execute()) {
        $notification_id = $conn->insert_id;
        
        // Mark notification for specific user
        $read_stmt = $conn->prepare("INSERT INTO notification_reads (notification_id, user_id) VALUES (?, ?)");
        $read_stmt->bind_param("ii", $notification_id, $user_id);
        $read_stmt->execute();
        
        $success = 'Reminder sent successfully!';
    } else {
        $error = 'Error sending reminder.';
    }
}

// Get all overdue books
$overdue_books = $conn->query("SELECT lt.*, lb.title, lb.isbn, CONCAT(u.first_name, ' ', u.last_name) as user_name, 
                                u.email, u.phone, u.role,
                                DATEDIFF(CURDATE(), lt.due_date) as days_overdue,
                                (DATEDIFF(CURDATE(), lt.due_date) * 1) as calculated_fine
                                FROM library_transactions lt
                                JOIN library_books lb ON lt.book_id = lb.book_id
                                JOIN users u ON lt.user_id = u.user_id
                                WHERE lt.status = 'issued' AND lt.due_date < CURDATE()
                                ORDER BY lt.due_date ASC");

// Calculate total fines
$total_fines = 0;
$overdue_books_temp = $conn->query("SELECT lt.*, DATEDIFF(CURDATE(), lt.due_date) as days_overdue
                                    FROM library_transactions lt
                                    WHERE lt.status = 'issued' AND lt.due_date < CURDATE()");
while ($book = $overdue_books_temp->fetch_assoc()) {
    $total_fines += $book['days_overdue'] * 1; // $1 per day
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-librarian.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card danger">
                    <div class="card-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $overdue_books->num_rows; ?></h3>
                        <p>Overdue Books</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="card-content">
                        <h3>$<?php echo number_format($total_fines, 2); ?></h3>
                        <p>Total Pending Fines</p>
                    </div>
                </div>
                
                <?php
                $very_overdue = $conn->query("SELECT COUNT(*) as count FROM library_transactions 
                                              WHERE status = 'issued' AND DATEDIFF(CURDATE(), due_date) > 7")->fetch_assoc()['count'];
                ?>
                <div class="dashboard-card info">
                    <div class="card-icon info">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $very_overdue; ?></h3>
                        <p>Over 7 Days Late</p>
                    </div>
                </div>
                
                <?php
                $unique_borrowers = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM library_transactions 
                                                  WHERE status = 'issued' AND due_date < CURDATE()")->fetch_assoc()['count'];
                ?>
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $unique_borrowers; ?></h3>
                        <p>Unique Borrowers</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Overdue Books Management</h3>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by book title, borrower name, or ISBN..." onkeyup="searchTable('searchInput', 'overdueTable')">
                    </div>
                    
                    <?php if ($overdue_books->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table" id="overdueTable">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>ISBN</th>
                                    <th>Borrower</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Fine Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset the result pointer
                                $overdue_books->data_seek(0);
                                while ($book = $overdue_books->fetch_assoc()): 
                                    $severity_class = $book['days_overdue'] > 14 ? 'danger' : ($book['days_overdue'] > 7 ? 'warning' : 'info');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><?php echo htmlspecialchars($book['user_name']); ?></td>
                                    <td>
                                        <small>
                                            <?php if ($book['email']): ?>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($book['email']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($book['phone']): ?>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($book['phone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($book['role']); ?></span></td>
                                    <td><?php echo formatDate($book['due_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $severity_class; ?>">
                                            <?php echo $book['days_overdue']; ?> day(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger">
                                            $<?php echo number_format($book['calculated_fine'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo $book['transaction_id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $book['user_id']; ?>">
                                            <button type="submit" name="send_reminder" class="btn btn-sm btn-warning" title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </form>
                                        <a href="return-book.php" class="btn btn-sm btn-success" title="Process Return">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Summary Section -->
                    <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: white;">
                        <h4 style="margin: 0 0 15px 0; color: white;"><i class="fas fa-chart-line"></i> Overdue Summary</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <div style="font-size: 28px; font-weight: bold;"><?php echo $overdue_books->num_rows; ?></div>
                                <div style="opacity: 0.9;">Total Overdue Books</div>
                            </div>
                            <div>
                                <div style="font-size: 28px; font-weight: bold;">$<?php echo number_format($total_fines, 2); ?></div>
                                <div style="opacity: 0.9;">Total Fines ($1/day)</div>
                            </div>
                            <div>
                                <div style="font-size: 28px; font-weight: bold;">$<?php echo $overdue_books->num_rows > 0 ? number_format($total_fines / $overdue_books->num_rows, 2) : '0.00'; ?></div>
                                <div style="opacity: 0.9;">Average Fine per Book</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-check-circle" style="font-size: 48px; display: block; margin-bottom: 15px; color: var(--success-color);"></i>
                        <strong style="font-size: 18px;">Great! No overdue books</strong><br>
                        All books have been returned on time or are still within their due date.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

