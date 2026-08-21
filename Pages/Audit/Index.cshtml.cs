using Microsoft.AspNetCore.Mvc.RazorPages;

namespace ResortBookingSystem.Pages.Audit;

public class IndexModel : PageModel
{
    public List<LogEntry> Logs { get; set; } = new();

    public class LogEntry
    {
        public DateTime Timestamp { get; set; }
        public string User { get; set; } = "";
        public string Action { get; set; } = "";
        public string Module { get; set; } = "";
        public string Details { get; set; } = "";
        public string BadgeClass { get; set; } = "badge-info";
    }

    public void OnGet()
    {
        Logs = new List<LogEntry>
        {
            new() { Timestamp = DateTime.Now.AddMinutes(-5), User = "Juan Dela Cruz", Action = "Login", Module = "Auth", Details = "Admin logged in", BadgeClass = "badge-success" },
            new() { Timestamp = DateTime.Now.AddMinutes(-15), User = "Maria Santos", Action = "Check-In", Module = "Reservations", Details = "Reservation #1 checked in", BadgeClass = "badge-info" },
            new() { Timestamp = DateTime.Now.AddMinutes(-30), User = "Admin", Action = "Create", Module = "Rooms", Details = "Added room V03 - Poolside Villa", BadgeClass = "badge-success" },
            new() { Timestamp = DateTime.Now.AddHours(-1), User = "Jose Reyes", Action = "Update", Module = "Activities", Details = "Updated Kayaking price to ₱300/hr", BadgeClass = "badge-warning" },
            new() { Timestamp = DateTime.Now.AddHours(-2), User = "Admin", Action = "Payment", Module = "Payments", Details = "Recorded payment ₱5,000 for Reservation #3", BadgeClass = "badge-success" },
            new() { Timestamp = DateTime.Now.AddHours(-3), User = "System", Action = "Alert", Module = "System", Details = "Room R103 marked for maintenance", BadgeClass = "badge-danger" },
            new() { Timestamp = DateTime.Now.AddHours(-5), User = "Juan Dela Cruz", Action = "Export", Module = "Reports", Details = "Generated monthly revenue report", BadgeClass = "badge-info" },
            new() { Timestamp = DateTime.Now.AddDays(-1), User = "Admin", Action = "Create", Module = "Users", Details = "Created staff account for Mark Lopez", BadgeClass = "badge-success" },
        };
    }
}
