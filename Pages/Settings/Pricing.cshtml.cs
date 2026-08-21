using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Settings;

public class PricingModel : PageModel
{
    private readonly AppDbContext _db;
    public PricingModel(AppDbContext db) => _db = db;

    public List<SeasonalRateVM> Rates { get; set; } = new();
    public List<RoomType> RoomTypes { get; set; } = new();

    [BindProperty]
    public SeasonalRate NewRate { get; set; } = new();

    public class SeasonalRateVM
    {
        public int SeasonalRateId { get; set; }
        public string SeasonName { get; set; } = "";
        public DateTime StartDate { get; set; }
        public DateTime EndDate { get; set; }
        public decimal PriceMultiplier { get; set; }
        public string? RoomTypeName { get; set; }
        public bool IsActive { get; set; }
    }

    public async Task OnGetAsync()
    {
        RoomTypes = await _db.RoomTypes.Where(r => r.IsActive).OrderBy(r => r.TypeName).ToListAsync();
        Rates = await _db.SeasonalRates
            .Include(r => r.RoomType)
            .OrderByDescending(r => r.StartDate)
            .Select(r => new SeasonalRateVM
            {
                SeasonalRateId = r.SeasonalRateId,
                SeasonName = r.SeasonName,
                StartDate = r.StartDate,
                EndDate = r.EndDate,
                PriceMultiplier = r.PriceMultiplier,
                RoomTypeName = r.RoomType != null ? r.RoomType.TypeName : "All Room Types",
                IsActive = r.IsActive
            })
            .ToListAsync();
    }

    public async Task<IActionResult> OnPostAddAsync()
    {
        _db.SeasonalRates.Add(NewRate);
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = "Seasonal rate added" });
    }

    public async Task<IActionResult> OnPostDeleteAsync(int id)
    {
        var rate = await _db.SeasonalRates.FindAsync(id);
        if (rate != null)
        {
            _db.SeasonalRates.Remove(rate);
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Seasonal rate deleted" });
    }

    public async Task<IActionResult> OnPostToggleAsync(int id)
    {
        var rate = await _db.SeasonalRates.FindAsync(id);
        if (rate != null)
        {
            rate.IsActive = !rate.IsActive;
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Status updated" });
    }
}
