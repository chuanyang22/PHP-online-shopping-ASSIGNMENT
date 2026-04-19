<?php
// lib/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Sends a pre-configured email using PHPMailer.
 * Returns true on success, false on failure.
 */
function send_formatted_email($to_email, $to_username, $subject, $headline, $body_content, $call_to_action_url = null, $call_to_action_text = null) {
    // >>> UPDATE YOUR GMAIL SETTINGS HERE ONCE <<<
    $gmail_username = 'ahkong463@gmail.com'; 
    $gmail_password = 'dkfo jsea qofr clkd'; // (No spaces)
    $project_name   = 'Online Accessory Store';
    // >>> =============================== <<<

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmail_username;
        $mail->Password   = $gmail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($gmail_username, $project_name);
        $mail->addAddress($to_email, $to_username);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Build the HTML body with a common style
        $html_body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; line-height: 1.6;'>
                <h2 style='color: #ee4d2d;'>$headline</h2>
                $body_content";

        if ($call_to_action_url && $call_to_action_text) {
            $html_body .= "
                <p style='margin-top: 20px;'>
                    <a href='$call_to_action_url' style='background-color: #ee4d2d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>$call_to_action_text</a>
                </p>";
        }

        $html_body .= "
                <p style='margin-top: 30px; font-size: 12px; color: #777;'>If you did not request this, please ignore this email.</p>
            </div>";

        $mail->Body = $html_body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        // You can log the error here if needed
        return false;
    }
}