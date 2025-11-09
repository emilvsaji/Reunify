# Reunify - Lost & Found Management System

A comprehensive web-based Lost & Found application designed for educational campuses to help students and staff report and recover lost items.

## 🌟 Features

### User Features
- **User Registration & Authentication**
  - Separate registration for Students and Faculty/Staff
  - Secure password hashing
  - Role-based access control

- **Report Lost Items**
  - Detailed item description with images
  - Category classification
  - Location and date/time tracking
  - Contact information

- **Report Found Items**
  - Similar comprehensive reporting
  - Helper rewards and recognition
  - Secure item handling guidelines

- **Smart Search & Filtering**
  - Search by keywords
  - Filter by category, date range
  - Advanced matching algorithms

- **Claim System**
  - Submit claims for lost items
  - Provide proof of ownership
  - Track claim status
  - Verification workflow

- **Notifications**
  - Real-time notifications for claims
  - Item match alerts
  - Status updates
  - Email notifications (can be integrated)

- **User Dashboard**
  - View reported items
  - Track claims
  - Activity statistics
  - Recent notifications

### Admin Features
- **Claim Management**
  - Approve/reject claims
  - Verify proof of ownership
  - Add review notes

- **User Management**
  - View all users
  - Activate/deactivate accounts
  - Role management

- **Category Management**
  - Add/edit/delete categories
  - Category icons
  - Active/inactive status

- **Item Moderation**
  - Review reported items
  - Remove fake/spam reports
  - Item matching suggestions

- **Analytics Dashboard**
  - Success rate statistics
  - Category-wise reports
  - User activity metrics
  - Daily/monthly trends

### Faculty/Staff Specific Features
- **Equipment Tracking**
  - Report lost lab equipment
  - Department-level categorization
  - Special verification process

## 🛠️ Technology Stack

- **Frontend:**
  - HTML5
  - CSS3 (Custom responsive design)
  - JavaScript (Vanilla JS)
  - Font Awesome Icons

- **Backend:**
  - PHP 7.4+
  - MySQL 5.7+

- **Security:**
  - Password hashing (bcrypt)
  - CSRF protection
  - SQL injection prevention (Prepared statements)
  - XSS protection
  - Input sanitization

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- GD Library (for image handling)

## 🚀 Installation

### 1. Clone or Download the Project
```bash
git clone https://github.com/yourusername/reunify.git
cd reunify
```

### 2. Setup Database

#### Option A: Using phpMyAdmin
1. Open phpMyAdmin
2. Create a new database named `reunify`
3. Import the `database.sql` file

#### Option B: Using MySQL Command Line
```bash
mysql -u root -p
```
```sql
CREATE DATABASE reunify;
USE reunify;
SOURCE /path/to/reunify/database.sql;
```

### 3. Configure Database Connection

Edit `config/config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'reunify');
```

### 4. Setup Upload Directory

Create an uploads directory with proper permissions:

```bash
mkdir uploads
mkdir uploads/items
chmod 755 uploads
chmod 755 uploads/items
```

On Windows, simply create these folders manually.

### 5. Configure Base URL

Edit `config/config.php`:

```php
define('BASE_URL', 'http://localhost/reunify');
```

Change to your actual URL in production.

### 6. Start the Application

#### Using PHP Built-in Server (Development)
```bash
php -S localhost:8000
```

#### Using XAMPP/WAMP
1. Copy the project to `htdocs` (XAMPP) or `www` (WAMP)
2. Start Apache and MySQL
3. Access: `http://localhost/reunify`

#### Using Apache
1. Configure virtual host or place in DocumentRoot
2. Restart Apache
3. Access via configured URL

## 👤 Default Credentials

**Admin Account:**
- Email: admin@reunify.com
- Password: admin123

**Important:** Change the admin password immediately after first login!

## 📁 Project Structure

```
reunify/
├── config/
│   └── config.php              # Configuration and database connection
├── assets/
│   └── css/
│       └── style.css           # Main stylesheet
├── uploads/                    # File uploads directory
│   └── items/                  # Item images
├── admin/
│   ├── dashboard.php           # Admin dashboard
│   ├── manage_claims.php       # Claim management
│   ├── manage_users.php        # User management
│   ├── manage_categories.php   # Category management
│   └── analytics.php           # Analytics & reports
├── index.php                   # Homepage
├── login.php                   # User login
├── register.php                # User registration
├── logout.php                  # Logout handler
├── dashboard.php               # User dashboard
├── report_lost.php             # Report lost item
├── report_found.php            # Report found item
├── view_lost_items.php         # Browse lost items
├── view_found_items.php        # Browse found items
├── item_details.php            # Item details page
├── claim_item.php              # Claim submission
├── claim_status.php            # Claim tracking
├── notifications.php           # User notifications
├── database.sql                # Database schema
└── README.md                   # This file
```

## 🔐 Security Features

1. **Password Security**
   - Bcrypt password hashing
   - Minimum password length requirement
   - Password confirmation on registration

2. **SQL Injection Prevention**
   - All queries use prepared statements
   - Input validation and sanitization

3. **CSRF Protection**
   - CSRF tokens on all forms
   - Token verification on submission

4. **XSS Prevention**
   - Output escaping with htmlspecialchars()
   - Input sanitization

5. **File Upload Security**
   - File type validation
   - File size limits
   - Unique filename generation

6. **Session Security**
   - Session fixation prevention
   - Secure session configuration

## 📊 Database Schema

### Main Tables
- **users** - User accounts and profiles
- **items** - Lost and found items
- **categories** - Item categories
- **claims** - Claim submissions
- **notifications** - User notifications
- **departments** - Academic departments
- **activity_logs** - System activity tracking
- **messages** - User messaging (optional feature)

### Key Features in Schema
- Foreign key constraints
- Indexes for performance
- Triggers for automatic notifications
- Views for analytics
- Stored procedures for complex operations

## 🎨 Customization

### Changing Colors
Edit `assets/css/style.css` and modify CSS variables:

```css
:root {
    --primary-color: #4F46E5;
    --secondary-color: #10B981;
    /* Add your custom colors */
}
```

### Adding Categories
1. Login as admin
2. Go to Admin Dashboard → Manage Categories
3. Add new categories with icons

### Email Notifications (Optional)
To enable email notifications, integrate PHPMailer or similar:

```php
// In config/config.php
function sendEmailNotification($to, $subject, $message) {
    // Implement email sending logic
}
```

## 🐛 Troubleshooting

### Upload Issues
- Check folder permissions: `chmod 755 uploads`
- Verify PHP upload settings in php.ini:
  ```ini
  upload_max_filesize = 5M
  post_max_size = 8M
  ```

### Database Connection Errors
- Verify credentials in config/config.php
- Ensure MySQL service is running
- Check user permissions

### Session Issues
- Verify session.save_path is writable
- Check PHP session configuration

### Image Not Displaying
- Check file paths in code
- Verify upload directory exists
- Ensure proper file permissions

## 📈 Future Enhancements

- [ ] Email notifications
- [ ] SMS integration
- [ ] Mobile app (React Native/Flutter)
- [ ] Advanced AI matching
- [ ] QR code generation for items
- [ ] Multi-language support
- [ ] Reward points system
- [ ] Social media integration
- [ ] Export reports (PDF/Excel)
- [ ] Real-time chat between users

## 👥 User Roles

### Student
- Report lost/found items
- Browse items
- Submit claims
- View notifications

### Faculty/Staff
- All student features
- Report lab equipment
- Department-level verification
- Equipment tracking

### Admin
- All user management
- Claim approval/rejection
- Category management
- Analytics and reports
- System configuration

## 📝 Usage Guidelines

### Reporting Lost Items
1. Login to your account
2. Click "Report Lost Item"
3. Fill in detailed description
4. Add clear photos
5. Provide last seen location and date
6. Submit and wait for notifications

### Reporting Found Items
1. Login to your account
2. Click "Report Found Item"
3. Describe the item accurately
4. Upload photos
5. Provide found location and date
6. Keep item safe until claimed

### Claiming Items
1. Browse lost items
2. Find your item
3. Click "Claim This Item"
4. Provide proof of ownership
5. Add identifying details
6. Wait for admin verification

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is open-source and available under the MIT License.

## 👨‍💻 Developer

Developed for campus communities to facilitate lost and found item management.

## 📞 Support

For issues, questions, or suggestions:
- Create an issue on GitHub
- Contact: support@reunify.com
- Documentation: [Wiki](https://github.com/yourusername/reunify/wiki)

## 🙏 Acknowledgments

- Font Awesome for icons
- Open source community
- Campus community feedback

---

**Made with ❤️ for campus communities worldwide**
