using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Rooms;

public class CalendarModel : PageModel
{
    private readonly AppDbContext _db;
    public CalendarModel(AppDbContext db) => _db = db;

    public List<RoomVM> Rooms { get; set; } = new();
    public List<BookingBlock> Bookings { get; set; } = new();
    public int Year { get; set; }
    public int Month { get; set; }
    public string MonthName => new DateTime(Year, Month, 1).ToString("MMMM yyyy");

    public class RoomVM
    {
        public int RoomId { get; set; }
        public string RoomNumber { get; set; } = "";
        public string TypeName { get; set; } = "";
        public RoomStatus Status { get; set; }
    }

    public class BookingBlock
    {
        public int RoomId { get; set; }
        public int ReservationId { get; set; }
        public string GuestName { get; set; } = "";
        public DateTime StartDate { get; set; }
        public DateTime EndDate { get; set; }
        public string Status { get; set; } = "";
    }

    public async Task OnGetAsync(int? year, int? month)
    {
        var today = DateTime.Today;
        Year = year ?? today.Year;
        Month = month ?? today.Month;

        Rooms = await _db.Rooms
            .Include(r => r.RoomType)
            .OrderBy(r => r.RoomNumber)
            .Select(r => new RoomVM
            {
                RoomId = r.RoomId,
                RoomNumber = r.RoomNumber,
                TypeName = r.RoomType.TypeName,
                Status = r.Status
            })
            .ToListAsync();

        var monthStart = new DateTime(Year, Month, 1);
        var monthEnd = monthStart.AddMonths(1).AddDays(-1);

        var reservations = await _db.Reservations
            .Where(r => r.Status != ReservationStatus.Cancelled
                && r.CheckInDate <= monthEnd && r.CheckOutDate >= monthStart)
            .Include(r => r.Guest)
            .Include(r => r.User)
            .ToListAsync();

        Bookings = reservations.Select(r => new BookingBlock
        {
            RoomId = r.Items.FirstOrDefault(i => i.ItemType == ItemType.Room)?.ReferenceId ?? 0,
            ReservationId = r.ReservationId,
            GuestName = r.Guest != null ? r.Guest.FirstName + " " + r.Guest.LastName
                      : r.User.FirstName + " " + r.User.LastName,
            StartDate = r.CheckInDate < monthStart ? monthStart : r.CheckInDate,
            EndDate = r.CheckOutDate > monthEnd ? monthEnd : r.CheckOutDate,
            Status = r.Status.ToString()
        }).Where(b => b.RoomId > 0).ToList();
    }
}
