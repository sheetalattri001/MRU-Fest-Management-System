<?php
require_once "db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (
        $name === "" ||
        strlen($name) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150 ||
        strlen($password) < 8
    ) {
        $message = "Please enter valid details. Password must be at least 8 characters.";
        $messageType = "error";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM students WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "This email is already registered. Please log in.";
            $messageType = "error";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO students
                (name, email, password, approval_status)
                VALUES (?, ?, ?, 'pending')"
            );
            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $message = "Signup submitted successfully! Your account is pending Admin approval. You can log in after approval.";
                $messageType = "success";
            } else {
                $message = "Unable to create your account. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Signup | MRU Fest</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #101d3b, #344f83);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: #fff;
            padding: 32px;
            border-radius: 16px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.2);
        }

        .brand {
            text-align: center;
            color: #b88635;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        h2 {
            text-align: center;
            color: #172b50;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #68758a;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-top: 16px;
            color: #243654;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #d4dbe6;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #a9bce1;
            border-color: #344f83;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            border: 0;
            border-radius: 7px;
            background: #172b50;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #294779;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .success {
            background: #e8f7ed;
            color: #176536;
        }

        .error {
            background: #fff0f0;
            color: #a32626;
        }

        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            line-height: 2;
        }

        a {
            color: #294779;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="brand">MRU FEST</div>

        <h2>Create Student Account</h2>

        <p class="subtitle">
            Join campus events and competitions.
            Your account will require Admin approval.
        </p>

        <?php if ($message !== ""): ?>
            <p class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                autocomplete="name"
                placeholder="Enter your full name"
                required
            >

            <label for="email">University Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                placeholder="Enter your email address"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                placeholder="Minimum 8 characters"
                required
            >

            <button type="submit">Submit for Approval</button>
        </form>

        <div class="links">
            Already have an account?
            <a href="login.php">Student Login</a>
            <br>
            <a href="index.php">Back to Home</a>
        </div>
    </main>
</body>
</html>