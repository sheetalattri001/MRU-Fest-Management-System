<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"]) || ($_SESSION["role"] ?? "") !== "student") {
    header("Location: login.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$message = "";
$message_type = "info";

$stmt = $conn->prepare(
    "SELECT approval_status FROM students WHERE id = ?"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || $student["approval_status"] !== "approved") {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT status FROM volunteer_applications WHERE student_id = ?"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reason = trim($_POST["reason"] ?? "");

    if (strlen($reason) < 10) {
        $message = "Please enter at least 10 characters explaining why you want to volunteer.";
        $message_type = "error";
    } elseif (strlen($reason) > 2000) {
        $message = "Your explanation must be under 2000 characters.";
        $message_type = "error";
    } elseif ($application && $application["status"] === "approved") {
        $message = "Your volunteer application is already approved.";
        $message_type = "info";
    } elseif ($application && $application["status"] === "pending") {
        $message = "Your application is already waiting for admin approval.";
        $message_type = "info";
    } else {
        if ($application && $application["status"] === "rejected") {
            $stmt = $conn->prepare(
                "UPDATE volunteer_applications
                 SET reason = ?, status = 'pending', applied_at = CURRENT_TIMESTAMP
                 WHERE student_id = ?"
            );
            $stmt->bind_param("si", $reason, $student_id);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO volunteer_applications (student_id, reason)
                 VALUES (?, ?)"
            );
            $stmt->bind_param("is", $student_id, $reason);
        }

        if ($stmt->execute()) {
            $message = "Application submitted successfully! Please wait for admin approval.";
            $message_type = "success";

            $stmt->close();

            $stmt = $conn->prepare(
                "SELECT status FROM volunteer_applications WHERE student_id = ?"
            );
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $application = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $message = "Could not submit your application. Please try again.";
            $message_type = "error";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Volunteer Application | MRU Fest</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f3f5fa;color:#202b43}
nav{background:#101c36;color:white;padding:18px 7%;display:flex;justify-content:space-between;align-items:center}
nav a{color:white;text-decoration:none;margin-left:18px}
.brand{font-weight:bold;letter-spacing:1px;color:#f2c66d}
main{max-width:720px;margin:45px auto;padding:0 18px}
.card{background:white;border-radius:16px;padding:30px;box-shadow:0 8px 28px #17254412}
h1{margin-top:0;color:#14264a}
p{line-height:1.6;color:#64708a}
label{display:block;font-weight:bold;margin:22px 0 9px}
textarea{width:100%;min-height:150px;padding:13px;border:1px solid #ccd4e1;border-radius:9px;font:inherit;resize:vertical}
button{background:#15274b;color:white;border:0;padding:13px 20px;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:16px}
button:hover{background:#263e70}
.notice{padding:13px;border-radius:8px;margin:18px 0;line-height:1.5}
.success{background:#e4f7eb;color:#17653a}
.error{background:#fde9e9;color:#a12323}
.info{background:#eaf1ff;color:#214c91}
.status{font-weight:bold;text-transform:capitalize}
footer{text-align:center;color:#78839a;margin:25px}
</style>
</head>
<body>
<nav>
    <div class="brand">MRU FEST</div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main>
<div class="card">
    <h1>Become a Volunteer</h1>
    <p>Be part of MRU Fest! Apply to help organize events, coordinate participants, and make the fest successful.</p>

    <?php if ($message !== ""): ?>
        <div class="notice <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($application && $application["status"] === "approved"): ?>
        <div class="notice success">
            Your volunteer application is approved! Check with the admin for event assignments.
        </div>
        <p>Application status: <span class="status">Approved</span></p>

    <?php elseif ($application && $application["status"] === "pending"): ?>
        <p>Application status: <span class="status">Pending</span></p>
        <p>Your application is under review. You can check back here for updates.</p>

    <?php else: ?>
        <?php if ($application && $application["status"] === "rejected"): ?>
            <p>Your previous application was rejected. You may submit a new application.</p>
        <?php endif; ?>

        <form method="POST" action="volunteer_apply.php">
            <label for="reason">Why do you want to become a volunteer?</label>
            <textarea id="reason" name="reason" minlength="10" maxlength="2000"
                placeholder="Tell us about your interest, skills, and how you can help..." required></textarea>
            <button type="submit">Submit Application</button>
        </form>
    <?php endif; ?>
</div>
</main>
<footer>© <?= date("Y") ?> MRU Fest Management System</footer>
</body>
</html>