# Email Verification System - Complete Guide

## 📧 **Overview**

Your Medium Clone application has a comprehensive email verification system that ensures users verify their email addresses before accessing certain features. Here's how the complete system works:

## 🔄 **Email Verification Flow**

### **1. User Registration Process**

When a user registers:

1. **Frontend Registration** (`RegisterPage.tsx`):
   - User fills out registration form (username, email, password)
   - Form validates password strength in real-time
   - Submits data to `/api/auth/register`

2. **Backend Registration** (`AuthController.php`):
   - Validates input data (email format, password strength, etc.)
   - Checks if email/username already exists
   - Creates user account with `email_verified = false`
   - Generates JWT email verification token
   - Sends verification email
   - Returns user data and auth tokens

3. **Email Verification Token Generation** (`JWTHelper.php`):
   ```php
   // Token payload includes:
   {
     'user_id': userId,
     'type': 'email_verification',
     'exp': time() + (24 * 60 * 60), // 24 hours expiry
     'iat': time(),
     'iss': 'your-domain.com'
   }
   ```

### **2. Email Sending Process**

**EmailService.php** handles all email operations:

```php
// Email verification email contains:
- Welcome message
- Verification button/link
- 24-hour expiry notice
- Fallback text link
- Security notice
```

**Email Template Features:**
- HTML formatted with inline CSS
- Responsive design
- Clear call-to-action button
- Fallback plain text link
- Professional branding

### **3. Email Verification Process**

When user clicks verification link:

1. **Frontend Verification** (`EmailVerificationPage.tsx`):
   - Extracts token from URL query parameter
   - Shows loading state while verifying
   - Calls `/api/auth/verify-email` with token

2. **Backend Verification** (`AuthController.php`):
   - Validates JWT token signature and expiry
   - Checks token type is 'email_verification'
   - Verifies user exists and isn't already verified
   - Updates `email_verified = true` in database
   - Sends welcome email
   - Returns success response

3. **Success State**:
   - Shows success message
   - Provides navigation options
   - Updates user context with verified status

## 🛡️ **Security Features**

### **Token Security**
- **JWT-based tokens** with HMAC-SHA256 signature
- **24-hour expiry** for verification tokens
- **Type-specific tokens** (email_verification vs access tokens)
- **Signature validation** prevents tampering

### **Rate Limiting**
- **Registration rate limiting** prevents spam accounts
- **Verification rate limiting** prevents token abuse
- **Resend verification rate limiting** prevents email flooding

### **Email Security**
- **No sensitive data** in email content
- **Secure token generation** using cryptographically secure methods
- **Domain validation** in token issuer field
- **Anti-enumeration** - doesn't reveal if email exists

## 🎯 **Frontend Implementation**

### **Authentication Context Integration**

```tsx
// AuthContext.tsx provides:
const {
  user,                    // Current user object
  isAuthenticated,         // Boolean auth status
  verifyEmail,            // Verify email function
  resendVerification      // Resend verification function
} = useAuth();

// User object includes:
{
  id: number,
  username: string,
  email: string,
  email_verified: boolean,  // ← Key verification status
  profile_image_url?: string,
  created_at: string
}
```

### **Protected Routes**

```tsx
// ProtectedRoute.tsx handles verification requirements
<ProtectedRoute requireEmailVerification={true}>
  <SensitiveFeature />
</ProtectedRoute>

// Shows verification prompt if email not verified
// Allows resending verification email
// Provides clear user guidance
```

### **UI Components**

1. **EmailVerificationPage.tsx**:
   - Handles verification link clicks
   - Shows loading, success, and error states
   - Provides resend verification option
   - Clear navigation after verification

2. **AuthStatus.tsx**:
   - Shows current verification status
   - Displays verification warning if needed
   - Provides resend verification button
   - Visual verification badge

3. **ProtectedRoute.tsx**:
   - Blocks access to sensitive features
   - Shows verification requirement message
   - Provides verification action buttons

## 🔧 **Backend Implementation**

### **Database Schema**

```sql
-- Users table includes email verification
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email_verified BOOLEAN DEFAULT FALSE,  -- ← Verification status
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **API Endpoints**

1. **POST /api/auth/register**:
   - Creates user account
   - Sends verification email
   - Returns auth tokens

2. **POST /api/auth/verify-email**:
   - Validates verification token
   - Updates email_verified status
   - Sends welcome email

3. **POST /api/auth/resend-verification**:
   - Generates new verification token
   - Sends new verification email
   - Rate limited to prevent abuse

### **Email Configuration**

```php
// .env configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM_EMAIL=noreply@yourapp.com
SMTP_FROM_NAME=Medium Clone
```

## 📧 **Gmail Integration Setup**

### **For Gmail SMTP:**

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `SMTP_PASS`

3. **Configure Environment**:
   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your-gmail@gmail.com
   SMTP_PASS=your-16-char-app-password
   ```

### **For Production:**

Consider using dedicated email services:
- **SendGrid** - Reliable delivery, analytics
- **Mailgun** - Developer-friendly API
- **Amazon SES** - Cost-effective, scalable
- **Postmark** - Fast transactional emails

## 🎨 **Email Templates**

### **Verification Email Template**
- Clean, professional design
- Clear verification button
- 24-hour expiry notice
- Fallback text link
- Security disclaimer

### **Welcome Email Template**
- Congratulatory message
- Feature overview
- Getting started links
- Call-to-action buttons

### **Resend Verification Template**
- Updated verification link
- Clear instructions
- Support contact information

## 🔍 **Testing & Debugging**

### **Development Mode**
```php
// In development, emails are logged instead of sent
if ($_ENV['APP_ENV'] === 'development') {
    error_log("EMAIL TO: $to");
    error_log("EMAIL SUBJECT: $subject");
    error_log("EMAIL BODY: $body");
    return true;
}
```

### **Testing Checklist**
- [ ] Registration sends verification email
- [ ] Verification link works correctly
- [ ] Token expiry is enforced (24 hours)
- [ ] Invalid tokens are rejected
- [ ] Already verified users handled gracefully
- [ ] Resend verification works
- [ ] Rate limiting prevents abuse
- [ ] Protected routes require verification
- [ ] UI shows verification status clearly

### **Common Issues & Solutions**

1. **Emails not sending**:
   - Check SMTP credentials
   - Verify Gmail app password
   - Check server firewall settings
   - Review error logs

2. **Verification links not working**:
   - Check token generation
   - Verify JWT secret key
   - Check URL construction
   - Validate token expiry

3. **Rate limiting issues**:
   - Check session configuration
   - Review rate limit thresholds
   - Clear rate limit data if needed

## 🚀 **Production Considerations**

### **Email Deliverability**
- Use dedicated email service (SendGrid, etc.)
- Configure SPF, DKIM, DMARC records
- Monitor bounce rates and spam reports
- Use professional from address

### **Security Hardening**
- Use strong JWT secret keys
- Implement proper rate limiting
- Log verification attempts
- Monitor for abuse patterns

### **Performance Optimization**
- Queue email sending for better performance
- Use email templates caching
- Implement retry logic for failed sends
- Monitor email service quotas

### **User Experience**
- Clear verification instructions
- Mobile-friendly email templates
- Multiple verification reminders
- Easy resend verification process

## 📊 **Monitoring & Analytics**

Track these metrics:
- **Verification rate** - % of users who verify
- **Email delivery rate** - % of emails delivered
- **Verification time** - Time from registration to verification
- **Resend requests** - How often users need to resend
- **Failed verifications** - Invalid/expired token attempts

Your email verification system is production-ready and follows security best practices! 🎉