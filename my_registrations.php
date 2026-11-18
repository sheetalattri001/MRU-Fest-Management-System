<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$student_name = $_SESSION["student_name"] ?? "Student";

$sql = "SELECT
            r.registration_code,
            r.registered_at,
            e.title,
            e.description,
            e.event_date
        FROM registrations r
        INNER JOIN events e ON r.event_id = e.id
        WHERE r.student_id = ?
        ORDER BY r.registered_at DESC";

$stmt = $conn->prepare($sql);
$registrations = [];

if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }

    $stmt->close();
}

function safe($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$totalRegistrations = count($registrations);
$upcomingRegistrations = 0;
$completedRegistrations = 0;

foreach ($registrations as $registration) {
    if ($registration["event_date"] >= date("Y-m-d")) {
        $upcomingRegistrations++;
    } else {
        $completedRegistrations++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Registrations | MRU Fest</title>

<style>
:root {
    --navy: #171746;
    --navy-light: #303067;
    --gold: #f4bd63;
    --gold-dark: #d99b35;
    --background: #f5f6fb;
    --text: #292b48;
    --muted: #777b93;
    --border: #e8eaf2;
    --green: #168454;
    --red: #a72e39;
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

.nav-links a,
.sidebar-bottom a {
    color: #dedff0;
    padding: 13px 14px;
    border-radius: 9px;
    font-size: 14px;
    transition: 0.2s;
}

.nav-links a:hover,
.nav-links a.active,
.sidebar-bottom a:hover {
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
}

/* Main content */
.main {
    flex: 1;
    min-width: 0;
}

.topbar {
    min-height: 76px;
    background: white;
    padding: 18px 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-bottom: 1px solid var(--border);
}

.topbar h2 {
    color: var(--navy);
    font-size: 19px;
    margin: 0;
}

.topbar p {
    color: var(--muted);
    font-size: 12px;
    margin: 5px 0 0;
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
    background: #fffaf0;
    border-color: var(--gold-dark);
}

.content {
    max-width: 1450px;
    padding: 32px 35px 45px;
    margin: auto;
}

/* Welcome banner */
.hero {
    background: linear-gradient(120deg, #171746, #34346e);
    color: white;
    padding: 31px;
    border-radius: 17px;
    margin-bottom: 26px;
}

.hero-tag {
    color: var(--gold);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
}

.hero h1 {
    font-size: clamp(25px, 3vw, 33px);
    margin: 12px 0;
    line-height: 1.3;
}

.hero p {
    color: #dedff2;
    font-size: 14px;
    line-height: 1.7;
    max-width: 650px;
    margin: 0;
}

/* Summary cards */
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 34px;
}

.stat-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: 21px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 47px;
    height: 47px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #fff3dc;
    font-size: 22px;
}

.stat-card h3 {
    font-size: 25px;
    color: var(--navy);
    margin: 0 0 4px;
}

.stat-card p {
    color: var(--muted);
    font-size: 12px;
    margin: 0;
}

/* Registration list */
.section-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 20px;
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

.count-badge {
    background: #fff3dc;
    color: #865a13;
    border-radius: 20px;
    padding: 8px 13px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.registrations {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 290px), 1fr));
    gap: 22px;
}

.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 23px;
    box-shadow: 0 5px 18px #17174608;
    display: flex;
    flex-direction: column;
    min-width: 0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-3px);
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
    width: 47px;
    height: 47px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff3dc;
    border-radius: 12px;
    font-size: 23px;
}

.status {
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-align: center;
}

.status.upcoming {
    background: #e4f7ed;
    color: #167447;
}

.status.completed {
    background: #eef0f7;
    color: #555b77;
}

.card h3 {
    color: var(--navy);
    font-size: 19px;
    margin: 0 0 12px;
    overflow-wrap: anywhere;
}

.description {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
    margin: 0 0 19px;
    overflow-wrap: anywhere;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #f7f8fc;
    padding: 12px;
    border-radius: 9px;
    margin-bottom: 10px;
}

.detail-icon {
    font-size: 16px;
}

.detail-text {
    min-width: 0;
}

.detail-label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 5px;
}

.detail-value {
    display: block;
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.code-box {
    margin-top: 8px;
    padding: 15px;
    background: #fffaf0;
    border: 1px dashed var(--gold-dark);
    border-radius: 10px;
}

.code-label {
    color: #865a13;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.7px;
    margin-bottom: 9px;
}

.code-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: var(--navy);
    font-size: 15px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.copy-button {
    flex-shrink: 0;
    background: var(--navy);
    color: white;
    border: 0;
    border-radius: 7px;
    padding: 8px 10px;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.copy-button:hover {
    background: var(--navy-light);
}

.empty {
    grid-column: 1 / -1;
    text-align: center;
    background: white;
    border: 1px dashed #cfd2e3;
    border-radius: 15px;
    padding: 45px 22px;
}

.empty-icon {
    font-size: 43px;
    margin-bottom: 13px;
}

.empty h2 {
    color: var(--navy);
    margin: 0 0 10px;
    font-size: 21px;
}

.empty p {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
}

.button {
    display: inline-block;
    margin-top: 13px;
    padding: 12px 18px;
    background: var(--gold);
    color: var(--navy);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
}

.button:hover {
    background: #f7ce88;
}

.footer-note {
    text-align: center;
    color: var(--muted);
    font-size: 12px;
    margin-top: 35px;
}

/* Responsive */
@media (max-width: 900px) {
    .sidebar {
        width: 205px;
        padding: 22px 12px;
    }

    .content {
        padding: 25px 20px 35px;
    }

    .topbar {
        padding: 17px 20px;
    }

    .stats {
        grid-template-columns: 1fr;
        gap: 12px;
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
        padding: 24px 20px;
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
            <a href="dashboard.php">▦ &nbsp; Dashboard</a>
            <a href="events.php">✦ &nbsp; Explore Events</a>
            <a class="active" href="my_registrations.php">▤ &nbsp; My Registrations</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php">↪ &nbsp; Logout</a>
        </div>
    </aside>

    <div class="main">

        <header class="topbar">
            <div>
                <h2>My Registrations</h2>
                <p>Manage and review your event bookings</p>
            </div>

            <a class="home-link" href="dashboard.php">← Dashboard</a>
        </header>

        <main class="content">

            <section class="hero">
                <span class="hero-tag">YOUR FEST JOURNEY</span>
                <h1>Your Events, All in One Place ✨</h1>
                <p>
                    Hello, <?= safe($student_name) ?>! Here you can review
                    your registered events, check event dates and find your
                    unique registration confirmation codes.
                </p>
            </section>

            <section class="stats">

                <article class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div>
                        <h3><?= $totalRegistrations ?></h3>
                        <p>Total Registrations</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div>
                        <h3><?= $upcomingRegistrations ?></h3>
                        <p>Upcoming Events</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div>
                        <h3><?= $completedRegistrations ?></h3>
                        <p>Past Events</p>
                    </div>
                </article>

            </section>

            <div class="section-heading">
                <div>
                    <h2>Your Registered Events</h2>
                    <p>Keep your confirmation codes handy for reference.</p>
                </div>

                <span class="count-badge">
                    <?= $totalRegistrations ?>
                    <?= $totalRegistrations === 1 ? "Registration" : "Registrations" ?>
                </span>
            </div>

            <section class="registrations">

                <?php if ($totalRegistrations > 0): ?>

                    <?php foreach ($registrations as $registration): ?>
                        <?php
                            $eventDate = $registration["event_date"];
                            $isUpcoming = $eventDate >= date("Y-m-d");
                            $titleLower = strtolower($registration["title"]);

                            if (strpos($titleLower, "dance") !== false) {
                                $eventIcon = "💃";
                            } elseif (
                                strpos($titleLower, "sing") !== false ||
                                strpos($titleLower, "music") !== false
                            ) {
                                $eventIcon = "🎤";
                            } elseif (
                                strpos($titleLower, "technical") !== false ||
                                strpos($titleLower, "tech") !== false ||
                                strpos($titleLower, "coding") !== false
                            ) {
                                $eventIcon = "💻";
                            } elseif (
                                strpos($titleLower, "art") !== false ||
                                strpos($titleLower, "paint") !== false
                            ) {
                                $eventIcon = "🎨";
                            } else {
                                $eventIcon = "🎉";
                            }
                        ?>

                        <article class="card">

                            <div class="card-top">
                                <div class="event-icon"><?= $eventIcon ?></div>

                                <?php if ($isUpcoming): ?>
                                    <span class="status upcoming">✓ Registered</span>
                                <?php else: ?>
                                    <span class="status completed">Past Event</span>
                                <?php endif; ?>
                            </div>

                            <h3><?= safe($registration["title"]) ?></h3>

                            <p class="description">
                                <?php
                                $description = trim($registration["description"] ?? "");
                                echo $description !== ""
                                    ? nl2br(safe($description))
                                    : "You are registered for this university fest event.";
                                ?>
                            </p>

                            <div class="detail-row">
                                <span class="detail-icon">📅</span>
                                <div class="detail-text">
                                    <span class="detail-label">Event Date</span>
                                    <span class="detail-value">
                                        <?= safe(date("d M Y", strtotime($eventDate))) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="detail-row">
                                <span class="detail-icon">🕒</span>
                                <div class="detail-text">
                                    <span class="detail-label">Registration Date</span>
                                    <span class="detail-value">
                                        <?= safe(date(
                                            "d M Y, h:i A",
                                            strtotime($registration["registered_at"])
                                        )) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="code-box">
                                <div class="code-label">CONFIRMATION CODE</div>

                                <div class="code-value">
                                    <span><?= safe($registration["registration_code"]) ?></span>

                                    <button
                                        type="button"
                                        class="copy-button"
                                        onclick="copyCode(this)"
                                        data-code="<?= safe($registration["registration_code"]) ?>">
                                        Copy
                                    </button>
                                </div>
                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty">
                        <div class="empty-icon">🎟️</div>
                        <h2>No Registrations Yet</h2>
                        <p>
                            You haven't registered for any fest events yet.
                            Explore the available events and reserve your place!
                        </p>
                        <a class="button" href="events.php">Explore Events →</a>
                    </div>

                <?php endif; ?>

            </section>

            <p style="text-align:center;">
                <a class="button" href="events.php">+ Explore More Events</a>
                <a class="button" href="dashboard.php"
                   style="background:#171746;color:white;margin-left:7px;">
                    Back to Dashboard
                </a>
            </p>

            <p class="footer-note">
                MRU Fest Management System · Discover. Participate. Celebrate.
            </p>

        </main>
    </div>
</div>

<script>
function copyCode(button) {
    const code = button.getAttribute("data-code");

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code)
            .then(() => showCopied(button))
            .catch(() => fallbackCopy(code, button));
    } else {
        fallbackCopy(code, button);
    }
}

function showCopied(button) {
    const originalText = button.textContent;
    button.textContent = "Copied!";
    setTimeout(() => {
        button.textContent = originalText;
    }, 1500);
}

function fallbackCopy(code, button) {
    const textArea = document.createElement("textarea");
    textArea.value = code;
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.select();

    try {
        document.execCommand("copy");
        showCopied(button);
    } catch (error) {
        alert("Please copy this registration code manually: " + code);
    }

    document.body.removeChild(textArea);
}
</script>

</body>
</html>