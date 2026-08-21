using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Notifications;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public List<NotifItem> Notifications { get; set; } = new();
    public int UnreadCount { get; set; }

    public IndexModel(AppDbContext db) => _db = db;

    public async Task OnGetAsync()
    {
        var adminUserIds = await _db.Users
            .Where(u => u.RoleId == 1 && u.IsActive)
            .Select(u => u.UserId)
            .ToListAsync();

        Notifications = await _db.Notifications
            .Where(n => n.UserId != null && adminUserIds.Contains(n.UserId.Value))
            .OrderByDescending(n => n.CreatedAt)
            .Select(n => new NotifItem
            {
                Icon = n.Icon ?? "bi-info-circle",
                IconColor = n.Type == NotificationType.Success ? "green"
                    : n.Type == NotificationType.Danger ? "red"
                    : n.Type == NotificationType.Warning ? "orange" : "blue",
                Text = n.Title + ": " + n.Message,
                Time = n.CreatedAt,
                Type = n.Type.ToString().ToLower()
            })
            .ToListAsync();

        UnreadCount = Notifications.Count;
    }

    public class NotifItem
    {
        public string Icon { get; set; } = "";
        public string IconColor { get; set; } = "blue";
        public string Text { get; set; } = "";
        public DateTime Time { get; set; }
        public string Type { get; set; } = "";

        public string TimeAgo
        {
            get
            {
                var diff = DateTime.Now - Time;
                if (diff.TotalMinutes < 1) return "Just now";
                if (diff.TotalMinutes < 60) return $"{(int)diff.TotalMinutes} min ago";
                if (diff.TotalHours < 24) return $"{(int)diff.TotalHours} hour(s) ago";
                if (diff.TotalDays < 7) return $"{(int)diff.TotalDays} day(s) ago";
                return Time.ToString("MMM dd, yyyy");
            }
        }
    }
}
