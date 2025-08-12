<?php
session_start();

// Database connection
require 'db.php';

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Generate a unique virtual number
    do {
        $virtual_number = '+1-' . substr(str_shuffle('0123456789'), 0, 3) . '-' . substr(str_shuffle('0123456789'), 0, 3) . '-' . substr(str_shuffle('0123456789'), 0, 4);
        $stmt = $conn->prepare("SELECT id FROM users WHERE virtual_number = ?");
        $stmt->execute([$virtual_number]);
    } while ($stmt->fetch()); // Ensure the number is unique

    // Insert user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, password, virtual_number) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $virtual_number])) {
        echo "<script>alert('Signup successful! Please login.');</script>";
    } else {
        echo "<script>alert('Error: Username already exists.');</script>";
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from the database
    $stmt = $conn->prepare("SELECT id, password, virtual_number FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['virtual_number'] = $user['virtual_number'];
    } else {
        echo "<script>alert('Invalid credentials!');</script>";
    }
}

// Handle Call Forwarding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_forwarding'])) {
    $forward_number = $_POST['forward_number'];
    $user_id = $_SESSION['user_id'];

    // Insert or update call forwarding
    $stmt = $conn->prepare("INSERT INTO call_forwarding (user_id, forward_number) VALUES (?, ?) ON DUPLICATE KEY UPDATE forward_number = ?");
    $stmt->execute([$user_id, $forward_number, $forward_number]);
    echo "<script>alert('Call forwarding set to: $forward_number');</script>";
}

// Handle Voicemail Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_voicemail'])) {
    $voicemail_message = $_POST['voicemail_message'];
    $user_id = $_SESSION['user_id'];

    // Insert or update voicemail
    $stmt = $conn->prepare("INSERT INTO voicemail (user_id, message) VALUES (?, ?) ON DUPLICATE KEY UPDATE message = ?");
    $stmt->execute([$user_id, $voicemail_message, $voicemail_message]);
    echo "<script>alert('Voicemail message set!');</script>";
}

// Fetch user details if logged in
$virtual_number = '';
$forward_number = '';
$voicemail_message = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch virtual number
    $stmt = $conn->prepare("SELECT virtual_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $virtual_number = $stmt->fetchColumn();

    // Fetch call forwarding
    $stmt = $conn->prepare("SELECT forward_number FROM call_forwarding WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $forward_number = $stmt->fetchColumn();

    // Fetch voicemail message
    $stmt = $conn->prepare("SELECT message FROM voicemail WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $voicemail_message = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Phone System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 320px;
            backdrop-filter: blur(10px);
        }
        h1 {
            color: #2575fc;
            margin-bottom: 20px;
        }
        .form-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .form-toggle button {
            background: none;
            border: none;
            color: #2575fc;
            font-size: 16px;
            cursor: pointer;
            padding: 10px;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        .form-toggle button.active {
            font-weight: bold;
            border-bottom: 2px solid #2575fc;
        }
        .form-toggle button:hover {
            color: #1a5bbf;
        }
        .form-container {
            display: none;
        }
        .form-container.active {
            display: block;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        input:focus, textarea:focus {
            border-color: #2575fc;
        }
        .btn {
            background: #2575fc;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .btn:hover {
            background: #1a5bbf;
            transform: translateY(-2px);
        }
        p {
            color: #555;
        }
        .phone-number {
            font-size: 1.2em;
            margin: 20px 0;
            color: #2575fc;
            background: rgba(37, 117, 252, 0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #2575fc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Our Business Phone System</h1>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Login and Signup Forms -->
            <div class="form-toggle">
                <button id="login-toggle" class="active">Login</button>
                <button id="signup-toggle">Sign Up</button>
            </div>
            <div id="login-form" class="form-container active">
                <form method="POST" action="">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
            </div>
            <div id="signup-form" class="form-container">
                <form method="POST" action="">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="signup" class="btn">Sign Up</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Virtual Number, Call Forwarding, and Voicemail -->
            <div class="phone-number">
                Your Virtual Phone Number: <strong><?php echo htmlspecialchars($virtual_number); ?></strong>
            </div>
            <form method="POST" action="">
                <label for="forward_number">Call Forwarding:</label>
                <input type="text" id="forward_number" name="forward_number" placeholder="Enter phone number" value="<?php echo htmlspecialchars($forward_number); ?>">
                <button type="submit" name="set_forwarding" class="btn">Save</button>
            </form>
            <form method="POST" action="">
                <label for="voicemail_message">Voicemail Message:</label>
                <textarea id="voicemail_message" name="voicemail_message" placeholder="Type your voicemail message here..."><?php echo htmlspecialchars($voicemail_message); ?></textarea>
                <button type="submit" name="set_voicemail" class="btn">Save</button>
            </form>
            <a href="logout.php" class="btn">Logout</a>
        <?php endif; ?>
    </div>

    <script>
        // Toggle between Login and Signup forms
        const loginToggle = document.getElementById('login-toggle');
        const signupToggle = document.getElementById('signup-toggle');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');

        loginToggle.addEventListener('click', () => {
            loginToggle.classList.add('active');
            signupToggle.classList.remove('active');
            loginForm.classList.add('active');
            signupForm.classList.remove('active');
        });

        signupToggle.addEventListener('click', () => {
            signupToggle.classList.add('active');
            loginToggle.classList.remove('active');
            signupForm.classList.add('active');
            loginForm.classList.remove('active');
        });
    </script>
</body>
</html>
