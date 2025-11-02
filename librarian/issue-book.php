<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Issue Book';
$error = '';
$success = '';

// Handle book issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
    $book_id = (int)$_POST['book_id'];
    $user_id = (int)$_POST['user_id'];
    $issue_days = (int)$_POST['issue_days'];
    
    // Check if book is available
    $book_check = $conn->query("SELECT available_quantity FROM library_books WHERE book_id = $book_id");
    $book = $book_check->fetch_assoc();
    
    if ($book['available_quantity'] <= 0) {
        $error = 'This book is not available for issue!';
    } else {
        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+$issue_days days"));
        
        // Insert transaction
        $stmt = $conn->prepare("INSERT INTO library_transactions (book_id, user_id, issue_date, due_date, issued_by, status) VALUES (?, ?, ?, ?, ?, 'issued')");
        $stmt->bind_param("iissi", $book_id, $user_id, $issue_date, $due_date, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Update available quantity
            $conn->query("UPDATE library_books SET available_quantity = available_quantity - 1 WHERE book_id = $book_id");
            $success = 'Book issued successfully!';
        } else {
            $error = 'Error issuing book. Please try again.';
        }
    }
}

// Get available books
$books = $conn->query("SELECT * FROM library_books WHERE status = 'active' AND available_quantity > 0 ORDER BY title ASC");

// Get users (students and teachers)
$users = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) as name, role FROM users WHERE role IN ('student', 'teacher') AND status = 'active' ORDER BY first_name ASC");

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
                    <h3><i class="fas fa-hand-holding"></i> Issue Book to User</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Select Book *</label>
                            <select name="book_id" class="form-control" required>
                                <option value="">Choose a book</option>
                                <?php while ($book = $books->fetch_assoc()): ?>
                                    <option value="<?php echo $book['book_id']; ?>">
                                        <?php echo $book['title']; ?> by <?php echo $book['author']; ?> 
                                        (Available: <?php echo $book['available_quantity']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Select User *</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">Choose user (Student/Teacher)</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['user_id']; ?>">
                                        <?php echo $user['name']; ?> (<?php echo ucfirst($user['role']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Issue Duration (Days) *</label>
                            <input type="number" name="issue_days" class="form-control" value="14" min="1" max="90" required>
                            <small style="color: var(--text-light);">Default: 14 days</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="issue_book" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Issue Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recently Issued Books -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recently Issued Books</h3>
                </div>
                <div class="card-body">
                    <?php
                    $recent_issues = $conn->query("SELECT lt.*, lb.title, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
                                                    FROM library_transactions lt
                                                    JOIN library_books lb ON lt.book_id = lb.book_id
                                                    JOIN users u ON lt.user_id = u.user_id
                                                    WHERE lt.status = 'issued'
                                                    ORDER BY lt.created_at DESC LIMIT 10");
                    ?>
                    
                    <?php if ($recent_issues->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Issued To</th>
                                    <th>Role</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($issue = $recent_issues->fetch_assoc()): 
                                    $is_overdue = strtotime($issue['due_date']) < time();
                                ?>
                                <tr>
                                    <td><strong><?php echo $issue['title']; ?></strong></td>
                                    <td><?php echo $issue['user_name']; ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($issue['role']); ?></span></td>
                                    <td><?php echo formatDate($issue['issue_date']); ?></td>
                                    <td><?php echo formatDate($issue['due_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $is_overdue ? 'danger' : 'warning'; ?>">
                                            <?php echo $is_overdue ? 'Overdue' : 'Issued'; ?>
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
                        No books currently issued
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>





