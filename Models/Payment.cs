using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum PaymentMethod
{
    Cash,
    Card,
    GCash,
    Maya,
    BankTransfer
}

public enum PaymentType
{
    Downpayment,
    Full,
    Partial,
    Balance
}

public enum PaymentStatus
{
    Pending,
    Completed,
    Failed,
    Refunded
}

public class Payment
{
    [Key]
    public int PaymentId { get; set; }
    public int ReservationId { get; set; }
    [Column(TypeName = "decimal(12,2)")]
    public decimal Amount { get; set; }
    public PaymentMethod PaymentMethod { get; set; }
    public PaymentType PaymentType { get; set; }
    [MaxLength(100)]
    public string? ReferenceNumber { get; set; }
    public DateTime PaymentDate { get; set; } = DateTime.Now;
    public PaymentStatus Status { get; set; } = PaymentStatus.Completed;
    public int? ProcessedBy { get; set; }
    [ForeignKey("ReservationId")]
    public Reservation Reservation { get; set; } = null!;
    [ForeignKey("ProcessedBy")]
    public User? Processor { get; set; }
}
