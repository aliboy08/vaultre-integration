<?php
FF_PLUGINS_VITE->enqueue('settings_page', 'src/settings-page/settings-page.js');

echo '<h2>'. $this->plugin_name .' Settings</h2>';

$tabs = [
    'general' => 'General',
    'data_fetch/data_fetch' => 'Data Fetch',
];

if( function_exists('ff_admin_tabs') ) {
    ff_admin_tabs( $tabs, __DIR__, $this->plugin_slug, $this );
}
else {
    pre_debug('Update fivebyfive plugin to latest');
}