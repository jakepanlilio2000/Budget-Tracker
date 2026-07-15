<?php
declare(strict_types=1);

namespace App\Core;

class Mailer
{
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $config = require BASE_PATH . '/config/config.php';
        $this->fromEmail = $config['mail']['from_email'] ?? 'noreply@yourdomain.com';
        $this->fromName = $config['mail']['from_name'] ?? 'Expense Tracker';
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        Logger::info("Email sent", ['to' => $to, 'subject' => $subject]);

        // Note: For production, consider configuring SMTP via PHP.ini or swapping this for an SMTP socket wrapper.
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
}