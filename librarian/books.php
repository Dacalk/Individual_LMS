<?php
require_once '../config/config.php';

if (!hasRole('librarian') && !hasRole('admin')) {
    redirect('index.php');
}

$page_title = 'Book Catalog Management';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $isbn = sanitize($_POST['isbn']);
            $title = sanitize($_POST['title']);
            $author = sanitize($_POST['author']);
            $publisher = sanitize($_POST['publisher']);
            $publication_year = (int)$_POST['publication_year'];
            $category = sanitize($_POST['category']);
            $quantity = (int)$_POST['quantity'];
            $shelf_location = sanitize($_POST['shelf_location']);
            $description = sanitize($_POST['description']);
            
            $stmt = $conn->prepare("INSERT INTO library_books (isbn, title, author, publisher, publication_year, category, quantity, available_quantity, shelf_location, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisisss", $isbn, $title, $author, $publisher, $publication_year, $category, $quantity, $quantity, $shelf_location, $description);
            
            if ($stmt->execute()) {
                $success = 'Book added successfully!';
            } else {
                $error = 'Error adding book. ISBN may already exist.';
            }
        } elseif ($_POST['action'] === 'edit') {
            $book_id = (int)$_POST['book_id'];
            $isbn = sanitize($_POST['isbn']);
            $title = sanitize($_POST['title']);
            $author = sanitize($_POST['author']);
            $publisher = sanitize($_POST['publisher']);
            $publication_year = (int)$_POST['publication_year'];
            $category = sanitize($_POST['category']);
            $quantity = (int)$_POST['quantity'];
            $shelf_location = sanitize($_POST['shelf_location']);
            $description = sanitize($_POST['description']);
            $status = sanitize($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE library_books SET isbn = ?, title = ?, author = ?, publisher = ?, publication_year = ?, category = ?, quantity = ?, shelf_location = ?, description = ?, status = ? WHERE book_id = ?");
            $stmt->bind_param("ssssississi", $isbn, $title, $author, $publisher, $publication_year, $category, $quantity, $shelf_location, $description, $status, $book_id);
            
            if ($stmt->execute()) {
                $success = 'Book updated successfully!';
            } else {
                $error = 'Error updating book. ISBN may already exist.';
            }
        } elseif ($_POST['action'] === 'delete') {
            $book_id = (int)$_POST['book_id'];
            $stmt = $conn->prepare("UPDATE library_books SET status = 'inactive' WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            if ($stmt->execute()) {
                $success = 'Book removed successfully!';
            } else {
                $error = 'Error removing book.';
            }
        }
    }
}

// Get all books
$books = $conn->query("SELECT * FROM library_books ORDER BY created_at DESC");

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
                    <h3><i class="fas fa-book"></i> Book Catalog</h3>
                    <button class="btn btn-primary btn-sm" onclick="showModal('addBookModal')">
                        <i class="fas fa-plus"></i> Add New Book
                    </button>
                </div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search books by title, author, or ISBN..." onkeyup="searchTable('searchInput', 'booksTable')">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="booksTable">
                            <thead>
                                <tr>
                                    <th>ISBN</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Total</th>
                                    <th>Available</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($books->num_rows > 0): ?>
                                    <?php while ($book = $books->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $book['isbn']; ?></td>
                                        <td><strong><?php echo $book['title']; ?></strong></td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><span class="badge badge-info"><?php echo $book['category']; ?></span></td>
                                        <td><?php echo $book['quantity']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                <?php echo $book['available_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $book['shelf_location']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $book['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($book['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewBook(<?php echo $book['book_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editBook(<?php echo $book['book_id']; ?>)" title="Edit Book">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to remove this book?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Book">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No books found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div id="addBookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Book</h3>
            <button class="modal-close" onclick="hideModal('addBookModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">ISBN *</label>
                    <input type="text" name="isbn" class="form-control" placeholder="e.g., 978-3-16-148410-0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter book title" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-control" placeholder="Enter author name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Publisher</label>
                    <input type="text" name="publisher" class="form-control" placeholder="Enter publisher name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Publication Year</label>
                    <input type="number" name="publication_year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science">Science</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="History">History</option>
                        <option value="Geography">Geography</option>
                        <option value="Literature">Literature</option>
                        <option value="Reference">Reference</option>
                        <option value="Technology">Technology</option>
                        <option value="Biography">Biography</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Shelf Location</label>
                    <input type="text" name="shelf_location" class="form-control" placeholder="e.g., A-101">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the book"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('addBookModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Book</button>
            </div>
        </form>
    </div>
</div>

<!-- View Book Modal -->
<div id="viewBookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-book"></i> Book Details</h3>
            <button class="modal-close" onclick="hideModal('viewBookModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="bookDetailsContent">
                <p style="text-align: center; padding: 20px;">Loading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideModal('viewBookModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<div id="editBookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Book</h3>
            <button class="modal-close" onclick="hideModal('editBookModal')">&times;</button>
        </div>
        <form method="POST" id="editBookForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="book_id" id="edit_book_id">
                
                <div class="form-group">
                    <label class="form-label">ISBN *</label>
                    <input type="text" name="isbn" id="edit_isbn" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" id="edit_author" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Publisher</label>
                    <input type="text" name="publisher" id="edit_publisher" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Publication Year</label>
                    <input type="number" name="publication_year" id="edit_publication_year" class="form-control" min="1900" max="<?php echo date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" id="edit_category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science">Science</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="History">History</option>
                        <option value="Geography">Geography</option>
                        <option value="Literature">Literature</option>
                        <option value="Reference">Reference</option>
                        <option value="Technology">Technology</option>
                        <option value="Biography">Biography</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Shelf Location</label>
                    <input type="text" name="shelf_location" id="edit_shelf_location" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('editBookModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Book</button>
            </div>
        </form>
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

.info-table tr:last-child {
    border-bottom: none;
}

.info-table td {
    padding: 12px 8px;
}

.info-table td:first-child {
    font-weight: 600;
    color: var(--text-dark);
    width: 40%;
}

.info-table td:last-child {
    color: var(--text-light);
}
</style>

<script>
function viewBook(bookId) {
    fetch('get_book_details.php?book_id=' + bookId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const book = data.book;
                const content = `
                    <table class="info-table">
                        <tr>
                            <td>ISBN</td>
                            <td>${book.isbn || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td>Title</td>
                            <td><strong>${book.title}</strong></td>
                        </tr>
                        <tr>
                            <td>Author</td>
                            <td>${book.author}</td>
                        </tr>
                        <tr>
                            <td>Publisher</td>
                            <td>${book.publisher || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td>Publication Year</td>
                            <td>${book.publication_year || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td>Category</td>
                            <td><span class="badge badge-info">${book.category}</span></td>
                        </tr>
                        <tr>
                            <td>Total Quantity</td>
                            <td>${book.quantity}</td>
                        </tr>
                        <tr>
                            <td>Available Quantity</td>
                            <td><span class="badge badge-${book.available_quantity > 0 ? 'success' : 'danger'}">${book.available_quantity}</span></td>
                        </tr>
                        <tr>
                            <td>Shelf Location</td>
                            <td>${book.shelf_location || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><span class="badge badge-${book.status === 'active' ? 'success' : 'danger'}">${book.status.charAt(0).toUpperCase() + book.status.slice(1)}</span></td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td>${book.description || 'No description available'}</td>
                        </tr>
                        <tr>
                            <td>Added Date</td>
                            <td>${book.created_at || 'N/A'}</td>
                        </tr>
                    </table>
                `;
                document.getElementById('bookDetailsContent').innerHTML = content;
                showModal('viewBookModal');
            } else {
                alert('Error loading book details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading book details');
        });
}

function editBook(bookId) {
    fetch('get_book_details.php?book_id=' + bookId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const book = data.book;
                document.getElementById('edit_book_id').value = book.book_id;
                document.getElementById('edit_isbn').value = book.isbn;
                document.getElementById('edit_title').value = book.title;
                document.getElementById('edit_author').value = book.author;
                document.getElementById('edit_publisher').value = book.publisher;
                document.getElementById('edit_publication_year').value = book.publication_year;
                document.getElementById('edit_category').value = book.category;
                document.getElementById('edit_quantity').value = book.quantity;
                document.getElementById('edit_shelf_location').value = book.shelf_location;
                document.getElementById('edit_description').value = book.description;
                document.getElementById('edit_status').value = book.status;
                showModal('editBookModal');
            } else {
                alert('Error loading book details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading book details');
        });
}
</script>

<?php include '../includes/footer.php'; ?>





