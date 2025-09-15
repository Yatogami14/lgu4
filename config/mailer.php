<?php
/**
 * PHPMailer Configuration
 *
 * You can use a service like Gmail (with an App Password), SendGrid, Mailgun, or a development tool like Mailtrap.
 */
return [
    'host' => 'smtp.gmail.com',       // Your SMTP server, e.g., smtp.gmail.com or smtp.mailtrap.io
    'username' => 'healthsafetyinspection@gmail.com', // Your SMTP username
    'password' => 'nykz nyhp vvin yqci', // Your SMTP password or App Password
    'port' => 587,                     // SMTP port (587 for TLS, 465 for SSL, 2525 for Mailtrap)
    'from_email' => 'healthsafetyinspection@gmail.com',
    'from_name' => 'LGU Inspection Platform',
    'smtp_auth' => true,                // Enable SMTP authentication
    'smtp_secure' => 'tls',             // Set to 'tls' or 'ssl'.
];