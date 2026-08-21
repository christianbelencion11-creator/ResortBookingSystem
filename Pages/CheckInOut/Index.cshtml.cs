using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.CheckInOut;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public List<Reservation> TodayCheckIns { get; set; } = new();
    public List<Reservation> TodayCheckOuts { get; set; } = new();
    public List<Reservation> PendingReservations { get; set; } = new();
    public List<Reservation> ActiveStays { get; set; } = new();

    public async Task OnGetAsync()
    {
        var today = DateTime.Today;
        var query = _db.Reservations.Include(r => r.User).Include(r => r.Guest).Include(r => r.Items).Include(r => r.Payments);

        TodayCheckIns = await query
            .Where(r => r.Status == ReservationStatus.Confirmed && r.CheckInDate <= today)
            .OrderBy(r => r.CheckInDate).ToListAsync();

        TodayCheckOuts = await query
            .Where(r => r.Status == ReservationStatus.CheckedIn && r.CheckOutDate == today)
            .OrderBy(r => r.CreatedAt).ToListAsync();

        PendingReservations = await query
            .Where(r => r.Status == ReservationStatus.Pending)
            .OrderBy(r => r.CheckInDate).ToListAsync();

        ActiveStays = await query
            .Where(r => r.Status == ReservationStatus.CheckedIn)
            .OrderBy(r => r.CheckInDate).ToListAsync();
    }

    public async Task<IActionResult> OnPostCheckInAsync(int id)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation != null)
        {
            reservation.Status = ReservationStatus.CheckedIn;
            reservation.UpdatedAt = DateTime.Now;
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Guest checked in successfully!" });
    }

    public async Task<IActionResult> OnPostCheckOutAsync(int id)
    {
        var reservation = await _db.Reservations
            .Include(r => r.Items)
            .FirstOrDefaultAsync(r => r.ReservationId == id);
        if (reservation != null)
        {
            reservation.Status = ReservationStatus.CheckedOut;
            reservation.UpdatedAt = DateTime.Now;

            foreach (var item in reservation.Items.Where(i => i.ItemType == ItemType.Room))
            {
                var room = await _db.Rooms.FindAsync(item.ReferenceId);
                if (room != null) room.Status = RoomStatus.Available;
            }

            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Guest checked out successfully!" });
    }

    public async Task<IActionResult> OnPostConfirmAsync(int id)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation != null)
        {
            reservation.Status = ReservationStatus.Confirmed;
            reservation.UpdatedAt = DateTime.Now;
            await _db.SaveChangesAsync();
        }
        return RedirectToPage(new { success = "Reservation confirmed!" });
    }
}
