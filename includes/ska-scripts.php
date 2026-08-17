<?php
/**
 * Shared Supabase / static-host scripts — include before </body>
 */
?>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="<?= htmlspecialchars(($basePath ?? '') . 'assets/js/ska-config.js') ?>"></script>
<script src="<?= htmlspecialchars(($basePath ?? '') . 'assets/js/ska-api.js') ?>"></script>
<script src="<?= htmlspecialchars(($basePath ?? '') . 'assets/js/ska-forms.js') ?>"></script>
<script src="<?= htmlspecialchars(($basePath ?? '') . 'assets/js/ska-live.js') ?>"></script>
