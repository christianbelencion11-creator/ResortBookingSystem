using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Facilities;

public class EditModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public EditModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Facility Facility { get; set; } = null!;

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var facility = await _db.Facilities.FindAsync(id);
        if (facility == null) return NotFound();
        Facility = facility;
        return Page();
    }

    public async Task<IActionResult> OnPostAsync(int id)
    {
        ModelState.Remove("Facility.Schedules");
        var existing = await _db.Facilities.FindAsync(id);
        if (existing == null) return NotFound();

        var oldName = existing.FacilityName;
        existing.FacilityName = Facility.FacilityName;
        existing.Description = Facility.Description;
        existing.RentalPrice = Facility.RentalPrice;
        existing.Capacity = Facility.Capacity;
        existing.IsActive = Facility.IsActive;
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "Facility Updated",
            $"Facility '{oldName}' was updated to '{Facility.FacilityName}'.",
            NotificationType.Info,
            "bi-pencil",
            "/Facilities/Index"
        );

        return RedirectToPage("Index", new { success = $"Facility '{Facility.FacilityName}' updated successfully!" });
    }
}