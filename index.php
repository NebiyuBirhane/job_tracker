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

// DELETE JOB
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit();
        }
        $stmt->close();
    }
}

// UPDATE JOB
if (isset($_POST['update_job'])) {
    $errors = [];
    
    $id = intval($_POST['job_id']);
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    
    if (empty($title)) $errors[] = "Job title is required";
    if (empty($company)) $errors[] = "Company name is required";
    
    if (!empty($link)) {
        if (!preg_match('/^https?:\/\//i', $link)) $link = 'https://' . $link;
        if (!filter_var($link, FILTER_VALIDATE_URL)) $errors[] = "Invalid URL format";
    } else {
        $link = null;
    }
    
    if (empty($category)) $category = null;
    
    $valid_statuses = ['Pending', 'Applied', 'Interview', 'Offer', 'Rejected'];
    if (!in_array($status, $valid_statuses)) $status = 'Pending';
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE jobs SET title = ?, company = ?, link = ?, category = ?, status = ? WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("sssssii", $title, $company, $link, $category, $status, $id, $user_id);
            if ($stmt->execute()) {
                header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
                exit();
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ADD JOB
if (isset($_POST['add_job'])) {
    $errors = [];
    
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    
    if (empty($title)) $errors[] = "Job title is required";
    if (empty($company)) $errors[] = "Company name is required";
    
    if (!empty($link)) {
        if (!preg_match('/^https?:\/\//i', $link)) $link = 'https://' . $link;
        if (!filter_var($link, FILTER_VALIDATE_URL)) $errors[] = "Invalid URL format";
    } else {
        $link = null;
    }
    
    if (empty($category)) $category = null;
    
    $valid_statuses = ['Pending', 'Applied', 'Interview', 'Offer', 'Rejected'];
    if (!in_array($status, $valid_statuses)) $status = 'Pending';
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO jobs (title, company, link, category, status, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssi", $title, $company, $link, $category, $status, $user_id);
            if ($stmt->execute()) {
                header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
                exit();
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// FETCH JOBS WITH FILTERS
$where_clause = "WHERE user_id = ?";
$params = [$user_id];
$types = "i";
$search_term = '';

if (!empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $where_clause .= " AND (title LIKE ? OR company LIKE ? OR category LIKE ?)";
    $search_param = "%$search_term%";
    $params = [$user_id, $search_param, $search_param, $search_param];
    $types = "isss";
} elseif (!empty($_GET['status']) && in_array($_GET['status'], ['Pending', 'Applied', 'Interview', 'Offer', 'Rejected'])) {
    $where_clause .= " AND status = ?";
    $params = [$user_id, $_GET['status']];
    $types = "is";
}

$sql = "SELECT id, title, company, link, category, status, created_at FROM jobs $where_clause ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs_result = $stmt->get_result();
    $total_results = $jobs_result->num_rows;
} else {
    $jobs_result = false;
    $total_results = 0;
}

$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Applications | Job Saved</title>
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
            <form method="GET" class="relative w-full">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" class="w-full bg-gray-50 border-none rounded-xl py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none" placeholder="Search applications..."/>
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
        <a class="bg-white text-blue-700 shadow-sm rounded-lg p-3 mx-4 flex items-center gap-3 font-semibold text-sm" href="#">
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
        <header class="mb-8">
            <h1 class="text-5xl font-black font-headline tracking-tighter mb-2">Applications</h1>
            <p class="text-lg text-gray-600">Manage your active job hunt and interview pipeline.</p>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Total Active</p>
                <p class="text-3xl font-black text-blue-600"><?php echo $total_jobs; ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Interviews</p>
                <p class="text-3xl font-black text-green-600"><?php echo $stats['Interview']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Offers</p>
                <p class="text-3xl font-black text-purple-600"><?php echo $stats['Offer']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Pending</p>
                <p class="text-3xl font-black text-orange-600"><?php echo $stats['Pending']; ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 <?php echo empty($_GET['status']) ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">All</a>
                <a href="?status=Interview" class="px-4 py-2 <?php echo isset($_GET['status']) && $_GET['status'] == 'Interview' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">Interview</a>
                <a href="?status=Applied" class="px-4 py-2 <?php echo isset($_GET['status']) && $_GET['status'] == 'Applied' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">Applied</a>
                <a href="?status=Pending" class="px-4 py-2 <?php echo isset($_GET['status']) && $_GET['status'] == 'Pending' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">Pending</a>
                <a href="?status=Offer" class="px-4 py-2 <?php echo isset($_GET['status']) && $_GET['status'] == 'Offer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">Offer</a>
                <a href="?status=Rejected" class="px-4 py-2 <?php echo isset($_GET['status']) && $_GET['status'] == 'Rejected' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> rounded-full text-xs font-semibold transition-colors">Rejected</a>
            </div>
            <div class="text-xs text-gray-500">
                Showing <?php echo $total_results; ?> of <?php echo $total_jobs; ?> jobs
            </div>
        </div>

        <!-- Jobs List -->
        <div class="space-y-4">
            <?php if ($jobs_result && $jobs_result->num_rows > 0): ?>
                <?php while($job = $jobs_result->fetch_assoc()): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border hover:shadow-md transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center text-blue-700">
                                <span class="material-symbols-outlined">work</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <?php if (!empty($job['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($job['link']); ?>" target="_blank" class="text-sm font-semibold text-blue-600 hover:underline flex items-center gap-1">
                                            <?php echo htmlspecialchars($job['company']); ?>
                                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($job['company']); ?></span>
                                    <?php endif; ?>
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
                                <p class="text-sm font-medium"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick='openEditModal(<?php echo json_encode($job); ?>)' class="p-2 text-gray-400 hover:text-blue-600 transition-colors">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <a href="?delete=<?php echo $job['id']; ?>" onclick="return confirm('Delete this application?')" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                    <span class="material-symbols-outlined">delete</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-xl border">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">work_off</span>
                    <p class="text-gray-500 text-lg mb-4">No applications found</p>
                    <?php if (!empty($search_term) || !empty($_GET['status'])): ?>
                        <a href="?" class="inline-block px-6 py-3 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700">Clear Filters</a>
                    <?php else: ?>
                        <button onclick="openAddModal()" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Add Your First Application</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Button Footer -->
        <div class="mt-12 text-center">
            <button onclick="openAddModal()" class="text-blue-600 font-semibold text-sm hover:underline flex items-center gap-1 mx-auto">
                <span class="material-symbols-outlined text-sm">add</span>
                Add New Application
            </button>
        </div>
    </div>
</main>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Add Application</h2>
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
            <input type="text" name="title" placeholder="Job Title *" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="company" placeholder="Company Name *" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="link" placeholder="Job Link (Optional)" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="category" placeholder="Category (Optional)" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <select name="status" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="Pending">📝 Pending</option>
                <option value="Applied">📧 Applied</option>
                <option value="Interview">🎯 Interview</option>
                <option value="Offer">🎉 Offer</option>
                <option value="Rejected">❌ Rejected</option>
            </select>
            <button type="submit" name="add_job" class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Save Application</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Edit Application</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form id="editForm" method="POST" class="space-y-4">
            <input type="hidden" name="job_id" id="edit_id">
            <input type="text" name="title" id="edit_title" placeholder="Job Title *" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="company" id="edit_company" placeholder="Company Name *" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="link" id="edit_link" placeholder="Job Link (Optional)" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="text" name="category" id="edit_category" placeholder="Category (Optional)" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <select name="status" id="edit_status" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="Pending">📝 Pending</option>
                <option value="Applied">📧 Applied</option>
                <option value="Interview">🎯 Interview</option>
                <option value="Offer">🎉 Offer</option>
                <option value="Rejected">❌ Rejected</option>
            </select>
            <button type="submit" name="update_job" class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Update Application</button>
        </form>
    </div>
</div>

<!-- Mobile Bottom Nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t flex md:hidden justify-around items-center py-3 px-4 z-50">
    <a href="dashboard.php" class="flex flex-col items-center gap-1 text-gray-500">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-[10px] font-bold">Home</span>
    </a>
    <a href="#" class="flex flex-col items-center gap-1 text-blue-600">
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

function openEditModal(job) {
    document.getElementById('edit_id').value = job.id;
    document.getElementById('edit_title').value = job.title;
    document.getElementById('edit_company').value = job.company;
    document.getElementById('edit_link').value = job.link || '';
    document.getElementById('edit_category').value = job.category || '';
    document.getElementById('edit_status').value = job.status;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modals on outside click
document.getElementById('addModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeEditModal();
    }
});
</script>
</body>
</html>

<?php
if (isset($conn)) $conn->close();
?>