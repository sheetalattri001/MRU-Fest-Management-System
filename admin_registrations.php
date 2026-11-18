<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$sql = "SELECT
            s.name AS student_name,
            s.email,
            e.title AS event_title,
            r.registration_code,
            r.registered_at
        FROM registrations r
        INNER JOIN students s ON r.student_id = s.id
        INNER JOIN events e ON r.event_id = e.id
        ORDER BY r.registered_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load registrations. Please check the database tables.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registrations | MRU Fest</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #1f2937;
        }

        header {
            background: #172554;
            color: white;
            padding: 22px 6%;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        main {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000d;
        }

        .summary {
            color: #475569;
            margin-bottom: 20px;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            min-width: 750px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #f8fafc; }

        .empty {
            padding: 25px;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>

<body>
<header>
    <h2>MRU Fest | Student Registrations</h2>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
</header>

<main>
    <section class="panel">
        <h2>Registered Students</h2>

        <p class="summary">
            Total Registrations: <?php echo $result->num_rows; ?>
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Event</th>
                        <th>Registration Code</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["student_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["email"]); ?></td>
                            <td><?php echo htmlspecialchars($row["event_title"]); ?></td>
                            <td><?php echo htmlspecialchars($row["registration_code"]); ?></td>
                            <td><?php echo htmlspecialchars($row["registered_at"]); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No student registrations found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>