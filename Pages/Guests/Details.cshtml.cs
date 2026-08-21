using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Guests;

public class DetailsModel : PageModel
{
    private readonly AppDbContext _db;
    public DetailsModel(AppDbContext db) => _db = db;

    public GuestRecord Guest { get; set; } = null!;
    public List<ReservationHistoryVM> Reservations { get; set; } = new();
    public decimal TotalSpent { get; set; }
    public int TotalBookings { get; set; }

    public class ReservationHistoryVM
    {
        public int ReservationId { get; set; }
        public DateTime CheckInDate { get; set; }
        public DateTime CheckOutDate { get; set; }
        public int Nights { get; set; }
        public decimal TotalAmount { get; set; }
        public decimal AmountPaid { get; set; }
        public string Status { get; set; } = "";
        public int ItemsCount { get; set; }
    }

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var guest = await _db.GuestRecords.FindAsync(id);
        if (guest == null) return NotFound();

        Guest = guest;

        Reservations = await _db.Reservations
            .Where(r => r.GuestId == id)
            .Include(r => r.Items)
            .Include(r => r.Payments)
            .OrderByDescending(r => r.CreatedAt)
            .Select(r => new ReservationHistoryVM
            {
                ReservationId = r.ReservationId,
                CheckInDate = r.CheckInDate,
                CheckOutDate = r.CheckOutDate,
                Nights = r.NumberOfNights,
                TotalAmount = r.TotalAmount,
                AmountPaid = r.Payments.Where(p => p.Status == PaymentStatus.Completed).Sum(p => p.Amount),
                Status = r.Status.ToString(),
                ItemsCount = r.Items.Count
            })
            .ToListAsync();

        TotalSpent = Reservations.Sum(r => r.AmountPaid);
        TotalBookings = Reservations.Count;

        return Page();
    }
}
