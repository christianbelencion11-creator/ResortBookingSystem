using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using System.Text;

namespace ResortBookingSystem.Pages.Reports;

public class ExportModel : PageModel
{
    private readonly AppDbContext _db;
    public ExportModel(AppDbContext db) => _db = db;

    [BindProperty]
    public string ExportType { get; set; } = "reservations";

    [BindProperty]
    public DateTime? DateFrom { get; set; }

    [BindProperty]
    public DateTime? DateTo { get; set; }

    public string Message { get; set; } = "";

    public void OnGet()
    {
        DateFrom = DateTime.Today.AddMonths(-1);
        DateTo = DateTime.Today;
    }

    public async Task<IActionResult> OnPostAsync()
    {
        var from = DateFrom ?? DateTime.Today.AddMonths(-1);
        var to = DateTo ?? DateTime.Today;
        to = to.AddDays(1).AddSeconds(-1);

        var sb = new StringBuilder();

        switch (ExportType)
        {
            case "reservations":
                sb.AppendLine("ID,Guest,Check-In,Check-Out,Nights,Total Amount,Status");
                var reservations = await _db.Reservations
                    .Include(r => r.Guest).Include(r => r.User)
                    .Where(r => r.CreatedAt >= from && r.CreatedAt <= to)
                    .OrderByDescending(r => r.CreatedAt)
                    .ToListAsync();
                foreach (var r in reservations)
                {
                    var guest = r.Guest != null ? $"{r.Guest.FirstName} {r.Guest.LastName}" : $"{r.User.FirstName} {r.User.LastName}";
                    sb.AppendLine($"{r.ReservationId},\"{guest}\",{r.CheckInDate:yyyy-MM-dd},{r.CheckOutDate:yyyy-MM-dd},{r.NumberOfNights},{r.TotalAmount:F2},{r.Status}");
                }
                break;

            case "payments":
                sb.AppendLine("ID,Reservation,Amount,Method,Type,Date,Status");
                var payments = await _db.Payments
                    .Include(p => p.Reservation)
                    .Where(p => p.PaymentDate >= from && p.PaymentDate <= to)
                    .OrderByDescending(p => p.PaymentDate)
                    .ToListAsync();
                foreach (var p in payments)
                {
                    sb.AppendLine($"{p.PaymentId},#{p.ReservationId},{p.Amount:F2},{p.PaymentMethod},{p.PaymentType},{p.PaymentDate:yyyy-MM-dd},{p.Status}");
                }
                break;

            case "guests":
                sb.AppendLine("ID,Name,Email,Phone,ID Type,ID Number,Total Bookings");
                var guests = await _db.GuestRecords
                    .Select(g => new
                    {
                        g.GuestId,
                        Name = g.FirstName + " " + g.LastName,
                        g.Email,
                        g.PhoneNumber,
                        g.IdType,
                        g.IdNumber,
                        TotalBookings = _db.Reservations.Count(r => r.GuestId == g.GuestId)
                    })
                    .OrderBy(g => g.Name)
                    .ToListAsync();
                foreach (var g in guests)
                {
                    sb.AppendLine($"{g.GuestId},\"{g.Name}\",{g.Email},{g.PhoneNumber},{g.IdType},{g.IdNumber},{g.TotalBookings}");
                }
                break;

            case "rooms":
                sb.AppendLine("Room Number,Type,Floor,Status,Price/Night");
                var rooms = await _db.Rooms
                    .Include(r => r.RoomType)
                    .OrderBy(r => r.RoomNumber)
                    .ToListAsync();
                foreach (var r in rooms)
                {
                    sb.AppendLine($"\"{r.RoomNumber}\",{r.RoomType.TypeName},{r.Floor},{r.Status},{r.RoomType.BasePrice:F2}");
                }
                break;
        }

        var bytes = Encoding.UTF8.GetBytes("\uFEFF" + sb.ToString());
        return File(bytes, "text/csv", $"{ExportType}_{DateTime.Now:yyyyMMdd}.csv");
    }
}
