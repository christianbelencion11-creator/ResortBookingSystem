using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum FacilityScheduleStatus
{
    Available,
    Booked,
    Maintenance
}

public class FacilitySchedule
{
    [Key]
    public int FacilityScheduleId { get; set; }
    public int FacilityId { get; set; }
    public DateTime ScheduleDate { get; set; }
    public TimeSpan StartTime { get; set; }
    public TimeSpan EndTime { get; set; }
    public FacilityScheduleStatus Status { get; set; } = FacilityScheduleStatus.Available;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    [ForeignKey("FacilityId")]
    public Facility Facility { get; set; } = null!;
}
