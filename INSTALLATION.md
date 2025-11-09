# Reunify Installation Guide

## Quick Start Guide for Windows (XAMPP)

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp`
3. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup the Project
1. Copy the Reunify folder to `C:\xampp\htdocs\`
2. Your project path should be: `C:\xampp\htdocs\Reunify`

### Step 3: Create Database
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" in left sidebar
3. Create database named: `reunify`
4. Click on `reunify` database
5. Click "Import" tab
6. Click "Choose File" and select `database.sql` from your Reunify folder
7. Click "Go" button at bottom
8. Wait for success message

### Step 4: Configure Database Connection
1. Open file: `config/config.php`
2. Update these lines if needed (default XAMPP settings):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
define('DB_NAME', 'reunify');
```

### Step 5: Create Upload Folders
In your Reunify folder, create these folders:
- `uploads`
- `uploads/items`
- `uploads/claims`

### Step 6: Update Base URL
1. Open `config/config.php`
2. Update the BASE_URL:
```php
define('BASE_URL', 'http://localhost/Reunify');
```

### Step 7: Access the Application
1. Open browser
2. Go to: `http://localhost/Reunify`
3. You should see the homepage!

### Step 8: Login as Admin
- Email: `admin@reunify.com`
- Password: `admin123`

**IMPORTANT: Change this password immediately!**

## Troubleshooting

### Problem: "Cannot connect to database"
**Solution:**
- Make sure MySQL is running in XAMPP
- Check database credentials in config/config.php
- Make sure database name is correct

### Problem: "Upload failed"
**Solution:**
- Check if `uploads` folder exists
- Right-click folder → Properties → Uncheck "Read-only"
- Make sure PHP has write permissions

### Problem: Images not showing
**Solution:**
- Check uploads folder path
- Verify images are in correct folder
- Check file permissions

### Problem: "Session error"
**Solution:**
- Check PHP session settings
- Restart Apache in XAMPP
- Clear browser cookies

## Default Accounts

### Admin
- Email: admin@reunify.com
- Password: admin123

### Test Student Account (Create via Register page)
- Register as a student with your details

## Features Checklist

After installation, test these features:

- [ ] Register new student account
- [ ] Login successfully
- [ ] Report a lost item
- [ ] Report a found item
- [ ] Browse lost/found items
- [ ] Search and filter items
- [ ] Claim an item
- [ ] View notifications
- [ ] Check dashboard
- [ ] Admin login
- [ ] Admin approve/reject claims

## File Structure
```
Reunify/
├── admin/              # Admin panel files
├── assets/             # CSS, JS, images
│   └── css/
│       └── style.css
├── config/             # Configuration
│   └── config.php
├── uploads/            # User uploads (create this)
│   ├── items/
│   └── claims/
├── index.php           # Homepage
├── login.php           # Login page
├── register.php        # Registration
├── dashboard.php       # User dashboard
├── report_lost.php     # Report lost item
├── report_found.php    # Report found item
├── view_lost_items.php # Browse lost items
├── view_found_items.php# Browse found items
├── item_details.php    # Item details
├── claim_item.php      # Claim form
├── claim_status.php    # Track claims
├── notifications.php   # Notifications
├── database.sql        # Database schema
└── README.md          # Documentation
```

## Next Steps

1. **Customize Design**: Edit `assets/css/style.css` to match your institution's colors
2. **Add Logo**: Add your institution's logo
3. **Configure Email**: Set up email notifications (optional)
4. **Add Categories**: Go to Admin panel → Manage Categories
5. **Add Departments**: Update departments in database
6. **Test**: Create test items and claims
7. **Deploy**: When ready, move to production server

## Production Deployment

When moving to production:

1. **Update config.php**:
   - Change DB credentials
   - Update BASE_URL to your domain
   - Set error_reporting to 0
   
2. **Security**:
   - Change admin password
   - Use strong database password
   - Enable HTTPS
   - Set proper file permissions
   
3. **Backup**:
   - Regular database backups
   - Backup upload folder
   
4. **Performance**:
   - Enable caching
   - Optimize images
   - Use CDN for assets

## Support

For issues or questions:
- Check the README.md file
- Review troubleshooting section
- Check PHP error logs in XAMPP

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher  
- Apache/Nginx web server
- GD Library (usually included with PHP)
- At least 100MB free disk space

## Optional Enhancements

- Set up email notifications (PHPMailer)
- Enable SMS alerts (Twilio)
- Add reCAPTCHA to forms
- Implement two-factor authentication
- Add social media login

---

**Congratulations! Your Reunify Lost & Found System is ready to use!** 🎉
