using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Admin;

public class UsersModel : PageModel
{
    private readonly AppDbContext _db;
    public UsersModel(AppDbContext db) => _db = db;

    public List<User> Users { get; set; } = new();
    public SelectList RoleList { get; set; } = null!;

    [BindProperty]
    public UserInput NewUser { get; set; } = new();

    [BindProperty]
    public UserInput EditUser { get; set; } = new();

    public class UserInput
    {
        public int UserId { get; set; }
        public string FirstName { get; set; } = string.Empty;
        public string LastName { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string? PhoneNumber { get; set; }
        public int RoleId { get; set; }
        public string PasswordHash { get; set; } = string.Empty;
        public bool IsActive { get; set; } = true;
    }

    public async Task OnGetAsync()
    {
        await LoadData();
    }

    public async Task<IActionResult> OnPostAddUserAsync()
    {
        var user = new User
        {
            RoleId = NewUser.RoleId,
            FirstName = NewUser.FirstName,
            LastName = NewUser.LastName,
            Email = NewUser.Email,
            PhoneNumber = NewUser.PhoneNumber,
            PasswordHash = NewUser.PasswordHash
        };
        _db.Users.Add(user);
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = $"User {user.FirstName} {user.LastName} added!" });
    }

    public async Task<IActionResult> OnPostEditUserAsync()
    {
        var user = await _db.Users.FindAsync(EditUser.UserId);
        if (user == null) return NotFound();
        user.FirstName = EditUser.FirstName;
        user.LastName = EditUser.LastName;
        user.Email = EditUser.Email;
        user.PhoneNumber = EditUser.PhoneNumber;
        user.RoleId = EditUser.RoleId;
        user.IsActive = EditUser.IsActive;
        user.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = $"User {user.FirstName} {user.LastName} updated!" });
    }

    public async Task<IActionResult> OnPostToggleStatusAsync(int id)
    {
        var user = await _db.Users.FindAsync(id);
        if (user == null) return NotFound();
        user.IsActive = !user.IsActive;
        user.UpdatedAt = DateTime.Now;
        await _db.SaveChangesAsync();
        var status = user.IsActive ? "activated" : "deactivated";
        return RedirectToPage(new { success = $"User {user.FirstName} {user.LastName} {status}!" });
    }

    public async Task<IActionResult> OnPostDeleteUserAsync(int UserId)
    {
        var user = await _db.Users.FindAsync(UserId);
        if (user == null) return NotFound();
        var name = user.FirstName + " " + user.LastName;
        _db.Users.Remove(user);
        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = $"User {name} deleted!" });
    }

    private async Task LoadData()
    {
        Users = await _db.Users.Include(u => u.Role).OrderByDescending(u => u.CreatedAt).ToListAsync();
        RoleList = new SelectList(await _db.Roles.ToListAsync(), nameof(Role.RoleId), nameof(Role.RoleName));
    }
}
