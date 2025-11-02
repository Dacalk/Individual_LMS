<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Return Book';
$error = '';
$success = '';

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    
    // Get transaction details
    $trans_query = $conn->query("SELECT * FROM library_transactions WHERE transaction_id = $transaction_id AND status = 'issued'");
    $transaction = $trans_query->fetch_assoc();
    
    if (!$transaction) {
        $error = 'Invalid transaction or book already returned!';
    } else {
        $return_date = date('Y-m-d');
        $book_id = $transaction['book_id'];
        
        // Calculate fine if overdue
        $fine_amount = 0;
        $due_date = strtotime($transaction['due_date']);
        $return_timestamp = strtotime($return_date);
        
        if ($return_timestamp > $due_date) {
            // Fine: $1 per day overdue
            $days_overdue = ceil(($return_timestamp - $due_date) / (60 * 60 * 24));
            $fine_amount = $days_overdue * 1; // $1 per day
        }
        
        // Update transaction
        $stmt = $conn->prepare("UPDATE library_transactions SET status = 'returned', return_date = ?, fine_amount = ? WHERE transaction_id = ?");
        $stmt->bind_param("sdi", $return_date, $fine_amount, $transaction_id);
        
        if ($stmt->execute()) {
            // Update available quantity
            $conn->query("UPDATE library_books SET available_quantity = available_quantity + 1 WHERE book_id = $book_id");
            
            if ($fine_amount > 0) {
                $success = "Book returned successfully! Late fee: $" . number_format($fine_amount, 2);
            } else {
                $success = 'Book returned successfully!';
            }
        } else {
            $error = 'Error returning book. Please try again.';
        }
    }
}

// Get all issued books
$issued_books = $conn->query("SELECT lt.*, lb.title, lb.isbn, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
                               FROM library_transactions lt
                               JOIN library_books lb ON lt.book_id = lb.book_id
                               JOIN users u ON lt.user_id = u.user_id
                               WHERE lt.status = 'issued'
                               ORDER BY lt.due_date ASC");

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
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-undo"></i> Return Book</h3>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by book title, borrower name, or ISBN..." onkeyup="searchTable('searchInput', 'issuedBooksTable')">
                    </div>
                    
                    <?php if ($issued_books->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table" id="issuedBooksTable">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>ISBN</th>
                                    <th>Borrower</th>
                                    <th>Role</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Days Left</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($issue = $issued_books->fetch_assoc()): 
                                    $today = time();
                                    $due_date = strtotime($issue['due_date']);
                                    $days_diff = ceil(($due_date - $today) / (60 * 60 * 24));
                                    $is_overdue = $days_diff < 0;
                                    $status_class = $is_overdue ? 'danger' : ($days_diff <= 3 ? 'warning' : 'success');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($issue['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($issue['isbn']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['user_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($issue['role']); ?></span></td>
                                    <td><?php echo formatDate($issue['issue_date']); ?></td>
                                    <td><?php echo formatDate($issue['due_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php 
                                            if ($is_overdue) {
                                                echo abs($days_diff) . ' day(s) overdue';
                                            } else {
                                                echo $days_diff . ' day(s) left';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Overdue
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> On Time
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Confirm book return?')">
                                            <input type="hidden" name="transaction_id" value="<?php echo $issue['transaction_id']; ?>">
                                            <button type="submit" name="return_book" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Return
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No books currently issued
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards" style="margin-top: 24px;">
                <?php
                // Get statistics
                $total_issued = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued'")->fetch_assoc()['count'];
                $overdue_count = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued' AND due_date < CURDATE()")->fetch_assoc()['count'];
                $due_soon = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)")->fetch_assoc()['count'];
                $returned_today = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'returned' AND return_date = CURDATE()")->fetch_assoc()['count'];
                ?>
                
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $total_issued; ?></h3>
                        <p>Currently Issued</p>
                    </div>
                </div>
                
                <div class="dashboard-card danger">
                    <div class="card-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $overdue_count; ?></h3>
                        <p>Overdue Books</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $due_soon; ?></h3>
                        <p>Due in 3 Days</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $returned_today; ?></h3>
                        <p>Returned Today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

