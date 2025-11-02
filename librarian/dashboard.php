<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Library Dashboard';

// Get statistics
$stats = [
    'total_books' => 0,
    'available_books' => 0,
    'issued_books' => 0,
    'overdue_books' => 0,
];

// Total books
$result = $conn->query("SELECT SUM(quantity) as count FROM library_books WHERE status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['total_books'] = $row['count'] ?? 0;
}

// Available books
$result = $conn->query("SELECT SUM(available_quantity) as count FROM library_books WHERE status = 'active'");
if ($row = $result->fetch_assoc()) {
    $stats['available_books'] = $row['count'] ?? 0;
}

// Issued books
$result = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued'");
if ($row = $result->fetch_assoc()) {
    $stats['issued_books'] = $row['count'];
}

// Overdue books
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as count FROM library_transactions WHERE status = 'issued' AND due_date < '$today'");
if ($row = $result->fetch_assoc()) {
    $stats['overdue_books'] = $row['count'];
}

// Recent transactions
$recent_transactions = $conn->query("SELECT lt.*, lb.title, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
                                     FROM library_transactions lt
                                     JOIN library_books lb ON lt.book_id = lb.book_id
                                     JOIN users u ON lt.user_id = u.user_id
                                     ORDER BY lt.created_at DESC LIMIT 10");

// Popular books
$popular_books = $conn->query("SELECT lb.*, COUNT(lt.transaction_id) as issue_count
                               FROM library_books lb
                               LEFT JOIN library_transactions lt ON lb.book_id = lt.book_id
                               WHERE lb.status = 'active'
                               GROUP BY lb.book_id
                               ORDER BY issue_count DESC LIMIT 5");

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-librarian.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="content">
            <!-- Welcome Message -->
            <div class="card" style="background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; margin-bottom: 24px;">
                <div class="card-body" style="padding: 30px;">
                    <h2 style="margin: 0 0 10px 0;">Welcome to Library Management, <?php echo $_SESSION['first_name']; ?>! 📚</h2>
                    <p style="margin: 0; opacity: 0.9;">Manage books, track transactions, and serve students effectively</p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card primary">
                    <div class="card-icon primary">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['total_books']; ?></h3>
                        <p>Total Books</p>
                    </div>
                </div>
                
                <div class="dashboard-card success">
                    <div class="card-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['available_books']; ?></h3>
                        <p>Available Books</p>
                    </div>
                </div>
                
                <div class="dashboard-card warning">
                    <div class="card-icon warning">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['issued_books']; ?></h3>
                        <p>Issued Books</p>
                    </div>
                </div>
                
                <div class="dashboard-card danger">
                    <div class="card-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $stats['overdue_books']; ?></h3>
                        <p>Overdue Books</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="books.php?action=add" class="btn btn-primary" style="padding: 15px;">
                            <i class="fas fa-plus"></i> Add New Book
                        </a>
                        <a href="issue-book.php" class="btn btn-success" style="padding: 15px;">
                            <i class="fas fa-hand-holding"></i> Issue Book
                        </a>
                        <a href="return-book.php" class="btn btn-warning" style="padding: 15px;">
                            <i class="fas fa-undo"></i> Return Book
                        </a>
                        <a href="overdue.php" class="btn btn-danger" style="padding: 15px;">
                            <i class="fas fa-exclamation-triangle"></i> View Overdue
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                    <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_transactions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Borrower</th>
                                    <th>Role</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($trans = $recent_transactions->fetch_assoc()): 
                                    $is_overdue = ($trans['status'] === 'issued' && strtotime($trans['due_date']) < time());
                                    $status_class = $trans['status'] === 'returned' ? 'success' : ($is_overdue ? 'danger' : 'warning');
                                ?>
                                <tr>
                                    <td><strong><?php echo $trans['title']; ?></strong></td>
                                    <td><?php echo $trans['user_name']; ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($trans['role']); ?></span></td>
                                    <td><?php echo formatDate($trans['issue_date']); ?></td>
                                    <td><?php echo formatDate($trans['due_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo $is_overdue ? 'Overdue' : ucfirst($trans['status']); ?>
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
                        No transactions yet
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Popular Books -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-star"></i> Most Popular Books</h3>
                </div>
                <div class="card-body">
                    <?php if ($popular_books->num_rows > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                        <?php while ($book = $popular_books->fetch_assoc()): ?>
                        <div class="card" style="border: 2px solid var(--border-color); margin-bottom: 0;">
                            <div class="card-body">
                                <h4 style="color: var(--primary-color); margin-bottom: 10px; font-size: 16px;">
                                    <?php echo $book['title']; ?>
                                </h4>
                                <p style="color: var(--text-light); margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-user"></i> <?php echo $book['author']; ?>
                                </p>
                                <p style="color: var(--text-light); margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-hashtag"></i> <?php echo $book['isbn']; ?>
                                </p>
                                <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                                    <span class="badge badge-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $book['available_quantity']; ?> / <?php echo $book['quantity']; ?> Available
                                    </span>
                                    <span class="badge badge-primary">
                                        <?php echo $book['issue_count']; ?> Issues
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        No books in library yet
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>





