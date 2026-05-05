# Photo Selection System

A Laravel-based web application for managing photographer day events where families can view and select their favorite photos.

## Features

- **Family Photo Selection**: Families can log in with a PIN code to view and select their photos
- **30-Minute Timer**: Families have 30 minutes to make their selections after logging in
- **Automatic Photo Management**: Selected photos are automatically moved to a "final choices" directory
- **Admin Dashboard**: Admins can create families, manage access, and view selections
- **Docker-Based**: Easy setup and deployment using Docker Compose

## Quick Start

### Prerequisites

- Docker and Docker Compose installed on your system
- At least 2GB of free disk space

### Installation

1. Clone or download this repository

2. Make the start script executable and run it:
```bash
chmod +x start.sh
./start.sh
```

3. The application will be available at `http://localhost:8080`

### Default Credentials

- **Admin Password**: `admin123` (can be changed in `.env` file)

## Usage

### For Administrators

1. **Login to Admin Panel**
   - Navigate to `http://localhost:8080/admin/login`
   - Enter the admin password (default: `admin123`)

2. **Create a Family**
   - Click "Create New Family"
   - Enter the family name (e.g., "Smith Family")
   - Enter a directory name (e.g., "smith_family") - this should match the folder where you'll upload photos
   - A random 8-digit PIN will be generated automatically

3. **Upload Photos**
   - Photos should be uploaded to: `./photos/uploads/{directory_name}/`
   - For example: `./photos/uploads/smith_family/`
   - Supported formats: JPG, JPEG, PNG, GIF

4. **Enable Family Login**
   - Go to the family's detail page
   - Click "Enable Login" to allow the family to access their photos
   - Share the 8-digit PIN with the family

5. **View Selections**
   - Once a family completes their selection, view their choices in the admin panel
   - Selected photos are moved to: `./photos/final_choices/{directory_name}/`

### For Families

1. **Login**
   - Navigate to `http://localhost:8080`
   - Enter your 8-digit PIN code

2. **Select Photos**
   - Click on photos to select/deselect them
   - A green checkmark indicates selected photos
   - The timer shows remaining time (30 minutes)

3. **Submit Selections**
   - Click "Submit My Selections" when done
   - Or wait for the timer to expire (selections are auto-submitted)

## Directory Structure

```
.
├── photos/                    # Photo storage (bind-mounted volume)
│   ├── uploads/              # Uploaded photos organized by family
│   │   └── {family_name}/    # Family-specific photo directories
│   └── final_choices/        # Selected photos after submission
│       └── {family_name}/    # Family-specific final selections
├── docker-compose.yml        # Docker services configuration
├── Dockerfile               # PHP application container
├── start.sh                 # Quick start script
└── README.md               # This file
```

## Configuration

### Environment Variables

Edit the `.env` file to customize:

- `ADMIN_PASSWORD`: Admin panel password (default: `admin123`)
- `DB_PASSWORD`: MySQL database password
- `APP_URL`: Application URL

### Session Duration

To change the 30-minute selection timer, edit `app/Models/Family.php`:

```php
public function startSession()
{
    $this->session_started_at = now();
    $this->session_expires_at = now()->addMinutes(30); // Change 30 to desired minutes
    $this->save();
}
```

## Docker Services

The application uses three Docker containers:

1. **app**: PHP 8.2-FPM with Laravel
2. **nginx**: Web server (port 8080)
3. **db**: MySQL 8.0 database

## Troubleshooting

### Photos not showing up

- Ensure photos are in the correct directory: `./photos/uploads/{directory_name}/`
- Check file permissions: `chmod -R 755 ./photos`
- Verify the directory name matches exactly (case-sensitive)

### Cannot login as admin

- Check the `ADMIN_PASSWORD` in `.env` file
- Default password is `admin123`

### Family PIN not working

- Ensure login is enabled for the family in the admin panel
- Verify the PIN is exactly 8 digits
- Check that the family exists in the database

### Timer not working

- Ensure JavaScript is enabled in the browser
- Check browser console for errors
- Verify the session hasn't already expired

## Maintenance

### Backup Photos

```bash
# Backup all photos
tar -czf photos-backup-$(date +%Y%m%d).tar.gz ./photos
```

### Reset a Family's Session

In the admin panel:
1. Go to the family's detail page
2. Click "Reset Session"
3. This allows the family to log in again and make new selections

### Clear All Data

```bash
# Stop containers
docker-compose down

# Remove database volume
docker volume rm maison-des-familles_db_data

# Restart
./start.sh
```

## Security Notes

- Change the default admin password in production
- Use HTTPS in production environments
- Keep Docker and dependencies updated
- Restrict access to the admin panel
- Regularly backup photos and database

## License

This project is open-source software.
