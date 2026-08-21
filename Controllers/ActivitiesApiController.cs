using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class ActivitiesController : ControllerBase
{
    private readonly AppDbContext _db;
    public ActivitiesController(AppDbContext db) => _db = db;

    [HttpGet]
    public async Task<IActionResult> GetAll() =>
        Ok(await _db.Activities.Where(a => a.IsActive).ToListAsync());

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var activity = await _db.Activities.Include(a => a.Schedules).FirstOrDefaultAsync(a => a.ActivityId == id);
        return activity == null ? NotFound() : Ok(activity);
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] Activity activity)
    {
        _db.Activities.Add(activity);
        await _db.SaveChangesAsync();
        return CreatedAtAction(nameof(GetById), new { id = activity.ActivityId }, activity);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] Activity activity)
    {
        var existing = await _db.Activities.FindAsync(id);
        if (existing == null) return NotFound();
        existing.ActivityName = activity.ActivityName;
        existing.Description = activity.Description;
        existing.PricePerHour = activity.PricePerHour;
        existing.PricePerDay = activity.PricePerDay;
        existing.MaxParticipants = activity.MaxParticipants;
        existing.IsActive = activity.IsActive;
        await _db.SaveChangesAsync();
        return Ok(existing);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var activity = await _db.Activities.FindAsync(id);
        if (activity == null) return NotFound();
        activity.IsActive = false;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
