<?php
/**
 * WhatsApp Direct Admissions & Support Float Component
 *
 * Renders a high-visibility floating WhatsApp button on all public portal pages.
 * Includes client-side device capability detection to automatically route:
 * - Mobile/Tablet visitors -> Native WhatsApp protocol intent (wa.me)
 * - Desktop/Laptop visitors -> WhatsApp Web interface (web.whatsapp.com)
 *
 * Target Institutional Contact: +91 93717 14253
 */
$waCleanPhone = '919371714253';
?>

<!-- Floating WhatsApp Direct Button -->
<a href="https://wa.me/<?php echo $waCleanPhone; ?>" 
   target="_blank" 
   rel="noopener noreferrer" 
   id="soet-whatsapp-direct-btn" 
   class="soet-wa-float-btn" 
   title="Chat on WhatsApp (+91 93717 14253)" 
   aria-label="Chat on WhatsApp">
    <div class="soet-wa-circle">
        <i class="fa-brands fa-whatsapp soet-wa-icon"></i>
        <span class="soet-wa-ping"></span>
    </div>
    <span class="soet-wa-tooltip">Chat with us on WhatsApp</span>
</a>

<style>
/* Floating WhatsApp Button Styling */
.soet-wa-float-btn {
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 99999;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.soet-wa-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #ffffff;
    border: 3.5px solid #ffffff;
    box-shadow: 0 10px 25px rgba(37, 211, 102, 0.5), 0 4px 10px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}

.soet-wa-float-btn:hover .soet-wa-circle {
    transform: scale(1.12) rotate(-6deg);
    box-shadow: 0 14px 32px rgba(37, 211, 102, 0.65), 0 6px 14px rgba(0, 0, 0, 0.2);
}

.soet-wa-icon {
    font-size: 34px;
    line-height: 1;
    color: #ffffff;
}

.soet-wa-ping {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background-color: #ffffff;
    border: 3px solid #25D366;
    border-radius: 50%;
    animation: soetWaPulse 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes soetWaPulse {
    75%, 100% {
        transform: scale(1.6);
        opacity: 0;
    }
}

.soet-wa-tooltip {
    background: #0f172a;
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Times New Roman', Times, serif;
    padding: 7px 14px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.12);
    white-space: nowrap;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.soet-wa-float-btn:hover .soet-wa-tooltip {
    opacity: 1;
    transform: translateX(0);
}

/* Mobile Devices Optimization */
@media (max-width: 576px) {
    .soet-wa-float-btn {
        bottom: 18px;
        left: 18px;
    }
    .soet-wa-circle {
        width: 54px;
        height: 54px;
        border-width: 3px;
    }
    .soet-wa-icon {
        font-size: 30px;
    }
    .soet-wa-tooltip {
        display: none;
    }
}
</style>

<script>
(function() {
    const waPhone = '<?php echo $waCleanPhone; ?>';
    
    function isMobileOrTablet() {
        const ua = navigator.userAgent || navigator.vendor || window.opera;
        const mobileRegex = /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i;
        const tabletRegex = /android|ipad|playbook|silk/i;
        return mobileRegex.test(ua) || (tabletRegex.test(ua) && ('ontouchstart' in window)) || (window.innerWidth <= 768 && ('ontouchstart' in window));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('soet-whatsapp-direct-btn');
        if (btn) {
            btn.addEventListener('click', function(e) {
                const isMobile = isMobileOrTablet();
                
                if (isMobile) {
                    // Mobile/Tablet: Open via standard wa.me or native intent
                    // Default href https://wa.me/919371714253 triggers the native app automatically on iOS and Android
                    // If needed, let native link navigation handle it
                    return true;
                } else {
                    // Desktop / Laptop: Open WhatsApp Web directly in a new tab
                    e.preventDefault();
                    const desktopUrl = 'https://web.whatsapp.com/send?phone=' + waPhone;
                    window.open(desktopUrl, '_blank', 'noopener,noreferrer');
                }
            });
        }
    });
})();
</script>
