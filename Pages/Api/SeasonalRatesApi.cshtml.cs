using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;

namespace ResortBookingSystem.Pages.Api;

[IgnoreAntiforgeryToken]
public class SeasonalRatesApiModel : PageModel
{
    private readonly AppDbContext _db;
    public SeasonalRatesApiModel(AppDbContext db) => _db = db;

    public async Task<IActionResult> OnPostToggleAsync(int id)
    {
        var rate = await _db.SeasonalRates.FindAsync(id);
        if (rate == null) return new JsonResult(new { error = "Not found" });
        rate.IsActive = !rate.IsActive;
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, isActive = rate.IsActive });
    }

    public async Task<IActionResult> OnPostDeleteAsync(int id)
    {
        var rate = await _db.SeasonalRates.FindAsync(id);
        if (rate == null) return new JsonResult(new { error = "Not found" });
        _db.SeasonalRates.Remove(rate);
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true });
    }
}
