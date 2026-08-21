using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class GuestRecord
{
    [Key]
    public int GuestId { get; set; }
    public int? UserId { get; set; }
    [Required, MaxLength(100)]
    public string FirstName { get; set; } = string.Empty;
    [Required, MaxLength(100)]
    public string LastName { get; set; } = string.Empty;
    [MaxLength(255)]
    public string? Email { get; set; }
    [MaxLength(20)]
    public string? PhoneNumber { get; set; }
    [MaxLength(50)]
    public string? IdType { get; set; }
    [MaxLength(100)]
    public string? IdNumber { get; set; }
    public string? Address { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public User? User { get; set; }
    [NotMapped]
    public string FullName => $"{FirstName} {LastName}";
}
