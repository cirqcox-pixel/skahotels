<?php
/**
 * Admin layout — closing half (main shell, bootstrap JS, optional toast).
 *
 * Optional variables:
 *   $includeToast  — bool, include toast markup + showToast() helper (default: false)
 *   $toastMsg      — string, auto-fire toast on DOMContentLoaded
 *   $toastType     — 'success' | 'error' | 'warn' (default: 'success')
 */

$includeToast = $includeToast ?? !empty($toastMsg);
$toastType    = $toastType    ?? 'success';
?>
  </div><!-- /.ska-content -->
</main><!-- /.ska-main -->

<?php if ($includeToast): ?>
<div class="ska-toast" id="skaToast">
  <i id="skaToastIcon" class="fa-solid fa-circle-check"></i>
  <span id="skaToastMsg">Done!</span>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($includeToast): ?>
<script>
function showToast(msg, type) {
  type = type || 'success';
  var t  = document.getElementById('skaToast');
  var ic = document.getElementById('skaToastIcon');
  if (!t || !ic) return;
  document.getElementById('skaToastMsg').textContent = msg;
  t.className = 'ska-toast ska-toast--' + type;
  ic.className = type === 'success' ? 'fa-solid fa-circle-check'
               : type === 'error'   ? 'fa-solid fa-circle-xmark'
               : 'fa-solid fa-triangle-exclamation';
  t.classList.add('show');
  setTimeout(function () { t.classList.remove('show'); }, 4000);
}
<?php if (!empty($toastMsg)): ?>
window.addEventListener('DOMContentLoaded', function () {
  showToast(<?= json_encode($toastMsg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($toastType) ?>);
});
<?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>
