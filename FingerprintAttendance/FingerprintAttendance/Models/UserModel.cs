using System;

namespace FingerprintAttendance.Models
{
    public class UserModel
    {
        public int Id { get; set; }
        public string FullName { get; set; }
        public string EmployeeId { get; set; }
        public byte[] FpTemplate { get; set; }
        public DateTime CreatedAt { get; set; }
    }

    public class AttendanceModel
    {
        public int SessionId { get; set; }
        public int UserId { get; set; }
        public string FullName { get; set; }
        public DateTime? TimeIn { get; set; }
        public DateTime? TimeOut { get; set; }
        public decimal DailyTotalHours { get; set; }
        public bool HasActiveSession { get; set; }
        public DateTime DailyTotalHoursAsOf { get; set; }

        public decimal GetCurrentDailyTotalHours(DateTime now)
        {
            if (!HasActiveSession || now <= DailyTotalHoursAsOf)
                return DailyTotalHours;

            return DailyTotalHours + (decimal)(now - DailyTotalHoursAsOf).TotalHours;
        }
    }
}