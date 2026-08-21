using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Receipt;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public Reservation Reservation { get; set; } = null!;

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var reservation = await _db.Reservations
            .Include(r => r.User)
            .Include(r => r.Guest)
            .Include(r => r.Items)
            .Include(r => r.Payments)
            .FirstOrDefaultAsync(r => r.ReservationId == id);

        if (reservation == null) return NotFound();
        Reservation = reservation;
        return Page();
    }

    public string GetItemDescription(ReservationItem item)
    {
        return item.ItemType switch
        {
            ItemType.Room => $"Room ({item.Quantity} night(s))",
            ItemType.Activity => $"Activity (x{item.Quantity})",
            ItemType.Facility => $"Facility (x{item.Quantity})",
            _ => $"Item #{item.ReferenceId}"
        };
    }
}
