using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Payments;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public List<Payment> Payments { get; set; } = new();
    [BindProperty]
    public PaymentInput Input { get; set; } = new();

    public class PaymentInput
    {
        public int ReservationId { get; set; }
        public decimal Amount { get; set; }
        public PaymentMethod PaymentMethod { get; set; }
        public PaymentType PaymentType { get; set; }
        public string? ReferenceNumber { get; set; }
    }

    public async Task OnGetAsync([FromQuery] int? reservationId)
    {
        Payments = await _db.Payments
            .Include(p => p.Reservation)
                .ThenInclude(r => r.Guest)
            .Include(p => p.Reservation)
                .ThenInclude(r => r.User)
            .OrderByDescending(p => p.PaymentDate)
            .ToListAsync();

        if (reservationId.HasValue)
            Input.ReservationId = reservationId.Value;
    }

    public async Task<IActionResult> OnPostAsync()
    {
        var reservation = await _db.Reservations.FindAsync(Input.ReservationId);
        if (reservation == null)
        {
            ModelState.AddModelError(string.Empty, $"Reservation #{Input.ReservationId} not found.");
            await OnGetAsync(null);
            return Page();
        }

        var payment = new Payment
        {
            ReservationId = Input.ReservationId,
            Amount = Input.Amount,
            PaymentMethod = Input.PaymentMethod,
            PaymentType = Input.PaymentType,
            ReferenceNumber = Input.ReferenceNumber,
            Status = PaymentStatus.Completed
        };

        _db.Payments.Add(payment);
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = $"Payment of ₱{Input.Amount:N2} recorded successfully for Reservation #{Input.ReservationId}!" });
    }
}
