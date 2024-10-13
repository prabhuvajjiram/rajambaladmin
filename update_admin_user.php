<?php
// SQL update statement
$sql = "UPDATE admins SET username = ?, password = ? WHERE id = ?";

// This is just the SQL statement. In a real application, you would use
// prepared statements and proper error handling when executing this query.

// Example usage:
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("ssi", $new_username, $new_hashed_password, $user_id);
// $stmt->execute();
?>