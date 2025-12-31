<?php
/**
 * PHPMailer Configuration
 *
 * You can use a service like Gmail (with an App Password), SendGrid, Mailgun, or a development tool like Mailtrap.
 */
return [
    'host' => '',       // Your SMTP server, e.g., smtp.gmail.com or smtp.mailtrap.io
    'username' => '', // Your SMTP username
    'password' => '', // Your SMTP password or App Password
    'port' => 587,                     // SMTP port (587 for TLS, 465 for SSL, 2525 for Mailtrap)
    'from_email' => '',
    'from_name' => '',
    'smtp_auth' => false,                // Enable SMTP authentication
    'smtp_secure' => '',             // Set to 'tls' or 'ssl'.
];