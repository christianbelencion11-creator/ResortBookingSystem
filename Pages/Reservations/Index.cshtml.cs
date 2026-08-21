using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Reservations;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public List<Reservation> Reservations { get; set; } = new();

    public async Task OnGetAsync([FromQuery] string? status)
    {
        var query = _db.Reservations
            .Include(r => r.User)
            .Include(r => r.Guest)
            .Include(r => r.Items)
            .AsQueryable();

        if (!string.IsNullOrEmpty(status) && Enum.TryParse<ReservationStatus>(status, true, out var s))
            query = query.Where(r => r.Status == s);

        Reservations = await query.OrderByDescending(r => r.CreatedAt).ToListAsync();
    }

    public async Task<IActionResult> OnPostCancelAsync(int ReservationId)
    {
        var reservation = await _db.Reservations.FindAsync(ReservationId);
        if (reservation == null) return NotFound();
        reservation.Status = ReservationStatus.Cancelled;
        reservation.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = $"Reservation #{ReservationId} has been cancelled." });
    }
}
