using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ResortBookingSystem.Data;
using ResortBookingSystem.Models;

namespace ResortBookingSystem.Pages.Expenses;

public class IndexModel : PageModel
{
    private readonly AppDbContext _db;
    public IndexModel(AppDbContext db) => _db = db;

    public List<ExpenseEntry> Expenses { get; set; } = new();
    public decimal TotalExpenses { get; set; }
    public int ExpenseCount { get; set; }
    public decimal NetProfit { get; set; }
    [BindProperty] public ExpenseInput Input { get; set; } = new();

    public class ExpenseEntry { public DateTime Date { get; set; } public string Category { get; set; } = ""; public string Description { get; set; } = ""; public decimal Amount { get; set; } public string RecordedBy { get; set; } = ""; }
    public class ExpenseInput { public string Category { get; set; } = "Maintenance"; public string Description { get; set; } = ""; public decimal Amount { get; set; } public DateTime Date { get; set; } = DateTime.Today; }

    private static readonly List<ExpenseEntry> _store = new();

    public async Task OnGetAsync()
    {
        var monthStart = new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);
        var revenue = await _db.Payments.Where(p => p.Status == PaymentStatus.Completed && p.PaymentDate >= monthStart).SumAsync(p => p.Amount);
        Expenses = _store.Where(e => e.Date >= monthStart).OrderByDescending(e => e.Date).ToList();
        TotalExpenses = Expenses.Sum(e => e.Amount);
        ExpenseCount = Expenses.Count;
        NetProfit = revenue - TotalExpenses;
    }

    public IActionResult OnPost()
    {
        _store.Add(new ExpenseEntry { Date = Input.Date, Category = Input.Category, Description = Input.Description, Amount = Input.Amount, RecordedBy = "Admin" });
        return RedirectToPage(new { success = "Expense recorded successfully!" });
    }
}
