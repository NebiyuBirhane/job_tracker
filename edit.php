<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Debug: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "job_tracker");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$error = "";

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "Job ID is required";
}

if (empty($error)) {
    $id = intval($_GET['id']); // Sanitize ID

    // Fetch existing job
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id=? AND user_id=?");
    if (!$stmt) {
        $error = "Database prepare error: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            $error = "Job not found or you do not have permission to edit it";
        }
    }
}

// Update job
if (isset($_POST['update_job']) && empty($error)) {
    $title = trim($_POST['title']);
    $company = trim($_POST['company']);
    $link = trim($_POST['link']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE jobs SET title=?, company=?, link=?, category=?, status=? WHERE id=? AND user_id=?");
    if ($stmt) {
        $stmt->bind_param("sssssii", $title, $company, $link, $category, $status, $id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Job updated successfully!";
            // Refresh job data
            $stmt = $conn->prepare("SELECT * FROM jobs WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
        } else {
            $error = "Update failed: " . $stmt->error;
        }
    } else {
        $error = "Prepare failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Edit Job | Job Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: "#00488d",
                "primary-container": "#005fb8",
                "on-primary": "#ffffff",
                surface: "#f8f9fb",
                "surface-container-low": "#f2f4f6",
                "surface-container-lowest": "#ffffff",
                "on-surface": "#191c1e",
                "on-surface-variant": "#424752",
                "outline-variant": "#c2c6d4",
                "tertiary-container": "#d4edd5",
                error: "#ba1a1a",
                "error-container": "#ffdad6"
            },
            fontFamily: {
                headline: ["Manrope"],
                body: ["Inter"],
                label: ["Inter"]
            }
        }
    }
}
</script>
<style>
.material-symbols-outlined { 
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; 
    display: inline-block;
    line-height: 1;
}
.font-headline { font-family: 'Manrope', sans-serif; font-weight: 800; }
.font-label { font-family: 'Inter', sans-serif; font-weight: 600; }
.font-body { font-family: 'Inter', sans-serif; }
.custom-shadow { box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }
</style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen font-body antialiased">
<!-- Debug Info (Remove in production) -->
<?php if (!empty($error)): ?>
<div style="background: #fee; color: #c33; padding: 1rem; margin: 1rem; border-radius: 8px; font-family: monospace;">
<strong>ERROR:</strong> <?php echo htmlspecialchars($error); ?>
<br><a href="index.php">← Back to Dashboard</a>
</div>
<?php exit(); endif; ?>

<header class="fixed top-0 w-full z-50 bg-white shadow-sm border-b border-gray-100">
<div class="flex items-center justify-between px-4 md:px-8 h-16 max-w-6xl mx-auto">
<div class="text-xl font-black text-primary font-headline">Job Tracker</div>
<div class="flex items-center gap-4">
<a href="index.php" class="px-4 py-2 text-primary font-semibold hover:bg-gray-100 rounded-lg transition-all">Dashboard</a>
<button onclick="window.history.back()" class="p-2 rounded-lg hover:bg-gray-100 transition-all">
<span class="material-symbols-outlined text-gray-600">close</span>
</button>
</div>
</div>
</header>

<main class="pt-20 pb-12 px-4 md:px-8 max-w-6xl mx-auto">
<?php if($message): ?>
<div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 font-semibold text-center animate-pulse">
<?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="mb-12 text-center md:text-left">
<a href="index.php" class="inline-flex items-center gap-2 text-primary font-bold text-sm uppercase tracking-wider mb-6 hover:underline">
<span class="material-symbols-outlined text-sm">arrow_back</span> Back to Dashboard
</a>
<h1 class="font-headline font-extrabold text-4xl md:text-5xl text-gray-900 tracking-tight mb-4">Edit Job</h1>
<p class="text-gray-600 text-lg max-w-2xl leading-relaxed">
Update the details for this job application.
</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<!-- Main Form (2/3 width on large screens) -->
<div class="lg:col-span-2">
<div class="bg-white rounded-2xl p-8 md:p-12 shadow-xl border border-gray-100">
<form method="POST" class="space-y-8">
<!-- Job Title -->
<div>
<label class="block font-label font-semibold text-xs text-gray-600 uppercase tracking-wider mb-3">Job Title</label>
<input class="w-full bg-gray-50 border border-gray-200 rounded-xl p-5 text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none" 
       name="title" 
       value="<?php echo htmlspecialchars($row['title'] ?? ''); ?>" 
       required/>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<!-- Company -->
<div>
<label class="block font-label font-semibold text-xs text-gray-600 uppercase tracking-wider mb-3">Company</label>
<input class="w-full bg-gray-50 border border-gray-200 rounded-xl p-5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none" 
       name="company" 
       value="<?php echo htmlspecialchars($row['company'] ?? ''); ?>" 
       required/>
</div>
<!-- Category -->
<div>
<label class="block font-label font-semibold text-xs text-gray-600 uppercase tracking-wider mb-3">Category</label>
<input class="w-full bg-gray-50 border border-gray-200 rounded-xl p-5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none" 
       name="category" 
       value="<?php echo htmlspecialchars($row['category'] ?? ''); ?>" 
       required/>
</div>
</div>

<!-- Link -->
<div>
<label class="block font-label font-semibold text-xs text-gray-600 uppercase tracking-wider mb-3">Application Link</label>
<input class="w-full bg-gray-50 border border-gray-200 rounded-xl p-5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none" 
       name="link" 
       value="<?php echo htmlspecialchars($row['link'] ?? ''); ?>" 
       type="url"/>
</div>

<!-- Status -->
<!-- Replace the Status section in your edit_job.php with this FIXED version: -->

<!-- Status Selection - FIXED -->
<div>
<label class="block font-label font-semibold text-xs text-on-surface-variant uppercase tracking-wider mb-6">Application Status</label>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
<?php
$statuses = ['Pending', 'Applied', 'Interview', 'Offered', 'Rejected'];
$status_icons = ['schedule', 'send', 'forum', 'check_circle', 'close'];
foreach ($statuses as $index => $status): 
    $is_selected = ($row['status'] ?? '') === $status;
?>
<label class="relative flex flex-col items-center justify-center p-5 rounded-xl border-2 cursor-pointer transition-all group hover:shadow-md hover:scale-[1.02] <?php echo $is_selected ? 'border-primary bg-primary/5 shadow-md ring-2 ring-primary/20' : 'border-outline-variant/30 hover:border-primary/50'; ?>">
    <!-- ✅ FIXED: Radio input with proper z-index -->
    <input class="absolute inset-0 w-full h-full opacity-0 cursor-pointer peer z-10" 
           name="status" 
           type="radio" 
           value="<?php echo htmlspecialchars($status); ?>" 
           <?php echo $is_selected ? 'checked' : ''; ?> 
           required/>
    
    <!-- Background overlay -->
    <div class="absolute inset-0 bg-primary/10 rounded-xl opacity-0 peer-checked:opacity-100 transition-all z-0"></div>
    
    <!-- Icon -->
    <span class="material-symbols-outlined mb-3 text-2xl <?php echo $is_selected ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?> transition-colors z-20 relative"><?php echo $status_icons[$index] ?? 'help'; ?></span>
    
    <!-- Label -->
    <span class="text-sm font-bold uppercase tracking-wide relative z-20 <?php echo $is_selected ? 'text-primary' : 'text-on-surface group-hover:text-primary'; ?>">
        <?php echo htmlspecialchars($status); ?>
    </span>
</label>
<?php endforeach; ?>
</div>
</div>

<!-- Buttons -->
<div class="flex flex-col sm:flex-row gap-4 pt-8 border-t border-gray-100 mt-8">
<button type="submit" name="update_job" 
        class="flex-1 sm:flex-none px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-2">
<span>Save Changes</span>
<span class="material-symbols-outlined">save</span>
</button>
<a href="index.php" class="flex-1 sm:flex-none px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-all flex items-center justify-center gap-2">
Cancel
<span class="material-symbols-outlined">close</span>
</a>
</div>
</form>
</div>
</div>

<!-- Preview Sidebar (1/3 width) -->
<div>
<div class="bg-white rounded-2xl p-8 shadow-xl border border-gray-100 sticky top-8">
<h3 class="font-label font-bold text-xs uppercase tracking-wider text-gray-500 mb-6">Preview</h3>
<div class="space-y-4">
<div class="flex items-start justify-between mb-4">
<div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
<span class="material-symbols-outlined text-2xl text-gray-500">
<?php 
$status_icon = ['Pending'=>'schedule', 'Applied'=>'send', 'Interview'=>'smart_toy', 'Offered'=>'check_circle', 'Rejected'=>'close'][$row['status'] ?? 'schedule'] ?? 'help';
echo $status_icon;
?>
</span>
</div>
<span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase tracking-wide">
<?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
</span>
</div>
<h4 class="font-headline font-bold text-xl text-gray-900 mb-2"><?php echo htmlspecialchars($row['title'] ?? ''); ?></h4>
<p class="text-gray-600 font-medium mb-4"><?php echo htmlspecialchars($row['company'] ?? ''); ?></p>
<?php if($row['link']): ?>
<a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank" class="text-xs text-blue-600 font-bold hover:underline flex items-center gap-1">
View Application
<span class="material-symbols-outlined text-xs">open_in_new</span>
</a>
<?php endif; ?>
</div>
</div>
</div>
</div>
</main>

</body>
</html>