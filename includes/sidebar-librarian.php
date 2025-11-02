<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> LearnX</h2>
        <p><?php echo hasRole('admin') ? 'Library Management' : 'Library Panel'; ?></p>
    </div>
    
    <ul class="sidebar-menu">
        <?php if (hasRole('admin')): ?>
        <li>
            <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="menu-item" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Admin Panel</span>
            </a>
        </li>
        <li style="margin: 10px 0;">
            <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">
        </li>
        <?php endif; ?>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span><?php echo hasRole('admin') ? 'Library Dashboard' : 'Dashboard'; ?></span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/books.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Book Catalog</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/issue-book.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'issue-book.php' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding"></i>
                <span>Issue Book</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/return-book.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'return-book.php' ? 'active' : ''; ?>">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/transactions.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Transactions</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo SITE_URL; ?>librarian/overdue.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'overdue.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Overdue Books</span>
            </a>
        </li>
    </ul>
</div>




