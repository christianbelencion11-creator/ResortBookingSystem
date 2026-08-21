using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Rooms;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public IndexModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    public List<Room> Rooms { get; set; } = new();
    public List<RoomType> RoomTypes { get; set; } = new();

    public async Task OnGetAsync()
    {
        Rooms = await _db.Rooms.Include(r => r.RoomType).ToListAsync();
        RoomTypes = await _db.RoomTypes.Where(t => t.IsActive).ToListAsync();
    }

    public async Task<IActionResult> OnPostDeleteAsync(int RoomId)
    {
        var room = await _db.Rooms.Include(r => r.RoomType).FirstOrDefaultAsync(r => r.RoomId == RoomId);
        if (room == null) return NotFound();

        var roomNumber = room.RoomNumber;
        var floor = room.Floor;
        var typeName = room.RoomType?.TypeName;

        _db.Rooms.Remove(room);
        await _db.SaveChangesAsync();

        // Create notification for admins
        await _notifications.CreateForAllAdminsAsync(
            "Room Deleted",
            $"Room {roomNumber} (Floor {floor}, {typeName}) was deleted.",
            NotificationType.Danger,
            "bi-trash",
            "/Rooms/Index"
        );

        return RedirectToPage(new { success = $"Room #{roomNumber} deleted successfully!" });
    }
}