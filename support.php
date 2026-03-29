<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "job_tracker");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Get user info
$user_name = "User";
$user_email = "";
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['name'];
        $user_email = $row['email'];
    }
    $stmt->close();
}

// Handle support ticket submission
$success_message = "";
$error_message = "";

if (isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    $errors = [];
    
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // Create support_tickets table if not exists
        $create_table = "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ticket_number VARCHAR(20) UNIQUE,
            subject VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            priority VARCHAR(20) NOT NULL,
            status VARCHAR(20) DEFAULT 'open',
            admin_reply TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_table);
        
        // Generate unique ticket number
        $ticket_number = "TKT-" . strtoupper(uniqid());
        
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, ticket_number, subject, category, message, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'open')");
        if ($stmt) {
            $stmt->bind_param("isssss", $user_id, $ticket_number, $subject, $category, $message, $priority);
            
            if ($stmt->execute()) {
                $success_message = "Ticket submitted successfully! Ticket #: " . $ticket_number;
                $_POST = array();
            } else {
                $error_message = "Failed to submit ticket. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get user's recent tickets
$recent_tickets = [];
$stmt = $conn->prepare("SELECT ticket_number, subject, category, priority, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_tickets[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Support Center | Job Saved</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "secondary": "#48626e",
                    "surface-container-lowest": "#ffffff",
                    "primary-container": "#005fb8",
                    "secondary-container": "#cbe7f5",
                    "surface-container-low": "#f2f4f6",
                    "surface-container-highest": "#e0e3e5",
                    "outline-variant": "#c2c6d4",
                    "on-surface": "#191c1e",
                    "tertiary-container": "#1d6e25",
                    "background": "#f8f9fb",
                    "primary": "#00488d",
                    "tertiary-fixed": "#a3f69c",
                    "error-container": "#ffdad6",
                    "tertiary": "#005412",
                },
                fontFamily: {
                    "headline": ["Manrope"],
                    "body": ["Inter"],
                },
                borderRadius: { "xl": "0.5rem", "full": "0.75rem" },
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        display: inline-block;
        line-height: 1;
    }
</style>
</head>
<body class="bg-background font-body text-on-surface antialiased">

<!-- TopAppBar -->
<header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl shadow-sm">
    <div class="flex items-center justify-between px-8 h-20 max-w-[1440px] mx-auto">
        <div class="text-2xl font-black text-blue-900 tracking-tighter font-headline">Job Saved</div>
        <div class="hidden md:flex flex-1 max-w-md mx-8">
            <form method="GET" action="index.php" class="relative w-full">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" name="search" class="w-full bg-gray-50 border-none rounded-xl py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none" placeholder="Search applications..."/>
            </form>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-lg shadow-md">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
        </div>
    </div>
    <div class="bg-gray-100 h-px w-full"></div>
</header>

<!-- SideNavBar -->
<aside class="h-screen w-72 flex flex-col fixed left-0 top-0 bg-gray-50 py-8 gap-2 z-40 hidden md:flex pt-28">
    <div class="px-8 mb-8">
        <h2 class="text-xl font-extrabold text-blue-900 font-headline">Job Saved</h2>
        <p class="text-xs text-gray-500 font-medium uppercase mt-1">Track Your Journey</p>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
        <a class="text-gray-600 p-3 mx-4 hover:text-blue-600 hover:bg-blue-50/50 transition-all flex items-center gap-3 font-semibold text-sm" href="dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a class="text-gray-600 p-3 mx-4 hover:text-blue-600 hover:bg-blue-50/50 transition-all flex items-center gap-3 font-semibold text-sm" href="index.php">
            <span class="material-symbols-outlined">work</span>
            <span>Applications</span>
        </a>
    </nav>
    <div class="flex flex-col gap-1">
        <a class="bg-white text-blue-700 shadow-sm rounded-lg p-3 mx-4 flex items-center gap-3 font-semibold text-sm" href="support.php">
            <span class="material-symbols-outlined">help</span>
            <span>Help Center</span>
        </a>
        <a class="text-gray-600 p-3 mx-4 hover:text-red-600 transition-all flex items-center gap-3 font-semibold text-sm" href="logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span>Log Out</span>
        </a>
        <div class="flex items-center gap-3 px-4 py-4 mt-4 border-t border-gray-200">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-xs">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div>
                <p class="text-xs font-bold truncate"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-[10px] text-gray-500 uppercase">Job Seeker</p>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<main class="md:pl-72 pt-28 pb-12 px-8 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-5xl font-black font-headline tracking-tighter mb-2">Support Center</h1>
            <p class="text-lg text-gray-600">We're here to help with your job tracking needs.</p>
        </header>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <span><?php echo $success_message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <span><?php echo $error_message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Help Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white p-6 rounded-xl shadow-sm border text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-blue-600">quick_reference</span>
                </div>
                <h3 class="font-bold text-lg mb-2">Getting Started</h3>
                <p class="text-sm text-gray-600 mb-4">Learn how to track your job applications effectively</p>
                <button onclick="document.getElementById('faqSection').scrollIntoView({behavior: 'smooth'})" class="text-blue-600 text-sm font-semibold hover:underline">View Guide →</button>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-green-600">forum</span>
                </div>
                <h3 class="font-bold text-lg mb-2">Submit Ticket</h3>
                <p class="text-sm text-gray-600 mb-4">Get personalized help from our support team</p>
                <button onclick="document.getElementById('ticketForm').scrollIntoView({behavior: 'smooth'})" class="text-blue-600 text-sm font-semibold hover:underline">Contact Support →</button>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-purple-600">live_help</span>
                </div>
                <h3 class="font-bold text-lg mb-2">Live Chat</h3>
                <p class="text-sm text-gray-600 mb-4">Chat with our support team in real-time</p>
                <button onclick="alert('Live chat is currently offline. Please submit a ticket for support.')" class="text-blue-600 text-sm font-semibold hover:underline">Start Chat →</button>
            </div>
        </div>

        <!-- Recent Tickets -->
        <?php if (count($recent_tickets) > 0): ?>
        <div class="mb-12">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h3 class="text-xl font-bold font-headline">Your Recent Tickets</h3>
                <span class="text-xs text-gray-500">Last 5 tickets</span>
            </div>
            <div class="space-y-4">
                <?php foreach($recent_tickets as $ticket): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase
                                    <?php echo match($ticket['priority']) {
                                        'high' => 'bg-red-100 text-red-700',
                                        'medium' => 'bg-yellow-100 text-yellow-700',
                                        'low' => 'bg-green-100 text-green-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    }; ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase
                                    <?php echo match($ticket['status']) {
                                        'open' => 'bg-blue-100 text-blue-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'resolved' => 'bg-green-100 text-green-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    }; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                            <h4 class="font-bold"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                            <p class="text-xs text-gray-500 mt-1">Category: <?php echo ucfirst(htmlspecialchars($ticket['category'])); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Submitted</p>
                            <p class="text-sm font-medium"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- FAQ Section -->
        <div id="faqSection" class="mb-12">
            <div class="border-b pb-4 mb-6">
                <h3 class="text-xl font-bold font-headline">Frequently Asked Questions</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h4 class="font-bold mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">help</span>
                        How do I add a new job application?
                    </h4>
                    <p class="text-gray-600 text-sm">Click the "Add New Job" button on your dashboard or applications page. Fill in the job details and click save.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h4 class="font-bold mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">edit</span>
                        How do I update job status?
                    </h4>
                    <p class="text-gray-600 text-sm">Click the edit icon (pencil) next to any job entry, update the status field, and save your changes.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h4 class="font-bold mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">search</span>
                        How do I search for jobs?
                    </h4>
                    <p class="text-gray-600 text-sm">Use the search bar at the top of any page to search by job title, company name, or category.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h4 class="font-bold mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">delete</span>
                        Can I delete a job application?
                    </h4>
                    <p class="text-gray-600 text-sm">Yes, click the delete icon (trash can) next to any job entry. You'll be asked to confirm before deletion.</p>
                </div>
            </div>
        </div>

        <!-- Ticket Submission Form -->
        <div id="ticketForm" class="bg-white rounded-xl shadow-sm border p-8">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-3xl text-blue-600">support_agent</span>
                <h3 class="text-2xl font-bold font-headline">Submit a Support Ticket</h3>
            </div>
            <p class="text-gray-600 mb-6">Submit a ticket and our support team will get back to you within 24 hours.</p>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                        <input type="text" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                               placeholder="Brief description of your issue">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select name="category" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="general">General Question</option>
                            <option value="technical">Technical Issue</option>
                            <option value="feature">Feature Request</option>
                            <option value="bug">Bug Report</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                        <select name="priority" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="low">Low - General question</option>
                            <option value="medium" selected>Medium - Need help</option>
                            <option value="high">High - Urgent issue</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled
                               class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-600">
                        <p class="text-xs text-gray-500 mt-1">We'll respond to your registered email</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                    <textarea name="message" required rows="6" 
                              class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                              placeholder="Please describe your issue in detail..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="submit_ticket" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined">send</span>
                    Submit Ticket
                </button>
            </form>
        </div>

        <!-- Footer Contact -->
        <div class="mt-12 text-center pt-8 border-t">
            <p class="text-gray-600 text-sm mb-2">Still need help?</p>
            <p class="text-gray-600 text-sm">
                Email us at <a href="mailto:support@jobsaved.com" class="text-blue-600 font-semibold hover:underline">support@jobsaved.com</a>
                <span class="mx-2">•</span>
                Response within 24 hours
            </p>
        </div>
    </div>
</main>

<!-- Mobile Bottom Nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t flex md:hidden justify-around items-center py-3 px-4 z-50">
    <a href="dashboard.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-[10px] font-bold">Home</span>
    </a>
    <a href="index.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">work</span>
        <span class="text-[10px] font-bold">Apps</span>
    </a>
    <div class="relative -top-6">
        <button onclick="window.location.href='index.php'" class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-lg">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
    <a href="support.php" class="flex flex-col items-center gap-1 text-blue-600">
        <span class="material-symbols-outlined">help</span>
        <span class="text-[10px] font-bold">Help</span>
    </a>
    <a href="logout.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">logout</span>
        <span class="text-[10px] font-bold">Exit</span>
    </a>
</nav>

<script>
// Smooth scroll for anchor links
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Attach click handlers
document.querySelectorAll('button[onclick*="scrollIntoView"]').forEach(button => {
    const onclickAttr = button.getAttribute('onclick');
    if (onclickAttr) {
        const match = onclickAttr.match(/'([^']+)'/);
        if (match) {
            const targetId = match[1];
            button.onclick = (e) => {
                e.preventDefault();
                scrollToSection(targetId);
            };
        }
    }
});
</script>
</body>
</html>