<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";
$editEvent = null;

// Add or update an event
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "save") {
        $id = (int)($_POST["event_id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $eventDate = $_POST["event_date"] ?? "";
        $seats = filter_var(
            $_POST["total_seats"] ?? null,
            FILTER_VALIDATE_INT
        );

        $validDate = DateTime::createFromFormat("!Y-m-d", $eventDate);
        $dateIsValid = $validDate && $validDate->format("Y-m-d") === $eventDate;

        if ($title === "" || !$dateIsValid || $seats === false ||
            $seats === null || $seats < 1) {
            $message = "Please enter a valid title, date and seat capacity.";
        } else {
            if ($id > 0) {
                // Do not allow seat capacity below existing registrations
                $check = $conn->prepare(
                    "SELECT COUNT(*) AS total FROM registrations WHERE event_id = ?"
                );
                $check->bind_param("i", $id);
                $check->execute();
                $registered = $check->get_result()->fetch_assoc();
                $check->close();

                if (!$registered) {
                    $message = "Unable to verify existing registrations.";
                } elseif ($seats < (int)$registered["total"]) {
                    $message = "Seat capacity cannot be lower than existing registrations.";
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE events SET title = ?, description = ?, event_date = ?, total_seats = ? WHERE id = ?"
                    );
                    $stmt->bind_param(
                        "sssii",
                        $title,
                        $description,
                        $eventDate,
                        $seats,
                        $id
                    );

                    if ($stmt->execute() && $stmt->affected_rows >= 0) {
                        $message = "Event updated successfully.";
                    } else {
                        $message = "Unable to update the event.";
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO events (title, description, event_date, total_seats) VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "sssi",
                    $title,
                    $description,
                    $eventDate,
                    $seats
                );

                if ($stmt->execute()) {
                    $message = "Event added successfully.";
                } else {
                    $message = "Unable to add the event.";
                }
                $stmt->close();
            }
        }
    }

    if ($action === "delete") {
        $id = (int)($_POST["event_id"] ?? 0);

        if ($id > 0) {
            $check = $conn->prepare(
                "SELECT COUNT(*) AS total FROM registrations WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $registered = $check->get_result()->fetch_assoc();
            $check->close();

            if ((int)$registered["total"] > 0) {
                $message = "This event has registrations and cannot be deleted.";
            } else {
                $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $message = "Event deleted successfully.";
                } else {
                    $message = "Event not found or could not be deleted.";
                }
                $stmt->close();
            }
        }
    }
}

// Load an event into the edit form
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $conn->prepare(
        "SELECT id, title, description, event_date, total_seats FROM events WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// List events with registration counts
$events = $conn->query(
    "SELECT e.id, e.title, e.description, e.event_date, e.total_seats,
            COUNT(r.id) AS registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.id
     GROUP BY e.id, e.title, e.description, e.event_date, e.total_seats
     ORDER BY e.event_date ASC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | MRU Fest</title>
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
            padding: 20px 6%;
        }
        header a { color: white; text-decoration: none; }
        main { max-width: 1100px; margin: 30px auto; padding: 0 16px; }
        .panel {
            background: white;
            padding: 24px;
            border-radius: 10px;
            margin-bottom: 24px;
            box-shadow: 0 3px 12px #0000000d;
        }
        input, textarea {
            width: 100%;
            padding: 11px;
            margin: 7px 0 15px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font: inherit;
        }
        button, .btn {
            display: inline-block;
            padding: 9px 13px;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .primary { background: #1d4ed8; color: white; }
        .edit { background: #e0e7ff; color: #1e3a8a; }
        .delete { background: #fee2e2; color: #991b1b; }
        .cancel { color: #334155; margin-left: 10px; }
        .message {
            padding: 12px;
            background: #e0f2fe;
            border-radius: 6px;
            margin-bottom: 18px;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background: #f8fafc; }
        td form { display: inline; }
        td form button { margin: 2px; }
    </style>
</head>
<body>
<header>
    <h2>MRU Fest | Event Management</h2>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
</header>

<main>
    <?php if ($message !== ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2><?php echo $editEvent ? "Edit Event" : "Add New Event"; ?></h2>

        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="event_id"
                   value="<?php echo (int)($editEvent["id"] ?? 0); ?>">

            <label for="title">Event Title</label>
            <input id="title" name="title" required maxlength="200"
                   value="<?php echo htmlspecialchars($editEvent["title"] ?? ""); ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php
                echo htmlspecialchars($editEvent["description"] ?? "");
            ?></textarea>

            <label for="event_date">Event Date</label>
            <input id="event_date" type="date" name="event_date" required
                   value="<?php echo htmlspecialchars($editEvent["event_date"] ?? ""); ?>">

            <label for="total_seats">Total Seats</label>
            <input id="total_seats" type="number" name="total_seats"
                   min="1" required
                   value="<?php echo htmlspecialchars((string)($editEvent["total_seats"] ?? "50")); ?>">

            <button class="primary" type="submit">
                <?php echo $editEvent ? "Update Event" : "Add Event"; ?>
            </button>

            <?php if ($editEvent): ?>
                <a class="cancel" href="manage_events.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>All Events</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Seats</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($events && $events->num_rows > 0): ?>
                    <?php while ($event = $events->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event["title"]); ?></td>
                            <td><?php echo htmlspecialchars($event["event_date"]); ?></td>
                            <td><?php echo (int)$event["total_seats"]; ?></td>
                            <td><?php echo (int)$event["registered"]; ?></td>
                            <td>
                                <a class="btn edit"
                                   href="manage_events.php?edit=<?php echo (int)$event["id"]; ?>">
                                   Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id"
                                           value="<?php echo (int)$event["id"]; ?>">
                                    <button class="delete" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No events found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>