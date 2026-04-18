<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

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
    $values[] = $row['total_sold'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/mainstyle.css"
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="dashboard-chart-card">

            <h2 class="dashboard-title mt-0 mb-20">📊 Dashboard — Top Selling Products</h2>

            <?php if (empty($labels)): ?>
                <div class="empty-chart-state">
                    <p class="empty-chart-text-1">No sales recorded yet.</p>
                    <p class="empty-chart-text-2">Try placing an order as a member first!</p>
                </div>
            <?php else: ?>
                <canvas id="sellingChart" class="chart-canvas"></canvas>
            <?php endif; ?>

        </div>
    </main>

</div><!-- /.admin-layout -->

<script>
    <?php if (!empty($labels)): ?>
    const ctx = document.getElementById('sellingChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Total Sold',
                data: <?= json_encode($values) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor:     'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>
</script>
</body>
</html>