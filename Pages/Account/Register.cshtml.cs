using System.ComponentModel.DataAnnotations;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Account;

public class RegisterModel : PageModel
{
    private readonly AppDbContext _db;
    public RegisterModel(AppDbContext db) => _db = db;

    [BindProperty]
    public InputModel Input { get; set; } = new();

    public class InputModel
    {
        [Required] public string FirstName { get; set; } = string.Empty;
        [Required] public string LastName { get; set; } = string.Empty;
        [Required, EmailAddress] public string Email { get; set; } = string.Empty;
        public string? PhoneNumber { get; set; }
        [Required, DataType(DataType.Password), MinLength(6)]
        public string Password { get; set; } = string.Empty;
        [Required, DataType(DataType.Password), Compare("Password")]
        public string ConfirmPassword { get; set; } = string.Empty;
    }

    public void OnGet() { }

    public async Task<IActionResult> OnPostAsync()
    {
        if (!ModelState.IsValid) return Page();

        if (await _db.Users.AnyAsync(u => u.Email == Input.Email))
        {
            ModelState.AddModelError(string.Empty, "Email already registered.");
            return Page();
        }

        var guestRole = await _db.Roles.FirstOrDefaultAsync(r => r.RoleName == "Guest");
        if (guestRole == null) return Page();

        var user = new User
        {
            RoleId = guestRole.RoleId,
            FirstName = Input.FirstName,
            LastName = Input.LastName,
            Email = Input.Email,
            PhoneNumber = Input.PhoneNumber,
            PasswordHash = Input.Password // In production: BCrypt hash
        };

        _db.Users.Add(user);
        await _db.SaveChangesAsync();

        HttpContext.Session.SetInt32("UserId", user.UserId);
        HttpContext.Session.SetString("UserName", user.FullName);
        HttpContext.Session.SetString("UserRole", "Guest");

        return RedirectToPage("/Index");
    }
}
