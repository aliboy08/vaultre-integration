<?php
if( !isset($_GET['vaultre_test']) ) return;

add_action('wp_footer', function(){

    pre_debug('wp_footer');
    $vaultre = ff_vaultre();
    pre_debug($vaultre);
    // $vaultre->scheduled_update();

    // $q = new WP_Query([
    //     'post_type' => 'property',
    //     'showposts' => -1,
    //     'fields' => 'ids',
    //     'no_found_rows' => true,
    // ]);
    // foreach( $q->posts as $post_id ) {
    //     $title = get_the_title($post_id);
    //     $data = get_post_meta($post_id, 'vaultre_data', true);
    //     pre_debug([
    //         $post_id => $title,
    //         'data' => $data,
    //     ]);
    // }
    
});