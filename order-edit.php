<?php
require "auths.php";
if (!can('manage_orders')) die("access refuse");

$id = (int)$_GET['id'];
if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $status=$_POST['status'];
    $stmt=$connection->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si",$status,$id);
    $stmt->execute();
    header("Location: orders.php");
    exit();
}
?>
<<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit-Orders</title>
   <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
   <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            h2 {
                margin-bottom: 20px;
            }
            ul {
                list-style-type: none;
                padding: 0;
            }
            li {
                margin-bottom: 10px;
            }
            a {
                text-decoration: none;
                color: #007bff;
            }
            a:hover {
                text-decoration: underline;
            }
            form {
                 margin-top: 20px;
            }
            button {
                 background-color: #28a745;
                 color: white;
                 padding: 10px 15px;
                 border: none;
                 border-radius: 5px;
                 cursor: pointer;
            }

   </style>
</head>
<body>
   <form method="post">
<select name="status">
<option>pending</option>
<option>paid</option>
<option>shipped</option>
<option>completed</option>
<option>cancelled</option>
</select>
<button>Enregistrer</button>
</form>
</body>
</html>

