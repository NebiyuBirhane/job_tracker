<?php
session_start();
$conn = new mysqli("localhost", "root", "", "job_tracker");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$login_error = "";
$email_value = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    } else {
        $login_error = "Invalid email or password!";
        $email_value = $email;
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login | Job Track</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "secondary":"#48626e","on-secondary":"#ffffff","primary-container":"#005fb8",
                "on-surface-variant":"#424752","secondary-container":"#cbe7f5","surface-container-low":"#f2f4f6",
                "primary-fixed":"#d6e3ff","on-primary":"#ffffff","surface-container-high":"#e6e8ea",
                "outline-variant":"#c2c6d4","background":"#f8f9fb","error":"#ba1a1a","primary":"#00488d",
                "outline":"#727783","surface":"#f8f9fb","error-container":"#ffdad6"
            },
            fontFamily: {"headline":["Manrope"],"body":["Inter"],"label":["Inter"]}
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.font-headline { font-family: 'Manrope', sans-serif; font-weight: 800; }
</style>
</head>
<body class="bg-background text-on-surface antialiased min-h-screen flex flex-col">
<main class="flex-grow flex items-center justify-center px-4 py-12">
<div class="w-full max-w-[1100px] grid grid-cols-1 md:grid-cols-2 bg-white rounded-2xl overflow-hidden shadow-2xl">

<!-- Left Side - Info Panel -->
<div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-blue-800 text-white relative overflow-hidden">
    <div class="relative z-10">
        <div class="text-3xl font-black tracking-tighter mb-12 font-headline">Job Track</div>
        
        <div class="space-y-8">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-outlined text-2xl">work</span>
                    <h3 class="text-lg font-bold">Track Applications</h3>
                </div>
                <p class="text-blue-100 text-sm ml-9">Monitor all your job applications in one place</p>
            </div>
            
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-outlined text-2xl">analytics</span>
                    <h3 class="text-lg font-bold">View Statistics</h3>
                </div>
                <p class="text-blue-100 text-sm ml-9">See your progress with real-time analytics</p>
            </div>
            
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-outlined text-2xl">notifications_active</span>
                    <h3 class="text-lg font-bold">Stay Organized</h3>
                </div>
                <p class="text-blue-100 text-sm ml-9">Never miss an interview or follow-up</p>
            </div>
        </div>
    </div>
    
    <div class="relative z-10 mt-12">
        <div class="flex gap-2">
            <div class="w-2 h-2 rounded-full bg-white"></div>
            <div class="w-2 h-2 rounded-full bg-white/40"></div>
            <div class="w-2 h-2 rounded-full bg-white/20"></div>
        </div>
        <p class="text-blue-100 text-xs mt-4">Join thousands of job seekers tracking their success</p>
    </div>
    
    <!-- Decorative circles -->
    <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/5 rounded-full"></div>
    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
</div>

<!-- Right Side - Login Form -->
<div class="p-8 md:p-12 flex flex-col justify-center">
    <!-- Mobile Logo -->
    <div class="md:hidden text-center mb-8">
        <div class="text-3xl font-black text-blue-600 mb-2 font-headline">Job Track</div>
        <p class="text-sm text-gray-500">Sign in to your account</p>
    </div>
    
    <!-- Error Message -->
    <?php if($login_error): ?>
    <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
        <span class="material-symbols-outlined text-red-600 text-lg">error</span>
        <span class="text-sm text-red-700"><?php echo htmlspecialchars($login_error); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Welcome Text -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
        <p class="text-sm text-gray-500">Enter your credentials to access your dashboard</p>
    </div>
    
    <!-- Login Form -->
    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-2" for="email">EMAIL</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">mail</span>
                <input class="w-full h-12 pl-10 pr-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white transition-all outline-none" 
                       id="email" 
                       name="email" 
                       placeholder="name@company.com" 
                       value="<?php echo htmlspecialchars($email_value); ?>" 
                       required 
                       type="email"/>
            </div>
        </div>
        
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-2" for="password">PASSWORD</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">lock</span>
                <input class="w-full h-12 pl-10 pr-10 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white transition-all outline-none password-input" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••" 
                       required 
                       type="password"/>
                <button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors password-toggle" 
                        type="button">
                    <span class="material-symbols-outlined text-lg visibility-icon">visibility</span>
                </button>
            </div>
        </div>
        
        <button class="w-full h-12 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-6" 
                type="submit" 
                name="login">
            <span>Sign In</span>
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </button>
    </form>
    
    <!-- Sign Up Link -->
    <div class="mt-8 text-center">
        <p class="text-sm text-gray-600">
            Don't have an account? 
            <a class="text-blue-600 font-semibold hover:underline ml-1" href="register.php">Create Account</a>
        </p>
    </div>
    
    <!-- Demo Credentials (Optional - remove in production) -->
    <div class="mt-6 pt-6 border-t border-gray-100">
        <p class="text-xs text-gray-400 text-center mb-2">Demo Credentials</p>
        <div class="text-xs text-gray-400 text-center space-y-1">
            <p>Email: demo@example.com</p>
            <p>Password: demo123</p>
        </div>
    </div>
</div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.relative').querySelector('.password-input');
            const icon = this.querySelector('.visibility-icon');
            
            if (input && icon) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                icon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
            }
        });
    });
});
</script>

</body>
</html>