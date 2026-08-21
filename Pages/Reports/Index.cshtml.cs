using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Reports;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public decimal Revenue { get; set; }
    public int TotalBookings { get; set; }
    public int TotalGuests { get; set; }
    public double OccupancyRate { get; set; }
    public List<ActivityReport> TopActivities { get; set; } = new();
    public List<PeakDate> PeakDates { get; set; } = new();
    public List<RoomReportData> RoomReport { get; set; } = new();
    public List<PaymentReport> PaymentSummary { get; set; } = new();

    public class ActivityReport { public string Name { get; set; } = ""; public int Bookings { get; set; } public decimal Revenue { get; set; } }
    public class PeakDate { public DateTime Date { get; set; } public int Count { get; set; } public decimal Revenue { get; set; } }
    public class RoomReportData { public string RoomNumber { get; set; } = ""; public string TypeName { get; set; } = ""; public string Status { get; set; } = ""; public decimal Revenue { get; set; } }
    public class PaymentReport { public string Method { get; set; } = ""; public int Count { get; set; } public decimal Total { get; set; } }

    public async Task OnGetAsync()
    {
        var monthStart = new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);

        Revenue = await _db.Payments
            .Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= monthStart)
            .SumAsync(p => p.Amount);

        TotalBookings = await _db.Reservations.CountAsync();
        TotalGuests = await _db.GuestRecords.CountAsync();

        var totalRooms = await _db.Rooms.CountAsync();
        var occupiedRooms = await _db.ReservationItems
            .Where(i => i.ItemType == ItemType.Room)
            .Join(_db.Reservations.Where(r => r.Status == ReservationStatus.CheckedIn),
                i => i.ReservationId, r => r.ReservationId, (i, r) => i.ReferenceId)
            .Distinct().CountAsync();
        OccupancyRate = totalRooms > 0 ? Math.Round((double)occupiedRooms / totalRooms * 100, 1) : 0;

        TopActivities = await _db.ReservationItems
            .Where(i => i.ItemType == ItemType.Activity)
            .GroupBy(i => i.ReferenceId)
            .Select(g => new ActivityReport
            {
                Name = _db.Activities.Where(a => a.ActivityId == g.Key).Select(a => a.ActivityName).FirstOrDefault() ?? "Unknown",
                Bookings = g.Count(),
                Revenue = g.Sum(i => i.Subtotal)
            })
            .OrderByDescending(x => x.Bookings)
            .Take(5).ToListAsync();

        PeakDates = await _db.Reservations
            .GroupBy(r => r.CheckInDate.Date)
            .Select(g => new PeakDate { Date = g.Key, Count = g.Count(), Revenue = g.Sum(r => r.TotalAmount) })
            .OrderByDescending(x => x.Count).Take(5).ToListAsync();

        RoomReport = await _db.Rooms
            .Include(r => r.RoomType)
            .Select(r => new RoomReportData
            {
                RoomNumber = r.RoomNumber,
                TypeName = r.RoomType.TypeName,
                Status = r.Status.ToString(),
                Revenue = _db.ReservationItems
                    .Where(i => i.ItemType == ItemType.Room && i.ReferenceId == r.RoomId)
                    .Sum(i => i.Subtotal)
            })
            .ToListAsync();

        PaymentSummary = await _db.Payments
            .Where(p => p.Status == PaymentStatus.Completed)
            .GroupBy(p => p.PaymentMethod)
            .Select(g => new PaymentReport
            {
                Method = g.Key.ToString(),
                Count = g.Count(),
                Total = g.Sum(p => p.Amount)
            })
            .ToListAsync();
    }
}
