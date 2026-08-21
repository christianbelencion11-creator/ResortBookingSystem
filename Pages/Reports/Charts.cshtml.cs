using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Reports;

public class ChartsModel : PageModel
{
    private readonly AppDbContext _db;
    public ChartsModel(AppDbContext db) => _db = db;

    public decimal MonthlyRevenue { get; set; }
    public int TotalBookings { get; set; }
    public double AvgOccupancy { get; set; }
    public string TopActivityName { get; set; } = "-";

    public List<string> RevenueLabels { get; set; } = new();
    public List<decimal> RevenueData { get; set; } = new();
    public List<string> StatusLabels { get; set; } = new();
    public List<int> StatusData { get; set; } = new();
    public List<string> ActivityLabels { get; set; } = new();
    public List<int> ActivityData { get; set; } = new();
    public List<string> RoomStatusLabels { get; set; } = new();
    public List<int> RoomStatusData { get; set; } = new();
    public List<string> PaymentLabels { get; set; } = new();
    public List<decimal> PaymentData { get; set; } = new();

    public async Task OnGetAsync()
    {
        var monthStart = new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);
        MonthlyRevenue = await _db.Payments.Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= monthStart).SumAsync(p => p.Amount);
        TotalBookings = await _db.Reservations.CountAsync();

        var totalRooms = await _db.Rooms.CountAsync();
        var occupied = await _db.ReservationItems.Where(i => i.ItemType == ItemType.Room)
            .Join(_db.Reservations.Where(r => r.Status == ReservationStatus.CheckedIn), i => i.ReservationId, r => r.ReservationId, (i, r) => i.ReferenceId)
            .Distinct().CountAsync();
        AvgOccupancy = totalRooms > 0 ? Math.Round((double)occupied / totalRooms * 100, 1) : 0;

        // Revenue last 6 months
        for (int i = 5; i >= 0; i--)
        {
            var start = DateTime.Now.AddMonths(-i).Date.AddDays(1 - DateTime.Now.AddMonths(-i).Day);
            var end = start.AddMonths(1).AddDays(-1);
            RevenueLabels.Add(start.ToString("MMM yyyy"));
            RevenueData.Add(await _db.Payments.Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= start && p.PaymentDate <= end).SumAsync(p => p.Amount));
        }

        // Status distribution
        var statuses = await _db.Reservations.GroupBy(r => r.Status).Select(g => new { Status = g.Key.ToString(), Count = g.Count() }).ToListAsync();
        StatusLabels = statuses.Select(s => s.Status).ToList();
        StatusData = statuses.Select(s => s.Count).ToList();

        // Top activities
        var activities = await _db.ReservationItems.Where(i => i.ItemType == ItemType.Activity)
            .GroupBy(i => i.ReferenceId)
            .Select(g => new { Id = g.Key, Count = g.Count() })
            .OrderByDescending(x => x.Count).Take(5).ToListAsync();
        foreach (var a in activities)
        {
            var name = await _db.Activities.Where(x => x.ActivityId == a.Id).Select(x => x.ActivityName).FirstOrDefaultAsync() ?? "Unknown";
            ActivityLabels.Add(name);
            ActivityData.Add(a.Count);
        }
        if (ActivityLabels.Any()) TopActivityName = ActivityLabels.First();

        // Room status
        var roomStatuses = await _db.Rooms.GroupBy(r => r.Status).Select(g => new { Status = g.Key.ToString(), Count = g.Count() }).ToListAsync();
        RoomStatusLabels = roomStatuses.Select(s => s.Status).ToList();
        RoomStatusData = roomStatuses.Select(s => s.Count).ToList();

        // Payment methods
        var methods = await _db.Payments.Where(p => p.Status == PaymentStatus.Completed)
            .GroupBy(p => p.PaymentMethod).Select(g => new { Method = g.Key.ToString(), Total = g.Sum(p => p.Amount) })
            .OrderByDescending(x => x.Total).ToListAsync();
        PaymentLabels = methods.Select(m => m.Method).ToList();
        PaymentData = methods.Select(m => m.Total).ToList();
    }
}
