using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;

namespace ResortBookingSystem.Pages.Api;

public class DiscountsModel : PageModel
{
    private readonly AppDbContext _db;
    public DiscountsModel(AppDbContext db) => _db = db;

    public async Task<IActionResult> OnGetAsync(string? code)
    {
        if (string.IsNullOrEmpty(code))
            return new JsonResult(new { valid = false });

        var dc = await _db.DiscountCodes
            .FirstOrDefaultAsync(d => d.Code == code && d.IsActive);

        if (dc == null || dc.CurrentUses >= dc.MaxUses || DateTime.Now < dc.ValidFrom || DateTime.Now > dc.ValidTo)
            return new JsonResult(new { valid = false });

        return new JsonResult(new
        {
            valid = true,
            isPercent = dc.IsPercent,
            value = dc.DiscountValue,
            description = dc.Description
        });
    }
}
