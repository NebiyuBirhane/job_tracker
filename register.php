<?php
session_start();
$conn = new mysqli("localhost", "root", "", "job_tracker");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = ""; // "success" or "error"
$name_value = "";
$email_value = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address!";
        $message_type = "error";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $message = "Email already registered!";
            $message_type = "error";
            $email_value = $email;
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $message = "Registration successful! You can now <a href='login.php' style='color:#10b981;font-weight:bold;'>log in</a>.";
                $message_type = "success";
                $name_value = $email_value = "";
            } else {
                $message = "Registration failed: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Join Job Track | Premium Career Intelligence</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400;600&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "secondary":"#48626e","on-secondary":"#ffffff","primary-container":"#005fb8",
                "on-surface-variant":"#424752","secondary-container":"#cbe7f5","surface-container-low":"#f2f4f6",
                "on-primary":"#ffffff","surface-container-high":"#e6e8ea","outline-variant":"#c2c6d4",
                "background":"#f8f9fb","error":"#ba1a1a","primary":"#00488d","outline":"#727783",
                "surface":"#f8f9fb","error-container":"#ffdad6","surface-container":"#eceef0"
            },
            fontFamily: {"headline":["Manrope"],"body":["Inter"],"label":["Inter"]},
            borderRadius: {"DEFAULT":"0.125rem","lg":"0.25rem","xl":"0.5rem","full":"0.75rem"}
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.glass-effect { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); }
.primary-gradient { background: linear-gradient(135deg, #00488d 0%, #005fb8 100%); }
.font-headline { font-family: 'Manrope', sans-serif; font-weight: 800; }
.font-label { font-family: 'Inter', sans-serif; font-weight: 600; }
</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl shadow-sm">
<div class="flex items-center justify-between px-4 md:px-8 h-16 md:h-20 w-full max-w-[1440px] mx-auto">
<div class="text-xl md:text-2xl font-black text-primary tracking-tighter font-headline">Job Track</div>
<div class="hidden md:flex gap-6 items-center">
<span class="text-on-surface-variant font-medium font-label">Already registered?</span>
<a class="text-primary font-bold hover:text-primary-container transition-all font-label" href="login.php">Log In</a>
</div>
</div>
</header>

<main class="flex-grow flex items-center justify-center px-6 pt-28 md:pt-32 pb-20">
<div class="w-full max-w-lg">
<div class="bg-surface-container-lowest p-8 md:p-10 lg:p-12 rounded-xl shadow-[0_40px_80px_-15px_rgba(25,28,30,0.04)] border border-outline-variant/10">

<!-- MESSAGE DISPLAY -->
<?php if($message): ?>
<div class="mb-8 p-5 rounded-xl border-2 <?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-800' : 'bg-red-100 border-red-400 text-red-800'; ?> text-center font-semibold">
<span class="material-symbols-outlined text-2xl block mx-auto mb-2 <?php echo $message_type === 'success' ? 'text-green-600' : 'text-red-600'; ?>">
<?php echo $message_type === 'success' ? 'check_circle' : 'error'; ?>
</span>
<?php echo $message; ?>
</div>
<?php endif; ?>

<div class="mb-10 text-center">
<h2 class="font-headline font-bold text-2xl md:text-3xl text-on-surface mb-2">Create Account</h2>
<p class="text-on-surface-variant font-medium">Join thousands tracking their careers</p>
</div>

<form method="POST" class="flex flex-col gap-6">
<!-- Name Field -->
<div class="flex flex-col gap-2">
<label class="font-label font-semibold text-sm text-on-surface-variant ml-1 uppercase tracking-wider" for="name">Full Name</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors">person</span>
<input class="w-full pl-12 pr-4 py-4 bg-surface-container-low border border-outline-variant/30 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition-all placeholder:text-on-surface-variant/50 font-medium" 
       id="name" 
       name="name" 
       placeholder="John Doe" 
       value="<?php echo htmlspecialchars($name_value); ?>" 
       required/>
</div>
</div>

<!-- Email Field -->
<div class="flex flex-col gap-2">
<label class="font-label font-semibold text-sm text-on-surface-variant ml-1 uppercase tracking-wider" for="email">Email Address</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors">mail</span>
<input class="w-full pl-12 pr-4 py-4 bg-surface-container-low border border-outline-variant/30 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition-all placeholder:text-on-surface-variant/50 font-medium" 
       id="email" 
       name="email" 
       type="email"
       placeholder="john@example.com" 
       value="<?php echo htmlspecialchars($email_value); ?>" 
       required/>
</div>
</div>

<!-- Password Field -->
<div class="flex flex-col gap-2">
<label class="font-label font-semibold text-sm text-on-surface-variant ml-1 uppercase tracking-wider" for="password">Password</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors">lock</span>
<input class="w-full pl-12 pr-14 py-4 bg-surface-container-low border border-outline-variant/30 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition-all placeholder:text-on-surface-variant/50 font-medium password-input" 
       id="password" 
       name="password" 
       type="password"
       placeholder="••••••••" 
       minlength="6"
       required/>
<button class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-all p-1 rounded-full hover:bg-surface-container-low password-toggle" type="button" data-target="password">
<span class="material-symbols-outlined text-xl visibility-icon">visibility</span>
</button>
</div>
<p class="text-xs text-on-surface-variant mt-1 ml-1">Minimum 6 characters required</p>
</div>

<!-- Terms -->
<div class="flex items-start gap-3 px-1">
<input class="mt-1 w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" id="terms" type="checkbox" required/>
<label class="text-sm text-on-surface-variant leading-tight cursor-pointer select-none flex-1" for="terms">
I agree to the <a href="#" class="text-primary font-semibold hover:underline">Terms</a> and <a href="#" class="text-primary font-semibold hover:underline">Privacy Policy</a>
</label>
</div>

<!-- Submit Button -->
<button class="primary-gradient text-on-primary font-headline font-bold py-5 rounded-xl shadow-xl hover:shadow-2xl active:scale-95 transition-all flex items-center justify-center gap-3 mt-4" 
        type="submit" 
        name="register">
<span>Create Account</span>
<span class="material-symbols-outlined text-xl">arrow_forward</span>
</button>
</form>

<div class="md:hidden mt-8 pt-8 border-t border-outline-variant/30 text-center">
<p class="text-on-surface-variant font-medium mb-2">Already have an account?</p>
<a class="block text-primary font-bold font-label hover:underline text-lg" href="login.php">Log In →</a>
</div>
</div>

<!-- Trust badges -->
<div class="mt-12 flex justify-center gap-8 text-on-surface-variant font-label text-[11px] uppercase tracking-[0.2em]">
<span class="flex items-center gap-2"><span class="material-symbols-outlined text-sm">encrypted</span>256-bit AES</span>
<span class="flex items-center gap-2"><span class="material-symbols-outlined text-sm">cloud_done</span>Secure Cloud</span>
</div>
</div>
</main>

<footer class="py-12 px-8">
<div class="max-w-[1440px] mx-auto flex flex-col md:flex-row justify-between items-center gap-4 opacity-60">
<span class="text-xs font-medium font-label text-on-surface-variant">© 2024 Job Track. All rights reserved.</span>
<div class="flex gap-4">
<span class="material-symbols-outlined text-sm">security</span>
<span class="material-symbols-outlined text-sm">api</span>
</div>
</div>
</footer>

<!-- PASSWORD VISIBILITY TOGGLE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('.visibility-icon');
            if (input && icon) {
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
            }
        });
    });
});
</script>

</body>
</html>