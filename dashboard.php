<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require 'db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT virtual_number FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$virtual_number = $user['virtual_number'];

// Handle call forwarding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_number'])) {
    $forward_number = $_POST['forward_number'];
    $stmt = $conn->prepare("INSERT INTO call_forwarding (user_id, forward_number) VALUES (?, ?)");
    $stmt->execute([$user_id, $forward_number]);
}

// Handle voicemail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voicemail_message'])) {
    $voicemail_message = $_POST['voicemail_message'];
    $stmt = $conn->prepare("INSERT INTO voicemail (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $voicemail_message]);
}

// Fetch call forwarding and voicemail settings
$forward_number = '';
$voicemail_message = '';

$stmt = $conn->prepare("SELECT forward_number FROM call_forwarding WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$forward_number = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT message FROM voicemail WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$voicemail_message = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .dashboard {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 800px;
            backdrop-filter: blur(10px);
        }
        h1 {
            color: #2575fc;
            margin-bottom: 20px;
        }
        .phone-number {
            font-size: 1.5em;
            margin: 20px 0;
            color: #2575fc;
            background: rgba(37, 117, 252, 0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #2575fc;
        }
        .settings {
            margin-top: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #2575fc;
            font-weight: bold;
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
        a {
            color: #2575fc;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Dashboard</h1>
        <div class="phone-number">
            Your Virtual Phone Number: <strong><?php echo htmlspecialchars($virtual_number); ?></strong>
        </div>
        <div class="settings">
            <h2>Call Forwarding</h2>
            <form method="POST" action="dashboard.php">
                <label for="forward_number">Forward Calls To:</label>
                <input type="text" id="forward_number" name="forward_number" placeholder="Enter phone number" value="<?php echo htmlspecialchars($forward_number); ?>">
                <button type="submit" class="btn">Save</button>
            </form>
        </div>
        <div class="settings">
            <h2>Voicemail</h2>
            <form method="POST" action="dashboard.php">
                <label for="voicemail_message">Voicemail Message:</label>
                <textarea id="voicemail_message" name="voicemail_message" placeholder="Type your voicemail message here..."><?php echo htmlspecialchars($voicemail_message); ?></textarea>
                <button type="submit" class="btn">Save</button>
            </form>
        </div>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</body>
</html>
