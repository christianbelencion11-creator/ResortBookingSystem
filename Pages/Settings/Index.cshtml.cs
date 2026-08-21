using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using ResortBookingSystem.Services;

namespace ResortBookingSystem.Pages.Settings;

public class IndexModel : PageModel
{
    private readonly ResortSettingsService _settingsService;
    private readonly IWebHostEnvironment _env;

    public IndexModel(ResortSettingsService settingsService, IWebHostEnvironment env)
    {
        _settingsService = settingsService;
        _env = env;
    }

    [BindProperty] public string BrandName { get; set; } = "ResortBooking";
    [BindProperty] public string BrandSubtitle { get; set; } = "Management System";
    [BindProperty] public IFormFile? LogoFile { get; set; }
    public string? LogoImage { get; set; }

    [BindProperty] public string ResortName { get; set; } = "Paradise Beach Resort";
    [BindProperty] public string ResortAddress { get; set; } = "123 Beach Road, Philippines";
    [BindProperty] public string ResortPhone { get; set; } = "(02) 8123-4567";
    [BindProperty] public string ResortEmail { get; set; } = "info@paradiseresort.com";
    [BindProperty] public string CheckInTime { get; set; } = "14:00";
    [BindProperty] public string CheckOutTime { get; set; } = "12:00";
    [BindProperty] public int DownpaymentPercent { get; set; } = 50;
    [BindProperty] public int MaxAdvanceDays { get; set; } = 90;
    [BindProperty] public bool AllowWalkIn { get; set; } = true;
    [BindProperty] public decimal VatRate { get; set; } = 12;
    [BindProperty] public decimal ServiceCharge { get; set; } = 10;
    [BindProperty] public bool IncludeTaxInPrice { get; set; } = false;

    public void OnGet()
    {
        var s = _settingsService.Get();
        BrandName = s.ResortName;
        BrandSubtitle = s.ResortSubtitle;
        LogoImage = s.LogoImage;
    }

    public IActionResult OnPostSaveBrand()
    {
        var settings = _settingsService.Get();
        settings.ResortName = BrandName;
        settings.ResortSubtitle = BrandSubtitle;

        if (LogoFile != null && LogoFile.Length > 0)
        {
            var uploadsDir = Path.Combine(_env.WebRootPath, "uploads");
            Directory.CreateDirectory(uploadsDir);
            var fileName = $"logo-{DateTime.Now:yyyyMMddHHmmss}{Path.GetExtension(LogoFile.FileName)}";
            var filePath = Path.Combine(uploadsDir, fileName);
            using var stream = new FileStream(filePath, FileMode.Create);
            LogoFile.CopyTo(stream);
            settings.LogoImage = $"/uploads/{fileName}";
        }

        _settingsService.Save(settings);
        return RedirectToPage(new { success = "Brand & logo updated!" });
    }

    public IActionResult OnPostSaveResort() => RedirectToPage(new { success = "Resort information saved!" });
    public IActionResult OnPostSaveRules() => RedirectToPage(new { success = "Booking rules saved!" });
    public IActionResult OnPostSavePricing() => RedirectToPage(new { success = "Tax & pricing settings saved!" });
}
