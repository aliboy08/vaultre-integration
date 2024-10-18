<?php
function ff_vaultre_data_fetch_ajax(){
    $res = [];
    $vaultre = ff_vaultre();
    $request_url = $_POST['request_url'];
    $res['request_url'] = $request_url;
    $data = $vaultre->get_data($request_url);
    $res['data'] = $data;
    wp_send_json($res);
}
add_action('wp_ajax_vaultre_data_fetch', 'ff_vaultre_data_fetch_ajax');

function ff_vaultre_process_items_ajax(){
    $res = [];
    $vaultre = ff_vaultre();
    // $res['payload'] = $_POST;
    foreach( $_POST['items'] as $item ) {
        $vaultre->update_item($item);
    }

    if( isset($_POST['is_last']) ) {
        $res['complete'] = true;
        update_option('ff_vaultre_last_check', date("Y-m-d").'T00:00:00Z', false);
    }

    wp_send_json($res);
}
add_action('wp_ajax_vaultre_process_items', 'ff_vaultre_process_items_ajax');