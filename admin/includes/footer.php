  </main><!-- #main-content -->
</div><!-- #main-wrap -->

<!-- Global confirm dialog (WCAG 2.4.3 focus management) -->
<sl-dialog id="cms-confirm-dialog"
           label="Bevestigen"
           aria-describedby="cms-confirm-msg">
  <p id="cms-confirm-msg" style="margin:0;"></p>
  <div slot="footer" style="display:flex;gap:.5rem;justify-content:flex-end;">
    <sl-button id="cms-confirm-cancel" variant="neutral">Annuleren</sl-button>
    <sl-button id="cms-confirm-ok" variant="danger">Bevestigen</sl-button>
  </div>
</sl-dialog>

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

// Global confirm dialog using sl-dialog
let _confirmResolve = null;
const _confirmDialog = document.getElementById('cms-confirm-dialog');
const _confirmOk     = document.getElementById('cms-confirm-ok');
const _confirmCancel = document.getElementById('cms-confirm-cancel');

function cmsConfirm(message, okLabel) {
  document.getElementById('cms-confirm-msg').textContent = message || 'Weet u het zeker?';
  _confirmOk.textContent = okLabel || 'Bevestigen';
  _confirmDialog.show();
  return new Promise(resolve => { _confirmResolve = resolve; });
}

_confirmOk.addEventListener('click', () => {
  _confirmDialog.hide();
  if (_confirmResolve) { _confirmResolve(true); _confirmResolve = null; }
});
_confirmCancel.addEventListener('click', () => {
  _confirmDialog.hide();
  if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
});
_confirmDialog.addEventListener('sl-hide', () => {
  if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
});

// data-confirm attribute handler (link-based deletes)
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    const href = this.getAttribute('href') || this.dataset.href;
    const confirmed = await cmsConfirm(this.dataset.confirm || 'Weet u het zeker?', 'Verwijderen');
    if (confirmed && href) window.location.href = href;
  });
});

// Auto-generate slug from title (supports both native input and sl-input events)
document.querySelectorAll('[data-slug-source]').forEach(input => {
  const target = document.querySelector(input.dataset.slugSource);
  if (!target || target.dataset.slugManual) return;
  const slugify = val => val
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');
  const handler = function() { target.value = slugify(this.value); };
  input.addEventListener('input', handler);
  input.addEventListener('sl-input', handler);
  const manualHandler = function() { this.dataset.slugManual = 'true'; };
  target.addEventListener('input', manualHandler);
  target.addEventListener('sl-input', manualHandler);
});

// Global sl-alert toast helper
function showToast(type, msg) {
  const alert = document.createElement('sl-alert');
  const iconMap = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
  const variantMap = { success: 'success', error: 'danger', warning: 'warning', info: 'primary' };
  alert.variant = variantMap[type] || 'primary';
  alert.closable = true;
  alert.duration = 3000;
  alert.innerHTML = `<sl-icon slot="icon" name="${iconMap[type] || 'info-circle'}"></sl-icon>${msg}`;
  document.body.appendChild(alert);
  customElements.whenDefined('sl-alert').then(() => alert.toast());
}

function showAlert(msg, type) {
  showToast(type === 'danger' ? 'error' : type, msg);
}
</script>
<?php if (isset($extraScript)): ?>
<script><?= $extraScript ?></script>
<?php endif; ?>
<?php do_action('admin_footer'); ?>
</body>
</html>
