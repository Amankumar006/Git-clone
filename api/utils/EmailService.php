<?php
/**
 * Email Service
 * Handles email sending for verification, password reset, etc.
 */

class EmailService {
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    
    public function __construct() {
        $this->fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@yourapp.com';
        $this->fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Medium Clone';
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUser = $_ENV['SMTP_USER'] ?? '';
        $this->smtpPass = $_ENV['SMTP_PASS'] ?? '';
    }
    
    /**
     * Send email verification
     */
    public function sendEmailVerification($user, $token) {
        $verificationLink = $this->getBaseUrl() . "/verify-email?token=" . $token;
        
        $subject = "Verify Your Email Address";
        $body = $this->getEmailVerificationTemplate($user['username'], $verificationLink);
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $token) {
        $resetLink = $this->getBaseUrl() . "/reset-password?token=" . $token;
        
        $subject = "Password Reset Request";
        $body = $this->getPasswordResetTemplate($user['username'], $resetLink);
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($user) {
        $subject = "Welcome to Medium Clone!";
        $body = $this->getWelcomeTemplate($user['username']);
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    /**
     * Send publication invitation email
     */
    public function sendPublicationInvitation($user, $publication, $inviterUsername, $role) {
        $subject = "Invitation to Join " . $publication['name'];
        $body = $this->getPublicationInvitationTemplate(
            $user['username'], 
            $publication['name'], 
            $inviterUsername, 
            $role,
            $publication['id']
        );
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    /**
     * Send email using PHP mail() function or SMTP
     */
    private function sendEmail($to, $subject, $body) {
        try {
            // For development, just log the email
            if ((isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') || 
                (defined('APP_ENV') && APP_ENV === 'development') || 
                empty($this->smtpHost)) {
                error_log("EMAIL TO: $to");
                error_log("EMAIL SUBJECT: $subject");
                error_log("EMAIL BODY: $body");
                return true;
            }
            
            // In production, you would use PHPMailer or similar
            // For now, we'll use the basic mail() function
            $headers = [
                'From' => $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To' => $this->fromEmail,
                'Content-Type' => 'text/html; charset=UTF-8',
                'MIME-Version' => '1.0'
            ];
            
            $headerString = '';
            foreach ($headers as $key => $value) {
                $headerString .= $key . ': ' . $value . "\r\n";
            }
            
            return mail($to, $subject, $body, $headerString);
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email verification template
     */
    private function getEmailVerificationTemplate($username, $verificationLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verify Your Email</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Welcome to Medium Clone!</h2>
                <p>Hi {$username},</p>
                <p>Thank you for signing up! Please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationLink}' 
                       style='background-color: #3498db; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Verify Email Address
                    </a>
                </div>
                <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #7f8c8d;'>{$verificationLink}</p>
                <p>This link will expire in 24 hours for security reasons.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #7f8c8d; font-size: 12px;'>
                    If you didn't create an account, please ignore this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get password reset template
     */
    private function getPasswordResetTemplate($username, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset Request</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Password Reset Request</h2>
                <p>Hi {$username},</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' 
                       style='background-color: #e74c3c; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #7f8c8d;'>{$resetLink}</p>
                <p>This link will expire in 1 hour for security reasons.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #7f8c8d; font-size: 12px;'>
                    If you didn't request a password reset, please ignore this email. Your password will remain unchanged.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get welcome email template
     */
    private function getWelcomeTemplate($username) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to Medium Clone!</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Welcome to Medium Clone!</h2>
                <p>Hi {$username},</p>
                <p>Your email has been verified successfully! You can now start using all features of Medium Clone:</p>
                <ul>
                    <li>Write and publish articles</li>
                    <li>Follow other writers</li>
                    <li>Engage with content through claps and comments</li>
                    <li>Bookmark articles for later reading</li>
                    <li>Create and join publications</li>
                </ul>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$this->getBaseUrl()}' 
                       style='background-color: #27ae60; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Start Writing
                    </a>
                </div>
                <p>Happy writing!</p>
                <p>The Medium Clone Team</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get publication invitation email template
     */
    private function getPublicationInvitationTemplate($username, $publicationName, $inviterUsername, $role, $publicationId) {
        $acceptLink = $this->getBaseUrl() . "/publications/" . $publicationId . "?invitation=accept";
        $declineLink = $this->getBaseUrl() . "/publications/" . $publicationId . "?invitation=decline";
        
        $template = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Publication Invitation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>You've been invited to join a publication!</h2>
                <p>Hi {$username},</p>
                <p><strong>{$inviterUsername}</strong> has invited you to join <strong>\"{$publicationName}\"</strong> as a <strong>{$role}</strong>.</p>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #2c3e50;'>Publication: {$publicationName}</h3>
                    <p style='margin-bottom: 0;'>Role: <strong>" . ucfirst($role) . "</strong></p>
                </div>
                
                <p>As a {$role}, you will be able to:</p>
                <ul>";
        
        if ($role === 'admin') {
            $template .= "
                    <li>Manage publication settings and branding</li>
                    <li>Invite and manage members</li>
                    <li>Review and approve article submissions</li>
                    <li>Publish articles to the publication</li>";
        } elseif ($role === 'editor') {
            $template .= "
                    <li>Review and approve article submissions</li>
                    <li>Edit and publish articles to the publication</li>
                    <li>Manage publication content</li>";
        } else {
            $template .= "
                    <li>Submit articles for publication review</li>
                    <li>Publish articles under the publication</li>
                    <li>Collaborate with other publication members</li>";
        }
        
        $template .= "
                </ul>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$acceptLink}' 
                       style='background-color: #27ae60; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>
                        Accept Invitation
                    </a>
                    <a href='{$declineLink}' 
                       style='background-color: #e74c3c; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Decline
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If you're not interested in this invitation, you can safely ignore this email.
                </p>
                
                <p>Best regards,<br>The Medium Clone Team</p>
            </div>
        </body>
        </html>
        ";
        
        return $template;
    }

    /**
     * Get base URL for links
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
        return $protocol . '://' . $host;
    }
    
    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken($userId) {
        require_once __DIR__ . '/JWTHelper.php';
        return JWTHelper::generateEmailVerificationToken($userId);
    }
}