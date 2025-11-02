<?php
require_once '../config/config.php';

if (!hasRole('teacher')) {
    redirect('index.php');
}

$page_title = 'Library';
$error = '';
$success = '';

$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'issued';
$search_term = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build books query
$books_sql = "SELECT * FROM library_books WHERE status = 'active'";

if ($search_term !== '') {
    $books_sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR description LIKE ?)";
}

$books_sql .= " ORDER BY title ASC";

if ($search_term !== '') {
    $books_stmt = $conn->prepare($books_sql);
    $pattern = '%' . $search_term . '%';
    $books_stmt->bind_param('ssss', $pattern, $pattern, $pattern, $pattern);
    $books_stmt->execute();
    $books = $books_stmt->get_result();
} else {
    $books = $conn->query($books_sql);
}

// Issued books
$issued_stmt = $conn->prepare("SELECT lt.*, lb.title, lb.author, lb.isbn
                               FROM library_transactions lt
                               JOIN library_books lb ON lt.book_id = lb.book_id
                               WHERE lt.user_id = ? AND lt.status IN ('issued', 'overdue')
                               ORDER BY lt.due_date ASC");
$issued_stmt->bind_param("i", $_SESSION['user_id']);
$issued_stmt->execute();
$issued_books = $issued_stmt->get_result();

// History
$history_stmt = $conn->prepare("SELECT lt.*, lb.title, lb.author, lb.isbn
                                FROM library_transactions lt
                                JOIN library_books lb ON lt.book_id = lb.book_id
                                WHERE lt.user_id = ?
                                ORDER BY lt.created_at DESC");
$history_stmt->bind_param("i", $_SESSION['user_id']);
$history_stmt->execute();
$history = $history_stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar-teacher.php'; ?>
    
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
                    <h3><i class="fas fa-book-reader"></i> Library</h3>
                </div>
                <div class="card-body">
                    <div class="tabs">
                        <a href="?tab=issued" class="tab-item <?php echo $active_tab === 'issued' ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i> My Issued Books
                        </a>
                        <a href="?tab=browse" class="tab-item <?php echo $active_tab === 'browse' ? 'active' : ''; ?>">
                            <i class="fas fa-book"></i> Browse Library
                        </a>
                        <a href="?tab=history" class="tab-item <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i> History
                        </a>
                    </div>

                    <?php if ($active_tab === 'issued'): ?>
                        <?php if ($issued_books->num_rows > 0): ?>
                            <?php $issued_books->data_seek(0); ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($book = $issued_books->fetch_assoc()):
                                            $due_date = strtotime($book['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $is_overdue = $due_date < $today && $book['status'] !== 'returned';
                                            $status_class = $is_overdue ? 'danger' : ($book['status'] === 'overdue' ? 'danger' : 'success');
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($book['issue_date']); ?></td>
                                            <td>
                                                <strong class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                                    <?php echo formatDate($book['due_date']); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $status_class; ?>">
                                                    <?php echo $is_overdue ? 'Overdue' : ucfirst($book['status']); ?>
                                                </span>
                                                <?php if ($is_overdue): ?>
                                                    <br><small class="text-danger">Please return this book immediately</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">
                                <i class="fas fa-book"></i>
                                You do not have any books issued.
                            </p>
                        <?php endif; ?>

                    <?php elseif ($active_tab === 'history'): ?>
                        <?php if ($history->num_rows > 0): ?>
                            <?php $history->data_seek(0); ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($record = $history->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($record['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($record['author']); ?></td>
                                            <td><?php echo formatDate($record['issue_date']); ?></td>
                                            <td><?php echo formatDate($record['due_date']); ?></td>
                                            <td><?php echo $record['return_date'] ? formatDate($record['return_date']) : '-'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $record['status'] == 'returned' ? 'success' : ($record['status'] == 'overdue' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($record['fine_amount'] > 0): ?>
                                                    <span class="text-danger"><strong>$<?php echo number_format($record['fine_amount'], 2); ?></strong></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">
                                <i class="fas fa-history"></i>
                                No library history available.
                            </p>
                        <?php endif; ?>

                    <?php else: ?>
                        <form method="GET" class="search-bar">
                            <input type="hidden" name="tab" value="browse">
                            <input type="text" name="search" class="form-control" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                            <?php if ($search_term !== ''): ?>
                                <a href="library.php?tab=browse" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
                            <?php endif; ?>
                        </form>

                        <?php if ($books->num_rows > 0): ?>
                            <?php $books->data_seek(0); ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Category</th>
                                            <th>Availability</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($book = $books->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td><?php echo htmlspecialchars($book['category'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo (int)$book['available_quantity']; ?> / <?php echo (int)$book['quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" onclick="viewBook(<?php echo (int)$book['book_id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">
                                <i class="fas fa-search"></i>
                                No books found<?php echo $search_term !== '' ? ' for "' . htmlspecialchars($search_term) . '"' : ''; ?>.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Book Details Modal -->
<div id="viewBookModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
            <h3><i class="fas fa-book"></i> Book Details</h3>
            <button class="modal-close" onclick="hideModal('viewBookModal')" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" id="bookDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #6366f1;"></i>
                <p style="margin-top: 20px; color: var(--text-light);">Loading book details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewBookModal')">Close</button>
        </div>
    </div>
</div>

<style>
.tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 20px;
}

.tab-item {
    padding: 10px 20px;
    text-decoration: none;
    color: var(--text-light);
    font-weight: 600;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.tab-item:hover {
    color: var(--primary-color);
}

.tab-item.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.search-bar input {
    flex: 1;
    min-width: 220px;
}

.search-bar .btn {
    display: flex;
    align-items: center;
    gap: 6px;
}

.empty-state {
    text-align: center;
    color: var(--text-light);
    padding: 40px 20px;
    font-size: 16px;
}

.empty-state i {
    display: block;
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
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

@media (max-width: 768px) {
    .search-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .search-bar .btn,
    .search-bar a {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function viewBook(bookId) {
    showModal('viewBookModal');

    fetch('../librarian/get_book_details.php?book_id=' + bookId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const book = data.book;
                document.getElementById('bookDetailsContent').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                        <div style="text-align: center;">
                            <div style="width: 150px; height: 200px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 64px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div style="margin-top: 20px;">
                                <span class="badge badge-${book.available_quantity > 0 ? 'success' : 'danger'}" style="font-size: 14px; padding: 8px 16px;">
                                    ${book.available_quantity > 0 ? 'Available' : 'Not Available'}
                                </span>
                            </div>
                            <div style="margin-top: 10px; color: var(--text-light);">
                                ${book.available_quantity} / ${book.quantity} copies
                            </div>
                        </div>
                        <div>
                            <h2 style="color: var(--primary-color); margin: 0 0 15px 0;">${book.title}</h2>
                            <table class="info-table">
                                <tr><th>Author:</th><td><strong>${book.author}</strong></td></tr>
                                ${book.isbn ? `<tr><th>ISBN:</th><td>${book.isbn}</td></tr>` : ''}
                                ${book.publisher ? `<tr><th>Publisher:</th><td>${book.publisher}</td></tr>` : ''}
                                ${book.publication_year ? `<tr><th>Publication Year:</th><td>${book.publication_year}</td></tr>` : ''}
                                ${book.category ? `<tr><th>Category:</th><td><span class="badge badge-info">${book.category}</span></td></tr>` : ''}
                                ${book.shelf_location ? `<tr><th>Shelf Location:</th><td>${book.shelf_location}</td></tr>` : ''}
                                <tr><th>Total Copies:</th><td>${book.quantity}</td></tr>
                                <tr><th>Available:</th><td><span class="badge badge-${book.available_quantity > 0 ? 'success' : 'danger'}">${book.available_quantity}</span></td></tr>
                                <tr><th>Status:</th><td><span class="badge badge-${book.status === 'active' ? 'success' : 'danger'}">${book.status.charAt(0).toUpperCase() + book.status.slice(1)}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    ${book.description ? `
                    <div style="margin-top: 20px; padding: 20px; background: var(--light-gray); border-radius: 8px;">
                        <h4 style="color: var(--primary-color); margin-bottom: 10px;">
                            <i class="fas fa-align-left"></i> Description
                        </h4>
                        <p style="color: var(--text-dark); line-height: 1.6; margin: 0;">${book.description}</p>
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('bookDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 20px; color: var(--text-light);">${data.message || 'Failed to load book details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('bookDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
                    <p style="margin-top: 20px; color: var(--text-light);">Failed to load book details. Please try again.</p>
                </div>
            `;
        });
}
</script>

<?php include '../includes/footer.php'; ?>

