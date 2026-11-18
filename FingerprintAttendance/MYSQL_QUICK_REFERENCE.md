# Quick Reference: MySQL Connection Configuration

## Current Configuration ✅

```
Host     : 127.0.0.1 (localhost)
Port     : 3306
User     : root
Password : root
Database : attendance_db
```

## Location of Settings

**File**: `FingerprintAttendance/Core/DatabaseHelper.cs`
**Section**: `AppDbConnection` class (bottom of file, ~line 1320)

## To Change Credentials

1. Open `FingerprintAttendance/Core/DatabaseHelper.cs`
2. Find (Ctrl+G) → Line 1320
3. Edit these 4 lines:
   ```csharp
   public const string Host = "127.0.0.1";      // ← Change here
   public const uint Port = 3306;               // ← Change here
   public const string UserId = "root";         // ← Change here
   public const string Password = "root";       // ← Change here
   ```
4. Save (Ctrl+S)
5. Build (Ctrl+Shift+B)
6. Run (F5)

## Verify Connection Works

Run this in MySQL Workbench:
```sql
-- Show MySQL version and databases
SHOW DATABASES;

-- Use your database
USE attendance_db;

-- Show tables
SHOW TABLES;

-- Check user count
SELECT COUNT(*) as user_count FROM users;
```

If you can run these queries in MySQL Workbench with the same credentials as in AppDbConnection, the desktop app will work.

## What Happens on App Startup

1. ✅ Tries to connect using AppDbConnection credentials
2. ✅ If connection fails → Error dialog appears
3. ✅ If connection succeeds → Creates tables if needed
4. ✅ Loads fingerprint templates from database
5. ✅ Ready for scanning

## Synchronization with Web App

Both your desktop app and Laravel web app:
- ✅ Use same MySQL Server
- ✅ Use same `attendance_db` database
- ✅ Share `users`, `attendance_log`, `logbook` tables
- ✅ No special sync needed (same DB)

## Common Issues

| Issue | Check |
|-------|-------|
| "Cannot connect" | Host, Port, User, Password in AppDbConnection |
| "Device not found" | USB scanner connected & drivers installed |
| No enrollment | `users` table exists (app creates if missing) |
| Data not visible in web app | Both apps using same MySQL Server & DB |

## Connection String (Internal Use)

The app builds this connection string automatically:
```
Server=127.0.0.1;Port=3306;UserID=root;Password=root;Database=attendance_db;SslMode=Preferred;AllowPublicKeyRetrieval=True;
```

No need to edit this unless you understand MySQL connection strings.

## Files Modified

- ✅ `DatabaseHelper.cs` → Updated credentials in AppDbConnection
- ✅ `MainForm.cs` → Updated error message for MySQL Workbench

## Files Created

- 🆕 `DATABASE_CONFIGURATION.md` → Full documentation
- 🆕 `MYSQL_INTEGRATION_COMPLETE.md` → Integration summary
- 🆕 `App.config` → Optional config file reference
- 🆕 `MYSQL_QUICK_REFERENCE.md` → This file

## Next Step

Run the application and verify no connection error appears on startup.
