<?php

/**
 * Brevo Email Integration Test Script
 * 
 * This script tests the Brevo email configuration
 * Run with: php test-brevo-email.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Brevo Email Integration Test ===\n\n";

// Check environment
$environment = env('ENVIRONMENT');
echo "1. Current Environment: " . ($environment ?: 'Not set') . "\n";

// Check mail configuration
$defaultMailer = config('mail.default');
echo "2. Default Mailer: {$defaultMailer}\n";

// Check Brevo configuration
echo "\n3. Brevo Configuration:\n";
echo "   - Host: " . env('BREVO_SMTP_HOST') . "\n";
echo "   - Port: " . env('BREVO_SMTP_PORT') . "\n";
echo "   - Username: " . env('BREVO_SMTP_USERNAME') . "\n";
echo "   - API Key: " . (env('BREVO_API_KEY') ? substr(env('BREVO_API_KEY'), 0, 20) . '...' : 'Not set') . "\n";
echo "   - From Address: " . env('BREVO_FROM_ADDRESS') . "\n";
echo "   - From Name: " . env('BREVO_FROM_NAME') . "\n";

// Check mail.from configuration
echo "\n4. Mail From Configuration:\n";
echo "   - Address: " . config('mail.from.address') . "\n";
echo "   - Name: " . config('mail.from.name') . "\n";

// Check if Brevo mailer is configured
$brevoMailer = config('mail.mailers.brevo');
echo "\n5. Brevo Mailer Configuration: " . ($brevoMailer ? "✓ Configured" : "✗ Not configured") . "\n";

if ($brevoMailer) {
    echo "   - Transport: " . ($brevoMailer['transport'] ?? 'N/A') . "\n";
    echo "   - Host: " . ($brevoMailer['host'] ?? 'N/A') . "\n";
    echo "   - Port: " . ($brevoMailer['port'] ?? 'N/A') . "\n";
    echo "   - Encryption: " . ($brevoMailer['encryption'] ?? 'N/A') . "\n";
}

// Summary
echo "\n=== Summary ===\n";
if ($environment === 'development' && $defaultMailer === 'brevo') {
    echo "✓ Brevo is ACTIVE (development environment detected)\n";
} elseif ($environment !== 'development') {
    echo "○ Brevo is INACTIVE (using {$defaultMailer} for {$environment} environment)\n";
} else {
    echo "✗ Configuration issue detected\n";
}

echo "\n=== Testing Email Send ===\n";
echo "Send a test email? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) === 'y') {
    echo "Enter recipient email address: ";
    $recipient = trim(fgets($handle));
    
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Puck Recruiter using Brevo.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Brevo Integration Test - ' . date('Y-m-d H:i:s'));
            });
            echo "✓ Email sent successfully!\n";
            echo "Check your inbox at: {$recipient}\n";
        } catch (\Exception $e) {
            echo "✗ Failed to send email: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Invalid email address\n";
    }
}
fclose($handle);

echo "\nTest complete!\n";
