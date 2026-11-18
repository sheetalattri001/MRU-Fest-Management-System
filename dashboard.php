<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["student_id"]) || ($_SESSION["role"] ?? "") !== "student") {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$student_id = (int) $_SESSION["student_id"];

$name = htmlspecialchars(
    $_SESSION["student_name"] ?? "Student",
    ENT_QUOTES,
    "UTF-8"
);

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM registrations WHERE student_id = ?"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$totalRegistrations = (int) (
    $stmt->get_result()->fetch_assoc()["total"] ?? 0
);
$stmt->close();

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM events WHERE event_date >= CURDATE()"
);
$upcomingEvents = (int) (
    $result->fetch_assoc()["total"] ?? 0
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal | MRU Fest</title>

<style>
* {
    box-sizing: border-box;
}

:root {
    --navy: #191943;
    --gold: #efbb61;
    --bg: #f5f6fb;
    --text: #292a4d;
    --muted: #85869b;
    --white: #fff;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
}

a {
    text-decoration: none;
}

.layout {
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 235px;
    flex-shrink: 0;
    background: var(--navy);
    color: white;
    padding: 28px 17px;
    display: flex;
    flex-direction: column;
}

.brand {
    padding: 0 12px 30px;
    border-bottom: 1px solid #ffffff18;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 1px;
    color: var(--gold);
}

.brand span {
    color: white;
}

.portal-label {
    padding: 26px 12px 12px;
    font-size: 10px;
    color: #a9a9ce;
    letter-spacing: 2px;
    font-weight: bold;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 12px;
    color: #d9d9ee;
    border-radius: 9px;
    font-size: 14px;
    transition: 0.2s;
}

.menu a:hover,
.menu a.active {
    background: #ffffff16;
    color: var(--gold);
}

.menu-icon {
    width: 23px;
    text-align: center;
    font-size: 17px;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 25px;
}

.student-mini {
    background: #ffffff0d;
    border: 1px solid #ffffff15;
    border-radius: 12px;
    padding: 14px 12px;
    overflow-wrap: anywhere;
}

.student-mini small {
    display: block;
    color: #aaaad0;
    margin-bottom: 6px;
}

.student-mini strong {
    font-size: 13px;
    color: white;
}

.content {
    flex: 1;
    min-width: 0;
}

.topbar {
    min-height: 76px;
    padding: 18px 35px;
    background: white;
    border-bottom: 1px solid #e9eaf2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.topbar h2 {
    font-size: 18px;
    margin: 0;
}

.topbar p {
    color: var(--muted);
    font-size: 12px;
    margin: 5px 0 0;
}

.topbar-link {
    border: 1px solid #e7e7f0;
    color: var(--navy);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
}

.topbar-link:hover {
    background: #f5f5fc;
}

main {
    max-width: 1250px;
    padding: 30px 35px;
    margin: auto;
}

.welcome {
    position: relative;
    overflow: hidden;
    padding: 32px;
    color: white;
    border-radius: 16px;
    background: linear-gradient(115deg, #202052, #41418a);
    box-shadow: 0 9px 24px #20205218;
}

.welcome::after {
    content: "✦";
    position: absolute;
    right: 35px;
    top: 8px;
    font-size: 115px;
    color: #ffffff0d;
    pointer-events: none;
}

.welcome small {
    color: var(--gold);
    font-size: 11px;
    letter-spacing: 2px;
    font-weight: bold;
    text-transform: uppercase;
}

.welcome h1 {
    position: relative;
    z-index: 1;
    font-size: clamp(25px, 3vw, 34px);
    margin: 13px 0 9px;
    overflow-wrap: anywhere;
}

.welcome h1 span {
    color: var(--gold);
}

.welcome p {
    position: relative;
    z-index: 1;
    max-width: 600px;
    color: #e1e1f4;
    font-size: 14px;
    line-height: 1.8;
    margin: 0;
}

.section-title {
    margin: 30px 0 16px;
}

.section-title h2 {
    margin: 0 0 6px;
    font-size: 20px;
}

.section-title p {
    margin: 0;
    font-size: 13px;
    color: var(--muted);
}

.stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.stat {
    background: white;
    border: 1px solid #e9eaf2;
    border-radius: 13px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 3px 12px #19194305;
}

.stat-icon {
    height: 50px;
    width: 50px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 23px;
    background: #fff2dc;
    flex-shrink: 0;
}

.stat:nth-child(2) .stat-icon {
    background: #e9eaff;
}

.stat h3 {
    margin: 0 0 5px;
    font-size: 27px;
    color: var(--navy);
}

.stat p {
    margin: 0;
    font-size: 13px;
    color: var(--muted);
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.card {
    display: block;
    background: white;
    padding: 23px;
    border-radius: 13px;
    border: 1px solid #e8e9f2;
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-4px);
    border-color: #d3d4ec;
    box-shadow: 0 10px 25px #1919430d;
}

.card-icon {
    width: 47px;
    height: 47px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 23px;
    background: #fff2dc;
    margin-bottom: 17px;
}

.card:nth-child(2) .card-icon {
    background: #e9eaff;
}

.card:nth-child(3) .card-icon {
    background: #e2f6ed;
}

.card:nth-child(4) .card-icon {
    background: #e8f5ff;
}

.card h3 {
    font-size: 16px;
    margin: 0 0 9px;
}

.card p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
    margin: 0 0 17px;
}

.card .link-text {
    font-size: 13px;
    font-weight: bold;
    color: #393979;
}

.card:hover .link-text {
    color: #a36a10;
}

footer {
    text-align: center;
    color: var(--muted);
    font-size: 12px;
    padding: 25px 15px;
    border-top: 1px solid #e8e9f2;
    margin-top: 20px;
}

@media (max-width: 950px) {
    .sidebar {
        width: 195px;
    }

    main {
        padding: 25px 22px;
    }

    .topbar {
        padding: 17px 22px;
    }

    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        padding: 18px;
    }

    .brand {
        padding: 0 4px 16px;
    }

    .portal-label {
        padding: 17px 4px 9px;
    }

    .menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .menu a {
        padding: 11px 8px;
        font-size: 12px;
    }

    .sidebar-bottom {
        display: none;
    }

    .topbar {
        min-height: 65px;
        padding: 15px 18px;
    }

    main {
        padding: 22px 15px;
    }

    .welcome {
        padding: 26px 22px;
    }

    .stats,
    .cards {
        grid-template-columns: 1fr;
    }

    .stat {
        padding: 18px;
    }

    .card {
        padding: 21px;
    }
}
</style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <a class="brand" href="dashboard.php">MRU <span>FEST</span></a>

        <div class="portal-label">STUDENT MENU</div>

        <nav class="menu">
            <a href="dashboard.php" class="active">
                <span class="menu-icon">⌂</span> Dashboard
            </a>

            <a href="index.php">
                <span class="menu-icon">⌘</span> Home
            </a>

            <a href="events.php">
                <span class="menu-icon">✦</span> Explore Events
            </a>

            <a href="my_registrations.php">
                <span class="menu-icon">▤</span> My Registrations
            </a>

            <a href="volunteer_apply.php">
                <span class="menu-icon">🤝</span> Volunteer Program
            </a>

            <a href="logout.php">
                <span class="menu-icon">↪</span> Logout
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="student-mini">
                <small>Signed in as</small>
                <strong><?php echo $name; ?></strong>
            </div>
        </div>
    </aside>

    <div class="content">

        <div class="topbar">
            <div>
                <h2>Student Dashboard</h2>
                <p>Manage your MRU Fest experience</p>
            </div>

            <a class="topbar-link" href="index.php">Visit Homepage ↗</a>
        </div>

        <main>

            <section class="welcome">
                <small>MRU FEST 2026 · STUDENT PORTAL</small>

                <h1>Welcome back, <span><?php echo $name; ?></span>!</h1>

                <p>
                    Discover new experiences, take part in campus
                    competitions and make your fest memorable.
                    Everything you need is available in your student portal.
                </p>
            </section>

            <section>
                <div class="section-title">
                    <h2>Your Overview</h2>
                    <p>A snapshot of your fest activity.</p>
                </div>

                <div class="stats">
                    <div class="stat">
                        <div class="stat-icon">🎟️</div>
                        <div>
                            <h3><?php echo $totalRegistrations; ?></h3>
                            <p>Total Registrations</p>
                        </div>
                    </div>

                    <div class="stat">
                        <div class="stat-icon">🎉</div>
                        <div>
                            <h3><?php echo $upcomingEvents; ?></h3>
                            <p>Upcoming Events</p>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="section-title">
                    <h2>Quick Access</h2>
                    <p>Choose where you want to go next.</p>
                </div>

                <div class="cards">

                    <a class="card" href="events.php">
                        <div class="card-icon">🎭</div>
                        <h3>Explore Events</h3>
                        <p>
                            Find singing, dance, technical and art
                            competitions and discover your interests.
                        </p>
                        <div class="link-text">Browse events →</div>
                    </a>

                    <a class="card" href="my_registrations.php">
                        <div class="card-icon">📋</div>
                        <h3>My Registrations</h3>
                        <p>
                            Check your registered events and access
                            your registration confirmation codes.
                        </p>
                        <div class="link-text">View registrations →</div>
                    </a>

                    <a class="card" href="logout.php">
                        <div class="card-icon">👤</div>
                        <h3>My Account</h3>
                        <p>
                            You are currently signed in.
                            Log out securely when you finish.
                        </p>
                        <div class="link-text">Sign out →</div>
                    </a>

                    <a class="card" href="volunteer_apply.php">
                        <div class="card-icon">🤝</div>
                        <h3>Volunteer Program</h3>
                        <p>
                            Apply to become an MRU Fest volunteer.
                            Your application will be reviewed by the admin.
                        </p>
                        <div class="link-text">Apply now →</div>
                    </a>

                </div>
            </section>

        </main>

        <footer>
            © 2026 MRU Fest Management System · Celebrating campus life
        </footer>

    </div>
</div>
</body>
</html>