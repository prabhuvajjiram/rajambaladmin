<?php
header('Content-Type: application/json');

// Function to log errors
function logError($message) {
    $log_file = 'email_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    return $log_message;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? 'Customer';
    $email = $_POST['email'] ?? 'sales@rajambalcottons.com';
    $mobile = $_POST['mobile'] ?? '';
    $subject = $_POST['subject'] ?? 'New Order';
    $message = $_POST['message'] ?? '';
    
    $to = "sales@rajambalcottons.com";
    $headers = "From: $email\r\n";
    $headers .= "Cc: rajambal@rajambalcottons.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $email_body = "You have received a new message from your website contact form.\n\n" .
                  "Name: $name\n" .
                  "Email: $email\n" .
                  "Mobile: $mobile\n" .
                  "Subject: $subject\n" .
                  "Message:\n$message";

    $mail_result = mail($to, $subject, $email_body, $headers);
    
    if ($mail_result) {
        logError("Email sent successfully: To: $to, Subject: $subject");
        echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
    } else {
        $error_message = "Failed to send message. Error: " . error_get_last()['message'];
        $log_message = logError($error_message);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to send message. Please try again later.",
            "error" => $log_message
        ]);
    }
} else {
    $error_message = "Invalid request method.";
    $log_message = logError($error_message);
    echo json_encode([
        "status" => "error", 
        "message" => $error_message,
        "error" => $log_message
    ]);
}
?>
