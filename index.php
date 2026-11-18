<?php
require_once "db.php";

$sql = "SELECT id, title, description, event_date, total_seats
        FROM events
        ORDER BY event_date ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load events. Please try again later.");
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function eventEmoji($title) {
    $title = strtolower($title);

    if (strpos($title, "sing") !== false || strpos($title, "music") !== false) return "🎤";
    if (strpos($title, "dance") !== false) return "💃";
    if (strpos($title, "tech") !== false || strpos($title, "coding") !== false) return "💻";
    if (strpos($title, "art") !== false || strpos($title, "paint") !== false) return "🎨";
    if (strpos($title, "eco") !== false || strpos($title, "reel") !== false) return "🌿";

    return "🎉";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MRU Fest | University Events Portal</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f7fb;
    color: #182642;
    line-height: 1.6;
}

nav {
    background: #101d3a;
    color: white;
    padding: 16px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 4px 15px #101d3a20;
}

.brand {
    color: #e8bd68;
    font-size: 23px;
    font-weight: 800;
    letter-spacing: 1px;
    text-decoration: none;
}

.brand small {
    display: block;
    font-size: 10px;
    color: #d4dbea;
    letter-spacing: 2px;
}

nav .links {
    display: flex;
    align-items: center;
    gap: 25px;
}

nav .links a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

nav .links a:hover {
    color: #e8bd68;
}

nav .links .login {
    background: #e8bd68;
    color: #101d3a;
    padding: 10px 22px;
    border-radius: 6px;
}

nav .links .login:hover {
    background: #f5d99b;
    color: #101d3a;
}

.hero {
    min-height: 510px;
    padding: 85px 8%;
    display: flex;
    align-items: center;
    position: relative;
    background:
        linear-gradient(
            90deg,
            rgba(10, 22, 46, 0.92) 0%,
            rgba(16, 29, 58, 0.76) 55%,
            rgba(16, 29, 58, 0.38) 100%
        ),
        url("https://manavrachna.edu.in/assets/campus/mriirs/images/mri-about-1.webp")
        center / cover no-repeat;
    color: white;
}

.hero-content {
    max-width: 760px;
}

.eyebrow {
    color: #e8bd68;
    text-transform: uppercase;
    font-weight: bold;
    letter-spacing: 3px;
    font-size: 12px;
    margin-bottom: 18px;
}

.hero h1 {
    font-size: clamp(35px, 5vw, 60px);
    line-height: 1.15;
    margin-bottom: 20px;
}

.hero h1 span {
    color: #e8bd68;
}

.hero p {
    font-size: 17px;
    color: #f0f3f9;
    max-width: 620px;
    margin-bottom: 28px;
}

.hero-buttons {
    display: flex;
    gap: 13px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 13px 22px;
    background: #e8bd68;
    color: #101d3a;
    border: none;
    border-radius: 7px;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    background: #f5d99b;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 1px solid white;
}

.btn-outline:hover {
    background: #ffffff20;
    color: white;
}

.highlights {
    background: #172b4d;
    padding: 24px 7%;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    color: white;
}

.highlight {
    padding: 10px 18px;
    border-right: 1px solid #ffffff25;
}

.highlight:last-child {
    border-right: none;
}

.highlight strong {
    display: block;
    color: #e8bd68;
    margin-bottom: 4px;
}

.highlight p {
    font-size: 13px;
    color: #d4dbea;
}

section.content {
    padding: 65px 7%;
}

.section-heading {
    margin-bottom: 30px;
}

.kicker {
    color: #9b7127;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.section-heading h2,
.about h2 {
    font-size: 34px;
    color: #101d3a;
}

.section-heading p,
.about p {
    color: #68758b;
    margin-top: 10px;
}

.event-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 23px;
}

.event {
    background: white;
    border: 1px solid #e5e9f1;
    border-radius: 13px;
    overflow: hidden;
    box-shadow: 0 6px 22px #101d3a0b;
    display: flex;
    flex-direction: column;
    transition: .2s;
}

.event:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px #101d3a15;
}

.event-top {
    padding: 23px 23px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.emoji {
    background: #f8f1e3;
    width: 55px;
    height: 55px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    font-size: 29px;
}

.event-tag {
    color: #8a641f;
    background: #fbf3e2;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.event-body {
    padding: 12px 23px 23px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.event h3 {
    font-size: 20px;
    color: #101d3a;
    margin-bottom: 10px;
}

.event p {
    color: #68758b;
    font-size: 14px;
    overflow-wrap: anywhere;
    margin-bottom: 12px;
}

.event .date {
    color: #263b5e;
    font-weight: bold;
}

.event .btn {
    width: 100%;
    margin-top: auto;
}

.empty {
    background: white;
    border: 1px dashed #ccd4e2;
    border-radius: 10px;
    padding: 25px;
    color: #68758b;
    grid-column: 1 / -1;
}

.about {
    background: #eaf0f8;
}

.about-inner {
    max-width: 900px;
}

.about-points {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 25px;
}

.about-point {
    background: white;
    border: 1px solid #e1e7f0;
    padding: 18px;
    border-radius: 9px;
}

.about-point strong {
    display: block;
    color: #101d3a;
}

.about-point span {
    color: #68758b;
    font-size: 13px;
}

footer {
    background: #0b1429;
    color: #cbd3e1;
    text-align: center;
    padding: 25px 15px;
    font-size: 13px;
}

@media (max-width: 650px) {
    nav {
        padding: 14px 5%;
    }

    nav .links {
        width: 100%;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    nav .links a {
        font-size: 12px;
    }

    .hero {
        min-height: 450px;
        padding: 60px 6%;
        background-position: center;
    }

    .hero p {
        font-size: 15px;
    }

    .hero-buttons {
        flex-direction: column;
        align-items: stretch;
        max-width: 310px;
    }

    .highlights {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .highlight {
        border-right: none;
        border-bottom: 1px solid #ffffff25;
        padding: 12px 0;
    }

    .highlight:last-child {
        border-bottom: none;
    }

    section.content {
        padding: 48px 5%;
    }

    .section-heading h2,
    .about h2 {
        font-size: 28px;
    }

    .about-points {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<nav>
    <a href="index.php" class="brand">
        MRU FEST
        <small>UNIVERSITY EVENTS PORTAL</small>
    </a>

    <div class="links">
        <a href="index.php">Home</a>
        <a href="#events">Events</a>
        <a href="#about">About</a>
        <a href="login.php" class="login">Login ↗</a>
    </div>
</nav>

<main>

<section class="hero">
    <div class="hero-content">
        <div class="eyebrow">Manav Rachna University</div>

        <h1>
            Where Talent Meets
            <span>Celebration.</span>
        </h1>

        <p>
            Welcome to the MRU Fest Management System.
            Discover campus events, showcase your talent,
            and create unforgettable university memories.
        </p>

        <div class="hero-buttons">
            <a href="#events" class="btn">Explore Upcoming Events →</a>
            <a href="login.php" class="btn btn-outline">Login to Your Portal</a>
        </div>
    </div>
</section>

<div class="highlights">
    <div class="highlight">
        <strong>🎓 Student Community</strong>
        <p>A central place for university activities.</p>
    </div>

    <div class="highlight">
        <strong>📅 Upcoming Events</strong>
        <p>Explore event dates and details.</p>
    </div>

    <div class="highlight">
        <strong>✨ Your Talent, Your Stage</strong>
        <p>Participate and make great memories.</p>
    </div>
</div>

<section class="content" id="events">
    <div class="section-heading">
        <div class="kicker">Discover and participate</div>
        <h2>Explore Upcoming Events</h2>
        <p>Browse university events and view basic event information.</p>
    </div>

    <div class="event-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($event = $result->fetch_assoc()): ?>
                <article class="event">
                    <div class="event-top">
                        <div class="emoji">
                            <?= eventEmoji($event["title"]) ?>
                        </div>
                        <span class="event-tag">MRU FEST</span>
                    </div>

                    <div class="event-body">
                        <h3><?= e($event["title"]) ?></h3>

                        <p>
                            <?= nl2br(e($event["description"])) ?>
                        </p>

                        <p class="date">
                            📅 Date: <?= e($event["event_date"]) ?>
                        </p>

                        <p>
                            👥 Total seats: <?= (int)$event["total_seats"] ?>
                        </p>

                        <a href="login.php" class="btn">
                            Login to Explore →
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">
                No events are available right now. Please check again later.
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="content about" id="about">
    <div class="about-inner">
        <div class="kicker">About our platform</div>
        <h2>Your University Life, All in One Place.</h2>

        <p>
            MRU Fest Management System is a central platform for discovering
            university events and participating in campus activities.
            Browse the event list, then log in to access your portal.
        </p>

        <div class="about-points">
            <div class="about-point">
                <strong>Discover Events</strong>
                <span>Browse upcoming campus activities.</span>
            </div>

            <div class="about-point">
                <strong>Register Online</strong>
                <span>Participate in your favourite events.</span>
            </div>

            <div class="about-point">
                <strong>Track Registrations</strong>
                <span>Keep track of your event participation.</span>
            </div>

            <div class="about-point">
                <strong>Celebrate Together</strong>
                <span>Make your university experience memorable.</span>
            </div>
        </div>
    </div>
</section>

</main>

<footer>
    <p>MRU Fest Management System | University Events Portal</p>
    <p>&copy; <?= date("Y") ?> All rights reserved.</p>
</footer>

</body>
</html>