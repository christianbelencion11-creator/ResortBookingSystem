using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ResortBookingSystem.Models;

public class User
{
    [Key]
    public int UserId { get; set; }
    public int RoleId { get; set; }
    [Required, MaxLength(100)]
    public string FirstName { get; set; } = string.Empty;
    [Required, MaxLength(100)]
    public string LastName { get; set; } = string.Empty;
    [Required, MaxLength(255)]
    public string Email { get; set; } = string.Empty;
    [Required, MaxLength(500)]
    public string PasswordHash { get; set; } = string.Empty;
    [MaxLength(20)]
    public string? PhoneNumber { get; set; }
    public string? Address { get; set; }
    [MaxLength(500)]
    public string? ProfileImage { get; set; }
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public DateTime UpdatedAt { get; set; } = DateTime.Now;
    [ForeignKey("RoleId")]
    public Role Role { get; set; } = null!;
    [NotMapped]
    public string FullName => $"{FirstName} {LastName}";
}
