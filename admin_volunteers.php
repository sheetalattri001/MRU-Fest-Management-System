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

if (empty($_SESSION["volunteer_csrf"])) {
    $_SESSION["volunteer_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $applicationId = filter_input(
        INPUT_POST,
        "application_id",
        FILTER_VALIDATE_INT
    );
    $action = $_POST["action"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["volunteer_csrf"], $token)
    ) {
        $message = "Invalid request. Please refresh the page and try again.";
        $messageType = "error";
    } elseif (!$applicationId || !in_array($action, ["approve", "reject"], true)) {
        $message = "Invalid application action.";
        $messageType = "error";
    } else {
        $newStatus = $action === "approve" ? "approved" : "rejected";

        $stmt = $conn->prepare(
            "UPDATE volunteer_applications
             SET status = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param("si", $newStatus, $applicationId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Application successfully " . $newStatus . ".";
        } else {
            $message = "Application was not updated. It may already have been reviewed.";
            $messageType = "error";
        }

        $stmt->close();
    }
}

$sql = "SELECT
            va.id,
            va.student_id,
            va.reason,
            va.status,
            va.applied_at,
            s.name AS student_name,
            s.email AS student_email
        FROM volunteer_applications va
        INNER JOIN students s ON va.student_id = s.id
        ORDER BY
            CASE va.status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                ELSE 3
            END,
            va.applied_at DESC";

$result = $conn->query($sql);

$totalPending = 0;
$totalApproved = 0;
$totalRejected = 0;

$countResult = $conn->query(
    "SELECT status, COUNT(*) AS total
     FROM volunteer_applications
     GROUP BY status"
);

while ($row = $countResult->fetch_assoc()) {
    if ($row["status"] === "pending") {
        $totalPending = (int) $row["total"];
    } elseif ($row["status"] === "approved") {
        $totalApproved = (int) $row["total"];
    } elseif ($row["status"] === "rejected") {
        $totalRejected = (int) $row["total"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Volunteer Applications | MRU Fest Admin</title>

<style>
*{box-sizing:border-box}
body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f5fa;color:#242642}
header{background:#191943;color:white;padding:20px 5%;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
.brand{font-size:21px;font-weight:800;color:#efbb61;letter-spacing:1px}
header a{color:white;text-decoration:none;font-size:14px;margin-left:15px}
main{max-width:1250px;margin:0 auto;padding:30px 22px}
h1{margin:0 0 8px;font-size:28px}
.subtitle{color:#777991;margin:0 0 25px;line-height:1.6}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:25px}
.stat{background:white;border:1px solid #e7e8f0;border-radius:12px;padding:20px}
.stat p{margin:0 0 8px;color:#777991;font-size:13px}
.stat strong{font-size:28px;color:#191943}
.panel{background:white;border:1px solid #e7e8f0;border-radius:13px;padding:20px;overflow:hidden}
.panel h2{font-size:19px;margin:0 0 18px}
.notice{padding:13px 15px;border-radius:8px;margin-bottom:18px}
.success{background:#e4f7eb;color:#17653a}
.error{background:#fde9e9;color:#a12323}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:750px}
th,td{text-align:left;padding:14px 12px;border-bottom:1px solid #ececf3;vertical-align:top;font-size:13px}
th{background:#f7f7fb;color:#4d4e6a;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
.reason{max-width:330px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.6}
.status{display:inline-block;padding:6px 10px;border-radius:20px;font-size:12px;font-weight:700;text-transform:capitalize}
.pending{background:#fff1d6;color:#946200}
.approved{background:#e2f6e9;color:#197443}
.rejected{background:#fde8e8;color:#a52626}
.actions{display:flex;gap:7px;flex-wrap:wrap}
button{border:0;border-radius:7px;padding:9px 11px;font-weight:700;cursor:pointer}
.approve-btn{background:#198754;color:white}
.reject-btn{background:#b83232;color:white}
.reviewed{color:#777991}
.empty{text-align:center;padding:35px 10px;color:#777991}
footer{text-align:center;padding:22px;color:#777991;font-size:12px}
@media(max-width:650px){main{padding:22px 13px}.stats{grid-template-columns:1fr}.panel{padding:14px}h1{font-size:24px}header{padding:18px}}
</style>
</head>

<body>
<header>
    <div class="brand">MRU FEST | ADMIN</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Volunteer Applications</h1>
    <p class="subtitle">
        Review student applications and approve or reject volunteer requests.
    </p>

    <?php if ($message !== ""): ?>
        <div class="notice <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat">
            <p>Pending Applications</p>
            <strong><?= $totalPending ?></strong>
        </div>
        <div class="stat">
            <p>Approved Volunteers</p>
            <strong><?= $totalApproved ?></strong>
        </div>
        <div class="stat">
            <p>Rejected Applications</p>
            <strong><?= $totalRejected ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2>All Applications</h2>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Application Reason</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($app = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app["student_name"]) ?></strong><br>
                                <?= htmlspecialchars($app["student_email"]) ?><br>
                                <span class="reviewed">
                                    Student ID: <?= (int) $app["student_id"] ?>
                                </span>
                            </td>
                            <td>
                                <div class="reason"><?= htmlspecialchars($app["reason"]) ?></div>
                            </td>
                            <td><?= htmlspecialchars($app["applied_at"]) ?></td>
                            <td>
                                <span class="status <?= htmlspecialchars($app["status"]) ?>">
                                    <?= htmlspecialchars($app["status"]) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($app["status"] === "pending"): ?>
                                    <div class="actions">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION["volunteer_csrf"]) ?>">
                                            <input type="hidden" name="application_id"
                                                value="<?= (int) $app["id"] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="approve-btn" type="submit">Approve</button>
                                        </form>

                                        <form method="POST"
                                            onsubmit="return confirm('Are you sure you want to reject this application?');">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION["volunteer_csrf"]) ?>">
                                            <input type="hidden" name="application_id"
                                                value="<?= (int) $app["id"] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button class="reject-btn" type="submit">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="reviewed">Already reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                No volunteer applications found yet.
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>© 2026 MRU Fest Management System</footer>
</body>
</html>