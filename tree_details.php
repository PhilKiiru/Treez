<?php
// tree_details.php

require_once "db.php";
require_once "recommend.php";

// Get tree ID from query string
$tree_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tree_id <= 0) {
    echo "<div style='color:red;text-align:center;'>Invalid tree ID.</div>";
    exit();
}

$stmt = mysqli_prepare($db, "SELECT * FROM treespecies WHERE TREESPECIES_ID = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $tree_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$tree = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$tree) {
    echo "<div style='color:red;text-align:center;'>Tree not found.</div>";
    exit();
}


function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Get recommendations for this tree
$recs = get_recommendations($tree['COMMON_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($tree['COMMON_NAME']); ?> - Details</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #e0ffe7 0%, #f7f7f7 100%);
        }
        .main-card {
            box-shadow: 0 4px 24px #0002;
            border-radius: 18px;
            overflow: hidden;
            border: none;
        }
        .main-card .img-fluid {
            border-radius: 18px 0 0 18px;
            object-fit: cover;
            height: 100%;
            min-height: 320px;
        }
        .card-body h3 {
            color: #0f6e25;
            font-weight: 700;
        }
        .card-body h5 {
            color: #3a3a3a;
        }
        .card-body p {
            font-size: 1.08em;
        }
        .btn-success {
            background: #0f6e25;
            border: none;
        }
        .btn-success:hover {
            background: #13992f;
        }
        .recs-wrap {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .rec-card {
            background: #fff;
            border-radius: 12px;
            padding: 12px 10px 10px 10px;
            width: 160px;
            text-align: center;
            box-shadow: 0 2px 8px #0001;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .rec-card:hover {
            transform: translateY(-4px) scale(1.04);
            box-shadow: 0 6px 18px #0002;
        }
        .rec-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .rec-card strong {
            color: #0f6e25;
            font-size: 1.08em;
        }
        .rec-card .text-success {
            color: #13992f !important;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <a href="buyer.php" class="btn btn-secondary mb-3">&larr; Back to Trees</a>
        <div class="card main-card mb-3" style="max-width: 700px; margin:auto;">
            <div class="row g-0">
                <div class="col-md-5 d-flex align-items-center justify-content-center" style="background:#f6f6f6;">
                    <img src="<?= e($tree['IMAGE'] ?: 'images/no-image.png'); ?>" class="img-fluid rounded-start" alt="<?= e($tree['COMMON_NAME']); ?>" style="max-width: 95%; max-height: 320px; object-fit: contain; border-radius: 18px; box-shadow: 0 2px 12px #0001;">
                </div>
                <div class="col-md-7">
                    <div class="card-body">
                        <h3 class="card-title"><?= e($tree['COMMON_NAME']); ?></h3>
                        <h5 class="card-subtitle mb-2 text-muted"><em><?= e($tree['SCIENTIFIC_NAME']); ?></em></h5>
                        <p class="card-text"><strong>Price:</strong> KES <?= number_format($tree['PRICE'],2); ?></p>
                        <p class="card-text"><strong>Stock:</strong> <?= intval($tree['STOCK']); ?></p>
                        <p class="card-text"><strong>Description:</strong><br><?= nl2br(e($tree['DESCRIPTION'])); ?></p>
                        <form method="POST" action="buyer.php">
                            <input type="hidden" name="tree_id" value="<?= intval($tree['TREESPECIES_ID']); ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= intval($tree['STOCK']); ?>" class="form-control mb-2" style="width:120px;display:inline-block;">
                            <button name="add_to_cart" class="btn btn-success">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($recs)): ?>
    <div class="mt-4">
        <h5>Recommended for you</h5>
        <div class="recs-wrap">
        <?php foreach ($recs as $rec): ?>
            <a href="tree_details.php?id=<?= intval($rec['id']); ?>" style="text-decoration:none;color:inherit;">
                <div class="rec-card">
                    <?php $img = $rec['image'] ?: 'images/no-image.png'; ?>
                    <img src="<?= e($img); ?>" alt="<?= e($rec['name']); ?>">
                    <strong><?= e($rec['name']); ?></strong><br>
                    <span class="text-success">KES <?= number_format($rec['price'],2); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
