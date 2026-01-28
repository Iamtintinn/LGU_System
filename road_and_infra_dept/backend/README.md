# LGU Road and Infrastructure Department - Backend API

## Overview

This backend provides a RESTful API for the LGU Road and Infrastructure Department management system. It handles all major modules including damage reports, cost assessments, inspections, GIS mapping, document management, maintenance scheduling, and user management.

## Base URL

```
http://localhost/LGU-kristine/road_and_infra_dept/backend/
```

## Authentication

All API endpoints (except login) require authentication via Bearer token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

## API Endpoints

### 1. Damage Reports
- `GET /damage-reports` - Get all damage reports
- `GET /damage-reports/{id}` - Get specific damage report
- `POST /damage-reports` - Create new damage report
- `PUT /damage-reports/{id}` - Update damage report
- `DELETE /damage-reports/{id}` - Delete damage report (admin only)

### 2. Cost Assessments
- `GET /cost-assessments` - Get all cost assessments
- `GET /cost-assessments/{id}` - Get specific cost assessment
- `POST /cost-assessments` - Create new cost assessment (engineer+)
- `PUT /cost-assessments/{id}` - Update cost assessment
- `DELETE /cost-assessments/{id}` - Delete cost assessment (admin only)

### 3. Inspections
- `GET /inspections` - Get all inspection reports
- `GET /inspections/{id}` - Get specific inspection report
- `POST /inspections` - Create new inspection (engineer+)
- `PUT /inspections/{id}` - Update inspection report
- `DELETE /inspections/{id}` - Delete inspection report (admin only)

### 4. GIS Data
- `GET /gis-data` - Get all GIS features
- `GET /gis-data/{id}` - Get specific GIS feature
- `POST /gis-data` - Create new GIS feature (engineer+)
- `PUT /gis-data/{id}` - Update GIS feature
- `DELETE /gis-data/{id}` - Delete GIS feature (admin only)

### 5. Documents
- `GET /documents` - Get all documents
- `GET /documents/{id}` - Get specific document
- `POST /documents` - Upload new document
- `PUT /documents/{id}` - Update document metadata
- `DELETE /documents/{id}` - Delete document

### 6. Maintenance Schedule
- `GET /maintenance` - Get all maintenance tasks
- `GET /maintenance/{id}` - Get specific maintenance task
- `POST /maintenance` - Create new maintenance task
- `PUT /maintenance/{id}` - Update maintenance task
- `DELETE /maintenance/{id}` - Delete maintenance task (admin only)

### 7. Announcements
- `GET /announcements` - Get all active announcements
- `GET /announcements/{id}` - Get specific announcement
- `POST /announcements` - Create new announcement (LGU officer+)
- `PUT /announcements/{id}` - Update announcement (LGU officer+)
- `DELETE /announcements/{id}` - Delete announcement (admin only)

### 8. Users
- `GET /users` - Get all users (admin only)
- `GET /users/{id}` - Get specific user profile
- `POST /users` - Create new user (admin only)
- `PUT /users/{id}` - Update user profile
- `DELETE /users/{id}` - Delete user (admin only)

### 9. Analytics
- `GET /analytics` - Get complete analytics dashboard (admin only)
- `GET /analytics/overview` - Get overview statistics
- `GET /analytics/trends` - Get monthly trends
- `GET /analytics/distribution` - Get module distribution
- `GET /analytics/activity` - Get recent activity
- `GET /analytics/status` - Get status breakdown

## User Roles and Permissions

### Citizen
- View own damage reports
- Create damage reports
- View own documents
- View public announcements

### Engineer
- All citizen permissions
- Create cost assessments
- Create inspection reports
- Create GIS features
- Create maintenance tasks
- View all module data

### LGU Officer
- All engineer permissions
- Create and manage announcements
- View all system data

### Admin
- All permissions
- User management
- Delete any data
- System analytics

## Request/Response Format

### Request Headers
```
Content-Type: application/json
Authorization: Bearer <token>
```

### Response Format
```json
{
    "data": {...},
    "message": "Success message"
}
```

### Error Response
```json
{
    "error": "Error message"
}
```

## Sample Requests

### Create Damage Report
```bash
POST /damage-reports
Content-Type: application/json
Authorization: Bearer <token>

{
    "location": "Commonwealth Avenue",
    "description": "Large pothole causing traffic disruption",
    "severity": "high",
    "latitude": 14.6355,
    "longitude": 121.0320,
    "estimated_cost": 150000.00
}
```

### Get Analytics
```bash
GET /analytics
Authorization: Bearer <admin_token>
```

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Database Schema

The backend uses the following tables:
- `users` - User accounts and authentication
- `damage_reports` - Road damage reports
- `cost_assessments` - Cost estimation data
- `inspection_reports` - Inspection schedules and reports
- `gis_data` - Geographic information system data
- `documents` - File and document management
- `maintenance_schedule` - Maintenance task scheduling
- `public_announcements` - System announcements
- `login_attempts` - Security logging
- `user_sessions` - Session management
- `user_activity_log` - Activity tracking

## Security Features

- JWT-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- Input validation and sanitization
- CORS support for cross-origin requests

## Development Setup

1. Ensure database is created using the provided SQL setup
2. Configure database connection in `../config/database.php`
3. Set up proper file permissions for document uploads
4. Configure JWT secret in authentication system

## Testing

Use tools like Postman or curl to test API endpoints:

```bash
# Test getting all damage reports
curl -X GET "http://localhost/LGU-kristine/road_and_infra_dept/backend/damage-reports" \
     -H "Authorization: Bearer <your_token>"

# Test creating a damage report
curl -X POST "http://localhost/LGU-kristine/road_and_infra_dept/backend/damage-reports" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <your_token>" \
     -d '{"location":"Test Location","description":"Test Description","severity":"medium"}'
```

## File Structure

```
backend/
├── index.php              # Main API router
├── controllers/
│   ├── BaseController.php
│   ├── DamageReportController.php
│   ├── CostAssessmentController.php
│   ├── InspectionController.php
│   ├── GISController.php
│   ├── DocumentController.php
│   ├── MaintenanceController.php
│   ├── AnnouncementController.php
│   ├── UserController.php
│   └── AnalyticsController.php
└── README.md              # This file
```
