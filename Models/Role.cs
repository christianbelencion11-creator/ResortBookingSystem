using System.ComponentModel.DataAnnotations;

namespace ResortBookingSystem.Models;

public class Role
{
    [Key]
    public int RoleId { get; set; }
    [Required, MaxLength(50)]
    public string RoleName { get; set; } = string.Empty;
    [MaxLength(255)]
    public string? Description { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.Now;
    public ICollection<User> Users { get; set; } = new List<User>();
}
