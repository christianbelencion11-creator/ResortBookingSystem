using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Controllers;

[ApiController]
[Route("api/[controller]")]
public class PaymentsApiController : ControllerBase
{
    private readonly AppDbContext _db;
    public PaymentsApiController(AppDbContext db) => _db = db;

    [HttpGet("reservation/{reservationId}")]
    public async Task<IActionResult> GetByReservation(int reservationId) =>
        Ok(await _db.Payments.Where(p => p.ReservationId == reservationId).Include(p => p.Processor).OrderBy(p => p.PaymentDate).ToListAsync());

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] Payment payment)
    {
        _db.Payments.Add(payment);
        await _db.SaveChangesAsync();
        return Ok(payment);
    }

    [HttpGet("receipt/{reservationId}")]
    public async Task<IActionResult> GetReceipt(int reservationId)
    {
        var reservation = await _db.Reservations.Include(r => r.User).Include(r => r.Guest).Include(r => r.Items).Include(r => r.Payments)
            .FirstOrDefaultAsync(r => r.ReservationId == reservationId);
        if (reservation == null) return NotFound();
        return Ok(new
        {
            reservation.ReservationId,
            Guest = reservation.Guest?.FullName ?? reservation.User?.FullName,
            reservation.CheckInDate,
            reservation.CheckOutDate,
            reservation.NumberOfNights,
            Items = reservation.Items.Select(i => new { i.ItemType, i.Quantity, i.UnitPrice, i.Subtotal }),
            TotalAmount = reservation.TotalAmount,
            TotalPaid = reservation.TotalPaid,
            Balance = reservation.Balance,
            Payments = reservation.Payments.Select(p => new { p.Amount, p.PaymentMethod, p.PaymentType, p.PaymentDate, p.Status })
        });
    }
}
