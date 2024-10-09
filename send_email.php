<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $to = "Prabhu.Vajjiram@yahoo.com"; // Replace with your email address
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $email_body = "You have received a new message from your website contact form.\n\n" .
                  "Name: $name\n" .
                  "Email: $email\n" .
                  "Mobile: $mobile\n" .
                  "Subject: $subject\n" .
                  "Message:\n$message";
    
    if (mail($to, $subject, $email_body, $headers)) {
        echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send message. Please try again later."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>