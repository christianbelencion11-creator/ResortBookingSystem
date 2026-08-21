using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Api;

public class NotificationsModel : PageModel
{
    private readonly AppDbContext _db;
    public NotificationsModel(AppDbContext db) => _db = db;

    public async Task<IActionResult> OnGetAsync()
    {
        try
        {
            // Get admin user IDs
            var adminUserIds = await _db.Users
                .Where(u => u.RoleId == 1 && u.IsActive)
                .Select(u => u.UserId)
                .ToListAsync();

            // Only show stored notifications — these are real actions created by NotificationService
            var storedNotifications = await _db.Notifications
                .Where(n => n.UserId != null && adminUserIds.Contains(n.UserId.Value))
                .OrderByDescending(n => n.CreatedAt)
                .Take(20)
                .ToListAsync();

            var items = storedNotifications.Select(n => new NotifDto
            {
                icon = n.Icon ?? "bi-info-circle",
                color = n.Type == NotificationType.Success ? "green"
                    : n.Type == NotificationType.Danger ? "red"
                    : n.Type == NotificationType.Warning ? "orange" : "blue",
                text = n.Title + ": " + n.Message,
                time = n.CreatedAt
            }).ToList();

            return new JsonResult(items);
        }
        catch (Exception)
        {
            return new JsonResult(new List<object>());
        }
    }

    private class NotifDto
    {
        public string icon { get; set; } = "";
        public string color { get; set; } = "blue";
        public string text { get; set; } = "";
        public DateTime time { get; set; }
    }
}
