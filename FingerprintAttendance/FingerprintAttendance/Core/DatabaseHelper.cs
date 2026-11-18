using System;
using System.Collections.Generic;
using System.Globalization;
using System.Linq;
using System.Text;
using MySql.Data.MySqlClient;
using FingerprintAttendance.Models;
using Org.BouncyCastle.Crypto.Generators;

namespace FingerprintAttendance.Core
{
    public class VisitorProfile
    {
        public int FingerprintId { get; set; }
        public int PersonId { get; set; }
        public string PersonClassification { get; set; }
        public string FullName { get; set; }
        public string Address { get; set; }
        public string ContactNumber { get; set; }
        public string OfficeOrPersonToVisit { get; set; }

        /// <summary>True if any saved logbook row for this visitor has a fingerprint template.</summary>
        public bool HasFingerprintOnFile { get; set; }
    }

    /// <summary>One row from visitor identity search (latest visitor-type log per fingerprint id).</summary>
    public class VisitorIdentityPickRow
    {
        public int FingerprintId { get; set; }
        public int PersonId { get; set; }
        public string PersonClassification { get; set; }
        public string FullName { get; set; }
        public string Address { get; set; }
        public string ContactNumber { get; set; }
        public DateTime LastVisit { get; set; }
        public bool HasFingerprintOnFile { get; set; }
    }

    public class VisitorLogEntry
    {
        public int LogbookId { get; set; }
        public string FullName { get; set; }
        public string Address { get; set; }
        public string ContactNumber { get; set; }
        public string PurposeOfTransaction { get; set; }
        public string OfficeOrPersonToVisit { get; set; }
        /// <summary>See <see cref="DatabaseHelper.LogbookTransactionTypeVisitor"/> and <see cref="DatabaseHelper.LogbookTransactionTypeDocumentClaimBehalf"/>.</summary>
        public string TransactionType { get; set; }

        /// <summary>Friendly label for grids (derived from <see cref="TransactionType"/>).</summary>
        public string TransactionTypeLabel => DatabaseHelper.FormatLogbookTransactionTypeLabel(TransactionType);
        public bool IsAuthorizedRepresentative { get; set; }
        public bool AuthorizationLetterVerified { get; set; }
        public bool ValidIdVerified { get; set; }
        public string RepresentedPersonName { get; set; }
        public string PersonClassification { get; set; }
        public string RepresentedPersonClassification { get; set; }
        /// <summary>Visitor biometric id (900000+ range) when enrolled; null when the logbook row has no fingerprint registration.</summary>
        public int? FingerprintId { get; set; }
        public DateTime TimeIn { get; set; }

        /// <summary>True if this visitor profile has any saved fingerprint template (any logbook row for the same <see cref="FingerprintId"/>).</summary>
        public bool HasFingerprintOnFile { get; set; }

        /// <summary>Display text for grids (same rule as <see cref="HasFingerprintOnFile"/>).</summary>
        public string FingerprintEnrollmentLabel => HasFingerprintOnFile ? "Enrolled" : "Not enrolled";

        /// <summary>Detail / export text for fingerprint id column.</summary>
        public string FingerprintIdDisplay => FingerprintId.HasValue ? FingerprintId.Value.ToString() : "No fingerprint ID";
    }

    public class DatabaseHelper
    {
        public const int MinimumMinutesBetweenEmployeeTimeInAndTimeOut = 1;
        public const string TimeInOutsideAllowedScheduleMessage = "Time-In is only available from 5:00 AM to 5:00 PM.";

        /// <summary>Visitor / logbook fingerprint IDs are allocated in this range (see GetNextVisitorFingerprintId).</summary>
        public const int VisitorFingerprintIdRangeStart = 900000;
        private const string PersonIdAssignmentLockName = "FingerprintAttendance.PersonIdAssignment";

        /// <summary>Standard visitor or resident logbook entry.</summary>
        public const string LogbookTransactionTypeVisitor = "visitor";

        /// <summary>Someone claiming barangay documents on behalf of another person (authorization letter + valid IDs required).</summary>
        public const string LogbookTransactionTypeDocumentClaimBehalf = "document_claim_behalf";

        /// <summary>Privileged staff who may view full visitor logbook entries (PII). Use with <c>admin</c> in auth checks.</summary>
        public const string RoleLogbookPrivileged = "logbook";

        public static bool IsEmployeeTimeInWithinAllowedSchedule(DateTime timestamp)
        {
            return timestamp.TimeOfDay >= new TimeSpan(5, 0, 0) &&
                   timestamp.TimeOfDay < new TimeSpan(17, 0, 0);
        }

        /// <summary>Short label for grids and detail dialogs.</summary>
        public static string FormatLogbookTransactionTypeLabel(string transactionType)
        {
            if (string.Equals(transactionType, LogbookTransactionTypeDocumentClaimBehalf, StringComparison.OrdinalIgnoreCase))
                return "Document claim (on behalf)";
            return "Visitor";
        }
        public const string DefaultAdminUsername = "admin@gmail.com";
        public const string DefaultAdminPassword = "admin@gmail.com";

        /// <summary>Shown when a new person cannot be saved because full name and complete address already exist.</summary>
        public const string PersonAlreadyRegisteredMessage =
            "This person is already registered. A record with the same full name and complete address already exists. Search for the existing record to view or update it. A duplicate will not be created.";

        /// <summary>Set after a failed <see cref="TestConnection"/> for diagnostics (shown in the startup dialog).</summary>
        public string LastConnectionFailureDetail { get; private set; }

        /// <summary>DDL and CREATE DATABASE run once per process so polling does not open a server connection every tick.</summary>
        private static bool _schemaReady;
        private static readonly object _schemaLock = new object();

        /// <summary>Creates the database if it does not exist (MySQL must be running).</summary>
        private void EnsureDatabaseCreated()
        {
            using var conn = new MySqlConnection(AppDbConnection.ServerConnectionString);
            conn.Open();
            using var cmd = new MySqlCommand(
                $"CREATE DATABASE IF NOT EXISTS `{AppDbConnection.DatabaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                conn);
            cmd.ExecuteNonQuery();
        }

        private void EnsureCoreTables(MySqlConnection conn)
        {
            const string usersSql = @"
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(350) NOT NULL,
                    employee_id VARCHAR(50) NULL,
                    fp_template LONGBLOB NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_users_employee_id (employee_id)
                ) ENGINE=InnoDB;";
            using (var cmd = new MySqlCommand(usersSql, conn))
                cmd.ExecuteNonQuery();

            const string fingerprintTemplatesSql = @"
                CREATE TABLE IF NOT EXISTS fingerprint_templates (
                    fingerprint_id INT NOT NULL PRIMARY KEY,
                    employee_id INT NOT NULL,
                    finger_label VARCHAR(30) NOT NULL,
                    fp_template LONGBLOB NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_employee_finger (employee_id, finger_label),
                    INDEX idx_fingerprint_templates_employee (employee_id)
                ) ENGINE=InnoDB;";
            using (var cmd = new MySqlCommand(fingerprintTemplatesSql, conn))
                cmd.ExecuteNonQuery();

            // Attendance rows represent individual Time-In / Time-Out sessions.
            const string attendancesSql = @"
                CREATE TABLE IF NOT EXISTS attendances (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    attendance_date DATE NOT NULL,
                    time_in DATETIME NULL,
                    time_out DATETIME NULL,
                    remarks VARCHAR(500) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_attendances_date (attendance_date),
                    INDEX idx_attendance_user_date_session (user_id, attendance_date, time_in)
                ) ENGINE=InnoDB;";
            using (var cmd = new MySqlCommand(attendancesSql, conn))
                cmd.ExecuteNonQuery();

            DropSingleSessionPerDayUniqueIndexes(conn);
            if (!IndexExists(conn, "attendances", "idx_attendance_user_date_session"))
                ExecuteNonQuery(conn,
                    "ALTER TABLE attendances ADD INDEX idx_attendance_user_date_session (user_id, attendance_date, time_in)");

            // No FOREIGN KEY: errno 150 if an older `attendance_log` table is MyISAM or `id` type differs from user_id.
            const string attendanceLogSql = @"
                CREATE TABLE IF NOT EXISTS attendance_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    entry_type VARCHAR(10) NOT NULL,
                    log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_attendance_user_id (user_id),
                    INDEX idx_attendance_log_time (log_time)
                ) ENGINE=InnoDB;";
            using (var cmd = new MySqlCommand(attendanceLogSql, conn))
                cmd.ExecuteNonQuery();

        }

        private static void ExecuteNonQuery(MySqlConnection conn, string sql)
        {
            using var cmd = new MySqlCommand(sql, conn);
            cmd.ExecuteNonQuery();
        }

        private static void EnsureDefaultAdminAccount(MySqlConnection conn)
        {
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            bool hasFullName = ColumnExists(conn, "users", "full_name");
            bool hasName = ColumnExists(conn, "users", "name");
            bool hasPassword = ColumnExists(conn, "users", "password");

            string loginColumn = hasEmail ? "email" : hasUsername ? "username" : null;
            if (string.IsNullOrWhiteSpace(loginColumn))
                return;

            // If any admin already exists, do not change roles/passwords.
            const string anyAdminSql = "SELECT COUNT(1) FROM users WHERE role = 'admin'";
            using (var anyAdminCmd = new MySqlCommand(anyAdminSql, conn))
            {
                if (Convert.ToInt32(anyAdminCmd.ExecuteScalar()) > 0)
                    return;
            }

            if (hasUsername && hasEmail)
            {
                const string syncUsernameSql = "UPDATE users SET username = @login WHERE email = @login AND (username IS NULL OR username = '')";
                using var syncUsernameCmd = new MySqlCommand(syncUsernameSql, conn);
                syncUsernameCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                syncUsernameCmd.ExecuteNonQuery();
            }

            if (hasPassword)
            {
                string syncPasswordSql = $"UPDATE users SET password = @password WHERE {loginColumn} = @login AND (password IS NULL OR password = '')";
                using var passwordCmd = new MySqlCommand(syncPasswordSql, conn);
                passwordCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                passwordCmd.Parameters.AddWithValue("@password", DefaultAdminPassword);
                passwordCmd.ExecuteNonQuery();
            }

            if (hasFullName)
            {
                string fullNameSql = $"UPDATE users SET full_name = 'System Administrator' WHERE {loginColumn} = @login AND (full_name IS NULL OR full_name = '')";
                using var fullNameCmd = new MySqlCommand(fullNameSql, conn);
                fullNameCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                fullNameCmd.ExecuteNonQuery();
            }
            else if (hasName)
            {
                string nameSql = $"UPDATE users SET name = 'System Administrator' WHERE {loginColumn} = @login AND (name IS NULL OR name = '')";
                using var nameCmd = new MySqlCommand(nameSql, conn);
                nameCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                nameCmd.ExecuteNonQuery();
            }

            string existingTargetAdminSql = $@"
                SELECT COUNT(1)
                FROM users
                WHERE {loginColumn} = @login
                  AND role = 'admin'";
            using (var checkCmd = new MySqlCommand(existingTargetAdminSql, conn))
            {
                checkCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                if (Convert.ToInt32(checkCmd.ExecuteScalar()) > 0)
                    return;
            }

            // Promote the default login if it already exists (by email/username).
            string promoteSql = $"UPDATE users SET role = 'admin' WHERE {loginColumn} = @login";
            using (var promoteCmd = new MySqlCommand(promoteSql, conn))
            {
                promoteCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
                int updated = promoteCmd.ExecuteNonQuery();
                if (updated > 0)
                    return;
            }

            string insertColumns = hasFullName ? "full_name, employee_id, fp_template, role, password, " + loginColumn
                : hasName ? "name, employee_id, fp_template, role, password, " + loginColumn
                : "employee_id, fp_template, role, password, " + loginColumn;
            string insertValues = hasFullName ? "@displayName, NULL, NULL, 'admin', @password, @login"
                : hasName ? "@displayName, NULL, NULL, 'admin', @password, @login"
                : "NULL, NULL, 'admin', @password, @login";
            string seedAdminSql = $"INSERT INTO users ({insertColumns}) VALUES ({insertValues})";
            using var insertCmd = new MySqlCommand(seedAdminSql, conn);
            insertCmd.Parameters.AddWithValue("@displayName", "System Administrator");
            insertCmd.Parameters.AddWithValue("@login", DefaultAdminUsername);
            insertCmd.Parameters.AddWithValue("@password", DefaultAdminPassword);
            insertCmd.ExecuteNonQuery();
        }

        private static bool TableExists(MySqlConnection conn, string table)
        {
            using var cmd = new MySqlCommand(
                @"SELECT 1 FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @t LIMIT 1",
                conn);
            cmd.Parameters.AddWithValue("@t", table);
            return cmd.ExecuteScalar() != null;
        }

        private static bool ColumnExists(MySqlConnection conn, string table, string column)
        {
            using var cmd = new MySqlCommand(
                @"SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND COLUMN_NAME = @col LIMIT 1",
                conn);
            cmd.Parameters.AddWithValue("@tbl", table);
            cmd.Parameters.AddWithValue("@col", column);
            return cmd.ExecuteScalar() != null;
        }

        private static void EnsureLogbookContactNumberNullable(MySqlConnection conn)
        {
            using var cmd = new MySqlCommand(
                @"SELECT IS_NULLABLE FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logbook' AND COLUMN_NAME = 'contact_number'",
                conn);
            object nullable = cmd.ExecuteScalar();
            if (nullable == null || nullable == DBNull.Value ||
                string.Equals(Convert.ToString(nullable), "YES", StringComparison.OrdinalIgnoreCase))
                return;

            ExecuteNonQuery(conn,
                "ALTER TABLE logbook MODIFY COLUMN contact_number VARCHAR(50) NULL DEFAULT NULL");
        }

        private static bool IndexExists(MySqlConnection conn, string table, string index)
        {
            using var cmd = new MySqlCommand(
                @"SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND INDEX_NAME = @idx LIMIT 1",
                conn);
            cmd.Parameters.AddWithValue("@tbl", table);
            cmd.Parameters.AddWithValue("@idx", index);
            return cmd.ExecuteScalar() != null;
        }

        private static void DropSingleSessionPerDayUniqueIndexes(MySqlConnection conn)
        {
            var uniqueIndexes = new Dictionary<string, List<string>>(StringComparer.OrdinalIgnoreCase);
            using (var cmd = new MySqlCommand(
                @"SELECT INDEX_NAME, COLUMN_NAME
                  FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'attendances'
                    AND NON_UNIQUE = 0
                  ORDER BY INDEX_NAME, SEQ_IN_INDEX",
                conn))
            using (var reader = cmd.ExecuteReader())
            {
                while (reader.Read())
                {
                    string indexName = reader.GetString("INDEX_NAME");
                    if (!uniqueIndexes.TryGetValue(indexName, out List<string> columns))
                    {
                        columns = new List<string>();
                        uniqueIndexes.Add(indexName, columns);
                    }

                    columns.Add(reader.GetString("COLUMN_NAME"));
                }
            }

            foreach (var index in uniqueIndexes)
            {
                if (string.Equals(index.Key, "PRIMARY", StringComparison.OrdinalIgnoreCase))
                    continue;

                bool restrictsOneSessionPerDay =
                    index.Value.Count == 2 &&
                    index.Value.Contains("user_id", StringComparer.OrdinalIgnoreCase) &&
                    index.Value.Contains("attendance_date", StringComparer.OrdinalIgnoreCase);
                if (!restrictsOneSessionPerDay)
                    continue;

                string escapedIndexName = index.Key.Replace("`", "``");
                ExecuteNonQuery(conn, $"ALTER TABLE attendances DROP INDEX `{escapedIndexName}`");
            }
        }

        private static void EnsureNameColumnCapacity(MySqlConnection conn, string column)
        {
            using var lengthCmd = new MySqlCommand(
                @"SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = @col",
                conn);
            lengthCmd.Parameters.AddWithValue("@col", column);
            object length = lengthCmd.ExecuteScalar();
            if (length == null || length == DBNull.Value || Convert.ToInt64(length) >= 350)
                return;

            ExecuteNonQuery(conn, $"UPDATE users SET `{column}` = '' WHERE `{column}` IS NULL");
            ExecuteNonQuery(conn,
                $"ALTER TABLE users MODIFY COLUMN `{column}` VARCHAR(350) NOT NULL DEFAULT ''");
        }

        /// <summary>
        /// Older installs may have <c>users</c> / <c>logbook</c> without columns this app expects;
        /// <c>CREATE TABLE IF NOT EXISTS</c> does not add missing columns.
        /// </summary>
        private static void ApplyMissingColumnPatches(MySqlConnection conn)
        {
            if (TableExists(conn, "users"))
            {
                if (!ColumnExists(conn, "users", "fp_template"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN fp_template LONGBLOB NULL");
                if (!ColumnExists(conn, "users", "employee_id"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL");
                if (!ColumnExists(conn, "users", "address"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL");
                if (!ColumnExists(conn, "users", "contact_number"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN contact_number VARCHAR(50) NULL");
                if (!ColumnExists(conn, "users", "created_at"))
                    ExecuteNonQuery(conn,
                        "ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                if (!ColumnExists(conn, "users", "full_name"))
                    ExecuteNonQuery(conn,
                        "ALTER TABLE users ADD COLUMN full_name VARCHAR(350) NOT NULL DEFAULT ''");
                EnsureNameColumnCapacity(conn, "full_name");
                if (!ColumnExists(conn, "users", "middle_name"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN middle_name VARCHAR(100) NULL");
                if (!ColumnExists(conn, "users", "first_name"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL");
                if (!ColumnExists(conn, "users", "last_name"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL");
                if (!ColumnExists(conn, "users", "middle_initial"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN middle_initial VARCHAR(2) NULL");
                ExecuteNonQuery(conn,
                    "UPDATE users SET middle_name = NULLIF(TRIM(middle_initial), '') " +
                    "WHERE (middle_name IS NULL OR middle_name = '') AND middle_initial IS NOT NULL " +
                    "AND TRIM(middle_initial) <> ''");
                if (ColumnExists(conn, "users", "name"))
                    EnsureNameColumnCapacity(conn, "name");
                if (!ColumnExists(conn, "users", "name_extension"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN name_extension VARCHAR(20) NULL");
                if (!ColumnExists(conn, "users", "username"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL");
                if (!ColumnExists(conn, "users", "password"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL");
                if (!ColumnExists(conn, "users", "role"))
                    ExecuteNonQuery(conn, "ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'employee'");
                if (!ColumnExists(conn, "users", "can_access_logbook"))
                    ExecuteNonQuery(conn,
                        "ALTER TABLE users ADD COLUMN can_access_logbook TINYINT(1) NOT NULL DEFAULT 0");
            }

            if (TableExists(conn, "logbook"))
            {
                if (!ColumnExists(conn, "logbook", "fp_template"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN fp_template LONGBLOB NULL");
                if (!ColumnExists(conn, "logbook", "purpose_of_transaction"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN purpose_of_transaction TEXT NULL");
                if (!ColumnExists(conn, "logbook", "address"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN address VARCHAR(255) NOT NULL DEFAULT ''");
                if (!ColumnExists(conn, "logbook", "contact_number"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN contact_number VARCHAR(50) NULL");
                EnsureLogbookContactNumberNullable(conn);
                if (!ColumnExists(conn, "logbook", "office_or_person_to_visit"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN office_or_person_to_visit VARCHAR(255) NOT NULL DEFAULT ''");
                if (!ColumnExists(conn, "logbook", "is_authorized_representative"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN is_authorized_representative TINYINT(1) NOT NULL DEFAULT 0");
                if (!ColumnExists(conn, "logbook", "authorization_letter_verified"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN authorization_letter_verified TINYINT(1) NOT NULL DEFAULT 0");
                if (!ColumnExists(conn, "logbook", "valid_id_verified"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN valid_id_verified TINYINT(1) NOT NULL DEFAULT 0");
                if (!ColumnExists(conn, "logbook", "represented_person_name"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN represented_person_name VARCHAR(150) NOT NULL DEFAULT ''");
                if (!ColumnExists(conn, "logbook", "transaction_type"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN transaction_type VARCHAR(40) NOT NULL DEFAULT 'visitor'");
                if (!ColumnExists(conn, "logbook", "person_id"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN person_id INT NULL");
                if (!ColumnExists(conn, "logbook", "person_classification"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN person_classification VARCHAR(20) NOT NULL DEFAULT 'Visitor'");
                if (!ColumnExists(conn, "logbook", "represented_person_classification"))
                    ExecuteNonQuery(conn, "ALTER TABLE logbook ADD COLUMN represented_person_classification VARCHAR(20) NOT NULL DEFAULT 'Visitor'");
                using (var nullPersonIds = new MySqlCommand(
                    "SELECT EXISTS(SELECT 1 FROM logbook WHERE person_id IS NULL LIMIT 1)",
                    conn))
                {
                    if (Convert.ToInt32(nullPersonIds.ExecuteScalar()) == 1)
                    {
                        ExecuteNonQuery(conn,
                            @"UPDATE logbook l
                              INNER JOIN (
                                  SELECT fingerprint_id, MIN(logbook_id) AS person_id
                                  FROM logbook
                                  WHERE fingerprint_id IS NOT NULL
                                  GROUP BY fingerprint_id
                              ) p ON p.fingerprint_id = l.fingerprint_id
                              SET l.person_id = p.person_id
                              WHERE l.person_id IS NULL");
                        ExecuteNonQuery(conn,
                            @"UPDATE logbook l
                              INNER JOIN (
                                  SELECT LOWER(TRIM(full_name)) AS normalized_name,
                                         REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(contact_number, '')), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') AS normalized_contact,
                                         MIN(logbook_id) AS person_id
                                  FROM logbook
                                  WHERE fingerprint_id IS NULL
                                  GROUP BY LOWER(TRIM(full_name)),
                                           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(contact_number, '')), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')
                              ) p ON p.normalized_name = LOWER(TRIM(l.full_name))
                                 AND p.normalized_contact =
                                     REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(l.contact_number, '')), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')
                              SET l.person_id = p.person_id
                              WHERE l.person_id IS NULL");
                    }
                }
                if (!IndexExists(conn, "logbook", "idx_logbook_person_id"))
                {
                    ExecuteNonQuery(conn,
                        "ALTER TABLE logbook ADD INDEX idx_logbook_person_id (person_id)");
                }
                if (ColumnExists(conn, "logbook", "purpose_of_transaction"))
                    ExecuteNonQuery(conn,
                        "UPDATE logbook SET purpose_of_transaction = '' WHERE purpose_of_transaction IS NULL");
                // Visitor logbook rows without biometric registration do not allocate a fingerprint id.
                ExecuteNonQuery(conn, "ALTER TABLE logbook MODIFY COLUMN fingerprint_id INT NULL");
            }

            if (TableExists(conn, "attendance_log"))
            {
                if (!ColumnExists(conn, "attendance_log", "entry_type"))
                    ExecuteNonQuery(conn, "ALTER TABLE attendance_log ADD COLUMN entry_type VARCHAR(10) NOT NULL DEFAULT 'IN'");
                if (!ColumnExists(conn, "attendance_log", "log_time"))
                    ExecuteNonQuery(conn,
                        "ALTER TABLE attendance_log ADD COLUMN log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            }
        }

        public int AddEmployeeFingerprintTemplate(int employeeId, string fingerLabel, byte[] template)
        {
            if (employeeId <= 0)
                throw new ArgumentOutOfRangeException(nameof(employeeId));
            if (string.IsNullOrWhiteSpace(fingerLabel) || fingerLabel.Trim().Length > 30)
                throw new ArgumentException("A valid finger label is required.", nameof(fingerLabel));
            if (template == null || template.Length == 0)
                throw new ArgumentException("A valid fingerprint template is required.", nameof(template));

            using var conn = CreateOpenConnection();
            AcquirePersonIdAssignmentLock(conn);
            try
            {
                using var transaction = conn.BeginTransaction();
                using (var employeeCmd = new MySqlCommand(
                    "SELECT COUNT(1) FROM users WHERE id = @employeeId",
                    conn, transaction))
                {
                    employeeCmd.Parameters.AddWithValue("@employeeId", employeeId);
                    if (Convert.ToInt32(employeeCmd.ExecuteScalar()) == 0)
                        throw new InvalidOperationException("The employee record no longer exists.");
                }

                int fingerprintId = GetLowestAvailableId(
                    conn, transaction, "fingerprint_templates", "fingerprint_id", 1);
                if (fingerprintId >= VisitorFingerprintIdRangeStart)
                    throw new InvalidOperationException("No employee fingerprint template IDs remain.");

                using (var insertCmd = new MySqlCommand(
                    @"INSERT INTO fingerprint_templates
                      (fingerprint_id, employee_id, finger_label, fp_template)
                      VALUES (@fingerprintId, @employeeId, @fingerLabel, @template)",
                    conn, transaction))
                {
                    insertCmd.Parameters.AddWithValue("@fingerprintId", fingerprintId);
                    insertCmd.Parameters.AddWithValue("@employeeId", employeeId);
                    insertCmd.Parameters.AddWithValue("@fingerLabel", fingerLabel.Trim());
                    insertCmd.Parameters.AddWithValue("@template", template);
                    insertCmd.ExecuteNonQuery();
                }

                transaction.Commit();
                return fingerprintId;
            }
            finally
            {
                ReleasePersonIdAssignmentLock(conn);
            }
        }

        private static void MigrateLegacyEmployeeFingerprints(MySqlConnection conn)
        {
            if (!ColumnExists(conn, "users", "fp_template"))
                return;

            AcquirePersonIdAssignmentLock(conn);
            try
            {
                using var transaction = conn.BeginTransaction();
                var legacyTemplates = new List<KeyValuePair<int, byte[]>>();
                const string selectSql = @"
                    SELECT u.id, u.fp_template
                    FROM users u
                    WHERE u.fp_template IS NOT NULL
                      AND NOT EXISTS (
                          SELECT 1 FROM fingerprint_templates f
                          WHERE f.employee_id = u.id AND f.finger_label = 'Legacy'
                      )";
                using (var selectCmd = new MySqlCommand(selectSql, conn, transaction))
                using (var reader = selectCmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        byte[] template = reader["fp_template"] as byte[];
                        if (template != null && template.Length > 0)
                            legacyTemplates.Add(new KeyValuePair<int, byte[]>(reader.GetInt32("id"), template));
                    }
                }

                foreach (var legacy in legacyTemplates)
                {
                    int fingerprintId = GetLowestAvailableId(
                        conn, transaction, "fingerprint_templates", "fingerprint_id", 1);
                    if (fingerprintId >= VisitorFingerprintIdRangeStart)
                        throw new InvalidOperationException("No employee fingerprint template IDs remain.");

                    using var insertCmd = new MySqlCommand(
                        @"INSERT INTO fingerprint_templates
                          (fingerprint_id, employee_id, finger_label, fp_template)
                          VALUES (@fingerprintId, @employeeId, 'Legacy', @template)",
                        conn, transaction);
                    insertCmd.Parameters.AddWithValue("@fingerprintId", fingerprintId);
                    insertCmd.Parameters.AddWithValue("@employeeId", legacy.Key);
                    insertCmd.Parameters.AddWithValue("@template", legacy.Value);
                    insertCmd.ExecuteNonQuery();
                }

                using (var clearCmd = new MySqlCommand(
                    "UPDATE users SET fp_template = NULL WHERE fp_template IS NOT NULL",
                    conn, transaction))
                    clearCmd.ExecuteNonQuery();

                transaction.Commit();
            }
            finally
            {
                ReleasePersonIdAssignmentLock(conn);
            }
        }

        /// <summary>Open a connection to the app database after ensuring DB and core tables exist.</summary>
        private MySqlConnection CreateOpenConnection()
        {
            if (!_schemaReady)
            {
                lock (_schemaLock)
                {
                    if (!_schemaReady)
                    {
                        EnsureDatabaseCreated();
                        using (var setup = new MySqlConnection(AppDbConnection.FullConnectionString))
                        {
                            setup.Open();
                            EnsureCoreTables(setup);
                            EnsureLogbookTable(setup);
                            ApplyMissingColumnPatches(setup);
                            MigrateLegacyEmployeeFingerprints(setup);
                            EnsureDefaultAdminAccount(setup);
                        }
                        _schemaReady = true;
                    }
                }
            }

            var conn = new MySqlConnection(AppDbConnection.FullConnectionString);
            conn.Open();
            return conn;
        }

        // Test the connection
        public bool TestConnection()
        {
            LastConnectionFailureDetail = null;
            try
            {
                using var conn = CreateOpenConnection();
                return true;
            }
            catch (Exception ex)
            {
                LastConnectionFailureDetail = FormatConnectionException(ex);
                return false;
            }
        }

        private static string FormatConnectionException(Exception ex)
        {
            var msg = ex.Message;
            if (ex.InnerException != null)
                msg += Environment.NewLine + ex.InnerException.Message;
            return msg;
        }

        public void EnsureLogbookTable()
        {
            using var conn = CreateOpenConnection();
        }

        private void EnsureLogbookTable(MySqlConnection conn)
        {
            string sql = @"
                CREATE TABLE IF NOT EXISTS logbook (
                    logbook_id INT AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(150) NOT NULL,
                    address VARCHAR(255) NOT NULL,
                    contact_number VARCHAR(50) NULL,
                    purpose_of_transaction TEXT NOT NULL,
                    office_or_person_to_visit VARCHAR(255) NOT NULL DEFAULT '',
                    is_authorized_representative TINYINT(1) NOT NULL DEFAULT 0,
                    authorization_letter_verified TINYINT(1) NOT NULL DEFAULT 0,
                    valid_id_verified TINYINT(1) NOT NULL DEFAULT 0,
                    represented_person_name VARCHAR(150) NOT NULL DEFAULT '',
                    transaction_type VARCHAR(40) NOT NULL DEFAULT 'visitor',
                    person_id INT NULL,
                    person_classification VARCHAR(20) NOT NULL DEFAULT 'Visitor',
                    represented_person_classification VARCHAR(20) NOT NULL DEFAULT 'Visitor',
                    fingerprint_id INT NULL,
                    fp_template LONGBLOB NULL,
                    time_in DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_logbook_fingerprint_id (fingerprint_id),
                    INDEX idx_logbook_person_id (person_id),
                    INDEX idx_logbook_time_in (time_in)
                ) ENGINE=InnoDB;";

            using var cmd = new MySqlCommand(sql, conn);
            cmd.ExecuteNonQuery();
        }

        // Enroll a new user with their fingerprint template
        public int EnrollUser(string fullName, string employeeId, byte[] template, string address, string contactNumber)
        {
            return EnrollUserCore(
                fullName, null, null, null, null, null, employeeId,
                new List<KeyValuePair<string, byte[]>> { new KeyValuePair<string, byte[]>("Right Index", template) },
                address, contactNumber, out _);
        }

        public int EnrollUser(
            string firstName,
            string lastName,
            string middleName,
            string nameExtension,
            string employeeId,
            byte[] template,
            string address,
            string contactNumber)
        {
            return EnrollUser(
                firstName, lastName, middleName, nameExtension, employeeId,
                new List<KeyValuePair<string, byte[]>> { new KeyValuePair<string, byte[]>("Right Index", template) },
                address, contactNumber, out _);
        }

        public int EnrollUser(
            string firstName,
            string lastName,
            string middleName,
            string nameExtension,
            string employeeId,
            IList<KeyValuePair<string, byte[]>> templates,
            string address,
            string contactNumber,
            out List<int> fingerprintIds)
        {
            string fullName = BuildEmployeeFullName(firstName, middleName, lastName, nameExtension);
            string middleInitial = string.IsNullOrWhiteSpace(middleName)
                ? null
                : middleName.Trim().Substring(0, 1);
            return EnrollUserCore(
                fullName, firstName, lastName, middleName, middleInitial, nameExtension,
                employeeId, templates, address, contactNumber, out fingerprintIds);
        }

        public static string BuildEmployeeFullName(
            string firstName,
            string middleName,
            string lastName,
            string nameExtension)
        {
            var nameParts = new[]
            {
                firstName?.Trim(),
                middleName?.Trim(),
                lastName?.Trim(),
                nameExtension?.Trim()
            };
            return string.Join(" ", nameParts.Where(part => !string.IsNullOrWhiteSpace(part)));
        }

        private int EnrollUserCore(
            string fullName,
            string firstName,
            string lastName,
            string middleName,
            string middleInitial,
            string nameExtension,
            string employeeId,
            IList<KeyValuePair<string, byte[]>> templates,
            string address,
            string contactNumber,
            out List<int> fingerprintIds)
        {
            if (templates == null || templates.Count == 0 ||
                templates.Any(item => string.IsNullOrWhiteSpace(item.Key) || item.Value == null || item.Value.Length == 0))
                throw new ArgumentException("At least one valid finger template is required.", nameof(templates));
            if (templates.Select(item => item.Key).Distinct(StringComparer.OrdinalIgnoreCase).Count() != templates.Count)
                throw new ArgumentException("A finger can only be enrolled once per employee.", nameof(templates));

            fingerprintIds = new List<int>(templates.Count);
            if (IsPersonAlreadyRegistered(fullName, address))
                throw new InvalidOperationException(PersonAlreadyRegisteredMessage);

            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasName = ColumnExists(conn, "users", "name");
            bool hasPassword = ColumnExists(conn, "users", "password");
            bool hasUsername = ColumnExists(conn, "users", "username");
            bool hasAddress = ColumnExists(conn, "users", "address");
            bool hasContactNumber = ColumnExists(conn, "users", "contact_number");

            var generatedEmail = hasEmail
                ? BuildGeneratedEmployeeEmail(conn, firstName, lastName, fullName)
                : null;
            var columns = new List<string>
            {
                "full_name", "first_name", "last_name", "middle_name", "middle_initial", "name_extension",
                "employee_id", "fp_template", "role"
            };
            var values = new List<string>
            {
                "@name", "@firstName", "@lastName", "@middleName", "@middleInitial", "@nameExtension",
                "@eid", "NULL", "'employee'"
            };

            if (hasName)
            {
                columns.Add("name");
                values.Add("@name");
            }

            if (hasEmail)
            {
                columns.Add("email");
                values.Add("@email");
            }

            if (hasUsername)
            {
                columns.Add("username");
                values.Add("@username");
            }

            if (hasPassword)
            {
                columns.Add("password");
                values.Add("@password");
            }
            if (hasAddress)
            {
                columns.Add("address");
                values.Add("@address");
            }
            if (hasContactNumber)
            {
                columns.Add("contact_number");
                values.Add("@contact");
            }

            columns.Insert(0, "id");
            values.Insert(0, "@userId");
            string sql = $@"INSERT INTO users ({string.Join(", ", columns)})
                            VALUES ({string.Join(", ", values)})";
            AcquirePersonIdAssignmentLock(conn);
            try
            {
                using (var transaction = conn.BeginTransaction())
                {
                    int userId = GetLowestAvailableId(conn, transaction, "users", "id", 1);
                    using var cmd = new MySqlCommand(sql, conn, transaction);
                    cmd.Parameters.AddWithValue("@userId", userId);
                    cmd.Parameters.AddWithValue("@name", fullName);
                    cmd.Parameters.AddWithValue("@firstName", (object)firstName ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@lastName", (object)lastName ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@middleName", (object)middleName ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@middleInitial", (object)middleInitial ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@nameExtension", (object)nameExtension ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@eid", employeeId);
                    if (hasEmail)
                        cmd.Parameters.AddWithValue("@email", generatedEmail);
                    if (hasUsername)
                        cmd.Parameters.AddWithValue("@username", generatedEmail ?? employeeId ?? fullName);
                    if (hasPassword)
                        cmd.Parameters.AddWithValue("@password", string.Empty);
                    if (hasAddress)
                        cmd.Parameters.AddWithValue("@address", address ?? string.Empty);
                    if (hasContactNumber)
                        cmd.Parameters.AddWithValue("@contact", contactNumber ?? string.Empty);
                    cmd.ExecuteNonQuery();

                    foreach (var template in templates)
                    {
                        int fingerprintId = GetLowestAvailableId(
                            conn, transaction, "fingerprint_templates", "fingerprint_id", 1);
                        if (fingerprintId >= VisitorFingerprintIdRangeStart)
                            throw new InvalidOperationException("No employee fingerprint template IDs remain.");

                        using var templateCmd = new MySqlCommand(
                            @"INSERT INTO fingerprint_templates
                              (fingerprint_id, employee_id, finger_label, fp_template)
                              VALUES (@fingerprintId, @employeeId, @fingerLabel, @template)",
                            conn, transaction);
                        templateCmd.Parameters.AddWithValue("@fingerprintId", fingerprintId);
                        templateCmd.Parameters.AddWithValue("@employeeId", userId);
                        templateCmd.Parameters.AddWithValue("@fingerLabel", template.Key);
                        templateCmd.Parameters.AddWithValue("@template", template.Value);
                        templateCmd.ExecuteNonQuery();
                        fingerprintIds.Add(fingerprintId);
                    }

                    transaction.Commit();
                    return userId;
                }
            }
            finally
            {
                ReleasePersonIdAssignmentLock(conn);
            }
        }

        private static string BuildGeneratedEmployeeEmail(
            MySqlConnection conn,
            string firstName,
            string lastName,
            string fullName)
        {
            if (string.IsNullOrWhiteSpace(firstName) || string.IsNullOrWhiteSpace(lastName))
            {
                var nameParts = (fullName ?? string.Empty)
                    .Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries);
                if (string.IsNullOrWhiteSpace(firstName) && nameParts.Length > 0)
                    firstName = nameParts[0];
                if (string.IsNullOrWhiteSpace(lastName) && nameParts.Length > 1)
                    lastName = nameParts[nameParts.Length - 1];
            }

            string firstNameSlug = EmailNamePart(firstName);
            string lastNameSlug = EmailNamePart(lastName);
            if (string.IsNullOrEmpty(firstNameSlug))
                firstNameSlug = "employee";
            if (string.IsNullOrEmpty(lastNameSlug))
                lastNameSlug = "employee";

            string baseAddress = $"{firstNameSlug}.{lastNameSlug}";
            string candidate = $"{baseAddress}@biopaylog.com".ToLowerInvariant();
            int suffix = 2;
            using var cmd = new MySqlCommand(
                "SELECT COUNT(*) FROM users WHERE email = @email",
                conn);
            cmd.Parameters.Add("@email", MySqlDbType.VarChar);
            while (true)
            {
                cmd.Parameters["@email"].Value = candidate;
                if (Convert.ToInt32(cmd.ExecuteScalar()) == 0)
                    return candidate;

                candidate = $"{baseAddress}.{suffix}@biopaylog.com".ToLowerInvariant();
                suffix++;
            }
        }

        private static string EmailNamePart(string value)
        {
            string normalized = (value ?? string.Empty).Trim().Normalize(NormalizationForm.FormD);
            var result = new StringBuilder(normalized.Length);
            bool separatorPending = false;

            foreach (char character in normalized)
            {
                if (CharUnicodeInfo.GetUnicodeCategory(character) == UnicodeCategory.NonSpacingMark)
                    continue;

                char lower = char.ToLowerInvariant(character);
                if ((lower >= 'a' && lower <= 'z') || (lower >= '0' && lower <= '9'))
                {
                    if (separatorPending && result.Length > 0)
                        result.Append('.');
                    result.Append(lower);
                    separatorPending = false;
                }
                else
                {
                    separatorPending = true;
                }
            }

            return result.ToString();
        }

        // Load all users with their fingerprint templates (for matching)
        public List<UserModel> GetAllUsersWithTemplates()
        {
            var users = new List<UserModel>();
            using var conn = CreateOpenConnection();
            string sql = @"SELECT DISTINCT
                               u.id,
                               COALESCE(NULLIF(u.name, ''), NULLIF(u.full_name, ''), '(Unnamed)') AS full_name,
                               u.employee_id,
                               NULL AS fp_template
                           FROM users u
                           INNER JOIN fingerprint_templates f ON f.employee_id = u.id";
            using var cmd = new MySqlCommand(sql, conn);
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                users.Add(new UserModel
                {
                    Id = reader.GetInt32("id"),
                    FullName = reader.GetString("full_name"),
                    EmployeeId = reader.IsDBNull(reader.GetOrdinal("employee_id"))
                                 ? "" : reader.GetString("employee_id"),
                    FpTemplate = reader["fp_template"] as byte[]
                });
            }
            return users;
        }

        /// <summary>
        /// Resolves a scanner template ID to its employee and verifies the mapping still exists in MySQL.
        /// </summary>
        public UserModel TryGetUserWithValidTemplateByFingerprintId(int fingerprintId)
        {
            if (fingerprintId < 1 || fingerprintId >= VisitorFingerprintIdRangeStart) return null;
            using var conn = CreateOpenConnection();
            const string sql = @"SELECT
                                     u.id,
                                     COALESCE(NULLIF(u.name, ''), NULLIF(u.full_name, ''), '(Unnamed)') AS full_name,
                                     u.employee_id,
                                     f.fp_template
                                 FROM fingerprint_templates f
                                 INNER JOIN users u ON u.id = f.employee_id
                                 WHERE f.fingerprint_id = @fingerprintId";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@fingerprintId", fingerprintId);
            using var reader = cmd.ExecuteReader();
            if (!reader.Read()) return null;
            return new UserModel
            {
                Id = reader.GetInt32("id"),
                FullName = reader.GetString("full_name"),
                EmployeeId = reader.IsDBNull(reader.GetOrdinal("employee_id"))
                    ? "" : reader.GetString("employee_id"),
                FpTemplate = reader["fp_template"] as byte[]
            };
        }

        public string GetUserDisplayNameById(int userId)
        {
            if (userId < 1) return null;
            using var conn = CreateOpenConnection();
            const string sql = @"SELECT COALESCE(NULLIF(name, ''), NULLIF(full_name, ''), '(Unnamed)') AS display_name
                                 FROM users
                                 WHERE id = @id
                                 LIMIT 1";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@id", userId);
            var result = cmd.ExecuteScalar();
            return result == null || result is DBNull ? null : Convert.ToString(result);
        }

        // Log a single session punch, serialized per employee to prevent duplicate concurrent sessions.
        public decimal LogAttendance(int userId, string entryType)
        {
            if (!string.Equals(entryType, "IN", StringComparison.OrdinalIgnoreCase) &&
                !string.Equals(entryType, "OUT", StringComparison.OrdinalIgnoreCase))
                throw new ArgumentException("Attendance entry type must be IN or OUT.", nameof(entryType));

            bool isTimeIn = string.Equals(entryType, "IN", StringComparison.OrdinalIgnoreCase);
            DateTime now = DateTime.Now;
            if (isTimeIn && !IsEmployeeTimeInWithinAllowedSchedule(now))
                throw new InvalidOperationException(TimeInOutsideAllowedScheduleMessage);

            using var conn = CreateOpenConnection();
            DateTime today = now.Date;
            using (var transaction = conn.BeginTransaction())
            {
                using (var lockCmd = new MySqlCommand("SELECT id FROM users WHERE id = @uid FOR UPDATE", conn, transaction))
                {
                    lockCmd.Parameters.AddWithValue("@uid", userId);
                    if (lockCmd.ExecuteScalar() == null)
                        throw new InvalidOperationException("Attendance could not be recorded because the employee no longer exists.");
                }

                int? openSessionId = null;
                DateTime? openTimeIn = null;
                using (var sessionCmd = new MySqlCommand(@"
                    SELECT id, time_in
                    FROM attendances
                    WHERE user_id = @uid AND attendance_date = @date AND time_in IS NOT NULL AND time_out IS NULL
                    ORDER BY time_in DESC, id DESC
                    LIMIT 1
                    FOR UPDATE", conn, transaction))
                {
                    sessionCmd.Parameters.AddWithValue("@uid", userId);
                    sessionCmd.Parameters.AddWithValue("@date", today.ToString("yyyy-MM-dd"));
                    using var reader = sessionCmd.ExecuteReader();
                    if (reader.Read())
                    {
                        openSessionId = reader.GetInt32("id");
                        if (!TryReadAttendanceDateTime(reader["time_in"], out DateTime parsedTimeIn))
                            throw new InvalidOperationException("The open attendance session has an invalid Time-In value.");
                        openTimeIn = parsedTimeIn;
                    }
                }

                if (isTimeIn == openSessionId.HasValue)
                    throw new InvalidOperationException(isTimeIn
                        ? "Time-In was not recorded because a session is already open. Record Time-Out first."
                        : "Time-Out was not recorded because there is no open session.");

                if (isTimeIn)
                {
                    using var insertCmd = new MySqlCommand(@"
                        INSERT INTO attendances (user_id, attendance_date, time_in, created_at, updated_at)
                        SELECT @uid, @date, NOW(), NOW(), NOW()
                        WHERE TIME(NOW()) >= '05:00:00' AND TIME(NOW()) < '17:00:00'", conn, transaction);
                    insertCmd.Parameters.AddWithValue("@uid", userId);
                    insertCmd.Parameters.AddWithValue("@date", today.ToString("yyyy-MM-dd"));
                    if (insertCmd.ExecuteNonQuery() != 1)
                        throw new InvalidOperationException(TimeInOutsideAllowedScheduleMessage);
                }
                else
                {
                    DateTime nextAllowedOutAt = openTimeIn.Value.AddMinutes(MinimumMinutesBetweenEmployeeTimeInAndTimeOut);
                    if (DateTime.Now < nextAllowedOutAt)
                        throw new InvalidOperationException($"Time-Out is available after {nextAllowedOutAt:hh:mm tt}.");

                    using var updateCmd = new MySqlCommand(@"
                        UPDATE attendances
                        SET time_out = NOW(), updated_at = NOW()
                        WHERE id = @id AND time_out IS NULL", conn, transaction);
                    updateCmd.Parameters.AddWithValue("@id", openSessionId.Value);
                    if (updateCmd.ExecuteNonQuery() != 1)
                        throw new InvalidOperationException("Time-Out was not recorded because the session is no longer open.");
                }

                decimal totalHours = GetDailyWorkedHours(conn, transaction, userId, today);
                transaction.Commit();
                return totalHours;
            }
        }
        public bool TryDetermineNextAttendanceEntry(int userId, TimeSpan minimumGapBeforeOut, out string nextEntryType, out DateTime? nextAllowedOutAt)
        {
            nextEntryType = "IN";
            nextAllowedOutAt = null;

            using var conn = CreateOpenConnection();
            const string sql = @"
                SELECT id, time_in, time_out
                FROM attendances
                WHERE user_id = @uid
                  AND attendance_date = @date
                ORDER BY time_in DESC, id DESC";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@uid", userId);
            cmd.Parameters.AddWithValue("@date", DateTime.Now.Date.ToString("yyyy-MM-dd"));
            using var reader = cmd.ExecuteReader();
            bool hasOpenSession = false;
            DateTime? openTimeIn = null;
            while (reader.Read())
            {
                object timeInValue = reader.GetValue(reader.GetOrdinal("time_in"));
                object timeOutValue = reader.GetValue(reader.GetOrdinal("time_out"));
                if (timeInValue != DBNull.Value && timeOutValue == DBNull.Value)
                {
                    hasOpenSession = true;
                    if (!TryReadAttendanceDateTime(timeInValue, out DateTime parsedTimeIn))
                        throw new InvalidOperationException("The open attendance session has an invalid Time-In value.");
                    openTimeIn = parsedTimeIn;
                    break;
                }
            }
            reader.Close();

            if (hasOpenSession)
            {
                nextEntryType = "OUT";
                nextAllowedOutAt = openTimeIn.Value.Add(minimumGapBeforeOut);
                return DateTime.Now >= nextAllowedOutAt.Value;
            }

            nextEntryType = "IN";
            return true;
        }

        // Get attendance records (for the log viewer) - reads directly from attendances table
        public List<AttendanceModel> GetAttendanceLogs(DateTime date)
        {
            using var conn = CreateOpenConnection();
            var result = new List<AttendanceModel>();
            DateTime filterDate = date == DateTime.MinValue ? DateTime.Today : date.Date;
            string sql = @"SELECT
                               a.id,
                               a.user_id,
                               COALESCE(NULLIF(u.full_name, ''), '(Unnamed)') AS full_name,
                               a.time_in,
                               a.time_out,
                               COALESCE(daily.total_seconds, 0) AS daily_total_seconds,
                               COALESCE(daily.has_active_session, 0) AS has_active_session
                            FROM attendances a
                            JOIN users u ON a.user_id = u.id
                            LEFT JOIN (
                                SELECT user_id,
                                       SUM(CASE
                                           WHEN time_in IS NOT NULL AND time_out IS NULL
                                           THEN TIMESTAMPDIFF(SECOND, time_in, NOW())
                                           WHEN time_in IS NOT NULL AND time_out IS NOT NULL AND time_out >= time_in
                                           THEN TIMESTAMPDIFF(SECOND, time_in, time_out)
                                           ELSE 0
                                       END) AS total_seconds,
                                       MAX(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) AS has_active_session
                                FROM attendances
                                WHERE attendance_date = @date
                                GROUP BY user_id
                            ) daily ON daily.user_id = a.user_id
                            WHERE a.attendance_date = @date
                            ORDER BY a.user_id ASC, a.time_in DESC, a.id DESC";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@date", filterDate.ToString("yyyy-MM-dd"));
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                int userId = reader.GetInt32("user_id");
                string fullName = reader.GetString("full_name");
                object timeInObj = reader["time_in"];
                object timeOutObj = reader["time_out"];

                result.Add(new AttendanceModel
                {
                    SessionId = reader.GetInt32("id"),
                    UserId = userId,
                    FullName = fullName,
                    TimeIn = TryReadAttendanceDateTime(timeInObj, out DateTime timeIn) ? timeIn : (DateTime?)null,
                    TimeOut = TryReadAttendanceDateTime(timeOutObj, out DateTime timeOut) ? timeOut : (DateTime?)null,
                    DailyTotalHours = Convert.ToDecimal(reader["daily_total_seconds"]) / 3600m,
                    HasActiveSession = Convert.ToInt32(reader["has_active_session"]) != 0,
                    DailyTotalHoursAsOf = DateTime.Now
                });
            }

            return result
                .OrderByDescending(GetLastAttendanceTime)
                .ToList();
        }

        private static DateTime GetLastAttendanceTime(AttendanceModel attendance)
        {
            if (!attendance.TimeIn.HasValue)
                return attendance.TimeOut ?? DateTime.MinValue;
            if (!attendance.TimeOut.HasValue)
                return attendance.TimeIn.Value;
            return attendance.TimeIn.Value > attendance.TimeOut.Value
                ? attendance.TimeIn.Value
                : attendance.TimeOut.Value;
        }

        private static bool TryReadAttendanceDateTime(object value, out DateTime dateTime)
        {
            if (value is DateTime storedDateTime)
            {
                dateTime = storedDateTime;
                return true;
            }

            if (value == null || value == DBNull.Value)
            {
                dateTime = default;
                return false;
            }

            string text = Convert.ToString(value, CultureInfo.CurrentCulture);
            return DateTime.TryParse(text, CultureInfo.CurrentCulture, DateTimeStyles.None, out dateTime) ||
                   DateTime.TryParse(text, CultureInfo.InvariantCulture, DateTimeStyles.None, out dateTime);
        }

        private static decimal GetDailyWorkedHours(
            MySqlConnection conn,
            MySqlTransaction transaction,
            int userId,
            DateTime date)
        {
            const string sql = @"
                SELECT COALESCE(SUM(
                    CASE
                        WHEN time_in IS NOT NULL
                         AND COALESCE(time_out, NOW()) >= time_in
                        THEN TIMESTAMPDIFF(SECOND, time_in, COALESCE(time_out, NOW()))
                        ELSE 0
                    END
                ), 0)
                FROM attendances
                WHERE user_id = @uid AND attendance_date = @date";
            using var cmd = new MySqlCommand(sql, conn, transaction);
            cmd.Parameters.AddWithValue("@uid", userId);
            cmd.Parameters.AddWithValue("@date", date.ToString("yyyy-MM-dd"));
            return Convert.ToDecimal(cmd.ExecuteScalar()) / 3600m;
        }

        // Get all users (for list view)
        public List<UserModel> GetAllUsers()
        {
            var users = new List<UserModel>();
            using var conn = CreateOpenConnection();
            string sql = @"SELECT
                               id,
                               COALESCE(NULLIF(name, ''), NULLIF(full_name, ''), '(Unnamed)') AS full_name,
                               employee_id,
                               created_at
                           FROM users
                           ORDER BY full_name";
            using var cmd = new MySqlCommand(sql, conn);
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                users.Add(new UserModel
                {
                    Id = reader.GetInt32("id"),
                    FullName = reader.GetString("full_name"),
                    EmployeeId = reader.IsDBNull(reader.GetOrdinal("employee_id"))
                                 ? "" : reader.GetString("employee_id"),
                    CreatedAt = reader.GetDateTime("created_at")
                });
            }
            return users;
        }

        public int GetNextVisitorFingerprintId()
        {
            using var conn = CreateOpenConnection();
            AcquirePersonIdAssignmentLock(conn);
            try
            {
                return GetLowestAvailableId(
                    conn, null, "logbook", "fingerprint_id", VisitorFingerprintIdRangeStart);
            }
            finally
            {
                ReleasePersonIdAssignmentLock(conn);
            }
        }

        public VisitorProfile GetVisitorProfile(int fingerprintId)
        {
            using var conn = CreateOpenConnection();

            string sql = @"
                SELECT l.fingerprint_id, l.person_id, l.person_classification,
                       l.full_name, l.address, l.contact_number, l.office_or_person_to_visit,
                       EXISTS(
                           SELECT 1 FROM logbook x
                           WHERE x.person_id = l.person_id AND x.fp_template IS NOT NULL
                           LIMIT 1
                       ) AS has_fp
                FROM logbook l
                WHERE l.fingerprint_id = @fid
                ORDER BY l.logbook_id DESC
                LIMIT 1";

            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@fid", fingerprintId);
            using var reader = cmd.ExecuteReader();
            if (!reader.Read()) return null;

            return new VisitorProfile
            {
                FingerprintId = reader.GetInt32("fingerprint_id"),
                PersonId = reader.GetInt32("person_id"),
                PersonClassification = reader.GetString("person_classification"),
                FullName = reader.GetString("full_name"),
                Address = reader.GetString("address"),
                ContactNumber = reader.IsDBNull(reader.GetOrdinal("contact_number"))
                    ? string.Empty
                    : reader.GetString("contact_number"),
                OfficeOrPersonToVisit = reader.GetString("office_or_person_to_visit"),
                HasFingerprintOnFile = Convert.ToInt32(reader["has_fp"]) == 1
            };
        }

        /// <summary>
        /// Latest logbook row per person account, filtered by name/contact/address.
        /// Empty <paramref name="searchTerm"/> returns all saved person profiles.
        /// </summary>
        public List<VisitorIdentityPickRow> SearchVisitorIdentitiesForReturnVisit(string searchTerm, int limit = 500)
        {
            return SearchVisitorIdentitiesForReturnVisit(searchTerm, 0, limit, out _);
        }

        public List<VisitorIdentityPickRow> SearchVisitorIdentitiesForReturnVisit(
            string searchTerm,
            int pageIndex,
            int pageSize,
            out int totalCount)
        {
            var list = new List<VisitorIdentityPickRow>();
            string term = searchTerm?.Trim() ?? string.Empty;
            bool hasTerm = term.Length > 0;
            string like = hasTerm
                ? "%" + term.Replace("%", "\\%").Replace("_", "\\_") + "%"
                : null;

            using var conn = CreateOpenConnection();

            string fromSql = @"
                FROM logbook l
                INNER JOIN (
                    SELECT person_id, MAX(logbook_id) AS mid
                    FROM logbook
                    WHERE person_id IS NOT NULL
                    GROUP BY person_id
                ) z ON l.person_id = z.person_id AND l.logbook_id = z.mid";

            string filterSql = string.Empty;
            if (hasTerm)
            {
                filterSql = @"
                WHERE IFNULL(l.full_name, '') LIKE @like
                   OR IFNULL(l.contact_number, '') LIKE @like
                   OR IFNULL(l.address, '') LIKE @like";
            }

            using (var countCmd = new MySqlCommand("SELECT COUNT(*) " + fromSql + filterSql, conn))
            {
                if (hasTerm)
                    countCmd.Parameters.AddWithValue("@like", like);
                totalCount = Convert.ToInt32(countCmd.ExecuteScalar());
            }

            if (pageIndex < 0)
                throw new ArgumentOutOfRangeException(nameof(pageIndex));
            if (pageSize <= 0)
                throw new ArgumentOutOfRangeException(nameof(pageSize));

            string sql = @"
                SELECT l.fingerprint_id, l.person_id,
                       IFNULL(l.person_classification, '') AS person_classification,
                       IFNULL(l.full_name, '') AS full_name,
                       IFNULL(l.address, '') AS address,
                       IFNULL(l.contact_number, '') AS contact_number,
                       l.time_in,
                       EXISTS(
                          SELECT 1 FROM logbook x
                          WHERE x.person_id = l.person_id AND x.fp_template IS NOT NULL
                          LIMIT 1
                       ) AS has_fp
                " + fromSql + filterSql + @"
                ORDER BY l.full_name
                LIMIT @lim OFFSET @offset";

            using var cmd = new MySqlCommand(sql, conn);
            if (hasTerm)
                cmd.Parameters.AddWithValue("@like", like);
            cmd.Parameters.AddWithValue("@lim", pageSize);
            cmd.Parameters.AddWithValue("@offset", checked(pageIndex * pageSize));
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                list.Add(new VisitorIdentityPickRow
                {
                    FingerprintId = reader.IsDBNull(reader.GetOrdinal("fingerprint_id")) ? 0 : reader.GetInt32("fingerprint_id"),
                    PersonId = reader.GetInt32("person_id"),
                    PersonClassification = reader.IsDBNull(reader.GetOrdinal("person_classification")) ? string.Empty : reader.GetString("person_classification"),
                    FullName = reader.IsDBNull(reader.GetOrdinal("full_name")) ? string.Empty : reader.GetString("full_name"),
                    Address = reader.IsDBNull(reader.GetOrdinal("address")) ? string.Empty : reader.GetString("address"),
                    ContactNumber = reader.IsDBNull(reader.GetOrdinal("contact_number")) ? string.Empty : reader.GetString("contact_number"),
                    LastVisit = reader.IsDBNull(reader.GetOrdinal("time_in")) ? DateTime.MinValue : reader.GetDateTime("time_in"),
                    HasFingerprintOnFile = Convert.ToInt32(reader["has_fp"]) == 1
                });
            }

            return list;
        }

        /// <summary>
        /// True when an employee or logbook person already exists with the same full name and complete address.
        /// Used only when creating a new person; search, view, return visits, and updates are not blocked.
        /// </summary>
        public bool IsPersonAlreadyRegistered(string fullName, string completeAddress)
        {
            string name = NormalizePersonCompareValue(fullName);
            string address = NormalizePersonCompareValue(completeAddress);
            if (string.IsNullOrEmpty(name) || string.IsNullOrEmpty(address))
                return false;

            using var conn = CreateOpenConnection();

            if (TableExists(conn, "users"))
            {
                bool hasFullName = ColumnExists(conn, "users", "full_name");
                bool hasName = ColumnExists(conn, "users", "name");
                bool hasAddress = ColumnExists(conn, "users", "address");
                if (hasAddress && (hasFullName || hasName))
                {
                    string nameExpr = hasFullName && hasName
                        ? "COALESCE(NULLIF(full_name, ''), NULLIF(name, ''), '')"
                        : hasFullName ? "IFNULL(full_name, '')" : "IFNULL(name, '')";
                    string sql = $"SELECT {nameExpr} AS full_name, IFNULL(address, '') AS address FROM users";
                    using var cmd = new MySqlCommand(sql, conn);
                    using var reader = cmd.ExecuteReader();
                    if (ReaderContainsPerson(reader, name, address))
                        return true;
                }
            }

            if (TableExists(conn, "logbook") && ColumnExists(conn, "logbook", "full_name") && ColumnExists(conn, "logbook", "address"))
            {
                const string sql = "SELECT IFNULL(full_name, '') AS full_name, IFNULL(address, '') AS address FROM logbook";
                using var cmd = new MySqlCommand(sql, conn);
                using var reader = cmd.ExecuteReader();
                if (ReaderContainsPerson(reader, name, address))
                    return true;
            }

            return false;
        }

        private static bool ReaderContainsPerson(MySqlDataReader reader, string normalizedName, string normalizedAddress)
        {
            while (reader.Read())
            {
                string rowName = NormalizePersonCompareValue(reader.IsDBNull(0) ? string.Empty : reader.GetString(0));
                string rowAddress = NormalizePersonCompareValue(reader.IsDBNull(1) ? string.Empty : reader.GetString(1));
                if (rowName == normalizedName && rowAddress == normalizedAddress)
                    return true;
            }

            return false;
        }

        public static string NormalizePersonCompareValue(string value)
        {
            if (string.IsNullOrWhiteSpace(value))
                return string.Empty;

            string flattened = value.Replace(',', ' ');
            string[] parts = flattened.Trim().Split((char[])null, StringSplitOptions.RemoveEmptyEntries);
            return string.Join(" ", parts).ToLowerInvariant();
        }

        /// <summary>
        /// If a standard visitor identity already exists with the same normalized name and contact, reuse its fingerprint id to avoid duplicate profiles.
        /// </summary>
        public bool TryFindExistingVisitorIdentity(string fullName, string contactNumber, out int fingerprintId)
        {
            fingerprintId = 0;
            VisitorProfile existing = FindExistingPersonProfile(fullName, contactNumber);
            if (existing == null || existing.FingerprintId <= 0)
                return false;

            fingerprintId = existing.FingerprintId;
            return true;
        }

        public VisitorProfile FindExistingPersonProfile(string fullName, string contactNumber)
        {
            string n = (fullName ?? "").Trim().ToLowerInvariant();
            string c = NormalizeVisitorContactCompare(contactNumber);
            if (string.IsNullOrEmpty(n) || string.IsNullOrEmpty(c))
                return null;

            using var conn = CreateOpenConnection();
            string sql = @"
                SELECT l.fingerprint_id, l.person_id, l.person_classification,
                       l.full_name, l.address, l.contact_number,
                       EXISTS(
                           SELECT 1 FROM logbook x
                           WHERE x.person_id = l.person_id AND x.fp_template IS NOT NULL
                           LIMIT 1
                       ) AS has_fp
                FROM logbook l
                INNER JOIN (
                    SELECT person_id, MAX(logbook_id) AS mid
                    FROM logbook
                    WHERE person_id IS NOT NULL
                    GROUP BY person_id
                ) z ON l.person_id = z.person_id AND l.logbook_id = z.mid
                WHERE LOWER(TRIM(l.full_name)) = @name
                ORDER BY l.logbook_id DESC";

            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@name", n.Length > 150 ? n.Substring(0, 150) : n);
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                string savedContact = reader.IsDBNull(reader.GetOrdinal("contact_number"))
                    ? string.Empty
                    : reader.GetString("contact_number");
                if (!string.Equals(NormalizeVisitorContactCompare(savedContact), c, StringComparison.Ordinal))
                    continue;

                return new VisitorProfile
                {
                    FingerprintId = reader.IsDBNull(reader.GetOrdinal("fingerprint_id"))
                        ? 0
                        : reader.GetInt32("fingerprint_id"),
                    PersonId = reader.GetInt32("person_id"),
                    PersonClassification = reader.GetString("person_classification"),
                    FullName = reader.GetString("full_name"),
                    Address = reader.GetString("address"),
                    ContactNumber = savedContact,
                    HasFingerprintOnFile = Convert.ToInt32(reader["has_fp"]) == 1
                };
            }

            return null;
        }

        /// <summary>
        /// True if any visitor-type logbook row for this fingerprint id has a stored template (same rule as visitor profile search).
        /// </summary>
        public bool VisitorIdentityHasFingerprintTemplate(int fingerprintId)
        {
            if (fingerprintId <= 0)
                return false;

            using var conn = CreateOpenConnection();
            const string sql = @"
                SELECT EXISTS(
                    SELECT 1 FROM logbook x
                    WHERE x.fingerprint_id = @fid
                      AND x.fp_template IS NOT NULL
                      AND COALESCE(NULLIF(x.transaction_type, ''), @visitorType) = @visitorType
                    LIMIT 1
                )";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@fid", fingerprintId);
            cmd.Parameters.AddWithValue("@visitorType", LogbookTransactionTypeVisitor);
            return Convert.ToInt32(cmd.ExecuteScalar()) == 1;
        }

        private static string NormalizeVisitorContactCompare(string contactNumber)
        {
            if (string.IsNullOrWhiteSpace(contactNumber)) return string.Empty;
            string s = contactNumber.Trim()
                .Replace(" ", "")
                .Replace("-", "")
                .Replace("(", "")
                .Replace(")", "")
                .Replace("+", "");
            if (s.StartsWith("09") && s.Length == 11)
                return "63" + s.Substring(1);
            if (s.StartsWith("630") && s.Length == 13)
                return "63" + s.Substring(3);
            return s;
        }

        public int? InsertNewVisitorLog(
            int? fingerprintId,
            string fullName,
            string address,
            string contactNumber,
            string purposeOfTransaction,
            string officeOrPersonToVisit,
            bool isAuthorizedRepresentative,
            bool authorizationLetterVerified,
            bool validIdVerified,
            string representedPersonName,
            byte[] fingerprintTemplate,
            string transactionType = null,
            int? personId = null,
            string personClassification = "Visitor",
            string representedPersonClassification = "Visitor")
        {
            string resolvedTransactionType = string.IsNullOrWhiteSpace(transactionType)
                ? LogbookTransactionTypeVisitor
                : transactionType.Trim();
            if (!string.Equals(
                    resolvedTransactionType,
                    LogbookTransactionTypeDocumentClaimBehalf,
                    StringComparison.OrdinalIgnoreCase) &&
                !PurposeOfVisitCatalog.IsValidStoredPurpose(purposeOfTransaction))
            {
                throw new ArgumentException(
                    "Choose a standard purpose of visit or enter details for Other.",
                    nameof(purposeOfTransaction));
            }

            using var conn = CreateOpenConnection();
            AcquirePersonIdAssignmentLock(conn);
            try
            {
                using (var transaction = conn.BeginTransaction())
                {
                    int assignedPersonId = personId ?? GetLowestAvailableId(conn, transaction, "logbook", "person_id", 1);
                    int? assignedFingerprintId = fingerprintId;
                    bool fingerprintBelongsToPerson = false;
                    if (personId.HasValue && fingerprintId.HasValue)
                    {
                        using var checkFingerprint = new MySqlCommand(
                            @"SELECT COUNT(*) FROM logbook
                              WHERE fingerprint_id = @fingerprintId AND person_id = @personId",
                            conn,
                            transaction);
                        checkFingerprint.Parameters.AddWithValue("@fingerprintId", fingerprintId.Value);
                        checkFingerprint.Parameters.AddWithValue("@personId", personId.Value);
                        fingerprintBelongsToPerson = Convert.ToInt32(checkFingerprint.ExecuteScalar()) > 0;
                    }

                    if (!fingerprintBelongsToPerson &&
                        (fingerprintId.HasValue || fingerprintTemplate != null))
                    {
                        assignedFingerprintId = GetLowestAvailableId(
                            conn, transaction, "logbook", "fingerprint_id", VisitorFingerprintIdRangeStart);
                    }

                    string tt = resolvedTransactionType;
                    string classification = NormalizePersonClassification(personClassification);
                    string representedClassification = NormalizePersonClassification(representedPersonClassification);

                    const string sql = @"
                        INSERT INTO logbook
                        (full_name, address, contact_number, purpose_of_transaction, office_or_person_to_visit,
                         is_authorized_representative, authorization_letter_verified, valid_id_verified, represented_person_name,
                         transaction_type, person_id, person_classification, represented_person_classification,
                         fingerprint_id, fp_template)
                        VALUES (@name, @address, @contact, @purpose, @officeToVisit,
                                @isAuthorizedRep, @letterVerified, @validIdVerified, @representedPersonName,
                                @transactionType, @personId, @personClassification, @representedClassification, @fid, @tpl)";

                    using var cmd = new MySqlCommand(sql, conn, transaction);
                    cmd.Parameters.AddWithValue("@name", fullName);
                    cmd.Parameters.AddWithValue("@address", address);
                    cmd.Parameters.AddWithValue("@contact",
                        string.IsNullOrWhiteSpace(contactNumber) ? (object)DBNull.Value : contactNumber);
                    cmd.Parameters.AddWithValue("@purpose", purposeOfTransaction);
                    cmd.Parameters.AddWithValue("@officeToVisit", officeOrPersonToVisit ?? string.Empty);
                    cmd.Parameters.AddWithValue("@isAuthorizedRep", isAuthorizedRepresentative ? 1 : 0);
                    cmd.Parameters.AddWithValue("@letterVerified", authorizationLetterVerified ? 1 : 0);
                    cmd.Parameters.AddWithValue("@validIdVerified", validIdVerified ? 1 : 0);
                    cmd.Parameters.AddWithValue("@representedPersonName", representedPersonName ?? string.Empty);
                    cmd.Parameters.AddWithValue("@transactionType", tt);
                    cmd.Parameters.AddWithValue("@personId", assignedPersonId);
                    cmd.Parameters.AddWithValue("@personClassification", classification);
                    cmd.Parameters.AddWithValue("@representedClassification", representedClassification);
                    cmd.Parameters.AddWithValue("@fid", assignedFingerprintId.HasValue ? (object)assignedFingerprintId.Value : DBNull.Value);
                    cmd.Parameters.AddWithValue("@tpl", (object)fingerprintTemplate ?? DBNull.Value);
                    cmd.ExecuteNonQuery();

                    transaction.Commit();
                    return assignedFingerprintId;
                }
            }
            finally
            {
                ReleasePersonIdAssignmentLock(conn);
            }
        }

        private static int GetLowestAvailableId(
            MySqlConnection conn,
            MySqlTransaction transaction,
            string tableName,
            string columnName,
            int startAt)
        {
            string sql = $@"SELECT DISTINCT `{columnName}`
                            FROM `{tableName}`
                            WHERE `{columnName}` >= @startAt
                            ORDER BY `{columnName}`";
            using var cmd = new MySqlCommand(sql, conn, transaction);
            cmd.Parameters.AddWithValue("@startAt", startAt);
            using var reader = cmd.ExecuteReader();

            int candidate = startAt;
            while (reader.Read())
            {
                int existingId = reader.GetInt32(0);
                if (existingId > candidate)
                    return candidate;
                if (existingId == candidate)
                {
                    if (candidate == int.MaxValue)
                        throw new InvalidOperationException("No available IDs remain in the supported range.");
                    candidate++;
                }
            }

            return candidate;
        }

        private static void AcquirePersonIdAssignmentLock(MySqlConnection conn)
        {
            using var cmd = new MySqlCommand("SELECT GET_LOCK(@lockName, 30)", conn);
            cmd.Parameters.AddWithValue("@lockName", PersonIdAssignmentLockName);
            object result = cmd.ExecuteScalar();
            if (result == null || result is DBNull || Convert.ToInt32(result) != 1)
                throw new TimeoutException("Timed out waiting to assign a unique person ID.");
        }

        private static void ReleasePersonIdAssignmentLock(MySqlConnection conn)
        {
            using var cmd = new MySqlCommand("SELECT RELEASE_LOCK(@lockName)", conn);
            cmd.Parameters.AddWithValue("@lockName", PersonIdAssignmentLockName);
            object result = cmd.ExecuteScalar();
            if (result == null || result is DBNull || Convert.ToInt32(result) != 1)
                throw new InvalidOperationException("Unable to release the person ID assignment lock.");
        }

        private static string NormalizePersonClassification(string value)
        {
            if (string.Equals(value, "Visitor", StringComparison.OrdinalIgnoreCase))
                return "Visitor";
            if (string.Equals(value, "Resident", StringComparison.OrdinalIgnoreCase))
                return "Resident";
            if (string.Equals(value, "Personnel", StringComparison.OrdinalIgnoreCase))
                return "Personnel";

            throw new ArgumentOutOfRangeException(nameof(value), value, "Unknown person classification.");
        }

        public bool InsertExistingVisitorLog(int fingerprintId, string purposeOfTransaction, string officeOrPersonToVisit,
            byte[] fingerprintTemplate = null)
        {
            if (!PurposeOfVisitCatalog.IsValidStoredPurpose(purposeOfTransaction))
                throw new ArgumentException(
                    "Choose a standard purpose of visit or enter details for Other.",
                    nameof(purposeOfTransaction));

            using var conn = CreateOpenConnection();

            string selectSql = @"
                SELECT full_name, address, contact_number, person_id, person_classification
                FROM logbook
                WHERE fingerprint_id = @fid
                ORDER BY logbook_id DESC
                LIMIT 1";
            using var selectCmd = new MySqlCommand(selectSql, conn);
            selectCmd.Parameters.AddWithValue("@fid", fingerprintId);

            string fullName = null;
            string address = null;
            string contact = null;
            int personId;
            string personClassification;

            using (var reader = selectCmd.ExecuteReader())
            {
                if (!reader.Read()) return false;
                fullName = reader.GetString("full_name");
                address = reader.GetString("address");
                contact = reader.IsDBNull(reader.GetOrdinal("contact_number"))
                    ? null
                    : reader.GetString("contact_number");
                personId = reader.GetInt32("person_id");
                personClassification = reader.GetString("person_classification");
            }

            string insertSql = @"
                INSERT INTO logbook
                (full_name, address, contact_number, purpose_of_transaction, office_or_person_to_visit,
                 is_authorized_representative, authorization_letter_verified, valid_id_verified, represented_person_name,
                 transaction_type, person_id, person_classification, represented_person_classification,
                 fingerprint_id, fp_template)
                VALUES (@name, @address, @contact, @purpose, @officeToVisit,
                        0, 0, 0, '', @transactionType, @personId, @personClassification, 'Visitor', @fid, @tpl)";
            using var insertCmd = new MySqlCommand(insertSql, conn);
            insertCmd.Parameters.AddWithValue("@name", fullName);
            insertCmd.Parameters.AddWithValue("@address", address);
            insertCmd.Parameters.AddWithValue("@contact",
                string.IsNullOrWhiteSpace(contact) ? (object)DBNull.Value : contact);
            insertCmd.Parameters.AddWithValue("@purpose", purposeOfTransaction);
            insertCmd.Parameters.AddWithValue("@officeToVisit", officeOrPersonToVisit ?? string.Empty);
            insertCmd.Parameters.AddWithValue("@transactionType", LogbookTransactionTypeVisitor);
            insertCmd.Parameters.AddWithValue("@personId", personId);
            insertCmd.Parameters.AddWithValue("@personClassification", personClassification);
            insertCmd.Parameters.AddWithValue("@fid", fingerprintId);
            insertCmd.Parameters.AddWithValue("@tpl", (object)fingerprintTemplate ?? DBNull.Value);
            insertCmd.ExecuteNonQuery();
            return true;
        }

        /// <summary>
        /// Visitor logbook rows for the privileged grid. When <paramref name="searchFilter"/> is empty,
        /// only rows on <paramref name="date"/> (local calendar day of <c>time_in</c>) are returned.
        /// When <paramref name="searchFilter"/> is non-empty, all dates are searched (filter date is ignored).
        /// </summary>
        public List<VisitorLogEntry> GetVisitorLogEntries(DateTime date, string searchFilter = null)
        {
            return GetVisitorLogEntries(date, searchFilter, 1, int.MaxValue, out _);
        }

        public List<VisitorLogEntry> GetVisitorLogEntries(
            DateTime date,
            string searchFilter,
            int pageNumber,
            int pageSize,
            out int totalCount)
        {
            var logs = new List<VisitorLogEntry>();
            if (pageNumber < 1)
                throw new ArgumentOutOfRangeException(nameof(pageNumber), "Page number must be positive.");
            if (pageSize < 1)
                throw new ArgumentOutOfRangeException(nameof(pageSize), "Page size must be positive.");

            using var conn = CreateOpenConnection();

            string filter = searchFilter?.Trim();
            string like = string.IsNullOrEmpty(filter)
                ? null
                : "%" + filter.Replace("%", "\\%").Replace("_", "\\_") + "%";

            bool hasSearch = !string.IsNullOrEmpty(like);
            string whereClause = hasSearch
                ? @"(
                    l.full_name LIKE @like OR l.contact_number LIKE @like OR l.address LIKE @like
                    OR l.purpose_of_transaction LIKE @like OR l.represented_person_name LIKE @like
                    OR l.office_or_person_to_visit LIKE @like)"
                : "DATE(l.time_in) = @date";

            string countSql = $"SELECT COUNT(*) FROM logbook l WHERE {whereClause}";
            using (var countCmd = new MySqlCommand(countSql, conn))
            {
                AddVisitorLogFilterParameters(countCmd, hasSearch, date, like);
                totalCount = Convert.ToInt32(countCmd.ExecuteScalar());
            }

            string sql = @"
                SELECT l.logbook_id, l.full_name, l.address, l.contact_number, l.purpose_of_transaction, l.office_or_person_to_visit,
                       COALESCE(NULLIF(l.transaction_type, ''), 'visitor') AS transaction_type,
                       l.is_authorized_representative, l.authorization_letter_verified, l.valid_id_verified, l.represented_person_name,
                       l.person_classification, l.represented_person_classification,
                       l.fingerprint_id, l.time_in,
                       CASE WHEN l.fingerprint_id IS NULL THEN 0 ELSE EXISTS(
                           SELECT 1 FROM logbook x
                           WHERE x.fingerprint_id = l.fingerprint_id AND x.fp_template IS NOT NULL
                           LIMIT 1
                       ) END AS has_fp
                FROM logbook l
                WHERE " + whereClause + @"
                ORDER BY l.time_in DESC
                LIMIT @pageSize OFFSET @offset";

            using var cmd = new MySqlCommand(sql, conn);
            AddVisitorLogFilterParameters(cmd, hasSearch, date, like);
            cmd.Parameters.AddWithValue("@pageSize", pageSize);
            cmd.Parameters.AddWithValue("@offset", (long)(pageNumber - 1) * pageSize);
            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                logs.Add(new VisitorLogEntry
                {
                    LogbookId = reader.GetInt32("logbook_id"),
                    FullName = reader.GetString("full_name"),
                    Address = reader.GetString("address"),
                    ContactNumber = reader.IsDBNull(reader.GetOrdinal("contact_number"))
                        ? string.Empty
                        : reader.GetString("contact_number"),
                    PurposeOfTransaction = reader.GetString("purpose_of_transaction"),
                    OfficeOrPersonToVisit = reader.GetString("office_or_person_to_visit"),
                    TransactionType = reader.GetString("transaction_type"),
                    IsAuthorizedRepresentative = Convert.ToInt32(reader["is_authorized_representative"]) == 1,
                    AuthorizationLetterVerified = Convert.ToInt32(reader["authorization_letter_verified"]) == 1,
                    ValidIdVerified = Convert.ToInt32(reader["valid_id_verified"]) == 1,
                    RepresentedPersonName = reader.GetString("represented_person_name"),
                    PersonClassification = reader.GetString("person_classification"),
                    RepresentedPersonClassification = reader.GetString("represented_person_classification"),
                    FingerprintId = reader.IsDBNull(reader.GetOrdinal("fingerprint_id")) ? (int?)null : reader.GetInt32("fingerprint_id"),
                    TimeIn = reader.GetDateTime("time_in"),
                    HasFingerprintOnFile = Convert.ToInt32(reader["has_fp"]) == 1
                });
            }
            return logs;
        }

        private static void AddVisitorLogFilterParameters(
            MySqlCommand cmd,
            bool hasSearch,
            DateTime date,
            string like)
        {
            if (hasSearch)
                cmd.Parameters.AddWithValue("@like", like);
            else
                cmd.Parameters.AddWithValue("@date", date.ToString("yyyy-MM-dd"));
        }

        /// <summary>
        /// Clears stored fingerprint templates for a visitor <paramref name="fingerprintId"/>
        /// so the same person can be enrolled again. Logbook rows (name, time, purpose) are kept;
        /// only the binary template data is removed.
        /// </summary>
        public int ClearVisitorFingerprintTemplate(int fingerprintId)
        {
            using var conn = CreateOpenConnection();
            const string sql = "UPDATE logbook SET fp_template = NULL WHERE fingerprint_id = @fid";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@fid", fingerprintId);
            return cmd.ExecuteNonQuery();
        }

        /// <summary>Deletes a single logbook row. Returns false if no row was removed.</summary>
        public bool TryDeleteVisitorLogbookEntry(int logbookId)
        {
            using var conn = CreateOpenConnection();
            using var cmd = new MySqlCommand("DELETE FROM logbook WHERE logbook_id = @id LIMIT 1", conn);
            cmd.Parameters.AddWithValue("@id", logbookId);
            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ValidateAdminCredentials(string username, string password)
        {
            if (string.IsNullOrWhiteSpace(username) || string.IsNullOrWhiteSpace(password))
                return false;

            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            string login = username.Trim();

            string identityPredicate = hasEmail && hasUsername
                ? "(email = @login OR username = @login)"
                : hasEmail
                    ? "email = @login"
                    : "username = @login";

            string sql = $@"
                SELECT password
                FROM users
                WHERE {identityPredicate}
                  AND role = 'admin'
                LIMIT 1";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@login", login);
            var storedObj = cmd.ExecuteScalar();
            if (storedObj == null || storedObj is DBNull)
                return false;

            string stored = Convert.ToString(storedObj) ?? string.Empty;
            if (stored == password)
                return true;

            // Support Laravel / bcrypt hashes like $2y$...
            if (stored.StartsWith("$2y$", StringComparison.Ordinal) ||
                stored.StartsWith("$2a$", StringComparison.Ordinal) ||
                stored.StartsWith("$2b$", StringComparison.Ordinal))
            {
                try
                {
                    return OpenBsdBCrypt.CheckPassword(stored, password.ToCharArray());
                }
                catch
                {
                    return false;
                }
            }

            return false;
        }

        public bool HasAnyActiveAdmin()
        {
            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            string loginPresence = hasEmail && hasUsername
                ? "((email IS NOT NULL AND email <> '') OR (username IS NOT NULL AND username <> ''))"
                : hasEmail
                    ? "(email IS NOT NULL AND email <> '')"
                    : "(username IS NOT NULL AND username <> '')";
            string sql = $@"
                SELECT COUNT(1)
                FROM users
                WHERE role = 'admin'
                  AND {loginPresence}";
            using var cmd = new MySqlCommand(sql, conn);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool ValidateCredentialsForRoles(string username, string password, params string[] roles)
        {
            if (string.IsNullOrWhiteSpace(username) || string.IsNullOrWhiteSpace(password))
                return false;

            var normalizedRoles = NormalizeRoles(roles);
            if (normalizedRoles.Count == 0)
                return false;

            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            string login = username.Trim();
            string identityPredicate = BuildIdentityPredicate(hasEmail, hasUsername);

            var roleParams = new List<string>(normalizedRoles.Count);
            for (int i = 0; i < normalizedRoles.Count; i++)
                roleParams.Add($"@role{i}");

            string sql = $@"
                SELECT password
                FROM users
                WHERE {identityPredicate}
                  AND role IN ({string.Join(", ", roleParams)})
                LIMIT 1";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@login", login);
            for (int i = 0; i < normalizedRoles.Count; i++)
                cmd.Parameters.AddWithValue(roleParams[i], normalizedRoles[i]);

            var storedObj = cmd.ExecuteScalar();
            if (storedObj == null || storedObj is DBNull)
                return false;

            string stored = Convert.ToString(storedObj) ?? string.Empty;
            if (stored == password)
                return true;

            if (stored.StartsWith("$2y$", StringComparison.Ordinal) ||
                stored.StartsWith("$2a$", StringComparison.Ordinal) ||
                stored.StartsWith("$2b$", StringComparison.Ordinal))
            {
                try
                {
                    return OpenBsdBCrypt.CheckPassword(stored, password.ToCharArray());
                }
                catch
                {
                    return false;
                }
            }

            return false;
        }

        public bool HasAnyUserCanAccessLogbook()
        {
            using var conn = CreateOpenConnection();
            bool hasCanAccess = ColumnExists(conn, "users", "can_access_logbook");
            string accessPredicate = hasCanAccess
                ? "((LOWER(TRIM(role)) = 'admin') OR COALESCE(can_access_logbook, 0) = 1)"
                : "(LOWER(TRIM(role)) = 'admin')";
            using var cmd = new MySqlCommand(
                $"SELECT COUNT(1) FROM users WHERE {accessPredicate} LIMIT 1",
                conn);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool UserIdCanAccessLogbook(int userId)
        {
            if (userId <= 0)
                return false;

            using var conn = CreateOpenConnection();
            bool hasCanAccess = ColumnExists(conn, "users", "can_access_logbook");
            string accessPredicate = hasCanAccess
                ? "(LOWER(TRIM(role)) = 'admin' OR COALESCE(can_access_logbook, 0) = 1)"
                : "LOWER(TRIM(role)) = 'admin'";
            using var cmd = new MySqlCommand(
                $"SELECT COUNT(1) FROM users WHERE id = @id AND {accessPredicate} LIMIT 1",
                conn);
            cmd.Parameters.AddWithValue("@id", userId);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool ValidateLogbookCredentials(
            string username,
            string password,
            out bool authenticated,
            out bool authorized)
        {
            authenticated = false;
            authorized = false;
            if (string.IsNullOrWhiteSpace(username) || string.IsNullOrWhiteSpace(password))
                return false;

            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            bool hasCanAccess = ColumnExists(conn, "users", "can_access_logbook");
            string identityPredicate = BuildIdentityPredicate(hasEmail, hasUsername);
            string accessSelect = hasCanAccess
                ? "password AS password_value, role, COALESCE(can_access_logbook, 0) AS can_access_value"
                : "password AS password_value, role, 0 AS can_access_value";
            string sql = $@"
                SELECT {accessSelect}
                FROM users
                WHERE {identityPredicate}
                LIMIT 1";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@login", username.Trim());
            using var reader = cmd.ExecuteReader();
            if (!reader.Read())
                return false;

            string stored = Convert.ToString(reader["password_value"]) ?? string.Empty;
            string role = Convert.ToString(reader["role"]) ?? string.Empty;
            bool canAccess = Convert.ToInt32(reader["can_access_value"]) == 1;

            authenticated = stored == password;
            if (!authenticated &&
                (stored.StartsWith("$2y$", StringComparison.Ordinal) ||
                 stored.StartsWith("$2a$", StringComparison.Ordinal) ||
                 stored.StartsWith("$2b$", StringComparison.Ordinal)))
            {
                try
                {
                    authenticated = OpenBsdBCrypt.CheckPassword(stored, password.ToCharArray());
                }
                catch
                {
                    authenticated = false;
                }
            }

            authorized = authenticated &&
                (string.Equals(role.Trim(), "admin", StringComparison.OrdinalIgnoreCase) || canAccess);
            return authorized;
        }

        public bool UserIdHasAnyRole(int userId, params string[] roles)
        {
            if (userId <= 0)
                return false;

            var normalizedRoles = NormalizeRoles(roles);
            if (normalizedRoles.Count == 0)
                return false;

            using var conn = CreateOpenConnection();
            var roleParams = new List<string>(normalizedRoles.Count);
            for (int i = 0; i < normalizedRoles.Count; i++)
                roleParams.Add($"@role{i}");

            string sql = $@"
                SELECT COUNT(1)
                FROM users
                WHERE id = @id
                  AND role IN ({string.Join(", ", roleParams)})
                LIMIT 1";
            using var cmd = new MySqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@id", userId);
            for (int i = 0; i < normalizedRoles.Count; i++)
                cmd.Parameters.AddWithValue(roleParams[i], normalizedRoles[i]);

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool HasAnyActiveUserForRoles(params string[] roles)
        {
            var normalizedRoles = NormalizeRoles(roles);
            if (normalizedRoles.Count == 0)
                return false;

            using var conn = CreateOpenConnection();
            bool hasEmail = ColumnExists(conn, "users", "email");
            bool hasUsername = ColumnExists(conn, "users", "username");
            string loginPresence = hasEmail && hasUsername
                ? "((email IS NOT NULL AND email <> '') OR (username IS NOT NULL AND username <> ''))"
                : hasEmail
                    ? "(email IS NOT NULL AND email <> '')"
                    : "(username IS NOT NULL AND username <> '')";

            var roleParams = new List<string>(normalizedRoles.Count);
            for (int i = 0; i < normalizedRoles.Count; i++)
                roleParams.Add($"@role{i}");

            string sql = $@"
                SELECT COUNT(1)
                FROM users
                WHERE role IN ({string.Join(", ", roleParams)})
                  AND {loginPresence}";
            using var cmd = new MySqlCommand(sql, conn);
            for (int i = 0; i < normalizedRoles.Count; i++)
                cmd.Parameters.AddWithValue(roleParams[i], normalizedRoles[i]);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private static string BuildIdentityPredicate(bool hasEmail, bool hasUsername)
        {
            return hasEmail && hasUsername
                ? "(email = @login OR username = @login)"
                : hasEmail
                    ? "email = @login"
                    : "username = @login";
        }

        private static List<string> NormalizeRoles(string[] roles)
        {
            var normalized = new List<string>();
            if (roles == null) return normalized;

            foreach (var role in roles)
            {
                if (string.IsNullOrWhiteSpace(role)) continue;
                string value = role.Trim().ToLowerInvariant();
                if (value == "admin" || value == "employee" || value == "logbook")
                    normalized.Add(value);
            }
            return normalized;
        }
    }

    /// <summary>MySQL settings shared by <see cref="DatabaseHelper"/> and <see cref="FingerprintEngine"/>.</summary>
    internal static class AppDbConnection
    {
        /// <summary>Use IPv4 loopback so Windows does not prefer ::1 when MySQL only listens on 127.0.0.1.</summary>
        public const string Host = "127.0.0.1";
        public const uint Port = 3306;
        public const string UserId = "root";
        public const string Password = "root";
        public const string DatabaseName = "attendance_db";

        public static string ServerConnectionString { get; } = Build(false);
        public static string FullConnectionString { get; } = Build(true);

        private static string Build(bool includeDatabase)
        {
            var b = new MySqlConnectionStringBuilder
            {
                Server = Host,
                Port = Port,
                UserID = UserId,
                Password = Password,
                SslMode = MySqlSslMode.Preferred,
                AllowPublicKeyRetrieval = true,
                ConnectionTimeout = 8,
            };
            if (includeDatabase)
                b.Database = DatabaseName;
            return b.ConnectionString;
        }
    }
}