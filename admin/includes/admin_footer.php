<?php
declare(strict_types=1);
?>
</div> <!-- admin-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
  const sidebar = document.getElementById('adminSidebar');
  const mainContent = document.querySelector('.admin-main');

  if (!sidebar || !mainContent) return;

  // Helpers
  const isOffcanvas = sidebar.classList.contains('offcanvas') || sidebar.classList.contains('offcanvas-start');

  function resetMain() {
    mainContent.style.transform = '';
    mainContent.style.transition = '';
  }

  function shiftMain(px) {
    mainContent.style.transition = 'transform .2s ease';
    mainContent.style.transform = `translateX(${px}px)`;
  }

  // Only apply shifting for mobile offcanvas behavior
  if (isOffcanvas) {
    sidebar.addEventListener('show.bs.offcanvas', function () {
      // Shift the content slightly (nice effect)
      shiftMain(20);
    });

    sidebar.addEventListener('hidden.bs.offcanvas', function () {
      resetMain();
    });

    // If it gets closed by clicking backdrop, ensure we reset
    sidebar.addEventListener('hide.bs.offcanvas', function () {
      // do nothing here; "hidden" will reset
    });
  }
})();
</script>

</body>
</html>
