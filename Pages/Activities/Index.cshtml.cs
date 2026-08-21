using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Activities;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public IndexModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    public List<Activity> Activities { get; set; } = new();

    public async Task OnGetAsync()
    {
        Activities = await _db.Activities
            .Include(a => a.Schedules)
            .Where(a => a.IsActive)
            .ToListAsync();
    }

    public async Task<IActionResult> OnPostDeleteAsync(int ActivityId)
    {
        var activity = await _db.Activities.FindAsync(ActivityId);
        if (activity == null) return NotFound();

        var activityName = activity.ActivityName;
        _db.Activities.Remove(activity);
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "Activity Deleted",
            $"Activity '{activityName}' was deleted.",
            NotificationType.Danger,
            "bi-trash",
            "/Activities/Index"
        );

        return RedirectToPage(new { success = $"Activity \"{activityName}\" deleted successfully!" });
    }
}