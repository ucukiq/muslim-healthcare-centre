// Live Queue Status Widget JavaScript
class LiveQueueStatus {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = {
            refreshInterval: 10000, // 10 seconds default
            maxItems: 5,
            showDoctorInfo: false,
            showEmptyMessage: true,
            apiUrl: '../api/live_queue_status.php',
            ...options
        };
        
        this.refreshTimer = null;
        this.lastUpdate = null;
        this.isUpdating = false;
        
        if (this.container) {
            this.init();
        } else {
            console.error(`Live Queue Status: Container with ID '${containerId}' not found`);
        }
    }
    
    init() {
        this.startAutoRefresh();
        this.refresh(); // Initial load
    }
    
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.refreshTimer = setInterval(() => {
            this.refresh();
        }, this.options.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
    
    async refresh() {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        this.showLoading();
        
        try {
            const response = await fetch(this.options.apiUrl);
            const data = await response.json();
            
            if (response.ok && data.success) {
                this.renderQueue(data.data);
                this.lastUpdate = new Date();
            } else {
                this.showError(data.message || 'Failed to load queue data');
            }
        } catch (error) {
            console.error('Live Queue Status Error:', error);
            this.showError('Connection error. Please try again.');
        } finally {
            this.isUpdating = false;
        }
    }
    
    showLoading() {
        const loadingEl = document.getElementById(this.containerId + 'Loading');
        const contentEl = document.getElementById(this.containerId + 'Content');
        
        if (loadingEl) loadingEl.style.display = 'block';
        if (contentEl) contentEl.style.display = 'none';
    }
    
    showError(message) {
        const contentEl = document.getElementById(this.containerId + 'Content');
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="queue-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            `;
            contentEl.style.display = 'block';
        }
        
        const loadingEl = document.getElementById(this.containerId + 'Loading');
        if (loadingEl) loadingEl.style.display = 'none';
    }
    
    renderQueue(data) {
        const contentEl = document.getElementById(this.containerId + 'Content');
        const loadingEl = document.getElementById(this.containerId + 'Loading');
        
        if (!contentEl) return;
        
        let html = '';
        
        // Render queue stats
        if (data.stats) {
            html += this.renderStats(data.stats);
        }
        
        // Render doctor info if enabled
        if (this.options.showDoctorInfo && data.doctors && data.doctors.length > 0) {
            html += this.renderDoctors(data.doctors);
        }
        
        // Render queue list
        if (data.queues && data.queues.length > 0) {
            html += this.renderQueueList(data.queues);
        } else if (this.options.showEmptyMessage) {
            html += this.renderEmptyState();
        }
        
        // Add last updated timestamp
        if (this.lastUpdate) {
            html += `<div class="last-updated">Last updated: ${this.lastUpdate.toLocaleTimeString()}</div>`;
        }
        
        contentEl.innerHTML = html;
        contentEl.style.display = 'block';
        
        if (loadingEl) loadingEl.style.display = 'none';
        
        // Add animation class
        contentEl.classList.add('queue-updating');
        setTimeout(() => {
            contentEl.classList.remove('queue-updating');
        }, 300);
    }
    
    renderStats(stats) {
        return `
            <div class="queue-stats">
                <div class="queue-stat">
                    <span class="queue-stat-value">${stats.total_patients || 0}</span>
                    <span class="queue-stat-label">Total Patients</span>
                </div>
                <div class="queue-stat">
                    <span class="queue-stat-value">${stats.current_turn || '---'}</span>
                    <span class="queue-stat-label">Current Turn</span>
                </div>
                <div class="queue-stat">
                    <span class="queue-stat-value">${stats.waiting || 0}</span>
                    <span class="queue-stat-label">Waiting</span>
                </div>
            </div>
        `;
    }
    
    renderDoctors(doctors) {
        return doctors.map(doctor => `
            <div class="doctor-info">
                <div class="doctor-name">Dr. ${doctor.full_name}</div>
                <div class="doctor-specialization">${doctor.specialization || 'General Practice'}</div>
            </div>
        `).join('');
    }
    
    renderQueueList(queues) {
        const queueItems = queues.slice(0, this.options.maxItems);
        
        return `
            <div class="queue-list">
                ${queueItems.map((patient, index) => this.renderQueueItem(patient, index)).join('')}
            </div>
        `;
    }
    
    renderQueueItem(patient, index) {
        const isCurrent = patient.status === 'in_progress';
        const isNext = index === 1 && !isCurrent;
        const itemClass = isCurrent ? 'current' : (isNext ? 'next' : '');
        
        return `
            <div class="queue-item ${itemClass}">
                <span class="turn-number">#${patient.turn_number}</span>
                <span class="patient-name">${patient.full_name}</span>
                <span class="appointment-time">${patient.appointment_time}</span>
            </div>
        `;
    }
    
    renderEmptyState() {
        return `
            <div class="queue-empty">
                <i class="fas fa-users"></i>
                <p>No patients in queue</p>
            </div>
        `;
    }
    
    destroy() {
        this.stopAutoRefresh();
        // Clean up any event listeners or references
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Auto-initialize function for backward compatibility
window.initLiveQueueWidgets = function() {
    // Find all live queue containers and initialize them
    const containers = document.querySelectorAll('[id*="LiveQueueStatus"]');
    containers.forEach(container => {
        const containerId = container.id;
        // Skip if already initialized
        if (container.dataset.initialized) return;
        
        // Determine options based on container ID
        let options = {
            refreshInterval: 10000,
            maxItems: 5
        };
        
        if (containerId.includes('admin')) {
            options.refreshInterval = 15000;
            options.maxItems = 8;
            options.showDoctorInfo = true;
        } else if (containerId.includes('queue')) {
            options.refreshInterval = 5000;
            options.maxItems = 10;
            options.showDoctorInfo = true;
            options.showEmptyMessage = true;
        }
        
        // Initialize widget
        new LiveQueueStatus(containerId, options);
        
        // Mark as initialized
        container.dataset.initialized = 'true';
    });
};

// Initialize on DOM content loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initLiveQueueWidgets);
} else {
    window.initLiveQueueWidgets();
}
