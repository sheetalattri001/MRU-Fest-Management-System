<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

$message = "";
$loginType = $_POST["login_type"] ?? "student";

if (!in_array($loginType, ["student", "admin"], true)) {
    $loginType = "student";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identity = trim($_POST["identity"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($loginType === "student") {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, approval_status
             FROM students WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $identity);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student && password_verify($password, $student["password"])) {
            if ($student["approval_status"] === "pending") {
                $message = "Your account is awaiting admin approval.";
            } elseif ($student["approval_status"] === "rejected") {
                $message = "Your account was not approved. Please contact the administrator.";
            } else {
                session_regenerate_id(true);
                $_SESSION["student_id"] = $student["id"];
                $_SESSION["student_name"] = $student["name"];
                $_SESSION["student_email"] = $student["email"];
                $_SESSION["role"] = "student";

                header("Location: dashboard.php");
                exit;
            }
        } else {
            $message = "Invalid student email or password.";
        }
    } else {
        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM admins WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $identity);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];
            $_SESSION["role"] = "admin";

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $message = "Invalid admin username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | MRU Fest</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #101d3a, #343f78);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.card {
    background: #fff;
    padding: 32px;
    border-radius: 16px;
    width: 410px;
    max-width: 100%;
    box-shadow: 0 12px 35px rgba(0,0,0,.25);
}

.brand {
    text-align: center;
    color: #b47a21;
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 2px;
}

h2 {
    text-align: center;
    color: #17264b;
    margin-bottom: 8px;
}

.subtitle {
    color: #666;
    text-align: center;
    font-size: 14px;
    margin-bottom: 24px;
}

.role-options {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
}

.role-options label {
    flex: 1;
    cursor: pointer;
}

.role-options input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.role-options span {
    display: block;
    padding: 13px 8px;
    text-align: center;
    border: 1px solid #ccd1dc;
    border-radius: 8px;
    color: #263353;
    font-size: 14px;
    font-weight: bold;
}

.role-options input:checked + span {
    background: #17264b;
    border-color: #17264b;
    color: #fff;
}

.role-options input:focus-visible + span {
    outline: 3px solid #e8b85f;
}

.field-label {
    display: block;
    margin-top: 15px;
    font-size: 14px;
    color: #253353;
    font-weight: bold;
}

.field-input {
    width: 100%;
    padding: 12px;
    margin-top: 7px;
    border: 1px solid #ccd1dc;
    border-radius: 7px;
    font-size: 15px;
}

.field-input:focus {
    outline: 2px solid #d9b46b;
    border-color: #d9b46b;
}

button {
    width: 100%;
    margin-top: 23px;
    padding: 13px;
    border: 0;
    border-radius: 7px;
    background: #e8b85f;
    color: #17264b;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #dca744;
}

.message {
    background: #fff1f0;
    color: #9b2525;
    padding: 11px;
    border-radius: 7px;
    font-size: 14px;
    text-align: center;
}

.links {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    line-height: 2;
}

a {
    color: #263b70;
}
</style>
</head>

<body>
<main class="card">

    <div class="brand">MRU FEST</div>
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your university fest portal</p>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <div class="role-options">
            <label>
                <input type="radio" name="login_type"
                       value="student"
                       <?= $loginType === "student" ? "checked" : "" ?>
                       onchange="updateLoginFields()" >
                <span>Student Login</span>
            </label>

            <label>
                <input type="radio" name="login_type"
                       value="admin"
                       <?= $loginType === "admin" ? "checked" : "" ?>
                       onchange="updateLoginFields()">
                <span>Admin Login</span>
            </label>
        </div>

        <label class="field-label" id="identityLabel" for="identity">
            <?= $loginType === "student" ? "Student Email" : "Admin Username" ?>
        </label>

        <input class="field-input"
               type="<?= $loginType === "student" ? "email" : "text" ?>"
               id="identity"
               name="identity"
               placeholder="<?= $loginType === "student" ? "Enter your student email" : "Enter admin username" ?>"
               autocomplete="username"
               required>

        <label class="field-label" for="password">Password</label>
        <input class="field-input"
               type="password"
               id="password"
               name="password"
               placeholder="Enter your password"
               autocomplete="current-password"
               required>

        <button type="submit">Sign In</button>
    </form>

    <div class="links">
        New student? <a href="signup.php">Create an account</a>
        <br>
        <a href="index.php">← Back to Home</a>
    </div>
</main>

<script>
function updateLoginFields() {
    const selected = document.querySelector(
        'input[name="login_type"]:checked'
    ).value;

    const identity = document.getElementById("identity");
    const label = document.getElementById("identityLabel");

    if (selected === "student") {
        label.textContent = "Student Email";
        identity.type = "email";
        identity.placeholder = "Enter your student email";
    } else {
        label.textContent = "Admin Username";
        identity.type = "text";
        identity.placeholder = "Enter admin username";
    }

    identity.value = "";
}
</script>
</body>
</html>