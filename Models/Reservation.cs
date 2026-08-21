using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum ReservationStatus
{
    Pending,
    Confirmed,
    CheckedIn,
    CheckedOut,
    Cancelled
}

public class Reservation
{
    [Key]
    public int ReservationId { get; set; }
    public int UserId { get; set; }
    public int? GuestId { get; set; }
    public DateTime CheckInDate { get; set; }
    public DateTime CheckOutDate { get; set; }
    [Column(TypeName = "decimal(12,2)")]
    public decimal TotalAmount { get; set; } = 0;
    public ReservationStatus Status { get; set; } = ReservationStatus.Pending;
    public string? SpecialRequests { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public DateTime UpdatedAt { get; set; } = DateTime.Now;
    [ForeignKey("UserId")]
    public User User { get; set; } = null!;
    [ForeignKey("GuestId")]
    public GuestRecord? Guest { get; set; }
    public ICollection<ReservationItem> Items { get; set; } = new List<ReservationItem>();
    public ICollection<Payment> Payments { get; set; } = new List<Payment>();
    [NotMapped]
    public int NumberOfNights => (CheckOutDate - CheckInDate).Days;
    [NotMapped]
    public decimal TotalPaid => Payments
        .Where(p => p.Status == PaymentStatus.Completed)
        .Sum(p => p.Amount);
    [NotMapped]
    public decimal Balance => TotalAmount - TotalPaid;
}
