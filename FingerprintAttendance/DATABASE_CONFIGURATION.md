# MySQL Database Configuration Guide

## Overview
This document explains how to configure the C# Fingerprint Attendance desktop application to connect to your MySQL database through MySQL Workbench.

## Current Configuration

The application is configured to connect to your MySQL Server with the following settings:

### Connection Parameters
- **Host**: 127.0.0.1 (localhost)
- **Port**: 3306 (default MySQL port)
- **User**: root
- **Password**: root (configured in AppDbConnection)
- **Database**: attendance_db
- **SSL Mode**: Preferred (optional SSL)

### Configuration Location
The database connection settings are defined in:
```
FingerprintAttendance/Core/DatabaseHelper.cs
Line ~1320: AppDbConnection class
```

## How the Application Works

### On Startup
1. **Connection Test**: The application attempts to connect to MySQL when launched
2. **Table Creation**: If tables don't exist, they are created automatically:
   - `users` - stores employee/user records with fingerprint templates
   - `attendance_log` - records attendance IN/OUT entries
   - `logbook` - visitor/transaction log

### Attendance Workflow
1. **Enrollment**: Fingerprint scans are captured and stored in `users.fp_template`
2. **Verification**: When a finger is scanned, it's matched against stored templates
3. **Logging**: Successful matches create attendance records in `attendance_log`
4. **Web Sync**: Your Laravel web application reads from the same `attendance_db`

## Database Schema

### users Table
```sql
CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	full_name VARCHAR(150) NOT NULL,
	employee_id VARCHAR(50) NULL,
	fp_template LONGBLOB NULL,        -- Fingerprint binary data
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_users_employee_id (employee_id)
) ENGINE=InnoDB;
```

### attendance_log Table
```sql
CREATE TABLE attendance_log (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	entry_type VARCHAR(10) NOT NULL, -- 'IN' or 'OUT'
	log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_attendance_user_id (user_id),
	INDEX idx_attendance_log_time (log_time)
) ENGINE=InnoDB;
```

### logbook Table
```sql
CREATE TABLE logbook (
	logbook_id INT AUTO_INCREMENT PRIMARY KEY,
	full_name VARCHAR(150) NOT NULL,
	address VARCHAR(255) NOT NULL,
	contact_number VARCHAR(50) NOT NULL,
	purpose_of_transaction TEXT NOT NULL,
	office_or_person_to_visit VARCHAR(255) DEFAULT '',
	is_authorized_representative TINYINT(1) DEFAULT 0,
	authorization_letter_verified TINYINT(1) DEFAULT 0,
	valid_id_verified TINYINT(1) DEFAULT 0,
	represented_person_name VARCHAR(150) DEFAULT '',
	transaction_type VARCHAR(40) DEFAULT 'visitor',
	fingerprint_id INT NULL,
	fp_template LONGBLOB NULL,
	time_in DATETIME DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_logbook_fingerprint_id (fingerprint_id),
	INDEX idx_logbook_time_in (time_in)
) ENGINE=InnoDB;
```

## Changing Connection Settings

If you need to change the MySQL credentials or server address:

### Edit AppDbConnection Class
Open `FingerprintAttendance/Core/DatabaseHelper.cs` and locate the `AppDbConnection` class (around line 1320):

```csharp
internal static class AppDbConnection
{
	public const string Host = "127.0.0.1";      // Change MySQL host here
	public const uint Port = 3306;               // Change port if needed
	public const string UserId = "root";         // Change username here
	public const string Password = "root";       // Change password here
	public const string DatabaseName = "attendance_db";
	// ... rest of the class
}
```

### Steps to Update Credentials
1. Open `DatabaseHelper.cs` in Visual Studio
2. Locate the `AppDbConnection` class at the bottom
3. Update the `Host`, `Port`, `UserId`, or `Password` constants
4. Save the file
5. Rebuild the solution (Build → Rebuild Solution)
6. Run the application

## Troubleshooting

### "Cannot connect to MySQL" Error
If you see this error on startup:

1. **Verify MySQL Server is Running**
   - Open MySQL Workbench
   - Check that you can connect to your MySQL server
   - Verify the host and port

2. **Check Credentials**
   - Open MySQL Workbench
   - Note the Host, Port, Username, and Password
   - Update them in `AppDbConnection` class if different

3. **Verify Database Exists**
   - In MySQL Workbench, run: `SHOW DATABASES;`
   - Ensure `attendance_db` exists
   - If not, create it: `CREATE DATABASE attendance_db;`

4. **Test Connection Manually**
   - In MySQL Workbench, try connecting with the same credentials
   - If you can't connect, the desktop app won't be able to either

### Application Shows "Device not found"
This is the fingerprint scanner issue, not database. It appears after the database connection succeeds.

### Tables Are Missing
The application creates tables automatically on first run:
- If you deleted tables, restart the application to recreate them
- Existing data will not be recreated (schemas are preserved)

## Security Considerations

⚠️ **IMPORTANT**: The database password is embedded in the source code. For production systems, consider:

1. **Environment Variables**
   ```csharp
   public const string Password = Environment.GetEnvironmentVariable("DB_PASSWORD") ?? "";
   ```

2. **Config File**
   - Store credentials in `App.config` (encrypted)
   - Load at runtime instead of hardcoding

3. **Windows Authentication**
   - Use Windows domain credentials instead of MySQL user/password
   - Requires MySQL connector version 8.0.16+

## Shared Database with Web Application

Your Laravel web application and this C# desktop application both use `attendance_db`:

- **Enrollment**: Desktop app creates/updates `users.fp_template`
- **Attendance Logging**: Desktop app inserts into `attendance_log`
- **Web App Access**: Laravel app can query these tables in real-time
- **Data Sync**: No separate sync needed; both apps see the same data

### Important Rules
- ✅ DO: Both apps read/write to the same tables
- ✅ DO: Use parameterized queries (prevents SQL injection)
- ✅ DO: Use transactions for multi-step operations
- ❌ DON'T: Duplicate tables in separate databases
- ❌ DON'T: Let one app recreate tables created by the other

## Testing the Connection

### Test from Code
The application automatically tests the connection on startup:
```csharp
if (!_db.TestConnection())
{
	// Connection failed - error dialog shown
}
```

### Manual Test in MySQL Workbench
```sql
-- Verify connection details
SELECT VERSION();
USE attendance_db;
SHOW TABLES;
SELECT COUNT(*) FROM users;
```

## Support

If you encounter issues:
1. Check the error message shown in the connection warning dialog
2. Verify all credentials in `AppDbConnection` match MySQL Workbench
3. Ensure `attendance_db` database exists
4. Check that your MySQL Server is running and accessible
5. Look for firewall or network issues if connecting to remote server
