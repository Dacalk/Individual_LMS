<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'All Transactions';

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT lt.*, lb.title, lb.isbn, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role,
          CONCAT(issued_by_user.first_name, ' ', issued_by_user.last_name) as issued_by_name
          FROM library_transactions lt
          JOIN library_books lb ON lt.book_id = lb.book_id
          JOIN users u ON lt.user_id = u.user_id
          LEFT JOIN users issued_by_user ON lt.issued_by = issued_by_user.user_id
          WHERE 1=1";

if ($status_filter !== 'all') {
    if ($status_filter === 'overdue') {
        $query .= " AND lt.status = 'issued' AND lt.due_date < CURDATE()";
    } else {
        $query .= " AND lt.status = '$status_filter'";
    }
}

if ($date_filter) {
    $query .= " AND DATE(lt.issue_date) = '$date_filter'";
}

if ($search) {
    $query .= " AND (lb.title LIKE '%$search%' OR lb.isbn LIKE '%$search%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
}

$query .= " ORDER BY lt.created_at DESC";

$transactions = $conn->query($query);

// Get statistics
$total_transactions = $conn->query("SELECT COUNT(*) as count FROM library_transactions")->fetch_assoc()['count'];
$issued_count = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued'")->fetch_assoc()['count'];
$returned_count = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'returned'")->fetch_assoc()['count'];
$overdue_count = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued' AND due_date < CURDATE()")->fetch_assoc()['count'];

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-librarian.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $total_transactions; ?></h3>
                        <p>Total Transactions</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $issued_count; ?></h3>
                        <p>Currently Issued</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $returned_count; ?></h3>
                        <p>Returned Books</p>
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
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Transaction History</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 5px;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Book, ISBN, or User..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 5px;">Status</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="issued" <?php echo $status_filter === 'issued' ? 'selected' : ''; ?>>Issued</option>
                                <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 5px;">Issue Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        
                        <div style="display: flex; align-items: flex-end; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="transactions.php" class="btn btn-outline" style="flex: 1;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                    
                    <?php if ($transactions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Book Title</th>
                                    <th>ISBN</th>
                                    <th>Borrower</th>
                                    <th>Role</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Issued By</th>
                                    <th>Fine</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($trans = $transactions->fetch_assoc()): 
                                    $is_overdue = ($trans['status'] === 'issued' && strtotime($trans['due_date']) < time());
                                    $status_class = $trans['status'] === 'returned' ? 'success' : ($is_overdue ? 'danger' : 'warning');
                                    $status_text = $is_overdue ? 'Overdue' : ucfirst($trans['status']);
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $trans['transaction_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($trans['title']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['isbn']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['user_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($trans['role']); ?></span></td>
                                    <td><?php echo formatDate($trans['issue_date']); ?></td>
                                    <td><?php echo formatDate($trans['due_date']); ?></td>
                                    <td><?php echo $trans['return_date'] ? formatDate($trans['return_date']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($trans['issued_by_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($trans['fine_amount'] > 0): ?>
                                            <span class="badge badge-danger">$<?php echo number_format($trans['fine_amount'], 2); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No transactions found
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

