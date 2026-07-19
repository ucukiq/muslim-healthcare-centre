<!-- Live Queue Status Widget -->
<div id="liveQueueStatus" class="live-queue-widget">
    <div class="queue-header">
        <h6><i class="fas fa-ticket-alt me-2"></i>Live Queue Status</h6>
        <div class="live-indicator">
            <span class="live-dot"></span>
            <span class="live-text">LIVE</span>
        </div>
    </div>
    
    <div class="queue-content">
        <div id="queueLoading" class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mb-0 mt-2 small text-muted">Loading queue status...</p>
        </div>
        
        <div id="queueContent" style="display: none;">
            <!-- Queue content will be dynamically loaded here -->
        </div>
    </div>
</div>

<style>
.live-queue-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 15px;
    color: white;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.live-queue-widget::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.queue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    padding-bottom: 10px;
}

.queue-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 14px;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: bold;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #ff4444;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
}

.queue-content {
    position: relative;
    z-index: 1;
}

.queue-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.queue-stat {
    text-align: center;
    background: rgba(255,255,255,0.1);
    padding: 10px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.queue-stat .stat-value {
    font-size: 18px;
    font-weight: bold;
    display: block;
}

.queue-stat .stat-label {
    font-size: 10px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.queue-list {
    max-height: 200px;
    overflow-y: auto;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 10px;
}

.queue-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    margin-bottom: 5px;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    font-size: 12px;
    transition: all 0.3s ease;
}

.queue-item:hover {
    background: rgba(255,255,255,0.2);
    transform: translateX(5px);
}

.queue-item.current {
    background: rgba(76, 175, 80, 0.3);
    border-left: 3px solid #4CAF50;
}

.queue-item.next {
    background: rgba(33, 150, 243, 0.3);
    border-left: 3px solid #2196F3;
}

.turn-number {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 11px;
}

.patient-name {
    flex: 1;
    margin: 0 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.appointment-time {
    font-size: 10px;
    opacity: 0.8;
}

.doctor-info {
    background: rgba(255,255,255,0.1);
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 10px;
    font-size: 12px;
}

.doctor-name {
    font-weight: bold;
    margin-bottom: 2px;
}

.doctor-specialization {
    opacity: 0.8;
    font-size: 11px;
}

.last-updated {
    text-align: center;
    font-size: 10px;
    opacity: 0.6;
    margin-top: 10px;
}

/* Scrollbar styling */
.queue-list::-webkit-scrollbar {
    width: 6px;
}

.queue-list::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}

.queue-list::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}

.queue-list::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.5);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .queue-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    
    .queue-stat .stat-value {
        font-size: 16px;
    }
    
    .queue-stat .stat-label {
        font-size: 9px;
    }
    
    .patient-name {
        font-size: 11px;
    }
}
</style>

<script>
class LiveQueueStatus {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            refreshInterval: 10000, // 10 seconds
            doctorId: options.doctorId || null,
            maxItems: options.maxItems || 5,
            showDoctorInfo: options.showDoctorInfo !== false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.startAutoRefresh();
        this.loadQueueStatus();
    }
    
    async loadQueueStatus() {
        try {
            const url = this.options.doctorId 
                ? `../api/live_queue_status.php?doctor_id=${this.options.doctorId}`
                : '../api/live_queue_status.php';
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.renderQueueStatus(data.data);
                this.updateLastUpdated(data.timestamp);
            } else {
                this.showError('Unable to load queue status');
            }
        } catch (error) {
            console.error('Error loading queue status:', error);
            this.showError('Connection error');
        }
    }
    
    renderQueueStatus(data) {
        const content = document.getElementById('queueContent');
        const loading = document.getElementById('queueLoading');
        
        let html = '';
        
        // Doctor info
        if (this.options.showDoctorInfo && data.doctor_info) {
            html += `
                <div class="doctor-info">
                    <div class="doctor-name">Dr. ${data.doctor_info.full_name}</div>
                    <div class="doctor-specialization">${data.doctor_info.specialization || 'General Practice'}</div>
                </div>
            `;
        }
        
        // Queue statistics
        html += `
            <div class="queue-stats">
                <div class="queue-stat">
                    <span class="stat-value">${data.current_turn || '---'}</span>
                    <span class="stat-label">Current Turn</span>
                </div>
                <div class="queue-stat">
                    <span class="stat-value">${data.next_patient ? data.next_patient.turn_number : '---'}</span>
                    <span class="stat-label">Next</span>
                </div>
                <div class="queue-stat">
                    <span class="stat-value">${data.waiting_count}</span>
                    <span class="stat-label">Waiting</span>
                </div>
            </div>
        `;
        
        // Queue list
        if (data.queue && data.queue.length > 0) {
            html += '<div class="queue-list">';
            
            const queueToShow = data.queue.slice(0, this.options.maxItems);
            
            queueToShow.forEach((patient, index) => {
                const isCurrent = patient.turn_number === data.current_turn;
                const isNext = patient.turn_number === data.next_patient?.turn_number;
                
                let itemClass = 'queue-item';
                if (isCurrent) itemClass += ' current';
                if (isNext) itemClass += ' next';
                
                html += `
                    <div class="${itemClass}">
                        <span class="turn-number">#${patient.turn_number}</span>
                        <span class="patient-name">${patient.patient_name}</span>
                        <span class="appointment-time">${patient.appointment_time}</span>
                    </div>
                `;
            });
            
            if (data.queue.length > this.options.maxItems) {
                html += `
                    <div class="text-center mt-2">
                        <small class="opacity-75">+${data.queue.length - this.options.maxItems} more patients</small>
                    </div>
                `;
            }
            
            html += '</div>';
        } else {
            html += `
                <div class="text-center py-3">
                    <i class="fas fa-users fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0 small">No patients in queue</p>
                </div>
            `;
        }
        
        content.innerHTML = html;
        loading.style.display = 'none';
        content.style.display = 'block';
    }
    
    showError(message) {
        const content = document.getElementById('queueContent');
        const loading = document.getElementById('queueLoading');
        
        content.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle fa-2x mb-2 opacity-75"></i>
                <p class="mb-0 small">${message}</p>
                <button class="btn btn-sm btn-outline-light mt-2" onclick="liveQueue.loadQueueStatus()">
                    <i class="fas fa-sync-alt me-1"></i> Retry
                </button>
            </div>
        `;
        
        loading.style.display = 'none';
        content.style.display = 'block';
    }
    
    updateLastUpdated(timestamp) {
        const lastUpdated = this.container.querySelector('.last-updated');
        if (!lastUpdated) {
            const updatedDiv = document.createElement('div');
            updatedDiv.className = 'last-updated';
            this.container.querySelector('.queue-content').appendChild(updatedDiv);
        }
        
        const time = new Date(timestamp * 1000);
        const timeString = time.toLocaleTimeString();
        this.container.querySelector('.last-updated').textContent = `Last updated: ${timeString}`;
    }
    
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.refreshTimer = setInterval(() => {
            this.loadQueueStatus();
        }, this.options.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
    
    destroy() {
        this.stopAutoRefresh();
    }
}

// Initialize the live queue widget
document.addEventListener('DOMContentLoaded', function() {
    window.liveQueue = new LiveQueueStatus('liveQueueStatus', {
        refreshInterval: 10000, // 10 seconds
        maxItems: 5
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.liveQueue) {
        window.liveQueue.destroy();
    }
});
</script>
