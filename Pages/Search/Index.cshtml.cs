using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Search;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    [BindProperty(SupportsGet = true)]
    public SearchQuery Query { get; set; } = new();

    public List<Reservation> ReservationResults { get; set; } = new();
    public List<Room> RoomResults { get; set; } = new();
    public List<GuestRecord> GuestResults { get; set; } = new();
    public List<Activity> ActivityResults { get; set; } = new();
    public int TotalResults => ReservationResults.Count + RoomResults.Count + GuestResults.Count + ActivityResults.Count;

    public class SearchQuery
    {
        public string? Type { get; set; } = "all";
        public string? Keyword { get; set; }
        public DateTime? FromDate { get; set; }
        public DateTime? ToDate { get; set; }
    }

    public async Task OnGetAsync()
    {
        if (string.IsNullOrEmpty(Query.Keyword)) return;

        var kw = Query.Keyword.ToLower();
        var searchAll = Query.Type == "all";

        if (searchAll || Query.Type == "reservations")
        {
            var query = _db.Reservations
                .Include(r => r.User).Include(r => r.Guest)
                .Where(r => (r.User.FirstName + " " + r.User.LastName).ToLower().Contains(kw)
                         || (r.Guest != null && (r.Guest.FirstName + " " + r.Guest.LastName).ToLower().Contains(kw))
                         || r.ReservationId.ToString() == kw);
            if (Query.FromDate.HasValue) query = query.Where(r => r.CheckInDate >= Query.FromDate);
            if (Query.ToDate.HasValue) query = query.Where(r => r.CheckOutDate <= Query.ToDate);
            ReservationResults = await query.OrderByDescending(r => r.CreatedAt).Take(20).ToListAsync();
        }

        if (searchAll || Query.Type == "rooms")
        {
            RoomResults = await _db.Rooms.Include(r => r.RoomType)
                .Where(r => r.RoomNumber.ToLower().Contains(kw) || r.RoomType.TypeName.ToLower().Contains(kw))
                .Take(20).ToListAsync();
        }

        if (searchAll || Query.Type == "guests")
        {
            GuestResults = await _db.GuestRecords
                .Where(g => (g.FirstName + " " + g.LastName).ToLower().Contains(kw)
                         || (g.Email != null && g.Email.ToLower().Contains(kw))
                         || (g.PhoneNumber != null && g.PhoneNumber.Contains(kw)))
                .Take(20).ToListAsync();
        }

        if (searchAll || Query.Type == "activities")
        {
            ActivityResults = await _db.Activities
                .Where(a => a.ActivityName.ToLower().Contains(kw) || (a.Description != null && a.Description.ToLower().Contains(kw)))
                .Take(20).ToListAsync();
        }
    }
}
