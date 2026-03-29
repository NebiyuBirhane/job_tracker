<?php
session_start();
$conn = new mysqli("localhost", "root", "", "job_tracker");

$message = "";
$message_type = ""; // "success" or "error"

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['send_reset'])) {
    $email = trim($_POST['email']);
    
    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(16));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
            $stmt->bind_param("sss", $token, $expiry, $email);
            if ($stmt->execute()) {
                // Email sending logic here
                $reset_link = "http://localhost/reset_password.php?token=$token"; // Update with your domain
                // mail($email, "Password Reset", "Click this link to reset: $reset_link");
                
                $message = "If an account exists for this email, check your inbox for the reset link!";
                $message_type = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $message_type = "error";
            }
        } else {
            // Security: same message whether account exists or not
            $message = "If an account exists for this email, check your inbox for the reset link!";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Forgot Password - Career Concierge</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: {
                            'DEFAULT': '#ffffff',
                            'container-lowest': '#f8fafc',
                        },
                        'on-surface': '#0f172a',
                        'on-surface-variant': '#475569',
                        primary: '#3b82f6',
                        'primary-container': '#dbeafe',
                        outline: '#94a3b8'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined">
    <style>
        .font-headline { font-family: 'Inter', sans-serif; font-weight: 800; }
        .font-label { font-family: 'Inter', sans-serif; font-weight: 600; }
        .font-body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-family: 'Material Symbols Outlined'; font-weight: normal; font-style: normal; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-body min-h-screen flex flex-col">
    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="mb-12 ml-4">
                <h2 class="font-headline font-extrabold text-gray-900 text-4xl md:text-5xl tracking-tighter leading-tight mb-4">
                    Forgot <br/>Password?
                </h2>
                <p class="text-gray-600 text-lg leading-relaxed max-w-[90%]">
                    Enter the email associated with your account and we'll send you a link to reset your password.
                </p>
            </div>

            <!-- Form Card -->
            <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl border border-gray-100">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border-l-4 <?php echo $message_type === 'success' ? 'bg-green-50 border-green-400 text-green-800' : 'bg-red-50 border-red-400 text-red-800'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" method="POST">
                    <div class="space-y-2">
                        <label class="block font-label text-sm font-semibold tracking-wider text-gray-600 uppercase" for="email">
                            Email Address
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <span class="material-symbols-outlined text-xl">mail</span>
                            </div>
                            <input 
                                class="w-full bg-gray-50 border-0 border-b-2 border-gray-200 focus:border-blue-500 focus:ring-0 px-12 py-4 text-gray-900 placeholder:text-gray-400 transition-all duration-300 rounded-t-lg hover:border-gray-300" 
                                id="email" 
                                name="email" 
                                placeholder="name@company.com" 
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required 
                                type="email"
                                autocomplete="email"
                            />
                        </div>
                    </div>

                    <button 
                        class="w-full bg-blue-600 text-white font-headline font-bold py-4 px-6 rounded-xl flex items-center justify-center gap-3 hover:bg-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl active:scale-[0.98] active:shadow-md" 
                        type="submit" 
                        name="send_reset"
                    >
                        Send Reset Link
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <a 
                        class="inline-flex items-center gap-2 font-label text-sm font-semibold tracking-wide text-blue-600 hover:text-blue-700 hover:underline underline-offset-4 transition-all" 
                        href="login.php"
                    >
                        <span class="material-symbols-outlined text-base">login</span>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>