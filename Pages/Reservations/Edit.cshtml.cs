using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;
using System.Text.Json;

namespace ResortBookingSystem.Pages.Reservations;

public class EditModel : PageModel
{
    private readonly AppDbContext _db;
    public EditModel(AppDbContext db) => _db = db;

    [BindProperty]
    public ReservationVM Reservation { get; set; } = new();

    public List<RoomOption> AvailableRooms { get; set; } = new();
    public List<ActivityOption> AvailableActivities { get; set; } = new();
    public List<GuestRecord> Guests { get; set; } = new();
    public List<SeasonalRate> ActiveRates { get; set; } = new();
    public List<DiscountCode> ActiveDiscounts { get; set; } = new();

    public class ReservationVM
    {
        public int ReservationId { get; set; }
        public int? GuestId { get; set; }
        public DateTime CheckInDate { get; set; } = DateTime.Today;
        public DateTime CheckOutDate { get; set; } = DateTime.Today.AddDays(1);
        public decimal TotalAmount { get; set; }
        public string Status { get; set; } = "";
        public string? SpecialRequests { get; set; }
        public string? DiscountCode { get; set; }
        public decimal DiscountAmount { get; set; }
        public List<CartItem> Items { get; set; } = new();
    }

    public class CartItem
    {
        public string ItemType { get; set; } = "";
        public int ReferenceId { get; set; }
        public string Name { get; set; } = "";
        public decimal UnitPrice { get; set; }
        public int Quantity { get; set; } = 1;
        public decimal Subtotal => UnitPrice * Quantity;
    }

    public class RoomOption
    {
        public int RoomId { get; set; }
        public string RoomNumber { get; set; } = "";
        public string TypeName { get; set; } = "";
        public decimal BasePrice { get; set; }
        public RoomStatus Status { get; set; }
    }

    public class ActivityOption
    {
        public int ActivityId { get; set; }
        public string ActivityName { get; set; } = "";
        public decimal Price { get; set; }
    }

    public async Task<IActionResult> OnGetAsync(int id)
    {
        var res = await _db.Reservations
            .Include(r => r.Items)
            .Include(r => r.Guest)
            .FirstOrDefaultAsync(r => r.ReservationId == id);

        if (res == null) return NotFound();
        if (res.Status == ReservationStatus.CheckedIn || res.Status == ReservationStatus.CheckedOut || res.Status == ReservationStatus.Cancelled)
            return RedirectToPage("/Reservations/Index");

        Reservation = new ReservationVM
        {
            ReservationId = res.ReservationId,
            GuestId = res.GuestId,
            CheckInDate = res.CheckInDate,
            CheckOutDate = res.CheckOutDate,
            TotalAmount = res.TotalAmount,
            Status = res.Status.ToString(),
            SpecialRequests = res.SpecialRequests,
            DiscountCode = null,
            Items = res.Items.Select(i => new CartItem
            {
                ItemType = i.ItemType.ToString(),
                ReferenceId = i.ReferenceId,
                Name = GetItemName(i.ItemType, i.ReferenceId),
                UnitPrice = i.UnitPrice,
                Quantity = i.Quantity
            }).ToList()
        };

        await LoadOptions();
        return Page();
    }

    public async Task<IActionResult> OnPostAsync(int id, [FromForm] string itemsJson, [FromForm] string? discountCode)
    {
        var res = await _db.Reservations
            .Include(r => r.Items)
            .FirstOrDefaultAsync(r => r.ReservationId == id);

        if (res == null) return NotFound();
        if (res.Status == ReservationStatus.CheckedIn || res.Status == ReservationStatus.CheckedOut || res.Status == ReservationStatus.Cancelled)
            return RedirectToPage("/Reservations/Index");

        res.GuestId = Reservation.GuestId > 0 ? Reservation.GuestId : null;
        res.CheckInDate = Reservation.CheckInDate;
        res.CheckOutDate = Reservation.CheckOutDate;
        res.SpecialRequests = Reservation.SpecialRequests;
        res.UpdatedAt = DateTime.Now;

        _db.ReservationItems.RemoveRange(res.Items);

        if (!string.IsNullOrEmpty(itemsJson))
        {
            var items = JsonSerializer.Deserialize<List<CartItem>>(itemsJson, new JsonSerializerOptions { PropertyNameCaseInsensitive = true });
            if (items != null)
            {
                decimal total = 0;
                foreach (var item in items)
                {
                    var parsedType = Enum.Parse<ItemType>(item.ItemType);
                    var ri = new ReservationItem
                    {
                        ReservationId = id,
                        ItemType = parsedType,
                        ReferenceId = item.ReferenceId,
                        UnitPrice = item.UnitPrice,
                        Quantity = item.Quantity,
                        Subtotal = item.UnitPrice * item.Quantity
                    };
                    _db.ReservationItems.Add(ri);
                    total += ri.Subtotal;
                }
                res.TotalAmount = total;
            }
        }

        if (!string.IsNullOrEmpty(discountCode))
        {
            var dc = await _db.DiscountCodes.FirstOrDefaultAsync(d => d.Code == discountCode && d.IsActive);
            if (dc != null && dc.CurrentUses < dc.MaxUses && DateTime.Now >= dc.ValidFrom && DateTime.Now <= dc.ValidTo)
            {
                decimal discount = dc.IsPercent ? res.TotalAmount * (dc.DiscountValue / 100m) : dc.DiscountValue;
                res.TotalAmount -= discount;
                dc.CurrentUses++;
            }
        }

        await _db.SaveChangesAsync();
        return RedirectToPage("/Reservations/Index", new { success = $"Reservation #{id} updated successfully" });
    }

    private async Task LoadOptions()
    {
        AvailableRooms = await _db.Rooms
            .Include(r => r.RoomType)
            .Where(r => r.Status == RoomStatus.Available || r.Status == RoomStatus.Reserved)
            .Select(r => new RoomOption
            {
                RoomId = r.RoomId,
                RoomNumber = r.RoomNumber,
                TypeName = r.RoomType.TypeName,
                BasePrice = r.RoomType.BasePrice,
                Status = r.Status
            })
            .ToListAsync();

        AvailableActivities = await _db.Activities
            .Where(a => a.IsActive)
            .Select(a => new ActivityOption
            {
                ActivityId = a.ActivityId,
                ActivityName = a.ActivityName,
                Price = a.PricePerDay ?? a.PricePerHour ?? 0
            })
            .ToListAsync();

        Guests = await _db.GuestRecords
            .OrderBy(g => g.LastName)
            .ToListAsync();

        ActiveRates = await _db.SeasonalRates
            .Where(r => r.IsActive && r.EndDate >= DateTime.Today)
            .ToListAsync();

        ActiveDiscounts = await _db.DiscountCodes
            .Where(d => d.IsActive && d.CurrentUses < d.MaxUses && DateTime.Now >= d.ValidFrom && DateTime.Now <= d.ValidTo)
            .ToListAsync();
    }

    private string GetItemName(ItemType type, int refId)
    {
        return type switch
        {
            ItemType.Room => _db.Rooms.Find(refId)?.RoomNumber ?? "Room",
            ItemType.Activity => _db.Activities.Find(refId)?.ActivityName ?? "Activity",
            ItemType.Facility => _db.Facilities.Find(refId)?.FacilityName ?? "Facility",
            _ => "Item"
        };
    }
}
