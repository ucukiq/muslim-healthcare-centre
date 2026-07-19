<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Queue Status Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/live_queue.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .widget-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .test-header {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>Live Queue Status Widget Test</h1>
            <p class="text-muted">Testing the live queue status widget with different configurations</p>
        </div>

        <div class="row">
            <!-- Standard Widget -->
            <div class="col-md-4">
                <div class="widget-card">
                    <h5>Standard Widget</h5>
                    <div id="liveQueueStatus" class="live-queue-widget">
                        <div id="liveQueueStatusLoading" class="queue-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading queue status...</p>
                        </div>
                        <div id="liveQueueStatusContent" class="queue-content">
                            <!-- Queue content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Green Widget (Admin Style) -->
            <div class="col-md-4">
                <div class="widget-card">
                    <h5>Admin Widget (Compact, Green)</h5>
                    <div id="adminLiveQueueStatus" class="live-queue-widget compact theme-green">
                        <div id="adminLiveQueueStatusLoading" class="queue-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading queue status...</p>
                        </div>
                        <div id="adminLiveQueueStatusContent" class="queue-content">
                            <!-- Queue content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Management Widget -->
            <div class="col-md-4">
                <div class="widget-card">
                    <h5>Queue Management Widget</h5>
                    <div id="queueLiveQueueStatus" class="live-queue-widget compact theme-green">
                        <div id="queueLiveQueueStatusLoading" class="queue-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading queue status...</p>
                        </div>
                        <div id="queueLiveQueueStatusContent" class="queue-content">
                            <!-- Queue content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="widget-card">
            <h5>Manual Controls</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="refreshAllWidgets()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh All
                </button>
                <button class="btn btn-success" onclick="startAutoRefresh()">
                    <i class="fas fa-play me-1"></i> Start Auto Refresh
                </button>
                <button class="btn btn-warning" onclick="stopAutoRefresh()">
                    <i class="fas fa-stop me-1"></i> Stop Auto Refresh
                </button>
                <button class="btn btn-danger" onclick="destroyAllWidgets()">
                    <i class="fas fa-trash me-1"></i> Destroy All
                </button>
            </div>
        </div>

        <div class="widget-card">
            <h5>API Response</h5>
            <div id="apiResponse" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                API response will be displayed here...
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="includes/live_queue_widget.js"></script>
    <script>
        let liveQueue, adminLiveQueue, queueLiveQueue;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize standard widget
            liveQueue = new LiveQueueStatus('liveQueueStatus', {
                refreshInterval: 10000,
                maxItems: 5
            });

            // Initialize admin widget
            adminLiveQueue = new LiveQueueStatus('adminLiveQueueStatus', {
                refreshInterval: 15000,
                maxItems: 8,
                showDoctorInfo: true
            });

            // Initialize queue management widget
            queueLiveQueue = new LiveQueueStatus('queueLiveQueueStatus', {
                refreshInterval: 5000,
                maxItems: 10,
                showDoctorInfo: true,
                showEmptyMessage: true
            });

            // Test API endpoint
            testAPI();
        });

        function testAPI() {
            fetch('api/live_queue_status.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    document.getElementById('apiResponse').textContent = 'Error: ' + error.message;
                });
        }

        function refreshAllWidgets() {
            if (liveQueue) liveQueue.refresh();
            if (adminLiveQueue) adminLiveQueue.refresh();
            if (queueLiveQueue) queueLiveQueue.refresh();
            testAPI();
        }

        function startAutoRefresh() {
            if (liveQueue) liveQueue.startAutoRefresh();
            if (adminLiveQueue) adminLiveQueue.startAutoRefresh();
            if (queueLiveQueue) queueLiveQueue.startAutoRefresh();
        }

        function stopAutoRefresh() {
            if (liveQueue) liveQueue.stopAutoRefresh();
            if (adminLiveQueue) adminLiveQueue.stopAutoRefresh();
            if (queueLiveQueue) queueLiveQueue.stopAutoRefresh();
        }

        function destroyAllWidgets() {
            if (liveQueue) liveQueue.destroy();
            if (adminLiveQueue) adminLiveQueue.destroy();
            if (queueLiveQueue) queueLiveQueue.destroy();
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            destroyAllWidgets();
        });
    </script>
</body>
</html>
