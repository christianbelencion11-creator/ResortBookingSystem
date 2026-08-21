using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum ItemType
{
    Room,
    Activity,
    Facility
}

public class ReservationItem
{
    [Key]
    public int ItemId { get; set; }
    public int ReservationId { get; set; }
    public ItemType ItemType { get; set; }
    public int ReferenceId { get; set; }
    public int? ScheduleId { get; set; }
    public int Quantity { get; set; } = 1;
    [Column(TypeName = "decimal(10,2)")]
    public decimal UnitPrice { get; set; }
    [Column(TypeName = "decimal(12,2)")]
    public decimal Subtotal { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    [ForeignKey("ReservationId")]
    public Reservation Reservation { get; set; } = null!;
}
