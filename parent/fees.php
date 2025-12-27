
<?php
// Fees page disabled - redirect to parent dashboard
require_once '../config/config.php';
redirect('dashboard.php');
?>
                                <tbody>

                                    <?php while ($payment = $fee_payments->fetch_assoc()): ?>

