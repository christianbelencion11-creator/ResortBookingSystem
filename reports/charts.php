<?php
$pageTitle = 'Visual Analytics & Charts';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Monthly Revenue for last 6 months
$monthsData = [];
for ($i = 5; $i >= 0; $i--) {
    $mStart = date('Y-m-01 00:00:00', strtotime("-$i months"));
    $mEnd = date('Y-m-t 23:59:59', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));

    $stmtRev = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentDate >= ? AND PaymentDate <= ?");
    $stmtRev->execute([$mStart, $mEnd]);
    $rev = (float)$stmtRev->fetchColumn();

    $stmtExp = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM expenses WHERE ExpenseDate >= ? AND ExpenseDate <= ?");
    $stmtExp->execute([date('Y-m-01', strtotime("-$i months")), date('Y-m-t', strtotime("-$i months"))]);
    $exp = (float)$stmtExp->fetchColumn();

    $monthsData[] = [
        'month' => $label,
        'revenue' => $rev,
        'expenses' => $exp
    ];
}

// Room Status Breakdown
$roomStatuses = [
    'Available' => (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 0")->fetchColumn(),
    'Occupied' => (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 1")->fetchColumn(),
    'Maintenance' => (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 2")->fetchColumn(),
    'Reserved' => (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 3")->fetchColumn()
];

// Payment methods distribution
$pmData = $pdo->query("SELECT PaymentMethod, SUM(Amount) as Total FROM payments WHERE Status = 1 GROUP BY PaymentMethod")->fetchAll();
$payLabels = [];
$payValues = [];
foreach ($pmData as $pm) {
    $payLabels[] = getPaymentMethodName($pm['PaymentMethod']);
    $payValues[] = (float)$pm['Total'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Visual Analytics & Charts</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Interactive revenue trajectory, room occupancy, and payment distributions</p>
    </div>
    <a href="<?= url('/reports/index.php') ?>" class="btn btn-outline">
        <i class="bi bi-file-earmark-text me-1"></i> Data Tables
    </a>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row g-4 mb-4">
    <!-- Monthly Cash Flow Bar Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-graph-up-arrow"></i> Revenue vs. Expenses (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyFlowChart" style="max-height: 320px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Room Status Doughnut -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart"></i> Room Status Breakdown</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="roomStatusChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Payment Channels Pie Chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-credit-card-2-front"></i> Collections by Payment Channel</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="paymentsChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Summary Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-calendar-check"></i> Monthly Performance Summary</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                            <th>Expenses</th>
                            <th>Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthsData as $md): 
                            $profit = $md['revenue'] - $md['expenses'];
                        ?>
                            <tr>
                                <td><strong><?= $md['month'] ?></strong></td>
                                <td class="text-success"><?= formatCurrency($md['revenue']) ?></td>
                                <td class="text-danger"><?= formatCurrency($md['expenses']) ?></td>
                                <td>
                                    <strong class="<?= $profit >= 0 ? 'text-primary' : 'text-danger' ?>">
                                        <?= formatCurrency($profit) ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#94a3b8' : '#64748b';

    // 1. Monthly Revenue & Expenses
    var flowCtx = document.getElementById('monthlyFlowChart').getContext('2d');
    var flowData = <?= json_encode($monthsData) ?>;
    new Chart(flowCtx, {
        type: 'bar',
        data: {
            labels: flowData.map(function(d) { return d.month; }),
            datasets: [
                {
                    label: 'Revenue (₱)',
                    data: flowData.map(function(d) { return d.revenue; }),
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6
                },
                {
                    label: 'Expenses (₱)',
                    data: flowData.map(function(d) { return d.expenses; }),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: { color: textColor, callback: function(val) { return '₱' + val.toLocaleString(); } }
                },
                x: { ticks: { color: textColor } }
            }
        }
    });

    // 2. Room Status Doughnut
    var roomCtx = document.getElementById('roomStatusChart').getContext('2d');
    var roomStatusMap = <?= json_encode($roomStatuses) ?>;
    new Chart(roomCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(roomStatusMap),
            datasets: [{
                data: Object.values(roomStatusMap),
                backgroundColor: ['#10b981', '#0284c7', '#f59e0b', '#64748b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor } }
            }
        }
    });

    // 3. Payment Methods Pie
    var payCtx = document.getElementById('paymentsChart').getContext('2d');
    var payLabels = <?= json_encode($payLabels) ?>;
    var payValues = <?= json_encode($payValues) ?>;
    new Chart(payCtx, {
        type: 'pie',
        data: {
            labels: payLabels.length ? payLabels : ['None'],
            datasets: [{
                data: payValues.length ? payValues : [1],
                backgroundColor: ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
