using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Api;

[IgnoreAntiforgeryToken]
public class CrudApiModel : PageModel
{
    private readonly AppDbContext _db;
    public CrudApiModel(AppDbContext db) => _db = db;

    public async Task<IActionResult> OnPostDeleteRoomAsync(int id)
    {
        var room = await _db.Rooms.FindAsync(id);
        if (room == null) return new JsonResult(new { error = "Not found" });
        _db.Rooms.Remove(room);
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, message = $"Room #{room.RoomNumber} deleted" });
    }

    public async Task<IActionResult> OnPostDeleteActivityAsync(int id)
    {
        var activity = await _db.Activities.FindAsync(id);
        if (activity == null) return new JsonResult(new { error = "Not found" });
        _db.Activities.Remove(activity);
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, message = $"Activity '{activity.ActivityName}' deleted" });
    }

    public async Task<IActionResult> OnPostDeleteFacilityAsync(int id)
    {
        var facility = await _db.Facilities.FindAsync(id);
        if (facility == null) return new JsonResult(new { error = "Not found" });
        _db.Facilities.Remove(facility);
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, message = $"Facility '{facility.FacilityName}' deleted" });
    }

    public async Task<IActionResult> OnPostCancelReservationAsync(int id)
    {
        var reservation = await _db.Reservations.FindAsync(id);
        if (reservation == null) return new JsonResult(new { error = "Not found" });
        reservation.Status = ReservationStatus.Cancelled;
        reservation.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();
        return new JsonResult(new { success = true, message = $"Reservation #{id} cancelled" });
    }
}
