using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Reservations;

public class CreateModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly NotificationService _notifications;
    public CreateModel(AppDbContext db, NotificationService notifications) { _db = db; _notifications = notifications; }

    [BindProperty]
    public BookingInput Input { get; set; } = new();
    public SelectList RoomList { get; set; } = null!;
    public SelectList ActivityList { get; set; } = null!;
    public Dictionary<int, decimal> ActivityPrices { get; set; } = new();

    public class BookingInput
    {
        public string GuestName { get; set; } = string.Empty;
        public string? GuestPhone { get; set; }
        public DateTime CheckInDate { get; set; } = DateTime.Today;
        public DateTime CheckOutDate { get; set; } = DateTime.Today.AddDays(1);
        public string? SpecialRequests { get; set; }
        public int? SelectedRoomId { get; set; }
        public int RoomNights { get; set; } = 1;
        public int? SelectedActivityId { get; set; }
        public int ActivityQty { get; set; } = 1;
        public decimal ActivityPrice { get; set; }
        public string ActivityPriceType { get; set; } = "Hour";
        public string ItemsJson { get; set; } = "[]";
    }

    public async Task OnGetAsync()
    {
        await LoadSelectLists();
    }

    public async Task<IActionResult> OnPostAsync()
    {
        var items = System.Text.Json.JsonSerializer.Deserialize<List<ClientBookingItem>>(Input.ItemsJson ?? "[]");
        if (items == null || !items.Any())
        {
            await LoadSelectLists();
            return Page();
        }

        int? guestId = null;
        if (!string.IsNullOrWhiteSpace(Input.GuestName))
        {
            var nameParts = Input.GuestName.Trim().Split(' ', 2, StringSplitOptions.RemoveEmptyEntries);
            var firstName = nameParts.Length > 0 ? nameParts[0] : Input.GuestName;
            var lastName = nameParts.Length > 1 ? nameParts[1] : "";

            var guest = new GuestRecord
            {
                FirstName = firstName,
                LastName = lastName,
                PhoneNumber = Input.GuestPhone,
                CreatedAt = DateTime.Now
            };
            _db.GuestRecords.Add(guest);
            await _db.SaveChangesAsync();
            guestId = guest.GuestId;
        }

        var reservation = new Reservation
        {
            UserId = HttpContext.Session.GetInt32("UserId") ?? 1,
            GuestId = guestId,
            CheckInDate = Input.CheckInDate,
            CheckOutDate = Input.CheckOutDate,
            SpecialRequests = Input.SpecialRequests,
            Status = ReservationStatus.Pending,
            TotalAmount = items.Sum(i => i.Subtotal),
            Items = items.Select(i => new ReservationItem
            {
                ItemType = i.Type == "Room" ? ItemType.Room : ItemType.Activity,
                ReferenceId = i.ReferenceId,
                Quantity = i.Quantity,
                UnitPrice = i.UnitPrice,
                Subtotal = i.Subtotal
            }).ToList()
        };

        _db.Reservations.Add(reservation);
        await _db.SaveChangesAsync();

        var roomNames = items.Where(i => i.Type == "Room").Select(i => i.Description).ToList();
        var activityNames = items.Where(i => i.Type == "Activity").Select(i => i.Description).ToList();
        var details = new List<string>();
        if (roomNames.Any()) details.Add($"Rooms: {string.Join(", ", roomNames)}");
        if (activityNames.Any()) details.Add($"Activities: {string.Join(", ", activityNames)}");

        await _notifications.CreateForAllAdminsAsync(
            "New Reservation Created",
            $"Reservation #{reservation.ReservationId} for {Input.GuestName} (₱{reservation.TotalAmount:N2}). {string.Join("; ", details)}",
            NotificationType.Success,
            "bi-calendar-check",
            $"/Reservations/Details?id={reservation.ReservationId}"
        );

        return RedirectToPage("Details", new { id = reservation.ReservationId, success = $"Reservation #{reservation.ReservationId} created for {Input.GuestName}!" });
    }

    public class ClientBookingItem
    {
        public string Description { get; set; } = "";
        public string Type { get; set; } = "";
        public int ReferenceId { get; set; }
        public int Quantity { get; set; }
        public decimal UnitPrice { get; set; }
        public decimal Subtotal => Quantity * UnitPrice;
    }

    private async Task LoadSelectLists()
    {
        var rooms = await _db.Rooms
            .Where(r => r.Status == RoomStatus.Available)
            .Include(r => r.RoomType)
            .Select(r => new { Value = r.RoomId, Text = $"{r.RoomNumber} - {r.RoomType.TypeName} (₱{r.RoomType.BasePrice})", Price = r.RoomType.BasePrice })
            .ToListAsync();

        RoomList = new SelectList(rooms, "Value", "Text");

        var activities = await _db.Activities
            .Where(a => a.IsActive)
            .Select(a => new { Value = a.ActivityId, Text = a.ActivityName, Price = a.PricePerHour ?? a.PricePerDay ?? 0 })
            .ToListAsync();

        ActivityList = new SelectList(activities, "Value", "Text");
        ActivityPrices = activities.ToDictionary(a => a.Value, a => a.Price);
    }
}
