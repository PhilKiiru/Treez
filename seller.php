<?php 
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);

// ----------------- ACCEPT ORDER (SELLER) -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_order"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "
        UPDATE orders o
        JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
        JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
        SET o.ORDER_STATUS='PROCESSING'
        WHERE o.ORDER_ID=? AND t.SELLER_ID=? AND o.ORDER_STATUS='PENDING'
    ");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: seller.php");
    exit();
}

// ----------------- CANCEL ORDER (SELLER) -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_order"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "
        UPDATE orders o
        JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
        JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
        SET o.ORDER_STATUS='CANCELLED'
        WHERE o.ORDER_ID=? AND t.SELLER_ID=? AND o.ORDER_STATUS='PENDING'
    ");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: seller.php");
    exit();
}


// ----------------- ADD NEW SEEDLING -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_treeseedling"])) {
    $name = $_POST["name"];
    $scientific = $_POST["scientific_name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = trim($_POST["description"] ?? '');

    // Check for duplicate COMMON_NAME for this seller
    $dup_check = mysqli_prepare($db, "SELECT 1 FROM treespecies WHERE COMMON_NAME = ? AND SELLER_ID = ?");
    mysqli_stmt_bind_param($dup_check, "si", $name, $seller_id);
    mysqli_stmt_execute($dup_check);
    mysqli_stmt_store_result($dup_check);
    if (mysqli_stmt_num_rows($dup_check) > 0) {
        // Duplicate found, show error and stop
        echo '<div class="alert alert-danger">A seedling with this COMMON NAME already exists for you.</div>';
        mysqli_stmt_close($dup_check);
    } else {
        mysqli_stmt_close($dup_check);
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $stmt = mysqli_prepare($db, "INSERT INTO treespecies 
                (COMMON_NAME, SCIENTIFIC_NAME, PRICE, STOCK, DESCRIPTION, SELLER_ID, IMAGE) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdsiss", $name, $scientific, $price, $stock, $description, $seller_id, $target_file);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: seller.php");
        exit();
    }
}

// ----------------- EDIT SEEDLING -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_treeseedling"])) {
    $tree_id = intval($_POST["tree_id"]);
    $name = $_POST["name"];
    $scientific = $_POST["scientific_name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = trim($_POST["description"] ?? '');

    $stmt = mysqli_prepare($db, "UPDATE treespecies 
        SET COMMON_NAME=?, SCIENTIFIC_NAME=?, PRICE=?, STOCK=?, DESCRIPTION=? 
        WHERE TREESPECIES_ID=? AND SELLER_ID=?");
    mysqli_stmt_bind_param($stmt, "ssdisii", $name, $scientific, $price, $stock, $description, $tree_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
}

// ----------------- DELETE SEEDLING -----------------
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $stmt = mysqli_prepare($db, "DELETE FROM treespecies WHERE TREESPECIES_ID=? AND SELLER_ID=?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
}
// End PHP logic, now start HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Treez Seller</a>
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#" id="dashboardLink">Dashboard</a>
            </li>
        </ul>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container">
        <!-- Charts Section (hidden by default) -->
        <div id="seller-charts" style="display:none;">
            <h3 class="mb-3">Seedling Sales & Recommendations</h3>
            <div class="row mb-4">
                <div class="col-md-6">
                    <canvas id="salesPieChart"></canvas>
                </div>
                <div class="col-md-6">
                    <?php
                    // Get the seller's seedlings from the database
                    $seedlings = [];
                    $result = mysqli_query($db, "SELECT COMMON_NAME FROM treespecies WHERE SELLER_ID = $seller_id");
                    while ($row = mysqli_fetch_assoc($result)) {
                        $seedlings[] = $row['COMMON_NAME'];
                    }
                    ?>
                    <h5 class="mb-2">Find Recommended Tree Seedlings</h5>
                    <div class="input-group mb-2">
                        <input type="text" id="seedlingInput" class="form-control" placeholder="Enter tree seedling name...">
                        <button class="btn btn-primary" id="recommendBtn" type="button">Recommend</button>
                    </div>
                    <ul id="recommendList" class="list-group mb-2" style="display:none;"></ul>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <script>
                        // Load recommendations from CSV (PHP to JS)
                        <?php
                        $rec_map = [];
                        if (($handle = fopen("ml/recommendations.csv", "r")) !== FALSE) {
                            $header = fgetcsv($handle);
                            while (($data = fgetcsv($handle)) !== FALSE) {
                                $ant = $data[0];
                                $con = $data[1];
                                $conf = $data[5];
                                // Only use single-item antecedents (not sets)
                                if (preg_match_all("/'([^']+)'/", $ant, $a_matches) && count($a_matches[1]) === 1) {
                                    $a_name = $a_matches[1][0];
                                    $a_name_lc = strtolower($a_name);
                                    if (!isset($rec_map[$a_name_lc])) $rec_map[$a_name_lc] = [];
                                    if (preg_match_all("/'([^']+)'/", $con, $c_matches)) {
                                        foreach ($c_matches[1] as $c_name) {
                                            $rec_map[$a_name_lc][] = [
                                                'name' => $c_name,
                                                'confidence' => floatval($conf)
                                            ];
                                        }
                                    }
                                }
                            }
                            fclose($handle);
                        }
                        ?>
                        const recommendationsData = <?php echo json_encode($rec_map); ?>;
                // Show recommendations after seller inputs a tree seedling name
                function showRecommendations(input) {
                    const recommendList = document.getElementById('recommendList');
                    const key = input.trim().toLowerCase();
                    let recommendations = [];
                    if (key && recommendationsData[key]) {
                        recommendations = recommendationsData[key];
                    }
                    if (key) {
                        if (recommendations.length > 0) {
                            recommendList.innerHTML = recommendations.map(r => `<li class=\"list-group-item d-flex justify-content-between align-items-center\">${r.name}<span class=\"badge bg-primary rounded-pill\">${(r.confidence*100).toFixed(0)}%</span></li>`).join('');
                        } else {
                            recommendList.innerHTML = '<li class=\"list-group-item\">No specific recommendations found</li>';
                        }
                        recommendList.style.display = 'block';
                    } else {
                        recommendList.innerHTML = '';
                        recommendList.style.display = 'none';
                    }
                }
                document.getElementById('seedlingInput').addEventListener('input', function() {
                    showRecommendations(this.value);
                });
                document.getElementById('recommendBtn').addEventListener('click', function() {
                    const input = document.getElementById('seedlingInput').value;
                    showRecommendations(input);
                });
                document.getElementById('seedlingInput').addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        showRecommendations(this.value);
                    }
                });
        let chartsInitialized = false;
        let recBarChart = null;
        document.getElementById('dashboardLink').addEventListener('click', function(e) {
            e.preventDefault();
            const chartsDiv = document.getElementById('seller-charts');
            chartsDiv.style.display = chartsDiv.style.display === 'none' ? 'block' : 'none';
            if (!chartsInitialized && chartsDiv.style.display === 'block') {
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
                // Bar chart placeholder for recommendations (empty initially)
                const recBarCtx = document.getElementById('recommendBarChart').getContext('2d');
                recBarChart = new Chart(recBarCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Recommendation Confidence',
                            data: [],
                            backgroundColor: '#4e79a7'
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        title: { display: true, text: 'Recommended Seedlings' }
                    }
                });
                chartsInitialized = true;
            }
        });
        // When a seedling is selected, show recommended seedlings (placeholder logic)
        document.getElementById('recommendSelect').addEventListener('change', function() {
            const selected = this.value;
            const recommendList = document.getElementById('recommendList');
            let recommendations = [];
            if (selected) {
                // Example: static recommendations, replace with real data
                if (selected === 'Grevillea robusta') {
                    recommendations = [
                        { name: 'Croton megalocarpus', confidence: 0.85 },
                        { name: 'Markhamia lutea', confidence: 0.7 }
                    ];
                } else if (selected === 'Croton megalocarpus') {
                    recommendations = [
                        { name: 'Grevillea robusta', confidence: 0.8 },
                        { name: 'Eucalyptus saligna', confidence: 0.6 }
                    ];
                } else if (selected === 'Markhamia lutea') {
                    recommendations = [
                        { name: 'Grevillea robusta', confidence: 0.7 },
                        { name: 'Croton megalocarpus', confidence: 0.6 }
                    ];
                } else {
                    // Always show a default recommendation for any seedling
                    recommendations = [
                        { name: 'No specific recommendations found', confidence: 0 }
                    ];
                }
            }
            // Update list
            if (selected) {
                recommendList.innerHTML = recommendations.map(r => `<li class=\"list-group-item d-flex justify-content-between align-items-center\">${r.name}${r.confidence ? `<span class=\"badge bg-primary rounded-pill\">${(r.confidence*100).toFixed(0)}%</span>` : ''}</li>`).join('');
                recommendList.style.display = 'block';
            } else {
                recommendList.innerHTML = '';
                recommendList.style.display = 'none';
            }
        });
        </script>

    <!-- Add New Seedling -->
    <h3 class="mb-3">Add New Seedling</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mb-5">
        <div class="row g-3">
            <div class="col-md-3"><input type="text" name="name" placeholder="Common Name" class="form-control" required></div>
            <div class="col-md-3"><input type="text" name="scientific_name" placeholder="Scientific Name" class="form-control" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="price" placeholder="Price" class="form-control" required></div>
            <div class="col-md-2"><input type="number" name="stock" placeholder="Stock" class="form-control" required></div>
            <div class="col-md-12">
                <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-md-3"><input type="file" name="image" accept="image/*" class="form-control" required></div>
            <div class="col-md-12"><button type="submit" name="add_treeseedling" class="btn btn-success">Add</button></div>
        </div>
    </form>

    <!-- Seedlings -->
    <h3 class="mb-3">Your Tree Seedlings</h3>
    <form method="GET" class="mb-3">
      <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search your seedlings..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
      </div>
    </form>
    <?php 
    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = '%' . mysqli_real_escape_string($db, $search) . '%';
        $result = mysqli_query($db, "SELECT * FROM treespecies WHERE SELLER_ID=$seller_id AND (COMMON_NAME LIKE '$like' OR SCIENTIFIC_NAME LIKE '$like')");
    } else {
        $result = mysqli_query($db,"SELECT * FROM treespecies WHERE SELLER_ID=$seller_id");
    }
    if(mysqli_num_rows($result) > 0) {
        echo "<div class='row'>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "
            <div class='col-md-4'>
                <div class='card mb-4 shadow-sm'>
                    <img src='{$row['IMAGE']}' class='card-img-top' alt='Tree Image' style='height:200px; object-fit:cover;'>
                    <div class='card-body'>
                        <form method='POST'>
                            <input type='hidden' name='tree_id' value='{$row['TREESPECIES_ID']}'>
                            <input type='text' name='name' value='".htmlspecialchars($row['COMMON_NAME'])."' class='form-control mb-2'>
                            <input type='text' name='scientific_name' value='".htmlspecialchars($row['SCIENTIFIC_NAME'])."' class='form-control mb-2'>
                            <input type='number' step='0.01' name='price' value='{$row['PRICE']}' class='form-control mb-2'>
                            <input type='number' name='stock' value='{$row['STOCK']}' class='form-control mb-2'>
                            <textarea name='description' class='form-control mb-2' rows='3'>".htmlspecialchars($row['DESCRIPTION'])."</textarea>
                            <button type='submit' name='edit_treeseedling' class='btn btn-warning btn-sm'>Update</button>
                            <a href='seller.php?delete_id={$row['TREESPECIES_ID']}' onclick=\"return confirm('Delete this tree?');\" class='btn btn-danger btn-sm'>Delete</a>
                        </form>
                    </div>
                </div>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>You haven't added any seedlings yet.</div>";
    }
    ?>

    <!-- Orders for Your Seedlings -->
    <h3 class="mt-5 mb-3">Orders for Your Seedlings</h3>
    <?php
    // Filter logic
    $filter_status = $_GET['filter_status'] ?? '';
    $where_status = '';
    if ($filter_status === 'accepted') {
        $where_status = " AND o.ORDER_STATUS = 'PROCESSING' ";
    }
    $orders = mysqli_query($db, "
        SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, o.TOTAL_PRICE,
               u.USERNAME AS buyer,
               t.COMMON_NAME, t.SCIENTIFIC_NAME, t.DESCRIPTION, od.QUANTITY, od.PRICE
        FROM orders o
        JOIN users u ON o.BUYER_ID = u.USER_ID
        JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
        JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
        WHERE t.SELLER_ID = $seller_id $where_status
        ORDER BY o.ORDER_DATE DESC
    ");

    // Filter form
    echo '<form method="GET" class="mb-3">
        <div class="input-group" style="max-width:350px;">
            <label class="input-group-text" for="filter_status">Show</label>
            <select name="filter_status" id="filter_status" class="form-select" onchange="this.form.submit()">
                <option value="">All Orders</option>
                <option value="accepted"' . ($filter_status==='accepted'?' selected':'') . '>Accepted Orders</option>
            </select>
        </div>
    </form>';

    if (mysqli_num_rows($orders) > 0) {
        echo "<table class='table table-hover table-bordered'>
                <thead class='table-dark'>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Buyer</th>
                        <th>Tree</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead><tbody>";
        while ($row = mysqli_fetch_assoc($orders)) {
            $item_total = $row['PRICE'] * $row['QUANTITY'];
            // Status badge logic
            if ($row['ORDER_STATUS'] == 'PROCESSING') {
                $badge = 'info';
                $status_text = 'Accepted';
            } elseif ($row['ORDER_STATUS'] == 'PENDING') {
                $badge = 'warning text-dark';
                $status_text = 'Pending';
            } elseif ($row['ORDER_STATUS'] == 'DELIVERED') {
                $badge = 'success';
                $status_text = 'Delivered';
            } elseif ($row['ORDER_STATUS'] == 'CANCELLED') {
                $badge = 'danger';
                $status_text = 'Cancelled';
            } else {
                $badge = 'secondary';
                $status_text = htmlspecialchars($row['ORDER_STATUS']);
            }
            echo "<tr>
                <td>{$row['ORDER_ID']}</td>
                <td>{$row['ORDER_DATE']}</td>
                <td><span class='badge bg-$badge'>$status_text</span></td>
                <td>{$row['buyer']}</td>
                <td>{$row['COMMON_NAME']}<br><em>{$row['SCIENTIFIC_NAME']}</em></td>
                <td>{$row['QUANTITY']}</td>
                <td>{$row['PRICE']}</td>
                <td><strong>$item_total</strong></td>
                <td>".nl2br(htmlspecialchars($row['DESCRIPTION']))."</td>
                <td>";
            // Accept order if pending
            if ($row['ORDER_STATUS'] == "PENDING") {
                echo "<form method='POST' class='d-inline ms-1'>
                          <input type='hidden' name='order_id' value='{$row['ORDER_ID']}'>
                          <button type='submit' name='accept_order' class='btn btn-sm btn-primary'>Accept Order</button>
                      </form> ";
                echo "<form method='POST' class='d-inline ms-1'>
                          <input type='hidden' name='order_id' value='{$row['ORDER_ID']}'>
                          <button type='submit' name='cancel_order' class='btn btn-sm btn-danger' onclick=\"return confirm('Cancel this order?');\">Cancel Order</button>
                      </form> ";
            }
            // Mark as delivered if processing
            if ($row['ORDER_STATUS'] == "PROCESSING") {
                echo "<form method='POST' class='d-inline ms-1'>
                          <input type='hidden' name='order_id' value='{$row['ORDER_ID']}'>
                          <button type='submit' name='mark_delivered' class='btn btn-sm btn-success'>Mark as Delivered</button>
                      </form> ";
            }
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>No orders for your seedlings yet.</div>";
    }
    ?>

</div>
</body>
</html>
