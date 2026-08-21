using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class RoomsController : ControllerBase
{
    private readonly AppDbContext _db;
    public RoomsController(AppDbContext db) => _db = db;

    [HttpGet]
    public async Task<IActionResult> GetAll() =>
        Ok(await _db.Rooms.Include(r => r.RoomType).ToListAsync());

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var room = await _db.Rooms.Include(r => r.RoomType).FirstOrDefaultAsync(r => r.RoomId == id);
        return room == null ? NotFound() : Ok(room);
    }

    [HttpGet("available")]
    public async Task<IActionResult> GetAvailable([FromQuery] DateTime checkIn, [FromQuery] DateTime checkOut)
    {
        var bookedRoomIds = await _db.ReservationItems
            .Where(i => i.ItemType == ItemType.Room)
            .Join(_db.Reservations.Where(r => r.Status != ReservationStatus.Cancelled && r.CheckInDate < checkOut && r.CheckOutDate > checkIn),
                i => i.ReservationId, r => r.ReservationId, (i, r) => i.ReferenceId)
            .Distinct().ToListAsync();

        var available = await _db.Rooms.Include(r => r.RoomType)
            .Where(r => r.Status == RoomStatus.Available && !bookedRoomIds.Contains(r.RoomId))
            .ToListAsync();
        return Ok(available);
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] Room room)
    {
        _db.Rooms.Add(room);
        await _db.SaveChangesAsync();
        return CreatedAtAction(nameof(GetById), new { id = room.RoomId }, room);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] Room room)
    {
        var existing = await _db.Rooms.FindAsync(id);
        if (existing == null) return NotFound();
        existing.RoomNumber = room.RoomNumber;
        existing.RoomTypeId = room.RoomTypeId;
        existing.Floor = room.Floor;
        existing.Status = room.Status;
        existing.Description = room.Description;
        existing.ImageUrl = room.ImageUrl;
        await _db.SaveChangesAsync();
        return Ok(existing);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var room = await _db.Rooms.FindAsync(id);
        if (room == null) return NotFound();
        _db.Rooms.Remove(room);
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
