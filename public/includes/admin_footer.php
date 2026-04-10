<?php
/* ============================================
   ADMIN FOOTER INCLUDE
   ============================================ */
date_default_timezone_set('Asia/Manila');
?>

<!-- Admin Footer -->
<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; 2025 Amuning Tokobe Enterprise System. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <p id="currentDateTime"><?php echo date('F j, Y | g:i:s A'); ?></p>
        </div>
    </div>
</footer>

<!-- Admin Footer Styles -->
<style>
.admin-footer {
    background: white;
    color: #1e293b;
    padding: 1rem 0;
    margin-top: auto;
    border-top: 1px solid #e2e8f0;
    width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    position: relative;
    z-index: 1001;
}

.footer-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.footer-content p {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 500;
}

.footer-left,
.footer-right {
    flex: 1;
}

.footer-right {
    text-align: right;
}

/* Mobile adjustments */
@media (max-width: 1024px) {
    .admin-footer {
        margin-left: 0;
        width: 100% !important;
    }

    .footer-content {
        width: 100%;
    }
}

/* Tablet adjustments */
@media (max-width: 768px) {
    .footer-content {
        padding: 0 1rem;
        flex-direction: column !important;
        gap: 0.5rem;
        text-align: center;
        justify-content: center;
        align-items: center;
    }

    .footer-content p {
        font-size: 0.8rem;
        line-height: 1.4;
        text-align: center;
    }

    .footer-left,
    .footer-right {
        flex: none;
        text-align: center;
        width: 100%;
        justify-content: center;
    }

    .footer-right {
        text-align: center;
    }
}

/* Small mobile adjustments */
@media (max-width: 480px) {
    .footer-content {
        padding: 0 0.75rem;
        gap: 0.75rem;
    }

    .footer-content p {
        font-size: 0.75rem;
        word-wrap: break-word;
        hyphens: auto;
    }

    .admin-footer {
        padding: 0.75rem 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateDateTime() {
        const now = new Date();
        // Calculate Manila time (UTC+8) from local time
        const manilaOffsetMinutes = 8 * 60; // Manila is UTC+8
        const localOffsetMinutes = now.getTimezoneOffset(); // minutes from UTC to local
        const offsetDiff = (manilaOffsetMinutes + localOffsetMinutes) * 60 * 1000; // milliseconds
        const manilaTime = new Date(now.getTime() + offsetDiff);

        const formatted = manilaTime.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) + ' | ' + manilaTime.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        document.getElementById('currentDateTime').textContent = formatted;
    }

    // Update immediately
    updateDateTime();

    // Update every second
    setInterval(updateDateTime, 1000);
});
</script>
