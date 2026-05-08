<?php

require_once '../../models/Database.php';
require_once '../../models/order.php';

$db = new Database();
$conn = $db->getConnection();

$sql = "
SELECT *
FROM orders
WHERE batch_id IS NULL
AND LOWER(status) = 'confirmed'
ORDER BY user_id
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupedOrders = [];

foreach($orders as $order){
    $groupedOrders[$order['user_id']][] = $order;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Delivery Batches</title>

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        body{
            font-family: Inter;
            background:#F8FAF8;
            padding:40px;
        }

        .container{
            background:white;
            padding:30px;
            border-radius:12px;
        }

        .user-box{
            border:1px solid #ddd;
            border-radius:10px;
            padding:20px;
            margin-bottom:30px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        th{
            background:#589A64;
            color:white;
            padding:12px;
            text-align:left;
        }

        td{
            padding:12px;
            border-bottom:1px solid #eee;
        }

        .btn{
            background:#589A64;
            color:white;
            padding:10px 20px;
            border-radius:8px;
            text-decoration:none;
            display:inline-block;
            margin-top:15px;
        }

    </style>

</head>

<body>

<div class="container">

<h2>
    📦 Generate Delivery Batches
</h2>

<?php foreach($groupedOrders as $user_id => $userOrders): ?>

<div class="user-box">

    <h3>
        User ID:
        <?= $user_id ?>
    </h3>

    <p>
        Total Orders:
        <?= count($userOrders) ?>
    </p>

    <table>

        <tr>
            <th>Order ID</th>
            <th>Status</th>
            <th>Order Date</th>
        </tr>

        <?php foreach($userOrders as $order): ?>

        <tr>

            <td>
                <?= $order['id'] ?>
            </td>

            <td>
                <?= $order['status'] ?>
            </td>

            <td>
                <?= $order['order_date'] ?>
            </td>

        </tr>

        <?php endforeach; ?>

    </table>

    <?php if(count($userOrders) > 1): ?>

        <a class="btn"
        href="../../controllers/CreateBatchController.php?user_id=<?= $user_id ?>">

            <i class="fa-solid fa-gears"></i>
            Create Batch

        </a>

    <?php else: ?>

        <p style="color:red;margin-top:15px;">
            User needs more than one confirmed order
        </p>

    <?php endif; ?>

</div>

<?php endforeach; ?>

</div>

</body>
</html>