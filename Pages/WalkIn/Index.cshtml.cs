using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.WalkIn;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    [BindProperty] public WalkInInput Input { get; set; } = new();
    [BindProperty] public int? EditReservationId { get; set; }
    public SelectList RoomList { get; set; } = null!;
    public List<Room> AvailableRooms { get; set; } = new();
    public List<Reservation> ActiveWalkIns { get; set; } = new();
    public int AvailableRoomsCount { get; set; }
    public int TodayWalkIns { get; set; }

    public class WalkInInput
    {
        public string GuestName { get; set; } = string.Empty;
        public string? Phone { get; set; }
        public string? Email { get; set; }
        public string? IdType { get; set; }
        public DateTime CheckIn { get; set; } = DateTime.Today;
        public DateTime CheckOut { get; set; } = DateTime.Today.AddDays(1);
        public int GuestCount { get; set; } = 1;
        public int RoomId { get; set; }
        public string PaymentMethod { get; set; } = "Cash";
        public decimal AmountPaid { get; set; }
        public string? Notes { get; set; }
    }

    public async Task OnGetAsync()
    {
        await LoadData();
    }

    public async Task<IActionResult> OnPostAsync()
    {
        var room = await _db.Rooms.Include(r => r.RoomType).FirstOrDefaultAsync(r => r.RoomId == Input.RoomId);
        if (room == null) { await LoadData(); return Page(); }

        var guest = new GuestRecord
        {
            FirstName = Input.GuestName.Split(' ').FirstOrDefault() ?? "",
            LastName = string.Join(" ", Input.GuestName.Split(' ').Skip(1)),
            Email = Input.Email,
            PhoneNumber = Input.Phone,
            IdType = Input.IdType
        };
        _db.GuestRecords.Add(guest);
        await _db.SaveChangesAsync();

        var nights = (Input.CheckOut - Input.CheckIn).Days;
        var total = room.RoomType.BasePrice * nights;

        var reservation = new Reservation
        {
            UserId = 1,
            GuestId = guest.GuestId,
            CheckInDate = Input.CheckIn,
            CheckOutDate = Input.CheckOut,
            Status = ReservationStatus.CheckedIn,
            TotalAmount = total,
            SpecialRequests = Input.Notes,
            Items = new List<ReservationItem>
            {
                new() { ItemType = ItemType.Room, ReferenceId = room.RoomId, Quantity = nights, UnitPrice = room.RoomType.BasePrice, Subtotal = total }
            }
        };
        _db.Reservations.Add(reservation);
        await _db.SaveChangesAsync();

        if (Input.AmountPaid > 0)
        {
            _db.Payments.Add(new Payment
            {
                ReservationId = reservation.ReservationId,
                Amount = Input.AmountPaid,
                PaymentMethod = Enum.Parse<PaymentMethod>(Input.PaymentMethod),
                PaymentType = Input.AmountPaid >= total ? PaymentType.Full : PaymentType.Downpayment,
                Status = PaymentStatus.Completed
            });
            await _db.SaveChangesAsync();
        }

        room.Status = RoomStatus.Occupied;
        await _db.SaveChangesAsync();

        return RedirectToPage(new { success = "Walk-in booking created successfully" });
    }

    public async Task<IActionResult> OnPostEditAsync()
    {
        if (!EditReservationId.HasValue) return RedirectToPage();
        var id = EditReservationId.Value;
        var reservation = await _db.Reservations
            .Include(r => r.Guest)
            .FirstOrDefaultAsync(r => r.ReservationId == id);
        if (reservation == null) return RedirectToPage();

        if (reservation.Guest != null)
        {
            reservation.Guest.FirstName = Input.GuestName.Split(' ').FirstOrDefault() ?? reservation.Guest.FirstName;
            reservation.Guest.LastName = string.Join(" ", Input.GuestName.Split(' ').Skip(1));
            reservation.Guest.Email = Input.Email;
            reservation.Guest.PhoneNumber = Input.Phone;
            reservation.Guest.IdType = Input.IdType;
        }

        reservation.CheckInDate = Input.CheckIn;
        reservation.CheckOutDate = Input.CheckOut;
        reservation.SpecialRequests = Input.Notes;
        reservation.UpdatedAt = DateTime.Now;

        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = "Walk-in updated successfully" });
    }

    public async Task<IActionResult> OnPostCheckoutAsync(int id)
    {
        var reservation = await _db.Reservations
            .Include(r => r.Items)
            .FirstOrDefaultAsync(r => r.ReservationId == id);
        if (reservation == null) return RedirectToPage();

        reservation.Status = ReservationStatus.CheckedOut;
        reservation.UpdatedAt = DateTime.Now;

        foreach (var item in reservation.Items.Where(i => i.ItemType == ItemType.Room))
        {
            var room = await _db.Rooms.FindAsync(item.ReferenceId);
            if (room != null) room.Status = RoomStatus.Available;
        }

        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = "Guest checked out successfully. Room is now available." });
    }

    private async Task LoadData()
    {
        AvailableRooms = await _db.Rooms
            .Include(r => r.RoomType)
            .Where(r => r.Status == RoomStatus.Available)
            .ToListAsync();
        AvailableRoomsCount = AvailableRooms.Count;
        TodayWalkIns = await _db.Reservations.CountAsync(r => r.CheckInDate == DateTime.Today && r.Status == ReservationStatus.CheckedIn);
        RoomList = new SelectList(AvailableRooms.Select(r => new { Value = r.RoomId, Text = $"{r.RoomNumber} - {r.RoomType.TypeName} (₱{r.RoomType.BasePrice}/night)" }), "Value", "Text");

        ActiveWalkIns = await _db.Reservations
            .Include(r => r.Guest)
            .Include(r => r.Items)
            .Include(r => r.Payments)
            .Where(r => r.Status == ReservationStatus.CheckedIn)
            .OrderByDescending(r => r.CheckInDate)
            .ToListAsync();
    }
}
