using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Rooms;

public class EditModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public EditModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Room Room { get; set; } = null!;
    public SelectList RoomTypeList { get; set; } = null!;

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var room = await _db.Rooms.FindAsync(id);
        if (room == null) return NotFound();
        Room = room;
        RoomTypeList = new SelectList(await _db.RoomTypes.Where(t => t.IsActive).ToListAsync(),
            nameof(RoomType.RoomTypeId), nameof(RoomType.TypeName));
        return Page();
    }

    public async Task<IActionResult> OnPostAsync(int id)
    {
        ModelState.Remove("Room.RoomType");

        // Check duplicate: same floor + room number (excluding current room)
        if (Room.Floor.HasValue)
        {
            var duplicate = await _db.Rooms
                .FirstOrDefaultAsync(r => r.RoomId != id && r.Floor == Room.Floor && r.RoomNumber == Room.RoomNumber);
            if (duplicate != null)
            {
                ModelState.AddModelError("Room.RoomNumber", 
                    $"Room '{Room.RoomNumber}' already exists on Floor {Room.Floor}.");
            }
        }

        if (!ModelState.IsValid)
        {
            RoomTypeList = new SelectList(await _db.RoomTypes.Where(t => t.IsActive).ToListAsync(),
                nameof(RoomType.RoomTypeId), nameof(RoomType.TypeName));
            return Page();
        }

        var existing = await _db.Rooms.FindAsync(id);
        if (existing == null) return NotFound();

        var oldFloor = existing.Floor;
        var oldNumber = existing.RoomNumber;
        var oldTypeName = (await _db.RoomTypes.FindAsync(existing.RoomTypeId))?.TypeName;

        existing.RoomNumber = Room.RoomNumber;
        existing.RoomTypeId = Room.RoomTypeId;
        existing.Floor = Room.Floor;
        existing.Status = Room.Status;
        existing.Description = Room.Description;
        existing.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();

        // Create notification for admins
        await _notifications.CreateForAllAdminsAsync(
            "Room Updated",
            $"Room {oldNumber} (Floor {oldFloor}, {oldTypeName}) was updated to {Room.RoomNumber} (Floor {Room.Floor}).",
            NotificationType.Info,
            "bi-pencil",
            "/Rooms/Index"
        );

        return RedirectToPage("Index", new { success = $"Room {Room.RoomNumber} updated successfully!" });
    }
}