using System.Text.Json;

namespace ResortBookingSystem.Services;

public class ResortSettings
{
    public string ResortName { get; set; } = "ResortBooking";
    public string ResortSubtitle { get; set; } = "Management System";
    public string? LogoImage { get; set; }
}

public class ResortSettingsService
{
    private readonly string _filePath;
    private ResortSettings _settings;

    public ResortSettingsService(IWebHostEnvironment env)
    {
        _filePath = Path.Combine(env.ContentRootPath, "resort-settings.json");
        _settings = Load();
    }

    public ResortSettings Get() => _settings;

    public void Save(ResortSettings settings)
    {
        _settings = settings;
        var json = JsonSerializer.Serialize(settings, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(_filePath, json);
    }

    private ResortSettings Load()
    {
        if (File.Exists(_filePath))
        {
            var json = File.ReadAllText(_filePath);
            return JsonSerializer.Deserialize<ResortSettings>(json) ?? new ResortSettings();
        }
        return new ResortSettings();
    }
}
