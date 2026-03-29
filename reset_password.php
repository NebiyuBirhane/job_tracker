<?php
$conn = new mysqli("localhost", "root", "", "job_tracker");
$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("Invalid or expired token!");
    }

    if (isset($_POST['new_password'])) {
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?");
        $stmt->bind_param("si", $password, $user['id']);
        if ($stmt->execute()) {
            $message = "Password updated successfully! You can <a href='login.php'>login now</a>.";
        }
    }
} else {
    die("No token provided!");
}
?>

<form method="POST">
    Enter new password: <input type="password" name="new_password" required>
    <button type="submit">Set New Password</button>
</form>

<?php if($message) echo "<p>$message</p>"; ?>