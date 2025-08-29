# PHP Backend Setup for Application Form

## Quick Setup

1. **Configure Email Settings**:
   - Edit `config.php` and update the email addresses:
     ```php
     define('ADMIN_EMAIL', 'your-admin@yourdomain.com');
     define('FROM_EMAIL', 'noreply@yourdomain.com');
     define('REPLY_TO_EMAIL', 'your-admin@yourdomain.com');
     ```

2. **Server Requirements**:
   - PHP 7.0+ with `mail()` function enabled
   - Web server (Apache, Nginx, etc.)

3. **Test the Setup**:
   - Submit a test application through the form
   - Check if emails are received

## Files

- `submit-application.php` - Main handler for form submissions
- `config.php` - Email and organization configuration
- `README.md` - This setup guide

## Email Features

✅ **Admin Notification**: Detailed application data sent to admin email  
✅ **Parent Confirmation**: Automatic confirmation email to parent  
✅ **Error Handling**: Graceful error handling with fallback to CSV export  
✅ **CORS Support**: Configured for React frontend  

## Configuration Options

In `config.php`:
- `SEND_CONFIRMATION`: Enable/disable parent confirmation emails
- `INCLUDE_IP_ADDRESS`: Include IP address in submissions
- `DEBUG_MODE`: Show detailed error messages (for testing only)

## Email Format

The admin email includes:
- Student Information
- Parent/Guardian Details
- Classroom Request
- Emergency Contacts (Primary + Additional)
- Medical Information
- Agreements & Consent
- Submission timestamp and IP address

## Troubleshooting

**No emails received?**
1. Check server mail configuration
2. Verify email addresses in `config.php`
3. Check spam/junk folders
4. Enable `DEBUG_MODE` for detailed error messages
5. Test with a simple PHP mail script

**CORS errors?**
- Ensure the PHP files are served from the same domain as your React app
- Or configure proper CORS headers for your domain

## Security Notes

- The script includes basic validation and sanitization
- Consider adding CSRF protection for production
- Monitor for spam submissions
- Consider rate limiting for production use
