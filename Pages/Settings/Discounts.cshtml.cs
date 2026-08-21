using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Settings;

public class DiscountsModel : PageModel
{
    private readonly AppDbContext _db;
    public DiscountsModel(AppDbContext db) => _db = db;

    public List<DiscountCode> Codes { get; set; } = new();

    [BindProperty]
    public DiscountCode NewCode { get; set; } = new();

    public async Task OnGetAsync()
    {
        Codes = await _db.DiscountCodes.OrderByDescending(d => d.CreatedAt).ToListAsync();
    }

    public async Task<IActionResult> OnPostAddAsync()
    {
        if (string.IsNullOrWhiteSpace(NewCode.Code))
            return RedirectToPage(new { error = "Code is required" });

        NewCode.Code = NewCode.Code.ToUpper().Trim();
        _db.DiscountCodes.Add(NewCode);
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = "Discount code created" });
    }

    public async Task<IActionResult> OnPostDeleteAsync(int id)
    {
        var code = await _db.DiscountCodes.FindAsync(id);
        if (code != null)
        {
            _db.DiscountCodes.Remove(code);
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Discount code deleted" });
    }

    public async Task<IActionResult> OnPostToggleAsync(int id)
    {
        var code = await _db.DiscountCodes.FindAsync(id);
        if (code != null)
        {
            code.IsActive = !code.IsActive;
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Status updated" });
    }
}
