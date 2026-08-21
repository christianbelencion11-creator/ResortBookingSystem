using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Data;

public class AppDbContext : DbContext
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<Role> Roles => Set<Role>();
    public DbSet<User> Users => Set<User>();
    public DbSet<RoomType> RoomTypes => Set<RoomType>();
    public DbSet<Room> Rooms => Set<Room>();
    public DbSet<Activity> Activities => Set<Activity>();
    public DbSet<ActivitySchedule> ActivitySchedules => Set<ActivitySchedule>();
    public DbSet<Facility> Facilities => Set<Facility>();
    public DbSet<FacilitySchedule> FacilitySchedules => Set<FacilitySchedule>();
    public DbSet<GuestRecord> GuestRecords => Set<GuestRecord>();
    public DbSet<Reservation> Reservations => Set<Reservation>();
    public DbSet<ReservationItem> ReservationItems => Set<ReservationItem>();
    public DbSet<Payment> Payments => Set<Payment>();
    public DbSet<Notification> Notifications => Set<Notification>();
    public DbSet<SeasonalRate> SeasonalRates => Set<SeasonalRate>();
    public DbSet<DiscountCode> DiscountCodes => Set<DiscountCode>();

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        modelBuilder.Entity<User>()
            .HasIndex(u => u.Email)
            .IsUnique();

        modelBuilder.Entity<Room>()
            .HasIndex(r => new { r.Floor, r.RoomNumber })
            .IsUnique()
            .HasFilter("[Floor] IS NOT NULL");

        modelBuilder.Entity<Notification>()
            .HasIndex(n => new { n.UserId, n.IsRead, n.CreatedAt });

        modelBuilder.Entity<Reservation>()
            .HasOne(r => r.User)
            .WithMany()
            .HasForeignKey(r => r.UserId)
            .OnDelete(DeleteBehavior.Restrict);

        modelBuilder.Entity<Reservation>()
            .HasOne(r => r.Guest)
            .WithMany()
            .HasForeignKey(r => r.GuestId)
            .OnDelete(DeleteBehavior.SetNull);

        modelBuilder.Entity<Payment>()
            .HasOne(p => p.Processor)
            .WithMany()
            .HasForeignKey(p => p.ProcessedBy)
            .OnDelete(DeleteBehavior.SetNull);

        modelBuilder.Entity<ReservationItem>()
            .HasOne(i => i.Reservation)
            .WithMany(r => r.Items)
            .HasForeignKey(i => i.ReservationId)
            .OnDelete(DeleteBehavior.Cascade);

        modelBuilder.Entity<ActivitySchedule>()
            .HasOne(s => s.Activity)
            .WithMany(a => a.Schedules)
            .HasForeignKey(s => s.ActivityId)
            .OnDelete(DeleteBehavior.Cascade);

        modelBuilder.Entity<FacilitySchedule>()
            .HasOne(s => s.Facility)
            .WithMany(f => f.Schedules)
            .HasForeignKey(s => s.FacilityId)
            .OnDelete(DeleteBehavior.Cascade);
    }
}
