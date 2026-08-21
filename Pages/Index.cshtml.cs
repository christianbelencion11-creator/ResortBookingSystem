using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public int TotalRooms { get; set; }
    public int AvailableRooms { get; set; }
    public int ActiveReservations { get; set; }
    public int PendingPayments { get; set; }
    public decimal MonthlyRevenue { get; set; }
    public List<Reservation> RecentReservations { get; set; } = new();
    public Dictionary<string, int> RoomStatusSummary { get; set; } = new();

    public async Task OnGetAsync()
    {
        TotalRooms = await _db.Rooms.CountAsync();
        AvailableRooms = await _db.Rooms.CountAsync(r => r.Status == RoomStatus.Available);
        ActiveReservations = await _db.Reservations.CountAsync(r => r.Status == ReservationStatus.CheckedIn);
        PendingPayments = await _db.Payments.CountAsync(p => p.Status == PaymentStatus.Pending);

        var monthStart = new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);
        MonthlyRevenue = await _db.Payments
            .Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= monthStart)
            .SumAsync(p => p.Amount);

        RecentReservations = await _db.Reservations
            .Include(r => r.User)
            .OrderByDescending(r => r.CreatedAt)
            .Take(5)
            .ToListAsync();

        var statuses = await _db.Rooms.GroupBy(r => r.Status)
            .Select(g => new { Status = g.Key.ToString(), Count = g.Count() })
            .ToListAsync();
        foreach (var s in statuses)
            RoomStatusSummary[s.Status] = s.Count;
    }
}
