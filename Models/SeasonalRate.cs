using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class SeasonalRate
{
    [Key]
    public int SeasonalRateId { get; set; }

    [Required, MaxLength(100)]
    public string SeasonName { get; set; } = string.Empty;

    public DateTime StartDate { get; set; }
    public DateTime EndDate { get; set; }

    [Column(TypeName = "decimal(5,2)")]
    public decimal PriceMultiplier { get; set; } = 1.00m;

    public int? RoomTypeId { get; set; }
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.Now;

    [ForeignKey("RoomTypeId")]
    public RoomType? RoomType { get; set; }
}
