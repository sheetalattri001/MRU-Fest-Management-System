<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

$isStudentLoggedIn = isset($_SESSION["student_id"]);
$student_id = $isStudentLoggedIn ? (int) $_SESSION["student_id"] : 0;

$message = "";
$message_type = "";

// Handle event registration
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["event_id"])) {

    if (!$isStudentLoggedIn) {
        header("Location: login.php");
        exit;
    }

    $event_id = filter_var($_POST["event_id"], FILTER_VALIDATE_INT);

    if ($event_id === false || $event_id === null || $event_id <= 0) {
        $message = "Invalid event selected.";
        $message_type = "error";
    } else {
        try {
            $conn->begin_transaction();

            // Lock event row to prevent overbooking
            $eventStmt = $conn->prepare(
                "SELECT id, total_seats FROM events WHERE id = ? FOR UPDATE"
            );
            $eventStmt->bind_param("i", $event_id);
            $eventStmt->execute();
            $event = $eventStmt->get_result()->fetch_assoc();
            $eventStmt->close();

            if (!$event) {
                $conn->rollback();
                $message = "Event not found.";
                $message_type = "error";
            } else {
                // Check duplicate registration
                $check = $conn->prepare(
                    "SELECT id FROM registrations
                     WHERE student_id = ? AND event_id = ?"
                );
                $check->bind_param("ii", $student_id, $event_id);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $check->close();
                    $conn->rollback();
                    $message = "You are already registered for this event!";
                    $message_type = "error";
                } else {
                    $check->close();

                    // Count current registrations
                    $countStmt = $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM registrations WHERE event_id = ?"
                    );
                    $countStmt->bind_param("i", $event_id);
                    $countStmt->execute();
                    $countRow = $countStmt->get_result()->fetch_assoc();
                    $countStmt->close();

                    $registered = (int) $countRow["total"];
                    $capacity = (int) $event["total_seats"];

                    if ($registered >= $capacity) {
                        $conn->rollback();
                        $message = "Sorry, this event is fully booked.";
                        $message_type = "error";
                    } else {
                        $code = "MRU" . strtoupper(bin2hex(random_bytes(4)));

                        $insert = $conn->prepare(
                            "INSERT INTO registrations
                             (student_id, event_id, registration_code)
                             VALUES (?, ?, ?)"
                        );
                        $insert->bind_param("iis", $student_id, $event_id, $code);

                        if ($insert->execute()) {
                            $insert->close();
                            $conn->commit();

                            $message = "Registration successful! Your code is " . $code;
                            $message_type = "success";
                        } else {
                            $insert->close();
                            $conn->rollback();
                            $message = "Registration failed. Please try again.";
                            $message_type = "error";
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
                // Transaction may already be closed.
            }

            if ($e instanceof mysqli_sql_exception && $e->getCode() == 1062) {
                $message = "You are already registered for this event!";
            } else {
                $message = "Something went wrong. Please try again.";
            }

            $message_type = "error";
        }
    }
}

// Fetch events and registration status
$listStmt = $conn->prepare(
    "SELECT
        e.id,
        e.title,
        e.description,
        e.event_date,
        e.total_seats,
        COUNT(r.id) AS registered,
        COALESCE(
            MAX(CASE WHEN r.student_id = ? THEN 1 ELSE 0 END),
            0
        ) AS student_registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.id
     GROUP BY
        e.id, e.title, e.description, e.event_date, e.total_seats
     ORDER BY e.event_date ASC"
);

$listStmt->bind_param("i", $student_id);
$listStmt->execute();
$result = $listStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Explore Events | MRU Fest</title>

<style>
:root {
    --navy: #171746;
    --navy-light: #25255c;
    --gold: #f4bd63;
    --gold-dark: #d99b35;
    --background: #f5f6fb;
    --text: #292b48;
    --muted: #777b93;
    --border: #e8eaf2;
    --green: #168454;
    --red: #c43d48;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--background);
    color: var(--text);
}

a {
    text-decoration: none;
}

.layout {
    min-height: 100vh;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 245px;
    flex-shrink: 0;
    background: var(--navy);
    color: white;
    padding: 28px 18px;
    display: flex;
    flex-direction: column;
}

.brand {
    padding: 0 12px 30px;
    border-bottom: 1px solid #ffffff20;
}

.brand-name {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: 1px;
}

.brand-name span {
    color: var(--gold);
}

.brand small {
    display: block;
    color: #c4c5df;
    margin-top: 7px;
    font-size: 12px;
}

.nav-label {
    color: #a6a8ca;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    margin: 27px 12px 12px;
}

.nav-links {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.nav-links a {
    color: #dedff0;
    padding: 13px 14px;
    border-radius: 9px;
    font-size: 14px;
    transition: 0.2s;
}

.nav-links a:hover,
.nav-links a.active {
    background: #ffffff15;
    color: var(--gold);
}

.nav-links a.active {
    border-left: 3px solid var(--gold);
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 25px;
}

.sidebar-bottom a {
    display: block;
    color: #dedff0;
    padding: 13px 14px;
    border-radius: 9px;
}

.sidebar-bottom a:hover {
    background: #ffffff15;
    color: var(--gold);
}

/* Main content */
.main {
    flex: 1;
    min-width: 0;
}

.topbar {
    background: white;
    min-height: 76px;
    padding: 18px 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-bottom: 1px solid var(--border);
}

.topbar h2 {
    margin: 0;
    color: var(--navy);
    font-size: 19px;
}

.topbar p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.home-link {
    color: var(--navy);
    border: 1px solid var(--border);
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.home-link:hover {
    border-color: var(--gold-dark);
    background: #fffaf0;
}

.content {
    max-width: 1450px;
    margin: auto;
    padding: 32px 35px 45px;
}

.hero {
    background: linear-gradient(120deg, #171746, #34346e);
    border-radius: 18px;
    padding: 32px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 27px;
}

.hero::after {
    content: "";
    position: absolute;
    width: 210px;
    height: 210px;
    border: 35px solid #ffffff0c;
    border-radius: 50%;
    right: -45px;
    top: -85px;
}

.hero-tag {
    display: inline-block;
    color: var(--gold);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 12px;
}

.hero h1 {
    font-size: clamp(25px, 3vw, 34px);
    margin: 0 0 12px;
    line-height: 1.25;
}

.hero p {
    max-width: 570px;
    color: #dedff2;
    line-height: 1.7;
    font-size: 14px;
    margin: 0;
}

.section-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 15px;
    margin: 30px 0 20px;
}

.section-heading h2 {
    color: var(--navy);
    font-size: 22px;
    margin: 0;
}

.section-heading p {
    color: var(--muted);
    font-size: 13px;
    margin: 7px 0 0;
}

.event-count {
    background: #fff3dc;
    color: #865a13;
    border-radius: 20px;
    padding: 8px 13px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* Event cards */
.events {
    display: grid;
    grid-template-columns: repeat( auto-fit, minmax(min(100%, 275px), 1fr));
    gap: 22px;
}

.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 23px;
    display: flex;
    flex-direction: column;
    min-width: 0;
    box-shadow: 0 5px 18px #17174608;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px #17174612;
}

.card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.event-icon {
    display: flex;
    width: 47px;
    height: 47px;
    align-items: center;
    justify-content: center;
    background: #fff3dc;
    border-radius: 12px;
    font-size: 23px;
}

.status {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-align: center;
}

.available {
    background: #e4f7ed;
    color: #167447;
}

.full {
    background: #ffebed;
    color: #a72e39;
}

.registered {
    background: #e9edff;
    color: #343f9c;
}

.card h3 {
    color: var(--navy);
    font-size: 19px;
    margin: 0 0 11px;
    overflow-wrap: anywhere;
}

.description {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
    margin: 0 0 19px;
    overflow-wrap: anywhere;
    flex: 1;
}

.event-date {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 12px;
    border-radius: 9px;
    background: #f7f8fc;
    color: var(--navy);
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 18px;
}

.seat-info {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 9px;
}

.seat-info strong {
    color: var(--navy);
}

.progress-track {
    height: 7px;
    background: #eceef5;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 20px;
}

.progress-bar {
    height: 100%;
    background: var(--gold-dark);
    border-radius: 20px;
}

.card-action {
    margin-top: auto;
}

button,
.login-button,
.disabled-button {
    display: block;
    width: 100%;
    padding: 13px 16px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 800;
    text-align: center;
}

button {
    background: var(--gold);
    color: var(--navy);
    border: 0;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background: #f7ce88;
    transform: translateY(-1px);
}

.login-button {
    background: var(--navy);
    color: white;
}

.login-button:hover {
    background: var(--navy-light);
}

.disabled-button {
    background: #f0f1f6;
    color: #81849b;
}

.message {
    padding: 16px 18px;
    margin: 0 0 25px;
    border-radius: 10px;
    background: white;
    font-size: 14px;
    line-height: 1.6;
    overflow-wrap: anywhere;
    border: 1px solid var(--border);
}

.message.success {
    border-left: 5px solid #228b50;
}

.message.error {
    border-left: 5px solid #d9534f;
}

.empty-state {
    grid-column: 1 / -1;
    background: white;
    border: 1px dashed #cfd2e3;
    border-radius: 14px;
    padding: 45px 20px;
    text-align: center;
    color: var(--muted);
}

.empty-state strong {
    display: block;
    font-size: 18px;
    color: var(--navy);
    margin-bottom: 8px;
}

.footer-note {
    color: var(--muted);
    font-size: 12px;
    text-align: center;
    margin-top: 35px;
}

/* Responsive layout */
@media (max-width: 800px) {
    .sidebar {
        width: 190px;
        padding: 22px 12px;
    }

    .content {
        padding: 24px 20px 35px;
    }

    .topbar {
        padding: 17px 20px;
    }
}

@media (max-width: 600px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        padding: 15px;
    }

    .brand {
        padding: 0 5px 14px;
    }

    .brand-name {
        font-size: 20px;
    }

    .brand small {
        margin-top: 3px;
    }

    .nav-label {
        display: none;
    }

    .nav-links {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 12px;
    }

    .nav-links a,
    .sidebar-bottom a {
        padding: 9px 10px;
        font-size: 12px;
    }

    .sidebar-bottom {
        margin-top: 8px;
        padding-top: 0;
    }

    .topbar {
        min-height: 65px;
        padding: 14px 16px;
    }

    .topbar h2 {
        font-size: 16px;
    }

    .home-link {
        font-size: 11px;
        padding: 9px;
    }

    .content {
        padding: 20px 14px 30px;
    }

    .hero {
        padding: 25px 21px;
        border-radius: 13px;
    }

    .section-heading h2 {
        font-size: 19px;
    }

    .card {
        padding: 20px;
    }
}
</style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-name">MRU <span>FEST</span></div>
            <small>University Fest Management</small>
        </div>

        <div class="nav-label">MAIN MENU</div>

        <nav class="nav-links">
            <a href="index.php">⌂ &nbsp; Home</a>

            <?php if ($isStudentLoggedIn): ?>
                <a href="dashboard.php">▦ &nbsp; Dashboard</a>
            <?php endif; ?>

            <a class="active" href="events.php">✦ &nbsp; Explore Events</a>

            <?php if ($isStudentLoggedIn): ?>
                <a href="my_registrations.php">▤ &nbsp; My Registrations</a>
            <?php else: ?>
                <a href="login.php">↪ &nbsp; Student Login</a>
            <?php endif; ?>
        </nav>

        <?php if ($isStudentLoggedIn): ?>
            <div class="sidebar-bottom">
                <a href="logout.php">↪ &nbsp; Logout</a>
            </div>
        <?php endif; ?>
    </aside>

    <div class="main">

        <header class="topbar">
            <div>
                <h2>Explore Events</h2>
                <p>Discover your next campus experience</p>
            </div>

            <?php if ($isStudentLoggedIn): ?>
                <a class="home-link" href="dashboard.php">← Dashboard</a>
            <?php else: ?>
                <a class="home-link" href="index.php">← Homepage</a>
            <?php endif; ?>
        </header>

        <main class="content">

            <section class="hero">
                <span class="hero-tag">CAMPUS LIFE • CULTURE • CREATIVITY</span>
                <h1>Make Your Moment Count ✨</h1>
                <p>
                    From exciting competitions to creative showcases,
                    explore upcoming university events and find the experience
                    that inspires you.
                </p>
            </section>

            <?php if ($message !== ""): ?>
                <div class="message <?= htmlspecialchars($message_type, ENT_QUOTES, "UTF-8") ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                    <?php if ($message_type === "success" && $isStudentLoggedIn): ?>
                        <br>
                        <a href="my_registrations.php"
                           style="color:#171746;font-weight:700;">
                            View My Registrations →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section-heading">
                <div>
                    <h2>Upcoming Events</h2>
                    <p>Choose an event and secure your spot.</p>
                </div>

                <span class="event-count">
                    <?= (int) $result->num_rows ?> Events
                </span>
            </div>

            <div class="events">
                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($event = $result->fetch_assoc()): ?>
                        <?php
                            $totalSeats = max(0, (int) $event["total_seats"]);
                            $registeredCount = (int) $event["registered"];
                            $availableSeats = max(0, $totalSeats - $registeredCount);
                            $alreadyRegistered = (int) $event["student_registered"] === 1;
                            $isFull = $availableSeats <= 0;

                            $percentage = $totalSeats > 0
                                ? min(100, (int) round(($registeredCount / $totalSeats) * 100))
                                : 100;

                            $titleLower = strtolower($event["title"]);

                            if (strpos($titleLower, "dance") !== false) {
                                $eventIcon = "💃";
                            } elseif (strpos($titleLower, "sing") !== false
                                || strpos($titleLower, "music") !== false) {
                                $eventIcon = "🎤";
                            } elseif (strpos($titleLower, "technical") !== false
                                || strpos($titleLower, "tech") !== false
                                || strpos($titleLower, "coding") !== false) {
                                $eventIcon = "💻";
                            } elseif (strpos($titleLower, "art") !== false
                                || strpos($titleLower, "paint") !== false) {
                                $eventIcon = "🎨";
                            } elseif (strpos($titleLower, "eco") !== false
                                || strpos($titleLower, "environment") !== false) {
                                $eventIcon = "🌿";
                            } else {
                                $eventIcon = "🎉";
                            }
                        ?>

                        <article class="card">

                            <div class="card-top">
                                <div class="event-icon">
                                    <?= $eventIcon ?>
                                </div>

                                <?php if ($alreadyRegistered): ?>
                                    <span class="status registered">✓ Registered</span>
                                <?php elseif ($isFull): ?>
                                    <span class="status full">Fully Booked</span>
                                <?php else: ?>
                                    <span class="status available">● Open</span>
                                <?php endif; ?>
                            </div>

                            <h3>
                                <?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>
                            </h3>

                            <p class="description">
                                <?php
                                $description = trim($event["description"] ?? "");
                                echo $description !== ""
                                    ? nl2br(htmlspecialchars($description, ENT_QUOTES, "UTF-8"))
                                    : "Be part of this exciting campus event. Explore, participate and make memories.";
                                ?>
                            </p>

                            <div class="event-date">
                                <span>📅</span>
                                <span>
                                    <?= htmlspecialchars(
                                        date("d M Y", strtotime($event["event_date"])),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </span>
                            </div>

                            <div class="seat-info">
                                <span>Seat availability</span>
                                <strong><?= $availableSeats ?> / <?= $totalSeats ?> left</strong>
                            </div>

                            <div class="progress-track">
                                <div class="progress-bar"
                                     style="width: <?= $percentage ?>%;">
                                </div>
                            </div>

                            <div class="card-action">
                                <?php if (!$isStudentLoggedIn): ?>

                                    <?php if ($isFull): ?>
                                        <div class="disabled-button">Registration Closed</div>
                                    <?php else: ?>
                                        <a class="login-button" href="login.php">
                                            Login to Register →
                                        </a>
                                    <?php endif; ?>

                                <?php elseif ($alreadyRegistered): ?>

                                    <a class="login-button" href="my_registrations.php">
                                        View My Registration →
                                    </a>

                                <?php elseif ($isFull): ?>

                                    <div class="disabled-button">All Seats Booked</div>

                                <?php else: ?>

                                    <form method="POST" action="events.php">
                                        <input type="hidden" name="event_id"
                                               value="<?= (int) $event["id"] ?>">
                                        <button type="submit">Register Now →</button>
                                    </form>

                                <?php endif; ?>
                            </div>

                        </article>
                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <strong>No Events Available Yet</strong>
                        Please check back later for upcoming university events.
                    </div>

                <?php endif; ?>
            </div>

            <p class="footer-note">
                MRU Fest Management System · Discover. Participate. Celebrate.
            </p>

        </main>
    </div>
</div>
</body>
</html>

<?php
$listStmt->close();
?>