<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = filter_input(INPUT_POST, "student_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if ($student_id && in_array($action, ["approved", "rejected"], true)) {

        // Get the student's current status
        $check = $conn->prepare(
            "SELECT approval_status FROM students WHERE id = ?"
        );
        $check->bind_param("i", $student_id);
        $check->execute();
        $studentResult = $check->get_result();
        $currentStudent = $studentResult->fetch_assoc();
        $check->close();

        if (!$currentStudent) {
            $message = "Student account not found.";
            $messageType = "error";
        } elseif ($currentStudent["approval_status"] === $action) {
            $message = "Student is already " . $action . ".";
            $messageType = "info";
        } else {
            $stmt = $conn->prepare(
                "UPDATE students SET approval_status = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $action, $student_id);

            if ($stmt->execute()) {
                $message = $action === "approved"
                    ? "Student approved successfully."
                    : "Student rejected successfully.";

                $messageType = "success";
            } else {
                $message = "Unable to update student status. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }
    } else {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    }
}

$result = $conn->query(
    "SELECT id, name, email, approval_status, created_at
     FROM students
     ORDER BY
       CASE approval_status
         WHEN 'pending' THEN 1
         WHEN 'approved' THEN 2
         ELSE 3
       END,
       created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | MRU Fest</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #1f2937;
        }

        header {
            background: #172554;
            color: white;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
            font-size: 22px;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        header a:hover {
            color: #f4bd63;
        }

        main {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #1d4ed8;
            text-decoration: none;
            font-weight: bold;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid #e5eaf2;
            overflow-x: auto;
        }

        .panel h1 {
            margin: 0 0 10px;
            color: #172554;
            font-size: 27px;
        }

        .description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .notice {
            padding: 13px 15px;
            border-radius: 7px;
            margin: 15px 0 22px;
            font-size: 14px;
        }

        .notice.success {
            background: #dcfce7;
            color: #166534;
        }

        .notice.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .notice.info {
            background: #e0f2fe;
            color: #075985;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 680px;
        }

        th, td {
            text-align: left;
            padding: 15px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        th {
            background: #eff3ff;
            color: #172554;
            font-weight: bold;
        }

        tbody tr:hover {
            background: #fafbff;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        button {
            border: 0;
            padding: 9px 12px;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            font-size: 12px;
            font-weight: bold;
            transition: 0.2s;
        }

        .approve {
            background: #15803d;
        }

        .approve:hover {
            background: #166534;
        }

        .reject {
            background: #b91c1c;
        }

        .reject:hover {
            background: #991b1b;
        }

        .current-status {
            color: #64748b;
            font-size: 12px;
        }

        footer {
            text-align: center;
            padding: 24px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px;
            }

            header h2 {
                font-size: 19px;
            }

            main {
                margin-top: 24px;
                padding: 0 12px;
            }

            .panel {
                padding: 17px;
            }

            .panel h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
<header>
    <h2>MRU Fest | Manage Students</h2>
    <a href="admin_dashboard.php">← Admin Dashboard</a>
</header>

<main>
    <a class="back" href="admin_dashboard.php">← Back to Dashboard</a>

    <div class="panel">
        <h1>Student Approval</h1>
        <p class="description">
            Review student accounts and manage their access to MRU Fest.
            You can change a student's status whenever required.
        </p>

        <?php if ($message !== ""): ?>
            <div class="notice <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($student = $result->fetch_assoc()): ?>
                    <?php
                        $status = $student["approval_status"];
                        $studentId = (int) $student["id"];
                    ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <span class="status <?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>">
                                <?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>
                            </span>
                        </td>

                        <td>
                            <div class="actions">

                                <?php if ($status !== "approved"): ?>
                                    <form method="POST">
                                        <input type="hidden" name="student_id"
                                               value="<?= $studentId ?>">
                                        <input type="hidden" name="action"
                                               value="approved">

                                        <button class="approve" type="submit">
                                            Approve
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($status !== "rejected"): ?>
                                    <form method="POST">
                                        <input type="hidden" name="student_id"
                                               value="<?= $studentId ?>">
                                        <input type="hidden" name="action"
                                               value="rejected">

                                        <button class="reject" type="submit">
                                            Reject
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($status === "approved"): ?>
                                    <span class="current-status">Access granted</span>
                                <?php elseif ($status === "rejected"): ?>
                                    <span class="current-status">Access denied</span>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="4">No student accounts found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<footer>
    &copy; 2026 MRU Fest Management System. All rights reserved.
</footer>
</body>
</html>