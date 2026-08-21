using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class Facility
{
    [Key]
    public int FacilityId { get; set; }
    [Required, MaxLength(150)]
    public string FacilityName { get; set; } = string.Empty;
    public string? Description { get; set; }
    [Column(TypeName = "decimal(10,2)")]
    public decimal RentalPrice { get; set; }
    public int Capacity { get; set; } = 20;
    [MaxLength(500)]
    public string? ImageUrl { get; set; }
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public ICollection<FacilitySchedule> Schedules { get; set; } = new List<FacilitySchedule>();
}
