<?php
require "auths.php";
if (!can('manage_orders')) die("Accès refusé");

$id = (int)$_GET['id'];

$stmt = $connection->prepare("
    SELECT product_name, price, quantity
    FROM order_items
    WHERE order_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result();
?>
<<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Details of Command</title>
</head>
<body>
   <h3>📦 Details of command #<?= $id ?></h3>

<table class="table table-bordered">
<tr>
    <th>Product</th>
    <th>Prix</th>
    <th>Qté</th>
    <th>Total</th>
</tr>

<?php while ($i = $items->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($i['product_name']) ?></td>
    <td><?= $i['price'] ?> €</td>
    <td><?= $i['quantity'] ?></td>
    <td><?= $i['price'] * $i['quantity'] ?> €</td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>