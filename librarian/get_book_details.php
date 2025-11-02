<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!hasRole('librarian') && !hasRole('admin') && !hasRole('student') && !hasRole('teacher')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    if (!isset($_GET['book_id'])) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        exit();
    }
    
    $book_id = (int)$_GET['book_id'];
    
    $query = "SELECT * FROM library_books WHERE book_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        // Format the created_at date
        if ($book['created_at']) {
            $book['created_at'] = date('F j, Y, g:i a', strtotime($book['created_at']));
        }
        
        echo json_encode(['success' => true, 'book' => $book]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

