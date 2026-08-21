using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Guests;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public List<GuestVM> Guests { get; set; } = new();

    public class GuestVM
    {
        public int GuestId { get; set; }
        public string FullName { get; set; } = "";
        public string? Email { get; set; }
        public string? PhoneNumber { get; set; }
        public string? IdType { get; set; }
        public string? IdNumber { get; set; }
        public int TotalBookings { get; set; }
        public decimal TotalSpent { get; set; }
        public DateTime LastVisit { get; set; }
    }

    public async Task OnGetAsync()
    {
        Guests = await _db.GuestRecords
            .Select(g => new GuestVM
            {
                GuestId = g.GuestId,
                FullName = g.FirstName + " " + g.LastName,
                Email = g.Email,
                PhoneNumber = g.PhoneNumber,
                IdType = g.IdType,
                IdNumber = g.IdNumber,
                TotalBookings = _db.Reservations.Count(r => r.GuestId == g.GuestId),
                TotalSpent = _db.Payments
                    .Where(p => p.Reservation.GuestId == g.GuestId && p.Status == PaymentStatus.Completed)
                    .Sum(p => p.Amount),
                LastVisit = _db.Reservations
                    .Where(r => r.GuestId == g.GuestId)
                    .Max(r => (DateTime?)r.CreatedAt) ?? g.CreatedAt
            })
            .OrderByDescending(g => g.LastVisit)
            .ToListAsync();
    }
}
