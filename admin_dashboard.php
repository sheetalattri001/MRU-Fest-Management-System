<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["admin_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: admin_login.php");
    exit;
}

require_once "db.php";

$adminName = $_SESSION["admin_username"] ?? "Admin";

// Get live dashboard statistics
$totalEvents = 0;
$totalStudents = 0;
$totalRegistrations = 0;
$pendingVolunteers = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $totalEvents = (int) $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result) {
    $totalStudents = (int) $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) {
    $totalRegistrations = (int) $result->fetch_assoc()["total"];
}

// Count pending volunteer applications
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM volunteer_applications
     WHERE status = 'pending'"
);

if ($result) {
    $pendingVolunteers = (int) $result->fetch_assoc()["total"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MRU Fest</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --navy: #172554;
            --navy-light: #1e3a5f;
            --gold: #f4bd63;
            --background: #f4f6fb;
            --text: #1f2937;
            --muted: #64748b;
            --white: #ffffff;
            --border: #e5eaf2;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 245px;
            flex-shrink: 0;
            background: var(--navy);
            color: white;
            padding: 28px 16px;
        }

        .brand {
            padding: 0 12px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        .brand h2 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .brand p {
            margin: 0;
            color: #cbd5e1;
            font-size: 12px;
        }

        .nav-label {
            margin: 28px 12px 12px;
            color: #aebbd1;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sidebar nav a {
            display: block;
            padding: 13px 12px;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(244,189,99,0.15);
            color: var(--gold);
        }

        .sidebar nav a.logout {
            margin-top: 18px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .main-area {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            min-height: 76px;
            background: white;
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid var(--border);
        }

        .topbar h2 {
            margin: 0;
            font-size: 21px;
            color: var(--navy);
        }

        .topbar a {
            color: var(--navy);
            font-size: 14px;
            font-weight: bold;
        }

        .topbar a:hover {
            color: #b7791f;
        }

        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }

        .welcome {
            background: linear-gradient(120deg, #172554, #254675);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        .welcome p {
            margin: 0;
            max-width: 650px;
            color: #e2e8f0;
            line-height: 1.7;
            font-size: 14px;
        }

        .welcome .tag {
            display: inline-block;
            margin-top: 18px;
            padding: 7px 12px;
            border-radius: 20px;
            background: rgba(244,189,99,0.16);
            color: var(--gold);
            font-size: 12px;
            font-weight: bold;
        }

        .section-title {
            margin: 0 0 18px;
            color: var(--navy);
            font-size: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 34px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 23px;
            box-shadow: 0 4px 15px rgba(15,23,42,0.035);
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 13px;
        }

        .stat-number {
            color: var(--navy);
            font-size: 32px;
            font-weight: bold;
        }

        .stat-note {
            margin-top: 9px;
            color: #94a3b8;
            font-size: 12px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(15,23,42,0.035);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(15,23,42,0.09);
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 11px;
            background: #edf2ff;
            color: var(--navy);
            font-size: 22px;
            margin-bottom: 18px;
        }

        .card h3 {
            margin: 0 0 10px;
            color: var(--navy);
            font-size: 18px;
        }

        .card p {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
            flex: 1;
        }

        .btn {
            display: inline-block;
            background: var(--navy);
            color: white;
            padding: 11px 15px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn:hover {
            background: var(--navy-light);
        }

        .btn.gold {
            background: #f4bd63;
            color: var(--navy);
        }

        .btn.gold:hover {
            background: #e9aa3e;
        }

        .btn-count {
            display: inline-block;
            min-width: 23px;
            margin-left: 5px;
            padding: 3px 6px;
            border-radius: 12px;
            background: #fff;
            color: #172554;
            text-align: center;
            font-size: 11px;
        }

        footer {
            padding: 22px;
            text-align: center;
            color: var(--muted);
            border-top: 1px solid var(--border);
            font-size: 12px;
        }

        @media (max-width: 1050px) {
            .stats,
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 18px;
            }

            .brand {
                padding-bottom: 16px;
            }

            .nav-label {
                margin-top: 16px;
            }

            .sidebar nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sidebar nav a.logout {
                margin-top: 0;
            }

            .topbar {
                padding: 17px 20px;
            }

            .topbar h2 {
                font-size: 18px;
            }

            main {
                padding: 22px 16px;
            }

            .welcome {
                padding: 23px;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .stats,
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <h2>MRU Fest</h2>
            <p>Administration Portal</p>
        </div>

        <div class="nav-label">Main Menu</div>

        <nav>
            <a class="active" href="admin_dashboard.php">Dashboard</a>
            <a href="index.php">Home</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="admin_registrations.php">Registrations</a>
            <a href="manage_students.php">Manage Students</a>
            <a href="admin_volunteers.php">Volunteer Applications</a>
            <a class="logout" href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <h2>Admin Dashboard</h2>
            <a href="index.php">Visit Homepage →</a>
        </header>

        <main>
            <section class="welcome">
                <h1>
                    Welcome, <?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?>!
                </h1>

                <p>
                    Manage university events, review student accounts,
                    approve volunteer applications, and monitor registrations
                    from one central dashboard.
                </p>

                <span class="tag">ADMINISTRATOR ACCESS</span>
            </section>

            <h2 class="section-title">Overview</h2>

            <section class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Events</div>
                    <div class="stat-number"><?= $totalEvents ?></div>
                    <div class="stat-note">Events in the database</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-number"><?= $totalStudents ?></div>
                    <div class="stat-note">Registered student accounts</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Registrations</div>
                    <div class="stat-number"><?= $totalRegistrations ?></div>
                    <div class="stat-note">Event registrations recorded</div>
                </div>
            </section>

            <h2 class="section-title">Administration</h2>

            <section class="cards">
                <div class="card">
                    <div class="card-icon">&#128197;</div>
                    <h3>Event Management</h3>
                    <p>
                        Create events, update event details, manage dates,
                        and control seat capacity.
                    </p>
                    <a class="btn" href="manage_events.php">Manage Events →</a>
                </div>

                <div class="card">
                    <div class="card-icon">&#128101;</div>
                    <h3>Student Registrations</h3>
                    <p>
                        View registered students and review their event
                        registration details.
                    </p>
                    <a class="btn" href="admin_registrations.php">
                        View Registrations →
                    </a>
                </div>

                <div class="card">
                    <div class="card-icon">&#10003;</div>
                    <h3>Student Approval</h3>
                    <p>
                        Review student accounts and approve or reject
                        requests to access the student portal.
                    </p>
                    <a class="btn gold" href="manage_students.php">
                        Manage Students →
                    </a>
                </div>

                <div class="card">
                    <div class="card-icon">&#129309;</div>
                    <h3>Volunteer Applications</h3>
                    <p>
                        Review student volunteer requests, check their
                        reasons, and approve or reject pending applications.
                    </p>

                    <a class="btn gold" href="admin_volunteers.php">
                        Review Applications
                        <?php if ($pendingVolunteers > 0): ?>
                            <span class="btn-count">
                                <?= $pendingVolunteers ?>
                            </span>
                        <?php endif; ?>
                        →
                    </a>
                </div>
            </section>
        </main>

        <footer>
            &copy; 2026 MRU Fest Management System. All rights reserved.
        </footer>
    </div>
</div>
</body>
</html>