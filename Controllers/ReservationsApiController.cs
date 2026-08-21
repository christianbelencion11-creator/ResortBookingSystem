using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class ReservationsApiController : ControllerBase
{
    private readonly AppDbContext _db;
    public ReservationsApiController(AppDbContext db) => _db = db;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] string? status)
    {
        var query = _db.Reservations.Include(r => r.User).Include(r => r.Guest).Include(r => r.Items).Include(r => r.Payments).AsQueryable();
        if (!string.IsNullOrEmpty(status) && Enum.TryParse<ReservationStatus>(status, true, out var s))
            query = query.Where(r => r.Status == s);
        return Ok(await query.OrderByDescending(r => r.CreatedAt).ToListAsync());
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var reservation = await _db.Reservations.Include(r => r.User).Include(r => r.Guest).Include(r => r.Items).Include(r => r.Payments)
            .FirstOrDefaultAsync(r => r.ReservationId == id);
        return reservation == null ? NotFound() : Ok(reservation);
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] Reservation reservation)
    {
        _db.Reservations.Add(reservation);
        await _db.SaveChangesAsync();
        return CreatedAtAction(nameof(GetById), new { id = reservation.ReservationId }, reservation);
    }

    [HttpPut("{id}/status")]
    public async Task<IActionResult> UpdateStatus(int id, [FromBody] string status)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation == null) return NotFound();
        if (Enum.TryParse<ReservationStatus>(status, true, out var s))
        {
            reservation.Status = s;
            reservation.UpdatedAt = DateTime.Now;
            await _db.SaveChangesAsync();
        }
        return Ok(reservation);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Cancel(int id)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation == null) return NotFound();
        reservation.Status = ReservationStatus.Cancelled;
        reservation.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
