# Digital Health & Safety Inspection Platform

A comprehensive PHP-based web application for managing health and safety inspections for Local Government Units (LGUs). This platform provides digital tools for scheduling inspections, managing violations, tracking compliance, and generating analytics reports.

## Features

### Core Functionality
- **User Authentication** - Secure login system with role-based access
- **Dashboard** - Overview of inspections, violations, and compliance metrics
- **Inspections Management** - Schedule, track, and complete inspections
- **Business Management** - Maintain business profiles and compliance history
- **Violations Tracking** - Report and monitor safety violations
- **Analytics Dashboard** - Visualize inspection data and compliance trends
- **User Profiles** - Manage inspector profiles and notifications

### Advanced Features
- **AI-Powered Analysis** - Simulated NLP and computer vision analysis
- **Digital Checklists** - Customizable inspection templates
- **Media Upload** - Photo and video evidence management
- **Real-time Notifications** - Alert system for violations and updates
- **Compliance Scoring** - Automated compliance assessment

## Technology Stack

- **Backend**: PHP 7.4+ with MySQL/MariaDB
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Database**: MySQL with PDO connections
- **AI Integration**: Simulated NLP and OpenCV analysis
- **Security**: Session-based authentication with input sanitization

## Installation

1. **Prerequisites**
   - Web server (Apache/Nginx)
   - PHP 7.4 or higher
   - MySQL/MariaDB database
   - Composer (for dependencies)

2. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE lgu_inspection;
   
   -- Import the database schema from database_schema.sql
   ```

3. **Configuration**
   - Update `config/database.php` with your database credentials
   - Set proper file permissions for uploads directory

4. **Access the Application**
   - Navigate to the application URL in your browser
   - Default login credentials (if pre-populated in database):
     - Email: admin@lgu.gov
     - Password: admin123

## File Structure

```
lgu/
├── config/
│   └── database.php          # Database configuration
├── models/
│   ├── User.php             # User model and authentication
│   ├── Inspection.php       # Inspection management
│   ├── Business.php         # Business management
│   └── Notification.php     # Notification system
├── index.php               # Main dashboard
├── login.php              # Authentication page
├── inspections.php        # Inspections management
├── schedule.php           # Inspection scheduling
├── inspection_form.php    # Digital inspection form
├── violations.php         # Violations management
├── businesses.php         # Business directory
├── inspectors.php         # Inspector management
├── analytics.php          # Analytics dashboard
├── profile.php           # User profile
└── business_view.php     # Business details view
```

## Usage

### For Administrators
1. Manage user accounts and permissions
2. View system-wide analytics and reports
3. Configure inspection templates
4. Monitor overall compliance metrics

### For Inspectors
1. Schedule new inspections
2. Complete digital inspection forms
3. Upload photo/video evidence
4. Report violations and issues
5. Track inspection progress

### For Business Owners
1. View compliance history
2. Receive notifications about violations
3. Track corrective actions
4. Access inspection reports

## Security Features

- Password hashing with PHP password_hash()
- Input sanitization and validation
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars()

## Customization

### Adding New Inspection Types
1. Update the checklist templates in `inspection_form.php`
2. Add new inspection type options in the database
3. Modify the inspection form logic as needed

### Custom Reports
1. Extend the analytics functionality
2. Add new database queries for custom metrics
3. Create additional visualization components

## Support

For technical support or feature requests, please contact the development team or create an issue in the project repository.

## License

This project is developed for Local Government Unit use. Please consult with the development team for licensing information.
