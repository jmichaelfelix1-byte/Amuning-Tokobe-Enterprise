// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('active');
    
    if (overlay) {
        overlay.classList.toggle('active');
    }
}

// Close sidebar when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    // Create overlay element if it doesn't exist
    if (!document.getElementById('sidebarOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', toggleSidebar);
        document.body.appendChild(overlay);
    }
    
    // Close sidebar when clicking overlay
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar on window resize if screen is larger than tablet
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
    
    // Prevent body scroll when sidebar is open on mobile
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (sidebar.classList.contains('active') && window.innerWidth <= 1024) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
    }
});