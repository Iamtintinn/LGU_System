# LGU Admin UI

## 🏛️ **Overview**

A modern, glassmorphic admin interface for the LGU Road and Infrastructure Department system. Built with clean design principles and responsive layouts.

## 📁 **File Structure**

```
admin_ui/
├── index.php          # Main dashboard with statistics and activity
├── users.php          # User management with CRUD operations
├── permissions.php    # Role and permission management
├── reports.php        # System reports and analytics
├── settings.php       # System configuration and tools
└── README.md          # This documentation
```

## 🎨 **Design Features**

### **Glassmorphic Design**
- Modern glass effect with backdrop filters
- Smooth animations and transitions
- Gradient backgrounds
- Responsive layout system

### **Color Scheme**
- **Primary**: `#2563eb` (Blue)
- **Success**: `#10b981` (Green)
- **Warning**: `#f59e0b` (Amber)
- **Danger**: `#ef4444` (Red)
- **Secondary**: `#64748b` (Gray)

### **Typography**
- **Font**: Inter (Google Fonts)
- **Weights**: 300, 400, 500, 600, 700
- **Responsive sizing**

## 🔐 **Security Features**

### **Authentication**
- Session-based authentication
- Role-based access control
- Admin-only access requirements
- Automatic redirect for unauthorized users

### **Input Validation**
- Form validation and sanitization
- CSRF protection ready
- SQL injection prevention
- XSS protection

## 📊 **Dashboard Features**

### **Main Dashboard (`index.php`)**
- **Statistics Cards**: Total users, active sessions, pending reports, system health
- **Recent Activity**: Real-time user activity feed
- **Quick Actions**: Fast access to common tasks
- **Navigation**: Sidebar with all admin sections

### **User Management (`users.php`)**
- **User Listing**: Searchable and filterable user table
- **Role Assignment**: Dynamic role management
- **Status Management**: Active/inactive/suspended status
- **CRUD Operations**: Create, read, update, delete users
- **Modal Interface**: Modern edit dialogs

### **Permissions (`permissions.php`)**
- **Role Matrix**: Visual permission assignment
- **Category Organization**: Grouped permissions by category
- **Real-time Updates**: Instant permission changes
- **User Role Assignment**: Quick role changes for users

### **Reports (`reports.php`)**
- **Report Library**: Generated reports management
- **Multiple Formats**: PDF, Excel exports
- **Quick Generation**: One-click report creation
- **Analytics Dashboard**: System metrics and insights

### **Settings (`settings.php`)**
- **General Configuration**: Site settings and preferences
- **Feature Toggles**: Enable/disable system features
- **System Tools**: Backup, cache clearing, log viewing
- **System Information**: Server and application metrics

## 🎯 **Key Components**

### **Sidebar Navigation**
- Fixed sidebar with icon navigation
- Active state indicators
- User profile section
- Responsive collapse on mobile

### **Glass Cards**
- Backdrop blur effects
- Smooth hover animations
- Consistent border radius
- Shadow effects

### **Form Controls**
- Modern input styling
- Focus states with color transitions
- Validation feedback
- Toggle switches for boolean settings

### **Data Tables**
- Clean table design
- Hover states
- Action buttons
- Responsive layout

### **Modals**
- Overlay dialogs
- Smooth animations
- Form integration
- Click-outside-to-close

## 📱 **Responsive Design**

### **Breakpoints**
- **Desktop**: 1024px+
- **Tablet**: 768px-1023px
- **Mobile**: <768px

### **Mobile Adaptations**
- Collapsible sidebar
- Stacked layouts
- Touch-friendly controls
- Optimized typography

## 🚀 **Getting Started**

### **Access Requirements**
- Admin role in the system
- Valid session authentication
- Proper file permissions

### **URL Structure**
```
http://localhost/LGU-kristine/road_and_infra_dept/admin_ui/
├── index.php      # Dashboard
├── users.php       # User management
├── permissions.php # Permission management
├── reports.php     # Reports
└── settings.php    # Settings
```

### **Dependencies**
- PHP 8.0+
- Modern web browser
- Font Awesome 6.4.0 (CDN)
- Google Fonts (Inter)

## 🔧 **Configuration**

### **Authentication**
The admin UI uses the `SimpleAuth` class and helper functions:

```php
// Require admin role
require_once '../user_and_access_management_module/SimpleAuth.php';
require_once '../helpers/functions.php';

$auth = new SimpleAuth();
if (!$auth->isLoggedIn() || !hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}
```

### **Database Integration**
Ready for database integration with prepared statements:
```php
// Example user update
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $newRole, $userId);
$stmt->execute();
```

## 🎨 **Customization**

### **Colors**
Modify CSS variables in each file:
```css
:root {
    --primary: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
}
```

### **Typography**
Change font family:
```css
body {
    font-family: "Inter", sans-serif;
}
```

### **Layout**
Adjust sidebar width:
```css
:root {
    --sidebar-width: 280px;
}
```

## 📊 **Data Structure**

### **User Roles**
- `admin` - Full system access
- `lgu_officer` - Report approval and oversight
- `engineer` - Technical assessments
- `citizen` - Report submission

### **Permissions**
- **General**: Dashboard access
- **Reports**: View, create, edit, delete, approve
- **Administration**: User management, role management
- **Analytics**: View analytics, export data
- **GIS**: View and edit maps
- **Documents**: Upload and manage files

## 🔍 **Browser Support**

- **Chrome**: 90+
- **Firefox**: 88+
- **Safari**: 14+
- **Edge**: 90+

## 🚀 **Future Enhancements**

### **Planned Features**
- [ ] Dark mode toggle
- [ ] Real-time notifications
- [ ] Advanced filtering
- [ ] Bulk operations
- [ ] Export to more formats
- [ ] Integration with main system database

### **Performance Optimizations**
- [ ] Lazy loading for large datasets
- [ ] Caching for static assets
- [ ] Database query optimization
- [ ] Image optimization

## 🐛 **Troubleshooting**

### **Common Issues**
1. **Authentication Errors**: Check session and role assignment
2. **CSS Not Loading**: Verify CDN connections
3. **Modal Issues**: Check JavaScript conflicts
4. **Responsive Problems**: Test on different screen sizes

### **Debug Mode**
Enable in settings:
```php
define('DEBUG_MODE', true);
```

## 📞 **Support**

### **Documentation**
- Main system: `../README.md`
- Structure guide: `../STRUCTURE.md`
- Helper functions: `../helpers/functions.php`

### **Contact**
- System Administrator: IT Department
- Technical Support: support@lgu.gov.ph

---

## 📋 **Version History**

- **v1.0.0**: Initial admin UI implementation
- **v1.1.0**: Added permissions management
- **v1.2.0**: Enhanced responsive design
- **v1.3.0**: Added system tools and settings

---

*Last Updated: January 14, 2026*
*UI Version: 1.3.0*
*Compatible with: LGU System v2.0.0+*
