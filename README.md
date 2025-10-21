# Christmas Toy Appeal - Referral System

A comprehensive web application for managing toy referrals for the Christmas Toy Appeal charity initiative. This system allows partner organisations to submit referrals and provides an admin portal for tracking, managing, and fulfilling toy parcels.

## Features

### Public Referral Form
- Simple, user-friendly form for partner organisations
- Multi-child support for families with multiple children
- Automatic email confirmation upon submission
- Collects comprehensive data for statistics and reporting
- Mobile-responsive design

### Admin Portal
- **Dashboard**: Overview with statistics and key metrics
- **Referral Management**: Search, filter, and manage all referrals
- **Status Tracking**: Five-stage workflow (Pending → Fulfilled → Located → Ready → Collected)
- **Label Printing**: Automatic A4 label generation (Avery L7160 compatible)
- **Email Notifications**: Automatic emails when parcels are ready for collection
- **User Management**: Multiple admin accounts with activity tracking
- **Warehouse Zones**: Configurable zones for parcel organization
- **Statistics & Reporting**: Comprehensive analytics by age, gender, organisation, location, etc.

### Workflow
1. **Pending** - Referral just received
2. **Fulfilled** - Warehouse team has prepared the parcel
3. **Located** - Parcel assigned to a warehouse zone
4. **Ready for Collection** - Email automatically sent to referrer
5. **Collected** - Parcel picked up by partner organisation

## Technical Specifications

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Standard cPanel hosting compatible

### Technology Stack
- **Backend**: PHP with MySQLi
- **Frontend**: HTML5, Tailwind CSS (via CDN), Vanilla JavaScript
- **Database**: MySQL
- **Authentication**: PHP Sessions with password hashing
- **Email**: PHP mail() function with SMTP support

## Installation Instructions

### 1. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE toy_appeal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p toy_appeal < database.sql
```

Or use phpMyAdmin to import the `database.sql` file.

### 2. Configuration

1. Edit `includes/config.php` and update these settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'toy_appeal');
define('SITE_URL', 'https://yourdomain.com/refsys');
```

2. For production, disable error display in `includes/config.php`:
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 3. File Upload

Upload all files to your web server via FTP or cPanel File Manager.

### 4. File Permissions

Ensure proper permissions:
```bash
chmod 755 admin/
chmod 755 includes/
chmod 644 *.php
```

### 5. Access the System

- **Public Referral Form**: `https://yourdomain.com/refsys/`
- **Admin Portal**: `https://yourdomain.com/refsys/admin/`

### 6. Default Login Credentials

```
Username: admin
Password: admin123
```

**⚠️ IMPORTANT: Change these credentials immediately after first login!**

Go to Settings → Users tab to change the admin password.

## Configuration

### Email Settings

Configure email in Admin → Settings → Email tab:

1. **SMTP Settings**: Configure your mail server settings
2. **From Email**: Set the sender email address
3. **From Name**: Set the sender display name

For cPanel hosting, typically use:
- SMTP Host: `localhost` or your mail server
- SMTP Port: `587` (or `465` for SSL)

### General Settings

Admin → Settings → General:

- **Site Name**: Your organisation name
- **Enable Referrals**: Toggle to enable/disable public submissions
- **Collection Location**: Where parcels are collected
- **Collection Hours**: Opening hours for collection
- **Current Year**: Used for reference number generation

### User Management

Admin → Settings → Users:

- Create multiple admin accounts
- Track login activity
- Deactivate users when needed

### Warehouse Zones

Admin → Settings → Zones:

- Create zones for parcel organization (e.g., "Zone A", "Shelf 1")
- Assign parcels to zones for easy location
- Deactivate zones when not in use

## Using the System

### For Partner Organisations

1. Visit the referral form
2. Fill in your details and the family's information
3. Add each child in the household
4. Submit the form
5. Receive confirmation email
6. Wait for collection notification email

### For Warehouse Team

1. **Log in** to the admin portal
2. **View pending referrals** on the dashboard
3. **Update status** as you prepare parcels:
   - Mark as "Fulfilled" when parcel is ready
   - Assign to a warehouse zone (Located)
   - Mark as "Ready for Collection" (auto-emails referrer)
   - Mark as "Collected" when picked up
4. **Print labels** for parcels
5. **Add notes** for internal tracking

### Printing Labels

1. Go to Admin → Print Labels
2. Choose which referrals to print:
   - All pending/fulfilled/located
   - Specific household
   - Individual referral
3. Print on Avery L7160 (or compatible) A4 labels
4. Affix labels to parcels

**Label Format**: 21 per sheet (63.5mm x 38.1mm)

## Statistics & Reporting

The dashboard provides comprehensive statistics:

- Total referrals and households
- Status breakdown
- Top referring organisations
- Age distribution
- Gender distribution
- Geographic distribution (by postcode)
- Family duration known to organisation
- Weekly activity trends

## Data Collected

### Referrer Information
- Name, organisation, phone, email
- How long family has been known to organisation
- Additional notes

### Child Information
- Initials (for privacy)
- Age and gender
- Postcode (family address)
- Special requirements (learning difficulties, disabilities, etc.)

### Tracking Data
- Reference number
- Submission date
- Status and timestamps
- Warehouse location
- Activity log

## Security Features

- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- Session-based authentication
- HTTP-only cookies
- Activity logging

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Android)

## Troubleshooting

### Database Connection Errors
- Check credentials in `includes/config.php`
- Verify MySQL service is running
- Check database user permissions

### Email Not Sending
- Check SMTP settings in Admin → Settings → Email
- Verify PHP mail() is configured on server
- Check spam folders
- View error logs for details

### Labels Not Printing Correctly
- Use 100% print scale (no fit-to-page)
- Disable headers and footers
- Use portrait orientation
- Test with plain paper first
- Check printer alignment

### Session/Login Issues
- Check `session.save_path` in php.ini
- Verify cookies are enabled
- Clear browser cache and cookies

## Backup Recommendations

Regular backups are essential:

1. **Database**: Export via phpMyAdmin or mysqldump
```bash
mysqldump -u username -p toy_appeal > backup_$(date +%Y%m%d).sql
```

2. **Files**: Backup entire directory
```bash
tar -czf refsys_backup_$(date +%Y%m%d).tar.gz /path/to/refsys/
```

## Support & Maintenance

### Recommended Maintenance Schedule

- **Daily**: Check pending referrals
- **Weekly**: Review statistics and trends
- **Monthly**: Database backup, review user accounts
- **Yearly**: Update reference number year in settings

### Log Files

Check server error logs for issues:
- Apache: `/var/log/apache2/error.log`
- PHP: Check `error_log` in application directory

## License

This application was built for the Christmas Toy Appeal charity initiative.

## Credits

Built with:
- PHP
- MySQL
- Tailwind CSS
- Vanilla JavaScript

---

**For technical support or questions, please contact your system administrator.**

Last Updated: October 2024
Version: 1.0
