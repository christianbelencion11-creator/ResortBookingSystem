using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Services;

public class NotificationService
{
    private readonly AppDbContext _db;

    public NotificationService(AppDbContext db) => _db = db;

    public async Task CreateAsync(string title, string message, NotificationType type = NotificationType.Info, 
        string? icon = null, string? actionUrl = null, int? userId = null)
    {
        var notification = new Notification
        {
            Title = title,
            Message = message,
            Type = type,
            Icon = icon,
            ActionUrl = actionUrl,
            UserId = userId,
            CreatedAt = DateTime.Now
        };
        _db.Notifications.Add(notification);
        await _db.SaveChangesAsync();
    }

    public async Task CreateForAllAdminsAsync(string title, string message, NotificationType type = NotificationType.Info,
        string? icon = null, string? actionUrl = null)
    {
        var adminUserIds = await _db.Users
            .Where(u => u.RoleId == 1 && u.IsActive)
            .Select(u => u.UserId)
            .ToListAsync();

        var notifications = adminUserIds.Select(id => new Notification
        {
            Title = title,
            Message = message,
            Type = type,
            Icon = icon,
            ActionUrl = actionUrl,
            UserId = id,
            CreatedAt = DateTime.Now
        }).ToList();

        _db.Notifications.AddRange(notifications);
        await _db.SaveChangesAsync();
    }

    public async Task<List<Notification>> GetUnreadAsync(int userId, int take = 10)
    {
        return await _db.Notifications
            .Where(n => n.UserId == userId && !n.IsRead)
            .OrderByDescending(n => n.CreatedAt)
            .Take(take)
            .ToListAsync();
    }

    public async Task<int> GetUnreadCountAsync(int userId)
    {
        return await _db.Notifications
            .CountAsync(n => n.UserId == userId && !n.IsRead);
    }

    public async Task MarkAsReadAsync(int notificationId, int userId)
    {
        var notification = await _db.Notifications
            .FirstOrDefaultAsync(n => n.NotificationId == notificationId && n.UserId == userId);
        if (notification != null)
        {
            notification.IsRead = true;
            await _db.SaveChangesAsync();
        }
    }

    public async Task MarkAllAsReadAsync(int userId)
    {
        var notifications = await _db.Notifications
            .Where(n => n.UserId == userId && !n.IsRead)
            .ToListAsync();
        foreach (var n in notifications) n.IsRead = true;
        await _db.SaveChangesAsync();
    }
}