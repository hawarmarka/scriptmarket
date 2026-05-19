<?php
/**
 * Admin layout footer
 */
?>
</main>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<script>
// Sidebar mobile toggle
(function(){
  const btn = document.getElementById('adminToggle');
  const sb = document.getElementById('adminSidebar');
  if (!btn || !sb) return;
  btn.addEventListener('click', () => sb.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 980 && sb.classList.contains('open') && !sb.contains(e.target) && !btn.contains(e.target)) {
      sb.classList.remove('open');
    }
  });
})();

// Show admin user info on wider screens
function adjustHeader() {
  const info = document.querySelector('.admin-user-info');
  if (info) info.style.display = window.innerWidth > 768 ? 'block' : 'none';
}
adjustHeader();
window.addEventListener('resize', adjustHeader);

// Confirm before destructive action
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', (e) => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});
</script>
</body>
</html>
