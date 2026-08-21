using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class ReportsApiController : ControllerBase
{
    private readonly AppDbContext _db;
    public ReportsApiController(AppDbContext db) => _db = db;

    [HttpGet("revenue")]
    public async Task<IActionResult> GetRevenue([FromQuery] DateTime? from, [FromQuery] DateTime? to)
    {
        var startDate = from ?? new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);
        var endDate = to ?? startDate.AddMonths(1).AddDays(-1);
        var payments = await _db.Payments.Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= startDate && p.PaymentDate <= endDate).ToListAsync();
        return Ok(new
        {
            Period = new { From = startDate, To = endDate },
            TotalRevenue = payments.Sum(p => p.Amount),
            TotalTransactions = payments.Count,
            ByMethod = payments.GroupBy(p => p.PaymentMethod).Select(g => new { Method = g.Key, Total = g.Sum(p => p.Amount), Count = g.Count() }),
            ByDay = payments.GroupBy(p => p.PaymentDate.Date).Select(g => new { Date = g.Key, Total = g.Sum(p => p.Amount) }).OrderBy(x => x.Date)
        });
    }

    [HttpGet("occupancy")]
    public async Task<IActionResult> GetOccupancy([FromQuery] DateTime? date)
    {
        var targetDate = date ?? DateTime.Today;
        var totalRooms = await _db.Rooms.CountAsync();
        var occupied = await _db.ReservationItems.Where(i => i.ItemType == ItemType.Room)
            .Join(_db.Reservations.Where(r => r.Status == ReservationStatus.CheckedIn && r.CheckInDate <= targetDate && r.CheckOutDate > targetDate),
                i => i.ReservationId, r => r.ReservationId, (i, r) => i.ReferenceId).Distinct().CountAsync();
        return Ok(new { Date = targetDate, TotalRooms = totalRooms, Occupied = occupied, Available = totalRooms - occupied, OccupancyRate = totalRooms > 0 ? Math.Round((double)occupied / totalRooms * 100, 1) : 0 });
    }
}
