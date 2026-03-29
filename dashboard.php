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
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['name'];
    }
    $stmt->close();
}

// Stats Calculation
$stats = ['Pending' => 0, 'Applied' => 0, 'Interview' => 0, 'Offer' => 0, 'Rejected' => 0];
$total_jobs = 0;

$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM jobs WHERE user_id = ? GROUP BY status");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = $row['count'];
            $total_jobs += $row['count'];
        }
    }
    $stmt->close();
}

// Get recent applications (last 5)
$recent_jobs = [];
$stmt = $conn->prepare("SELECT id, title, company, category, status, created_at FROM jobs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_jobs[] = $row;
    }
    $stmt->close();
}

// ADD JOB from dashboard
if (isset($_POST['add_job'])) {
    $errors = [];
    
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    if (empty($company)) {
        $errors[] = "Company name is required";
    }
    
    if (!empty($link)) {
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . $link;
        }
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid URL format";
        }
    } else {
        $link = null;
    }
    
    if (empty($category)) {
        $category = null;
    }
    
    $valid_statuses = ['Pending', 'Applied', 'Interview', 'Offer', 'Rejected'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'Pending';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO jobs (title, company, link, category, status, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssi", $title, $company, $link, $category, $status, $user_id);
            
            if ($stmt->execute()) {
                header("Location: dashboard.php?success=added");
                exit();
            } else {
                $errors[] = "Database error";
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: dashboard.php");
        exit();
    }
}

// Helper Functions
function getInitials($company) {
    $words = explode(' ', $company);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($company, 0, 2));
}

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    $minutes = round($time_difference / 60);
    $hours = round($time_difference / 3600);
    $days = round($time_difference / 86400);
    $weeks = round($time_difference / 604800);
    $months = round($time_difference / 2629440);
    $years = round($time_difference / 31553280);
    
    if ($time_difference <= 60) {
        return "Just Now";
    } elseif ($minutes <= 60) {
        return $minutes == 1 ? "1 Minute Ago" : "$minutes Minutes Ago";
    } elseif ($hours <= 24) {
        return $hours == 1 ? "1 Hour Ago" : "$hours Hours Ago";
    } elseif ($days <= 7) {
        return $days == 1 ? "Yesterday" : "$days Days Ago";
    } elseif ($weeks <= 4.3) {
        return $weeks == 1 ? "1 Week Ago" : "$weeks Weeks Ago";
    } elseif ($months <= 12) {
        return $months == 1 ? "1 Month Ago" : "$months Months Ago";
    } else {
        return $years == 1 ? "1 Year Ago" : "$years Years Ago";
    }
}

$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$success_message = isset($_GET['success']) ? "Job added successfully!" : "";
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Job Saved | Dashboard</title>
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
        <a class="bg-white text-blue-700 shadow-sm rounded-lg p-3 mx-4 flex items-center gap-3 font-semibold text-sm" href="dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a class="text-gray-600 p-3 mx-4 hover:text-blue-600 hover:bg-blue-50/50 transition-all flex items-center gap-3 font-semibold text-sm" href="index.php">
            <span class="material-symbols-outlined">work</span>
            <span>Applications</span>
        </a>
    </nav>
    <div class="flex flex-col gap-1">
        <a class="text-gray-600 p-3 mx-4 hover:text-blue-600 transition-all flex items-center gap-3 font-semibold text-sm" href="support.php">
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
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
                <h1 class="text-5xl font-black font-headline tracking-tighter mb-3">Dashboard</h1>
                <p class="text-lg text-gray-600">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            <button onclick="openAddModal()" class="bg-primary text-white font-bold px-6 py-3 rounded-xl flex items-center gap-2 shadow-lg hover:bg-blue-700 transition-all active:scale-95">
                <span class="material-symbols-outlined">add</span>
                <span>Add New Job</span>
            </button>
        </header>

        <!-- Success Message -->
        <?php if ($success_message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            ✅ <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white p-8 rounded-2xl shadow-sm border">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs font-bold text-gray-500 uppercase">Total Applications</span>
                    <span class="material-symbols-outlined text-blue-600">analytics</span>
                </div>
                <div class="text-5xl font-black text-blue-600"><?php echo $total_jobs; ?></div>
            </div>
            
            <div class="bg-white p-8 rounded-2xl shadow-sm border">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs font-bold text-gray-500 uppercase">Interviews</span>
                    <span class="material-symbols-outlined text-green-600">calendar_today</span>
                </div>
                <div class="text-5xl font-black text-green-600"><?php echo $stats['Interview']; ?></div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-8 rounded-2xl shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex justify-between items-start mb-4">
                    <span class="text-xs font-bold text-blue-100 uppercase">Offers Received</span>
                    <span class="material-symbols-outlined">stars</span>
                </div>
                <div class="relative z-10 text-5xl font-black"><?php echo $stats['Offer']; ?></div>
                <div class="absolute -right-4 -bottom-4 opacity-10">
                    <span class="material-symbols-outlined text-8xl">emoji_events</span>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="space-y-6">
            <div class="flex justify-between items-center border-b pb-4">
                <h3 class="text-xl font-bold font-headline">Recent Applications</h3>
                <a href="index.php" class="text-sm font-semibold text-blue-600 hover:underline">View All →</a>
            </div>
            
            <div class="space-y-4">
                <?php if (count($recent_jobs) > 0): ?>
                    <?php foreach($recent_jobs as $job): ?>
                    <div class="group bg-white p-6 rounded-2xl shadow-sm border hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex gap-4 items-center">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg">
                                    <?php echo getInitials($job['company']); ?>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold"><?php echo htmlspecialchars($job['title']); ?></h4>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($job['company']); ?></span>
                                        <?php if (!empty($job['category'])): ?>
                                            <span class="text-xs text-gray-500">• <?php echo htmlspecialchars($job['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="inline-block px-2 py-1 rounded-full text-[10px] font-bold uppercase
                                            <?php echo match($job['status']) {
                                                'Applied' => 'bg-blue-100 text-blue-700',
                                                'Interview' => 'bg-green-100 text-green-700',
                                                'Offer' => 'bg-purple-100 text-purple-700',
                                                'Rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            }; ?>">
                                            <?php echo $job['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Added</p>
                                    <p class="text-sm font-medium"><?php echo timeAgo($job['created_at']); ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="index.php?edit=<?php echo $job['id']; ?>" class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors">
                                        <span class="material-symbols-outlined text-gray-600">edit</span>
                                    </a>
                                    <a href="index.php" class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors">
                                        <span class="material-symbols-outlined text-gray-600">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-16 bg-white rounded-2xl border">
                        <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">work_off</span>
                        <p class="text-gray-500 text-lg mb-4">No applications yet</p>
                        <button onclick="openAddModal()" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">
                            Add Your First Application
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Job Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Add New Application</h2>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <?php if (!empty($form_errors)): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <?php foreach($form_errors as $error): ?>
                    <p class="text-red-600 text-sm">• <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Title *</label>
                <input type="text" name="title" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                <input type="text" name="company" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Link (Optional)</label>
                <input type="text" name="link" placeholder="https://..." class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category (Optional)</label>
                <input type="text" name="category" placeholder="e.g., IT, Sales, Marketing" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="Pending">📝 Pending</option>
                    <option value="Applied">📧 Applied</option>
                    <option value="Interview">🎯 Interview</option>
                    <option value="Offer">🎉 Offer</option>
                    <option value="Rejected">❌ Rejected</option>
                </select>
            </div>
            
            <button type="submit" name="add_job" class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                Save Application
            </button>
        </form>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t flex md:hidden justify-around items-center py-3 px-4 z-50">
    <a href="dashboard.php" class="flex flex-col items-center gap-1 text-blue-600">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-[10px] font-bold">Home</span>
    </a>
    <a href="index.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">work</span>
        <span class="text-[10px] font-bold">Apps</span>
    </a>
    <div class="relative -top-6">
        <button onclick="openAddModal()" class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-lg">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
    <a href="support.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">help</span>
        <span class="text-[10px] font-bold">Help</span>
    </a>
    <a href="logout.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">logout</span>
        <span class="text-[10px] font-bold">Exit</span>
    </a>
</nav>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

document.getElementById('addModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('addModal');
        if (modal && !modal.classList.contains('hidden')) closeAddModal();
    }
});
</script>

</body>
</html>

<?php
if (isset($conn)) $conn->close();
?>