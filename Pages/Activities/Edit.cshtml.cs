using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Activities;

public class EditModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public EditModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Activity Activity { get; set; } = null!;

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var activity = await _db.Activities.FindAsync(id);
        if (activity == null) return NotFound();
        Activity = activity;
        return Page();
    }

    public async Task<IActionResult> OnPostAsync(int id)
    {
        ModelState.Remove("Activity.Schedules");
        var existing = await _db.Activities.FindAsync(id);
        if (existing == null) return NotFound();

        var oldName = existing.ActivityName;
        existing.ActivityName = Activity.ActivityName;
        existing.Description = Activity.Description;
        existing.PricePerHour = Activity.PricePerHour;
        existing.PricePerDay = Activity.PricePerDay;
        existing.MaxParticipants = Activity.MaxParticipants;
        existing.IsActive = Activity.IsActive;
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "Activity Updated",
            $"Activity '{oldName}' was updated to '{Activity.ActivityName}'.",
            NotificationType.Info,
            "bi-pencil",
            "/Activities/Index"
        );

        return RedirectToPage("Index", new { success = $"Activity '{Activity.ActivityName}' updated successfully!" });
    }
}