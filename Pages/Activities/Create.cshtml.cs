using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Activities;

public class CreateModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public CreateModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Activity Activity { get; set; } = new();

    public void OnGet() { }

    public async Task<IActionResult> OnPostAsync()
    {
        ModelState.Remove("Activity.Schedules");
        if (!ModelState.IsValid) return Page();
        _db.Activities.Add(Activity);
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "New Activity Added",
            $"Activity '{Activity.ActivityName}' was created.",
            NotificationType.Success,
            "bi-compass",
            "/Activities/Index"
        );

        return RedirectToPage("Index", new { success = $"Activity '{Activity.ActivityName}' created successfully!" });
    }
}