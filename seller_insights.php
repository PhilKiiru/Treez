<?php
// Load recommendations from CSV for PHP display
$rec_map = [];
if (($handle = fopen("ml/recommendations.csv", "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        $ant = trim($data[0]);
        $con = trim($data[1]);
        // Only use single-item antecedents (no comma)
        if ($ant !== '' && strpos($ant, ',') === false) {
            $a_name_lc = strtolower($ant);
            if (!isset($rec_map[$a_name_lc])) $rec_map[$a_name_lc] = [];
            // Consequent may be a set, but we only use the first if comma-separated
            $consequents = array_map('trim', explode(',', $con));
            foreach ($consequents as $c_name) {
                if ($c_name !== '') {
                    $rec_map[$a_name_lc][] = $c_name;
                }
            }
        }
    }
    fclose($handle);
}
?>
<?php
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
            <!-- Removed Seller Insights brand from navbar -->
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link fw-bold text-white" href="seller.php">&larr; Back to Seller Dashboard</a>
            </li>
        </ul>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>
<div class="container">
    <div class="bg-white p-4 rounded shadow-sm mb-4">
        <h3 class="text-center text-white bg-primary py-2 rounded">Seller Insights</h3>
        <div class="mb-4">
            <h5>Tree Seedling Recommendation Search</h5>
            <input type="text" id="recSearchInput" class="form-control" placeholder="Search tree seedling...">
            <div id="recResult"></div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-6">
            <canvas id="salesPieChart"></canvas>
        </div>
        <div class="col-md-6">
<?php
// Pie chart data: top 5 most sold seedlings for this seller
$pie = mysqli_query($db, "SELECT t.COMMON_NAME, SUM(od.QUANTITY) as total_sold FROM orderdetails od JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID WHERE t.SELLER_ID = $seller_id GROUP BY t.COMMON_NAME ORDER BY total_sold DESC LIMIT 5");
$pie_labels = [];
$pie_data = [];
while($r = mysqli_fetch_assoc($pie)) {
    $pie_labels[] = $r['COMMON_NAME'];
    $pie_data[] = (int)$r['total_sold'];
}
?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Recommendations data from PHP
        const recMap = <?php echo json_encode($rec_map); ?>;
        var recResult = document.getElementById('recResult');
        document.getElementById('recSearchInput').addEventListener('input', function() {
            const filter = this.value.trim().toLowerCase();
            recResult.innerHTML = '';
            if (!filter) return;
            if (recMap[filter]) {
                // Remove duplicates
                const uniqueRecs = Array.from(new Set(recMap[filter]));
                recResult.innerHTML = `<div class='card mt-2'><div class='card-body'><strong>${filter.charAt(0).toUpperCase() + filter.slice(1)}</strong><br>Recommended Seedlings: <span class='text-success'>${uniqueRecs.map(r => r.charAt(0).toUpperCase() + r.slice(1)).join(', ')}</span></div></div>`;
            } else {
                // Try partial/fuzzy match
                const foundKey = Object.keys(recMap).find(k => k.replace(/\s+/g, '') === filter.replace(/\s+/g, ''));
                if (foundKey) {
                    const uniqueRecs = Array.from(new Set(recMap[foundKey]));
                    recResult.innerHTML = `<div class='card mt-2'><div class='card-body'><strong>${foundKey.charAt(0).toUpperCase() + foundKey.slice(1)}</strong><br>Recommended Seedlings: <span class='text-success'>${uniqueRecs.map(r => r.charAt(0).toUpperCase() + r.slice(1)).join(', ')}</span></div></div>`;
                } else {
                    recResult.innerHTML = `<div class='alert alert-warning mt-2'>No recommendations found for <strong>${filter}</strong>.</div>`;
                }
            }
        });
        </script>
    <script>
    // Pie chart for most selling seedlings
    const pieCtx = document.getElementById('salesPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($pie_labels) ?>,
            datasets: [{
                data: <?= json_encode($pie_data) ?>,
                backgroundColor: ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f'],
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            title: { display: true, text: 'Top 5 Most Selling Seedlings' }
        }
    });
    </script>
</div>
</body>
</html>
