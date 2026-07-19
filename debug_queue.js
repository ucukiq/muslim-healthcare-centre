// Debug script to test queue widget functionality
console.log('Debug: Starting queue widget test');

// Test if the container exists
const container = document.getElementById('adminLiveQueueStatus');
console.log('Debug: Container found:', !!container);

if (container) {
    // Test if loading element exists
    const loadingEl = container.querySelector('[id$="Loading"]');
    console.log('Debug: Loading element found:', !!loadingEl);
    console.log('Debug: Loading element ID:', loadingEl?.id);
    
    // Test if content element exists
    const contentEl = container.querySelector('[id$="Content"]');
    console.log('Debug: Content element found:', !!contentEl);
    console.log('Debug: Content element ID:', contentEl?.id);
    
    // Test API call
    console.log('Debug: Testing API call...');
    fetch('../api/live_queue_status.php')
        .then(response => response.json())
        .then(data => {
            console.log('Debug: API Response:', data);
            
            // Manually hide loading and show content
            if (loadingEl) loadingEl.style.display = 'none';
            if (contentEl) {
                contentEl.style.display = 'block';
                contentEl.innerHTML = '<div class="text-center py-3"><i class="fas fa-check-circle text-success"></i><p>Debug: API working!</p></div>';
            }
        })
        .catch(error => {
            console.error('Debug: API Error:', error);
        });
} else {
    console.error('Debug: Container not found!');
}
