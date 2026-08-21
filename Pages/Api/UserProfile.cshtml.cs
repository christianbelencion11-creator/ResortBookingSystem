using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;

namespace ResortBookingSystem.Pages.Api;

public class UserProfileModel : PageModel
{
    private readonly AppDbContext _db;
    private readonly IWebHostEnvironment _env;
    public UserProfileModel(AppDbContext db, IWebHostEnvironment env) { _db = db; _env = env; }

    public async Task<IActionResult> OnGetAsync()
    {
        var user = await _db.Users.FirstOrDefaultAsync();
        if (user == null) return new JsonResult(new { name = "Admin", email = "", image = (string?)null });

        return new JsonResult(new
        {
            name = user.FirstName + " " + user.LastName,
            email = user.Email,
            image = user.ProfileImage
        });
    }

    public async Task<IActionResult> OnPostUploadAsync(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return new JsonResult(new { success = false, error = "No file uploaded" });

        var allowed = new[] { ".jpg", ".jpeg", ".png", ".gif", ".webp" };
        var ext = Path.GetExtension(file.FileName).ToLowerInvariant();
        if (!allowed.Contains(ext))
            return new JsonResult(new { success = false, error = "Invalid file type" });

        var user = await _db.Users.FirstOrDefaultAsync();
        if (user == null) return new JsonResult(new { success = false, error = "User not found" });

        var uploadsDir = Path.Combine(_env.WebRootPath, "uploads");
        Directory.CreateDirectory(uploadsDir);
        var fileName = $"avatar-{user.UserId}-{DateTime.Now:yyyyMMddHHmmss}{ext}";
        var filePath = Path.Combine(uploadsDir, fileName);
        using (var stream = new FileStream(filePath, FileMode.Create))
        {
            await file.CopyToAsync(stream);
        }

        var imageUrl = $"/uploads/{fileName}";
        user.ProfileImage = imageUrl;
        await _db.SaveChangesAsync();

        return new JsonResult(new { success = true, image = imageUrl });
    }
}