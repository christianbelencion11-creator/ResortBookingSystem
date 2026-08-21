using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class RoomType
{
    [Key]
    public int RoomTypeId { get; set; }
    [Required, MaxLength(100)]
    public string TypeName { get; set; } = string.Empty;
    public string? Description { get; set; }
    [Column(TypeName = "decimal(10,2)")]
    public decimal BasePrice { get; set; }
    public int MaxOccupancy { get; set; } = 2;
    [MaxLength(500)]
    public string? ImageUrl { get; set; }
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public ICollection<Room> Rooms { get; set; } = new List<Room>();
}
