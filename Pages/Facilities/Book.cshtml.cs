using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Facilities;

public class BookModel : PageModel
{
    private readonly AppDbContext _db;
    public BookModel(AppDbContext db) => _db = db;

    public Facility Facility { get; set; } = null!;
    [BindProperty] public DateTime BookDate { get; set; } = DateTime.Today;
    [BindProperty] public TimeSpan StartTime { get; set; } = new(8, 0, 0);
    [BindProperty] public TimeSpan EndTime { get; set; } = new(17, 0, 0);
    public List<FacilitySchedule> AvailableSchedules { get; set; } = new();

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var facility = await _db.Facilities.FindAsync(id);
        if (facility == null) return NotFound();
        Facility = facility;
        await LoadSchedules(id);
        return Page();
    }

    public async Task<IActionResult> OnPostAsync(int id)
    {
        var facility = await _db.Facilities.FindAsync(id);
        if (facility == null) return NotFound();
        Facility = facility;

        var schedule = new FacilitySchedule
        {
            FacilityId = id,
            ScheduleDate = BookDate,
            StartTime = StartTime,
            EndTime = EndTime,
            Status = FacilityScheduleStatus.Booked
        };

        _db.FacilitySchedules.Add(schedule);
        await _db.SaveChangesAsync();
        return RedirectToPage("Index", new { success = $"Facility '{facility.FacilityName}' booked successfully!" });
    }

    private async Task LoadSchedules(int facilityId)
    {
        AvailableSchedules = await _db.FacilitySchedules
            .Where(s => s.FacilityId == facilityId && s.ScheduleDate == BookDate && s.Status == FacilityScheduleStatus.Available)
            .OrderBy(s => s.StartTime)
            .ToListAsync();
    }
}
