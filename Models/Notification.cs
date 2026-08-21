using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public enum NotificationType
{
    Info,
    Success,
    Warning,
    Danger
}

public class Notification
{
    [Key]
    public int NotificationId { get; set; }

    [Required, MaxLength(100)]
    public string Title { get; set; } = string.Empty;

    [Required]
    public string Message { get; set; } = string.Empty;

    public NotificationType Type { get; set; } = NotificationType.Info;

    [MaxLength(50)]
    public string? Icon { get; set; }

    [MaxLength(50)]
    public string? ActionUrl { get; set; }

    public bool IsRead { get; set; } = false;

    public int? UserId { get; set; }

    [ForeignKey("UserId")]
    public User? User { get; set; }

    public DateTime CreatedAt { get; set; } = DateTime.Now;
}