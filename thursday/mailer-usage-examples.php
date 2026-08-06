<?php
/**
 * Example usage — drop these patterns into your existing
 * password-reset and login controllers/handlers.
 */

require_once __DIR__ . '/Mailer.php';
// require_once __DIR__ . '/vendor/autoload.php'; // for PHPMailer, if not already loaded

// -----------------------------------------------------------------
// Example: Password reset request (forgot-password.php)
// -----------------------------------------------------------------
function handlePasswordResetRequest(string $email): void
{
    // 1. Look up user (your existing DB logic)
    // $user = User::findByEmail($email);
    // if (!$user) { /* still show generic success message, don't leak existence */ }

    // 2. Generate OTP using your existing OTP_LENGTH / OTP_TTL_MINUTES logic
    $otpLength   = (int) env('OTP_LENGTH', 6);
    $ttlMinutes  = (int) env('OTP_TTL_MINUTES', 10);
    $otp         = str_pad((string) random_int(0, (int) str_repeat('9', $otpLength)), $otpLength, '0', STR_PAD_LEFT);

    // 3. Store OTP + expiry in DB, tied to the user (your existing logic)
    // OtpStore::save($user->id, $otp, now()->addMinutes($ttlMinutes));

    // 4. Send it
    [$sent, $error] = Mailer::sendPasswordResetOtp($email, 'User Name', $otp, $ttlMinutes);

    if (!$sent) {
        // Show the "Email sending is not configured..." style error to the user
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Reset code sent to your email.']);
}

// -----------------------------------------------------------------
// Example: Login OTP (login.php, after password check passes)
// -----------------------------------------------------------------
function handleLoginOtpSend(string $email): void
{
    $otpLength  = (int) env('OTP_LENGTH', 6);
    $ttlMinutes = (int) env('OTP_TTL_MINUTES', 10);
    $otp        = str_pad((string) random_int(0, (int) str_repeat('9', $otpLength)), $otpLength, '0', STR_PAD_LEFT);

    // Store OTP tied to the login session (your existing logic)
    // OtpStore::save($user->id, $otp, now()->addMinutes($ttlMinutes));

    [$sent, $error] = Mailer::sendLoginOtp($email, 'User Name', $otp, $ttlMinutes);

    if (!$sent) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Verification code sent to your email.']);
}
