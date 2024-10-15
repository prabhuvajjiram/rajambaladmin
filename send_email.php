<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? 'Customer';
    $email = $_POST['email'] ?? 'sales@rajambalcottons.com';
    $mobile = $_POST['mobile'] ?? '';
    $subject = $_POST['subject'] ?? 'New Order';
    $message = $_POST['message'] ?? '';
    
    
    $to = "sales@rajambalcottons.com"; // Replace with your email address
    $headers = "From: $email\r\n";
    $headers .= "Cc: rajambal@rajambalcottons.com \r\n";
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