  </div><!-- #content -->
</div><!-- #main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.querySelector('.sidebar-toggle-btn');
  if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
    if (!sidebar.contains(e.target) && !(toggleBtn && toggleBtn.contains(e.target))) {
      closeSidebar();
    }
  }
});

// Confirm delete dialogs
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Weet u het zeker?')) {
      e.preventDefault();
    }
  });
});

// Auto-generate slug from title
const titleInputs = document.querySelectorAll('[data-slug-source]');
titleInputs.forEach(input => {
  const target = document.querySelector(input.dataset.slugSource);
  if (target && !target.dataset.slugManual) {
    input.addEventListener('input', function() {
      target.value = this.value
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    });
    target.addEventListener('input', function() {
      this.dataset.slugManual = 'true';
    });
  }
});
</script>
<?php if (isset($extraScript)): ?>
<script><?= $extraScript ?></script>
<?php endif; ?>
</body>
</html>
