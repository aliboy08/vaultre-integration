<?php
$plugin->vite->enqueue('data_fetch', 'src/admin/settings/tabs/data_fetch/data_fetch.js');
?>

<?php include 'since_last_update.php'; ?>

<button class="button button-primary vaultre_get_data">Get all data</button>

<div class="vaultre_results"></div>