<?php
/**
 * Create/Reset Admin User
 * Run this file once to create or reset the admin account
 * Access: http://localhost/LMS/create_admin.php
 */

require_once 'config/database.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@learnx.com';
$first_name = 'Admin';
$last_name = 'User';
$role = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user already exists
$check_query = "SELECT user_id FROM users WHERE username = '$username'";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    // Update existing admin
    $update_query = "UPDATE users 
                     SET password = '$hashed_password', 
                         email = '$email', 
                         status = 'active' 
                     WHERE username = '$username'";
    
    if ($conn->query($update_query)) {
        echo "✅ <strong>Admin account updated successfully!</strong><br><br>";
    } else {
        echo "❌ Error updating admin: " . $conn->error . "<br><br>";
    }
} else {
    // Create new admin
    $insert_query = "INSERT INTO users (username, email, password, role, first_name, last_name, phone, gender, status) 
                     VALUES ('$username', '$email', '$hashed_password', '$role', '$first_name', '$last_name', '1234567890', 'other', 'active')";
    
    if ($conn->query($insert_query)) {
        echo "✅ <strong>Admin account created successfully!</strong><br><br>";
    } else {
        echo "❌ Error creating admin: " . $conn->error . "<br><br>";
    }
}

// Display credentials
echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border: 2px solid #4F46E5; border-radius: 10px; background: #F3F4F6;'>";
echo "<h2 style='color: #4F46E5; text-align: center;'>🎉 Admin Account Ready!</h2>";
echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3 style='color: #1F2937; margin-top: 0;'>Login Credentials:</h3>";
echo "<p style='font-size: 18px; margin: 10px 0;'><strong>Username:</strong> <code style='background: #E5E7EB; padding: 5px 10px; border-radius: 4px;'>$username</code></p>";
echo "<p style='font-size: 18px; margin: 10px 0;'><strong>Password:</strong> <code style='background: #E5E7EB; padding: 5px 10px; border-radius: 4px;'>$password</code></p>";
echo "<p style='font-size: 18px; margin: 10px 0;'><strong>Email:</strong> <code style='background: #E5E7EB; padding: 5px 10px; border-radius: 4px;'>$email</code></p>";
echo "</div>";
echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='login.php' style='display: inline-block; background: #4F46E5; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>Go to Login Page →</a>";
echo "</div>";
echo "<div style='margin-top: 30px; padding: 15px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 4px;'>";
echo "<strong style='color: #92400E;'>⚠️ Security Notice:</strong><br>";
echo "<span style='color: #92400E; font-size: 14px;'>Please delete this file (create_admin.php) after creating the admin account for security reasons.</span>";
echo "</div>";
echo "</div>";

$conn->close();
?>







