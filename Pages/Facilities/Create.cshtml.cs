using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Facilities;

public class CreateModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public CreateModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Facility Facility { get; set; } = new();

    public void OnGet() { }

    public async Task<IActionResult> OnPostAsync()
    {
        ModelState.Remove("Facility.Schedules");
        if (!ModelState.IsValid) return Page();
        _db.Facilities.Add(Facility);
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "New Facility Added",
            $"Facility '{Facility.FacilityName}' was created.",
            NotificationType.Success,
            "bi-building",
            "/Facilities/Index"
        );

        return RedirectToPage("Index", new { success = $"Facility '{Facility.FacilityName}' created successfully!" });
    }
}