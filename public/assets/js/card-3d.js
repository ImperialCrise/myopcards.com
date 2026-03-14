/**
 * card-3d.js — Interactive 3D card effect with foil, glare, and edge lighting.
 * Usage: initCard3D('card3d')
 * Expects DOM elements: #id, #idInner, #idImg, #idGlare, #idFoil, #idEdge
 */
(function (global) {
    function initCard3D(id) {
        var wrap  = document.getElementById(id);
        var inner = document.getElementById(id + 'Inner');
        var img   = document.getElementById(id + 'Img');
        var glare = document.getElementById(id + 'Glare');
        var foil  = document.getElementById(id + 'Foil');
        var edge  = document.getElementById(id + 'Edge');

        if (!wrap || !inner) return;

        var maxTilt    = 20;
        var edgeLayers = 10;
        var isActive   = false;
        var introDone  = false;
        var shimmerBase = 0; // slowly animated base angle for idle shimmer
        var hintEl     = document.getElementById(id + 'Hint');

        // ── Intro animation (subtle tilt to show interactivity) ───────────────

        function playIntroAnim() {
            var duration = 1500;
            var introTilt = 8;
            var startTime = null;
            var introRaf;

            function tick(now) {
                if (!startTime) startTime = now;
                if (isActive) return; // user interacted, stop
                var elapsed = now - startTime;
                var t = Math.min(elapsed / duration, 1);
                // easing: ease-in-out
                var eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
                // sinusoid: 0 -> left (-1) -> 0 -> right (1) -> 0
                var phase = -Math.sin(eased * Math.PI * 2);
                var rotY = phase * introTilt;
                inner.style.transition = 'none';
                inner.style.transform = 'rotateX(0deg) rotateY(' + rotY.toFixed(1) + 'deg) scale3d(1.02,1.02,1.02)';
                if (t < 1) {
                    introRaf = requestAnimationFrame(tick);
                } else {
                    inner.style.transition = 'transform 0.5s cubic-bezier(.03,.98,.52,.99)';
                    inner.style.transform = 'rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
                    introDone = true;
                }
            }

            introRaf = requestAnimationFrame(tick);
        }

        // Idle shimmer: subtle foil sweep even when no mouse interaction
        var shimmerRaf;
        function runIdleShimmer() {
            if (isActive) return;
            shimmerBase = (shimmerBase + 0.3) % 360;
            if (foil) {
                var a1 = shimmerBase;
                var a2 = (shimmerBase + 60) % 360;
                var a3 = (shimmerBase + 120) % 360;
                var a4 = (shimmerBase + 180) % 360;
                var a5 = (shimmerBase + 240) % 360;
                var a6 = (shimmerBase + 300) % 360;
                foil.style.opacity = '0.18';
                foil.style.background = buildFoilGradient(shimmerBase, 0.10);
            }
            shimmerRaf = requestAnimationFrame(runIdleShimmer);
        }

        function stopIdleShimmer() {
            cancelAnimationFrame(shimmerRaf);
        }

        // ── Helpers ───────────────────────────────────────────────────────────

        function buildFoilGradient(angleDeg, opacity) {
            var o = opacity.toFixed(2);
            return (
                'linear-gradient(' + angleDeg.toFixed(1) + 'deg,' +
                'rgba(255,0,0,'     + o + ') 0%,'   +
                'rgba(255,140,0,'   + o + ') 16%,'  +
                'rgba(255,230,0,'   + o + ') 33%,'  +
                'rgba(0,200,80,'    + o + ') 50%,'  +
                'rgba(0,120,255,'   + o + ') 66%,'  +
                'rgba(140,0,255,'   + o + ') 83%,'  +
                'rgba(255,0,160,'   + o + ') 100%)'
            );
        }

        function buildEdgeGradient(rightIntensity, leftIntensity, topIntensity, bottomIntensity) {
            // Each "border" is a thin linear-gradient on each side.
            // We compose them as 4 background layers (each only covers the relevant edge).
            var edgeWidth = '3px';
            var edgeColor = function(i) {
                var clamped = Math.max(0, Math.min(1, i));
                var r = Math.round(180 + clamped * 75);
                var g = Math.round(180 + clamped * 75);
                var b = Math.round(200 + clamped * 55);
                return 'rgba(' + r + ',' + g + ',' + b + ',' + (0.5 + clamped * 0.5).toFixed(2) + ')';
            };
            return [
                'linear-gradient(to left, '   + edgeColor(rightIntensity)  + ', transparent) right  center / ' + edgeWidth + ' 100% no-repeat',
                'linear-gradient(to right, '  + edgeColor(leftIntensity)   + ', transparent) left   center / ' + edgeWidth + ' 100% no-repeat',
                'linear-gradient(to bottom, ' + edgeColor(topIntensity)    + ', transparent) center top    / 100% ' + edgeWidth + ' no-repeat',
                'linear-gradient(to top, '    + edgeColor(bottomIntensity) + ', transparent) center bottom / 100% ' + edgeWidth + ' no-repeat',
            ].join(', ');
        }

        // ── Main move handler ─────────────────────────────────────────────────

        function onMove(e) {
            isActive = true;
            stopIdleShimmer();
            if (hintEl) {
                hintEl.style.transition = 'opacity 0.5s ease';
                hintEl.style.opacity = '0';
            }

            var rect    = wrap.getBoundingClientRect();
            var cx      = rect.left + rect.width / 2;
            var cy      = rect.top  + rect.height / 2;
            var clientX = e.touches ? e.touches[0].clientX : e.clientX;
            var clientY = e.touches ? e.touches[0].clientY : e.clientY;
            var dx      = Math.max(-1, Math.min(1, (clientX - cx) / (rect.width  / 2)));
            var dy      = Math.max(-1, Math.min(1, (clientY - cy) / (rect.height / 2)));
            var rotY    = dx * maxTilt;
            var rotX    = -dy * maxTilt;
            var intensity = (Math.abs(dx) + Math.abs(dy)) / 2;

            // 1. Tilt transform
            inner.style.transition = 'transform 0.1s ease-out';
            inner.style.transform  = 'rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) scale3d(1.03,1.03,1.03)';

            // 2. Edge box-shadow (3D thickness)
            var edgeShadows = [];
            for (var i = 1; i <= edgeLayers; i++) {
                var ox = (-rotY / maxTilt) * i * 0.6;
                var oy = (rotX  / maxTilt) * i * 0.6;
                var b  = Math.round(210 - (i / edgeLayers) * 80);
                edgeShadows.push(ox.toFixed(1) + 'px ' + oy.toFixed(1) + 'px 0 rgb(' + b + ',' + (b - 8) + ',' + (b - 16) + ')');
            }
            var gsx = (-rotY / maxTilt) * 15;
            var gsy = 10 + (rotX / maxTilt) * 10;
            edgeShadows.push(gsx.toFixed(1) + 'px ' + gsy.toFixed(1) + 'px 30px rgba(0,0,0,0.45)');
            inner.style.boxShadow = edgeShadows.join(', ');

            // 3. Glare (metallic radial highlight)
            if (glare) {
                var gx = ((dx + 1) / 2 * 100).toFixed(1);
                var gy = ((dy + 1) / 2 * 100).toFixed(1);
                var glareOpacity = (0.10 + intensity * 0.28).toFixed(2);
                var glareOpacity2 = (0.04 + intensity * 0.10).toFixed(2);
                glare.style.background = (
                    'radial-gradient(ellipse at ' + gx + '% ' + gy + '%, rgba(255,255,255,' + glareOpacity + ') 0%, rgba(200,210,255,' + glareOpacity2 + ') 40%, transparent 70%)'
                );
                glare.style.opacity = '1';
            }

            // 4. Foil (rainbow holographic overlay, angle follows tilt)
            if (foil) {
                // Angle of rainbow rotates based on dx: gives "looking around" holo feel
                var foilAngle = 110 + dx * 80 + dy * 40;
                var foilOpacity = 0.12 + intensity * 0.38;
                foil.style.opacity    = Math.min(foilOpacity, 0.55).toFixed(2);
                foil.style.background = buildFoilGradient(foilAngle, 1.0);
                // Using mix-blend-mode:overlay so it lightens bright areas and adds hue to dark ones
            }

            // 5. Edge lighting (inset border that brightens the side facing the light)
            if (edge) {
                // rightIntensity: high when tilting right (dx > 0)
                var rightI  = Math.max(0,  dx);
                var leftI   = Math.max(0, -dx);
                var topI    = Math.max(0, -dy);
                var bottomI = Math.max(0,  dy);
                edge.style.background = buildEdgeGradient(rightI, leftI, topI, bottomI);
                edge.style.opacity = (0.4 + intensity * 0.6).toFixed(2);
            }
        }

        function onLeave() {
            isActive = false;
            inner.style.transition  = 'transform 0.6s cubic-bezier(.03,.98,.52,.99), box-shadow 0.6s ease';
            inner.style.transform   = 'rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
            inner.style.boxShadow   = '0 8px 30px rgba(0,0,0,0.35)';

            if (glare) {
                glare.style.transition = 'opacity 0.6s ease';
                glare.style.opacity    = '0';
            }
            if (foil) {
                foil.style.transition = 'opacity 0.6s ease';
                foil.style.opacity    = '0';
            }
            if (edge) {
                edge.style.transition = 'opacity 0.6s ease';
                edge.style.opacity    = '0';
            }

            // Restart idle shimmer after a brief pause
            setTimeout(function () {
                if (!isActive) runIdleShimmer();
            }, 700);
        }

        // ── Init ──────────────────────────────────────────────────────────────

        inner.style.boxShadow = '0 8px 30px rgba(0,0,0,0.35)';

        wrap.addEventListener('mousemove',  onMove);
        wrap.addEventListener('mouseleave', onLeave);
        wrap.addEventListener('touchmove',  function (e) { e.preventDefault(); onMove(e); }, { passive: false });
        wrap.addEventListener('touchend',   onLeave);

        // Intro animation (400ms delay, then ~1.5s tilt demo)
        setTimeout(playIntroAnim, 400);

        // Start idle shimmer
        runIdleShimmer();
    }

    global.initCard3D = initCard3D;
})(window);
