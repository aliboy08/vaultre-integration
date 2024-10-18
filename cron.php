<?php
if ( ! wp_next_scheduled( 'ff_vaultre_scheduled_update' ) && get_option('ff_vaultre_enable_cron') ) {
    wp_schedule_event( time(), 'twicedaily', 'ff_vaultre_scheduled_update' );
}

add_action('ff_vaultre_scheduled_update', function(){
    if( !get_option('ff_vaultre_enable_cron') ) return;
    include_once FF_VAULTRE_PATH . '/class-ff-vaultre.php';
    $vaultre = new FF_VaultRE();
    $vaultre->scheduled_update();
});