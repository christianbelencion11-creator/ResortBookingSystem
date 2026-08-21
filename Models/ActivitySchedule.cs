using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum ScheduleStatus
{
    Available,
    Full,
    Cancelled
}

public class ActivitySchedule
{
    [Key]
    public int ScheduleId { get; set; }
    public int ActivityId { get; set; }
    public DateTime ScheduleDate { get; set; }
    public TimeSpan StartTime { get; set; }
    public TimeSpan EndTime { get; set; }
    public int AvailableSlots { get; set; }
    public ScheduleStatus Status { get; set; } = ScheduleStatus.Available;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    [ForeignKey("ActivityId")]
    public Activity Activity { get; set; } = null!;
}
