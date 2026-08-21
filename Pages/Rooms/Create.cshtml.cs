using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Rooms;

public class CreateModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public CreateModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public Room Room { get; set; } = new();
    public SelectList RoomTypeList { get; set; } = null!;

    public async Task OnGetAsync()
    {
        RoomTypeList = new SelectList(await _db.RoomTypes.Where(t => t.IsActive).ToListAsync(),
            nameof(RoomType.RoomTypeId), nameof(RoomType.TypeName));
    }

    public async Task<IActionResult> OnPostAsync()
    {
        ModelState.Remove("Room.RoomType");

        // Check duplicate: same floor + room number
        if (Room.Floor.HasValue)
        {
            var duplicate = await _db.Rooms
                .FirstOrDefaultAsync(r => r.Floor == Room.Floor && r.RoomNumber == Room.RoomNumber);
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

        _db.Rooms.Add(Room);
        await _db.SaveChangesAsync();

        // Create notification for admins
        await _notifications.CreateForAllAdminsAsync(
            "New Room Added",
            $"Room {Room.RoomNumber} ({_db.RoomTypes.Find(Room.RoomTypeId)?.TypeName}) on Floor {Room.Floor} was created.",
            NotificationType.Success,
            "bi-door-open",
            "/Rooms/Index"
        );

        return RedirectToPage("Index", new { success = $"Room {Room.RoomNumber} created successfully!" });
    }
}