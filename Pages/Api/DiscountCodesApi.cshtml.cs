using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;

namespace ResortBookingSystem.Pages.Api;

[IgnoreAntiforgeryToken]
public class DiscountCodesApiModel : PageModel
{
    private readonly AppDbContext _db;
    public DiscountCodesApiModel(AppDbContext db) => _db = db;

    public async Task<IActionResult> OnPostToggleAsync(int id)
    {
        var code = await _db.DiscountCodes.FindAsync(id);
        if (code == null) return new JsonResult(new { error = "Not found" });
        code.IsActive = !code.IsActive;
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, isActive = code.IsActive });
    }

    public async Task<IActionResult> OnPostDeleteAsync(int id)
    {
        var code = await _db.DiscountCodes.FindAsync(id);
        if (code == null) return new JsonResult(new { error = "Not found" });
        _db.DiscountCodes.Remove(code);
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true });
    }
}
