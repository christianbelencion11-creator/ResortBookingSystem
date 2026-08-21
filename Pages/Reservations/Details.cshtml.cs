using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Reservations;

public class DetailsModel : PageModel
{
    private readonly AppDbContext _db;
    public DetailsModel(AppDbContext db) => _db = db;

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

    public async Task<IActionResult> OnPostUpdateStatusAsync(int id, string newStatus)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation == null) return NotFound();

        if (Enum.TryParse<ReservationStatus>(newStatus, true, out var status))
        {
            reservation.Status = status;
            reservation.UpdatedAt = DateTime.Now;
            await _db.SaveChangesAsync();
        }

        return RedirectToPage("Details", new { id, success = $"Reservation status updated to {newStatus}!" });
    }
}
