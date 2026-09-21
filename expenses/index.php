<?php
$pageTitle = 'Resort Expenses';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Record Expense POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    $category = trim($_POST['category'] ?? 'Maintenance');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
    $user = currentUser();
    $recordedBy = $user ? $user['name'] : 'Admin';

    if ($amount > 0 && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO expenses (Category, Description, Amount, ExpenseDate, RecordedBy, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$category, $description, $amount, $expenseDate, $recordedBy]);
        logAudit('Create', 'Expenses', "Recorded $category expense: $description (" . formatCurrency($amount) . ")", 'badge-danger');
        setFlash('success', 'Expense recorded successfully!');
        header("Location: " . url('/expenses/index.php'));
        exit;
    }
}

// Fetch Expenses
$categoryFilter = trim($_GET['category'] ?? '');
$query = "SELECT * FROM expenses WHERE 1=1";
$params = [];

if (!empty($categoryFilter)) {
    $query .= " AND Category = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY ExpenseDate DESC, ExpenseId DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Month Start calculation for profit/loss
$monthStart = date('Y-m-01');
$stmtMRev = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentDate >= ?");
$stmtMRev->execute([$monthStart]);
$monthlyRevenue = (float)$stmtMRev->fetchColumn();

$stmtMExp = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM expenses WHERE ExpenseDate >= ?");
$stmtMExp->execute([$monthStart]);
$monthlyExpenses = (float)$stmtMExp->fetchColumn();

$netProfit = $monthlyRevenue - $monthlyExpenses;

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Operating Expenses</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Track maintenance, utilities, supplies, staff payroll, and operational costs</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('addExpenseModal')).show()">
        <i class="bi bi-plus-lg me-1"></i> Record Expense
    </button>
</div>

<!-- Financial Summary Grid -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value text-success"><?= formatCurrency($monthlyRevenue) ?></div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-graph-up-arrow"></i> Current Month Income</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value text-danger"><?= formatCurrency($monthlyExpenses) ?></div>
                <div class="stat-label">Total Expenses</div>
            </div>
            <div class="stat-icon orange"><i class="bi bi-wallet2"></i></div>
        </div>
        <div class="stat-change down"><i class="bi bi-arrow-down-circle"></i> Current Month Outflow</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value <?= $netProfit >= 0 ? 'text-primary' : 'text-danger' ?>">
                    <?= formatCurrency($netProfit) ?>
                </div>
                <div class="stat-label">Net Operating Income</div>
            </div>
            <div class="stat-icon <?= $netProfit >= 0 ? 'blue' : 'red' ?>"><i class="bi bi-pie-chart-fill"></i></div>
        </div>
        <div class="stat-change <?= $netProfit >= 0 ? 'up' : 'down' ?>">
            <i class="bi bi-activity"></i> Net Profit / Margin
        </div>
    </div>
</div>

<!-- Expense Records Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-receipt-cutoff"></i> Expense Ledger</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px;">Filter Category:</span>
            <select class="form-select form-select-sm" style="width:auto;" onchange="location.href='<?= url("/expenses/index.php") ?>?category=' + this.value">
                <option value="">All Categories</option>
                <option value="Maintenance" <?= $categoryFilter === 'Maintenance' ? 'selected' : '' ?>>Maintenance & Repairs</option>
                <option value="Utilities" <?= $categoryFilter === 'Utilities' ? 'selected' : '' ?>>Utilities (Water/Electric)</option>
                <option value="Supplies" <?= $categoryFilter === 'Supplies' ? 'selected' : '' ?>>Guest Supplies & Linen</option>
                <option value="Food & Beverage" <?= $categoryFilter === 'Food & Beverage' ? 'selected' : '' ?>>Food & Beverage</option>
                <option value="Payroll" <?= $categoryFilter === 'Payroll' ? 'selected' : '' ?>>Staff Payroll</option>
                <option value="Marketing" <?= $categoryFilter === 'Marketing' ? 'selected' : '' ?>>Advertising & Marketing</option>
            </select>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No expenses recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= formatDate($e['ExpenseDate']) ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($e['Category']) ?></span></td>
                            <td><strong><?= htmlspecialchars($e['Description']) ?></strong></td>
                            <td><strong class="text-danger"><?= formatCurrency($e['Amount']) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($e['RecordedBy']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Expense -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_expense" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Record New Expense</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="Maintenance">Maintenance & Repairs</option>
                                <option value="Utilities">Utilities (Water/Electric)</option>
                                <option value="Supplies">Guest Supplies & Linen</option>
                                <option value="Food & Beverage">Food & Beverage</option>
                                <option value="Payroll">Staff Payroll</option>
                                <option value="Marketing">Advertising & Marketing</option>
                                <option value="Other">Other Operational</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Expense Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Amount (₱) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Description & Remarks *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Explain what this expense was for..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
