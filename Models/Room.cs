using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum RoomStatus
{
    Available,
    Occupied,
    UnderMaintenance,
    Reserved
}

public class Room
{
    [Key]
    public int RoomId { get; set; }
    public int RoomTypeId { get; set; }
    [Required, MaxLength(20)]
    public string RoomNumber { get; set; } = string.Empty;
    public int? Floor { get; set; }
    public RoomStatus Status { get; set; } = RoomStatus.Available;
    public string? Description { get; set; }
    [MaxLength(500)]
    public string? ImageUrl { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public DateTime UpdatedAt { get; set; } = DateTime.Now;
    [ForeignKey("RoomTypeId")]
    public RoomType RoomType { get; set; } = null!;
}
