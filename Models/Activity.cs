using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class Activity
{
    [Key]
    public int ActivityId { get; set; }
    [Required, MaxLength(150)]
    public string ActivityName { get; set; } = string.Empty;
    public string? Description { get; set; }
    [Column(TypeName = "decimal(10,2)")]
    public decimal? PricePerHour { get; set; }
    [Column(TypeName = "decimal(10,2)")]
    public decimal? PricePerDay { get; set; }
    public int MaxParticipants { get; set; } = 20;
    [MaxLength(500)]
    public string? ImageUrl { get; set; }
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public ICollection<ActivitySchedule> Schedules { get; set; } = new List<ActivitySchedule>();
}
