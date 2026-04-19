<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

// Force Admin Auth
auth('Admin');

// Fetch Top 5 Selling Products
$stmt = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_sold 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY oi.product_id 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$chart_data = $stmt->fetchAll();

$labels = [];
$values = [];
foreach ($chart_data as $row) {
    $labels[] = $row['name'];
    $values[] = (int)$row['total_sold'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="dashboard-chart-card bg-white p-30 radius-12 shadow-sm">

            <h2 class="dashboard-title mt-0 mb-20">📊 Dashboard — Top Selling Products</h2>

            <?php if (empty($labels)): ?>
                <div class="text-center p-50">
                    <p class="empty-chart-text-1 text-muted font-1-2">No sales recorded yet.</p>
                    <p class="empty-chart-text-2">Try placing an order as a member first!</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="sellingChart"></canvas>
                </div>
            <?php endif; ?>

        </div>
    </main>

</div>

<script>
    // Wait for the page to load before drawing the chart
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($labels)): ?>
        const ctx = document.getElementById('sellingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Total Units Sold',
                    data: <?= json_encode($values) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 } 
                    } 
                } 
            }
        });
        <?php endif; ?>
    });
</script>
</body>
</html>