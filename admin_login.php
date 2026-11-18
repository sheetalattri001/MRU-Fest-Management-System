<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, username, password FROM admins WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: admin_dashboard.php");
            exit;
        }
    }

    $message = "Invalid username or password.";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - MRU Fest</title>
</head>
<body>
    <h2>MRU Fest - Admin Login</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <label>Admin Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Admin Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login as Admin</button>
    </form>
</body>
</html>