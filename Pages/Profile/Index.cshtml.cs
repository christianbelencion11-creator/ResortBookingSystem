using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Profile;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly IWebHostEnvironment _env;

    public IndexModel(AppDbContext db, IWebHostEnvironment env)
    {
        _db = db;
        _env = env;
    }

    [BindProperty]
    public UserProfileInput Input { get; set; } = new();

    public string? CurrentImage { get; set; }

    public class UserProfileInput
    {
        public int UserId { get; set; }
        public string FirstName { get; set; } = string.Empty;
        public string LastName { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string? PhoneNumber { get; set; }
        public string? Address { get; set; }
        public IFormFile? ProfileImage { get; set; }
    }

    public async Task OnGetAsync()
    {
        var user = await _db.Users.FirstOrDefaultAsync();
        if (user != null)
        {
            Input = new UserProfileInput
            {
                UserId = user.UserId,
                FirstName = user.FirstName,
                LastName = user.LastName,
                Email = user.Email,
                PhoneNumber = user.PhoneNumber,
                Address = user.Address
            };
            CurrentImage = user.ProfileImage;
        }
    }

    public async Task<IActionResult> OnPostAsync()
    {
        var user = await _db.Users.FindAsync(Input.UserId);
        if (user == null) return NotFound();

        user.FirstName = Input.FirstName;
        user.LastName = Input.LastName;
        user.Email = Input.Email;
        user.PhoneNumber = Input.PhoneNumber;
        user.Address = Input.Address;
        user.UpdatedAt = DateTime.Now;

        if (Input.ProfileImage != null && Input.ProfileImage.Length > 0)
        {
            var uploadsDir = Path.Combine(_env.WebRootPath, "uploads");
            if (!Directory.Exists(uploadsDir))
                Directory.CreateDirectory(uploadsDir);

            var fileName = $"profile_{user.UserId}_{DateTime.Now:yyyyMMddHHmmss}{Path.GetExtension(Input.ProfileImage.FileName)}";
            var filePath = Path.Combine(uploadsDir, fileName);

            using (var stream = new FileStream(filePath, FileMode.Create))
            {
                await Input.ProfileImage.CopyToAsync(stream);
            }

            user.ProfileImage = $"/uploads/{fileName}";
        }

        await _db.SaveChangesAsync();
        return RedirectToPage(new { success = "Profile updated successfully!" });
    }
}
