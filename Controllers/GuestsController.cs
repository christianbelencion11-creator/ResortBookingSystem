using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class GuestsController : ControllerBase
{
    private readonly AppDbContext _db;
    public GuestsController(AppDbContext db) => _db = db;

    [HttpGet]
    public async Task<IActionResult> GetAll() => Ok(await _db.GuestRecords.ToListAsync());

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var guest = await _db.GuestRecords.FindAsync(id);
        return guest == null ? NotFound() : Ok(guest);
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] GuestRecord guest)
    {
        _db.GuestRecords.Add(guest);
        await _db.SaveChangesAsync();
        return CreatedAtAction(nameof(GetById), new { id = guest.GuestId }, guest);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] GuestRecord guest)
    {
        var existing = await _db.GuestRecords.FindAsync(id);
        if (existing == null) return NotFound();
        existing.FirstName = guest.FirstName;
        existing.LastName = guest.LastName;
        existing.Email = guest.Email;
        existing.PhoneNumber = guest.PhoneNumber;
        existing.IdType = guest.IdType;
        existing.IdNumber = guest.IdNumber;
        existing.Address = guest.Address;
        await _db.SaveChangesAsync();
        return Ok(existing);
    }
}
