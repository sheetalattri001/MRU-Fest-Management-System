<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["student_id"]) ||
    ($_SESSION["role"] ?? "") !== "volunteer"
) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$name = htmlspecialchars(
    $_SESSION["student_name"] ?? "Volunteer",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Volunteer Dashboard | MRU Fest</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fb;
    color: #292a4d;
}
header {
    background: #191943;
    color: white;
    padding: 22px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header h2 { margin: 0; color: #efbb61; }
header a {
    color: white;
    text-decoration: none;
}
main {
    max-width: 950px;
    margin: 45px auto;
    padding: 0 20px;
}
.welcome {
    background: linear-gradient(115deg, #202052, #41418a);
    color: white;
    padding: 35px;
    border-radius: 15px;
}
.welcome h1 { margin-top: 10px; }
.welcome span { color: #efbb61; }
.card {
    background: white;
    margin-top: 25px;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px #0000000b;
}
.card h2 { color: #191943; }
.card p { line-height: 1.7; color: #666; }
.links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}
.links a {
    background: #191943;
    color: white;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 7px;
}
.links a:hover { background: #41418a; }
</style>
</head>
<body>

<header>
    <h2>MRU FEST</h2>
    <a href="logout.php">Logout ↗</a>
</header>

<main>
    <section class="welcome">
        <p>MRU FEST 2026 · VOLUNTEER PORTAL</p>
        <h1>Welcome, <span><?php echo $name; ?></span>!</h1>
        <p>Thank you for being a part of MRU Fest.
        Your contribution helps make our events successful.</p>
    </section>

    <section class="card">
        <h2>Volunteer Dashboard</h2>
        <p>You are logged in as an approved volunteer.</p>
        <p>Your responsibilities will be displayed here once assigned by the admin.</p>

        <div class="links">
            <a href="index.php">Visit Homepage</a>
            <a href="events.php">Explore Events</a>
        </div>
    </section>
</main>

</body>
</html>