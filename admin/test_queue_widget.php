<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Set page title
$page_title = "Test Queue Widget";

// Include header
include "../includes/header.php";
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Test Live Queue Widget</h1>
            
            <!-- Test Widget -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Queue Status Test</h5>
                </div>
                <div class="card-body">
                    <div id="testLiveQueueStatus" class="live-queue-widget">
                        <div id="testLiveQueueStatusLoading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 mt-2 small text-muted">Loading queue status...</p>
                        </div>
                        
                        <div id="testLiveQueueStatusContent" style="display: none;">
                            <!-- Queue content will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Debug Info -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Debug Information</h5>
                </div>
                <div class="card-body">
                    <p>Open browser console to see debug output.</p>
                    <p>Expected behavior:</p>
                    <ul>
                        <li>Loading spinner should appear initially</li>
                        <li>After API call, loading should disappear</li>
                        <li>"No patients in queue" message should appear</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="../includes/live_queue_widget.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize test widget
    console.log('Initializing test widget...');
    window.testQueue = new LiveQueueStatus('testLiveQueueStatus', {
        refreshInterval: 10000,
        maxItems: 5,
        showDoctorInfo: true,
        showEmptyMessage: true
    });
    
    console.log('Test widget initialized:', window.testQueue);
});
</script>
