using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Facilities;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public IndexModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    public List<Facility> Facilities { get; set; } = new();

    public async Task OnGetAsync()
    {
        Facilities = await _db.Facilities.Include(f => f.Schedules).Where(f => f.IsActive).ToListAsync();
    }

    public async Task<IActionResult> OnPostDeleteAsync(int FacilityId)
    {
        var facility = await _db.Facilities.FindAsync(FacilityId);
        if (facility == null) return NotFound();

        var facilityName = facility.FacilityName;
        _db.Facilities.Remove(facility);
        await _db.SaveChangesAsync();

        await _notifications.CreateForAllAdminsAsync(
            "Facility Deleted",
            $"Facility '{facilityName}' was deleted.",
            NotificationType.Danger,
            "bi-trash",
            "/Facilities/Index"
        );

        return RedirectToPage(new { success = $"Facility \"{facilityName}\" deleted successfully!" });
    }
}