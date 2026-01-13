<?php
session_start();

$name = "";
$email = "";
$errorMessage = "";
$successMessage = "";

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF check
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errorMessage = "Invalid request.";
    } else {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($name === "" || $email === "" || $password === "") {
            $errorMessage = "All fields are required.";
        } else {

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                $db = new mysqli("localhost", "root", "", "commerce");
                $db->set_charset("utf8mb4");

                // Email unique
                $check = $db->prepare("SELECT id FROM users WHERE email = ?");
                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $errorMessage = "Email already exists.";
                } else {

                    // 🔒 ROLE CONTROL
                    $role = "user";
                    if (
                        isset($_SESSION["role"]) &&
                        $_SESSION["role"] === "admin" &&
                        isset($_POST["role"]) &&
                        in_array($_POST["role"], ["user", "admin"], true)
                    ) {
                        $role = $_POST["role"];
                    }

                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    $stmt = $db->prepare(
                        "INSERT INTO users (name, email, password, role)
                         VALUES (?, ?, ?, ?)"
                    );

                    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                    $stmt->execute();

                    $successMessage = "Account created successfully.";
                    $name = $email = "";
                }

            } catch (Exception $e) {
                $errorMessage = "Server error.";
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        padding: 20px;
        background-color: #f4f4f4;
    }
    h1 {
        color: #333;
        margin: auto;
        display: flex;
    }
    form {
         max-width: 300px;
                margin: auto;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #fff;
    }
    label {
        display: inline-block;
        width: 100px;
        margin-bottom: 10px;
    }
    input[type="text"], input[type="email"], input[type="password"], select {
        width: 200px;
        padding: 5px;
        margin-bottom: 10px;
    }
    input[type="submit"] {
        padding: 10px 20px;
        background-color: #28a745;
        color: white;
        border: none;
        cursor: pointer;
    }
    input[type="submit"]:hover {
        background-color: #218838;
    }
    </style>
<body>

<h1>Sign Up</h1>

<?php if (!empty($errorMessage)) echo "<p style='color:red'>$errorMessage</p>"; ?>
<?php if (!empty($successMessage)) echo "<p style='color:green'>$successMessage</p>"; ?>

<form method="post">

    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <label>Username:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label>Password:</label>
    <input type="password" name="password" required>

   
        <label>Role:</label>
        <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    <br>

    <input type="submit" value="Sign Up">
     <p class="text-center text-sm mt-4">
            If You have already an account?
            <a href="/COMMERCE/login.php" class="text-blue-600">Sign In</a>

        </p>

</form>


<script>
      document.getElementById('goBackBtn').onclick = () => window.history.back();
   </script>
</body>
</html>
