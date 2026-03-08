/**
 * 3D Coin Badge Interactive Effect
 * Gives each .badge-coin a 3D tilt + metallic shine on mouse move.
 */
(function () {
    'use strict';

    const MAX_TILT = 28;      // max degrees of tilt
    const SPRING = 0.12;      // spring factor for idle reset
    const SHINE_STRENGTH = 0.7;

    function initBadge(el) {
        if (el.dataset.badge3dInit) return;
        el.dataset.badge3dInit = '1';

        const inner = el.querySelector('.badge-coin-inner');
        if (!inner) return;

        // Create shine layer
        const shine = document.createElement('div');
        shine.className = 'badge-shine';
        inner.appendChild(shine);

        let rafId = null;
        let currentX = 0;
        let currentY = 0;
        let targetX = 0;
        let targetY = 0;
        let isHovered = false;

        function applyTransform(x, y) {
            const rotY = x * MAX_TILT;
            const rotX = -y * MAX_TILT;
            inner.style.transform = `perspective(600px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale3d(1.08, 1.08, 1.08)`;

            // Shine: position based on tilt angle
            const shineX = (x + 1) / 2 * 100;
            const shineY = (y + 1) / 2 * 100;
            const intensity = Math.sqrt(x * x + y * y) * SHINE_STRENGTH;
            shine.style.background = `radial-gradient(circle at ${shineX}% ${shineY}%, rgba(255,255,255,${intensity * 0.7}) 0%, rgba(255,255,255,${intensity * 0.2}) 40%, transparent 70%)`;
            shine.style.opacity = '1';
        }

        function resetTransform() {
            inner.style.transform = 'perspective(600px) rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
            shine.style.opacity = '0';
        }

        function springLoop() {
            if (!isHovered) {
                currentX += (0 - currentX) * SPRING;
                currentY += (0 - currentY) * SPRING;
                if (Math.abs(currentX) < 0.001 && Math.abs(currentY) < 0.001) {
                    currentX = 0;
                    currentY = 0;
                    resetTransform();
                    rafId = null;
                    return;
                }
                applyTransform(currentX, currentY);
            }
            rafId = requestAnimationFrame(springLoop);
        }

        el.addEventListener('mousemove', function (e) {
            const rect = el.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            targetX = (e.clientX - cx) / (rect.width / 2);
            targetY = (e.clientY - cy) / (rect.height / 2);
            // Clamp to [-1, 1]
            targetX = Math.max(-1, Math.min(1, targetX));
            targetY = Math.max(-1, Math.min(1, targetY));
            currentX = targetX;
            currentY = targetY;
            applyTransform(currentX, currentY);
        });

        el.addEventListener('mouseenter', function () {
            isHovered = true;
            if (rafId) cancelAnimationFrame(rafId);
        });

        el.addEventListener('mouseleave', function () {
            isHovered = false;
            if (!rafId) {
                rafId = requestAnimationFrame(springLoop);
            }
        });
    }

    // Idle float animation via CSS -- apply class staggered
    function initFloatAnimation() {
        const coins = document.querySelectorAll('.badge-coin.earned');
        coins.forEach(function (el, i) {
            el.style.animationDelay = (i * 0.18) + 's';
        });
    }

    function initAll() {
        document.querySelectorAll('.badge-coin').forEach(initBadge);
        initFloatAnimation();
    }

    // Init on DOM ready and after any dynamic content
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Expose for re-init after dynamic content
    window.initBadge3D = initAll;
})();
