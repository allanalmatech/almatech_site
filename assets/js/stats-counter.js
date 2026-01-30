/* assets/js/stats-counter.js
   Dependency-free stats counter (no counter.start()).
   Usage: put data-count="200" on an element, optional data-suffix="+"
*/

(function () {
  "use strict";

  function parseTarget(text) {
    const raw = (text || "").trim();

    // Extract first number from string (supports 100+, 200, 1,200 etc)
    const numMatch = raw.replace(/,/g, "").match(/(\d+(\.\d+)?)/);
    const number = numMatch ? parseFloat(numMatch[1]) : NaN;

    // Detect suffix/prefix around number
    if (!isNaN(number)) {
      const idx = raw.indexOf(numMatch[1]);
      const prefix = raw.slice(0, idx);
      const suffix = raw.slice(idx + numMatch[1].length);
      return { number, prefix, suffix };
    }

    // No number => return NaN to skip animation
    return { number: NaN, prefix: "", suffix: raw };
  }

  function formatNumber(n) {
    // keep integers clean
    if (Number.isInteger(n)) return String(n);
    return n.toFixed(1);
  }

  function animateCount(el, to, prefix, suffix, duration) {
    const start = 0;
    const startTime = performance.now();

    function tick(now) {
      const t = Math.min(1, (now - startTime) / duration);
      // easeOutCubic
      const eased = 1 - Math.pow(1 - t, 3);

      const current = start + (to - start) * eased;
      el.textContent = `${prefix}${formatNumber(Math.round(current))}${suffix}`;

      if (t < 1) requestAnimationFrame(tick);
      else el.textContent = `${prefix}${formatNumber(to)}${suffix}`;
    }

    requestAnimationFrame(tick);
  }

  function startCounting() {
    const items = document.querySelectorAll("[data-stat-counter]");
    if (!items.length) return;

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          const el = entry.target;
          if (el.dataset.counted === "1") {
            obs.unobserve(el);
            return;
          }
          el.dataset.counted = "1";

          // Prefer explicit data-count, else parse existing text
          const raw = (el.getAttribute("data-count") || el.textContent || "").trim();
          const { number, prefix, suffix } = parseTarget(raw);

          // If no number, do nothing
          if (isNaN(number)) {
            obs.unobserve(el);
            return;
          }

          const duration = parseInt(el.getAttribute("data-duration") || "1200", 10);
          animateCount(el, number, prefix, suffix, duration);

          obs.unobserve(el);
        });
      },
      { threshold: 0.35 }
    );

    items.forEach((el) => observer.observe(el));
  }

  function init() {
    // Delay slightly so layout is ready (optional)
    setTimeout(startCounting, 150);
  }

  document.addEventListener("DOMContentLoaded", init);
})();
