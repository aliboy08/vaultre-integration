<?php
class FF_VaultRE {

    public $base_url = 'https://ap-southeast-2.api.vaultre.com.au/api/v1.3';
    public $post_type;

    public function __construct() {
        $post_type = get_option('ff_vaultre_post_type');
        if( !$post_type ) {
            $post_type = 'property';
        }
        $this->post_type = $post_type;
    }

    function scheduled_update($last_check = false){

        if( !$last_check ) {
            $last_check = get_option('ff_vaultre_last_check');
            if( !$last_check ) {
                $last_check = '2024-10-08T00:00:00Z';
            }
        }

        $endpoints = [
            ["residential", "sale"],
            ["residential", "lease"],
            ["commercial", "sale"],
            ["commercial", "lease"],
            ["rural", "sale"],
            ["business", "sale"],
            ["land", "sale"],
            ["holidayRental", "lease"],
        ];

        foreach( $endpoints as $endpoint ) {

            $classification = $endpoint[0];
            $salelease = $endpoint[1];
        
            $request_url = '/properties/' . $classification . '/' . $salelease . '/available?pagesize=100';

            $data = $this->get_data($request_url);
        
            if( !$data['items'] ) continue;

            foreach( $data['items'] as $item ) {
                $this->update_item($item);
            }

            $this->scheduled_update_recursive($data);
        }
        
        update_option('ff_vaultre_last_check', date("Y-m-d").'T00:00:00Z', false);
    }
    
    function scheduled_update_v1($last_check = false){

        if( !$last_check ) {
            $last_check = get_option('ff_vaultre_last_check');
            if( !$last_check ) {
                $last_check = '2024-10-08T00:00:00Z';
            }
        }

        $request_url = '/properties/sale?modifiedSince='.$last_check;

        $data = $this->get_data($request_url);
        
        if( !$data['items'] ) return;

        foreach( $data['items'] as $item ) {
            $this->update_item($item);
        }
        
        $this->scheduled_update_recursive($data);
        update_option('ff_vaultre_last_check', date("Y-m-d").'T00:00:00Z', false);
    }
    
    function scheduled_update_recursive($data){
        if( !$data['urls'] ) return;
        if( !$data['urls']['next'] ) return;
        $request_url = $data['urls']['next'];
        $data = $this->get_data($request_url);
        $this->scheduled_update_recursive($data);
    }

    function update_item($item, $force_update = false){

        $post_id = $this->get_post_id($item['id']);

        if( !$force_update && $this->skip_update($post_id, $item) ) {
            pre_debug([
                'no_changes' => $post_id,
            ]);
            return; // no change
        }
        
        if( !$post_id ) {
            $post_id = $this->create_post($item);
        }
        else {
            // update post content
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $item['description'], 
            ]);
        }

        if( !$post_id ) return;

        update_post_meta($post_id, 'vaultre_data', $item);

        // $this->update_item_post_data($post_id, $item);

        // $this->assign_contact_staff($post_id, $item);

        $this->assign_featured_image($post_id, $item);

        // if( $type == 'Buy' ) {
        //     $this->update_property_sale_open_homes($item['id'], $item['saleLifeId'], $post_id);
        // }
        
        update_post_meta($post_id, 'vaultre_modified_time', $item['modified']);
    }

    function update_item_post_data($post_id, $item){

        $type = $item['status'] == 'unconditional' ? 'Sold' : 'Buy';

        // Category = Buy / Sold
        // $this->assign_terms($post_id, [$type], 'property_category');

        $property_type = $item['type']['propertyClass']['name']; // Land / Commercial / Residential
        if( $property_type == 'Residential' ) {
            $property_type = $item['type']['name']; // House / Unit
        }
        // $this->assign_terms($post_id, [$property_type], 'property_type');

        $suburb = $item['address']['suburb']['name'];

        // price
        // pricing is different per type: Buy = searchPrice | Search = salePrice
        $price = ( $type == 'Sold' ) ? $item['saleDetails']['salePrice'] : $item['searchPrice'];

        $metas = [
            'property_type' => $property_type,
            'property_sqm' => $item['landArea']['value'],
            'property_address' => $item['displayAddress'],
            'property_price' => $price,
            'property_no_of_beds' => $item['bed'],
            'property_no_of_baths' => $item['bath'],
            'property_no_of_car' => $item['garages'],
            'suburb' => $suburb,
            'heading' => $item['heading'],
            // 'location' => $this->get_location_post_id($suburb),
            'status' => $item['status'],
            'portal_status' => $item['portalStatus'],
            'under_offer' => $item['portalStatus'] === 'conditional',
        ];

        if( $type == 'Buy' ) {
            $metas['price_freeform_text'] = $item['displayPrice'];
        }

        // $this->update_post_metas($post_id, $metas);

    }

    function assign_featured_image($post_id, $item){

        if( !$item['photos'] ) return;

        // first image as featured image
        $featured_image_id = $this->upload_image_from_url($item['photos'][0]['url']);
        if( $featured_image_id ) {
            update_post_meta($post_id, '_thumbnail_id', $featured_image_id);
        };
    }

    function create_post($item){
        
        $suburb = $item['address']['suburb']['name'];
        $address = $item['displayAddress'];
        $post_title = $suburb .' - '. $address;

        $post_date = $item['inserted'];
        if( !$post_date ) {
            $post_date = $item['modified'];
        }

        $post_id = wp_insert_post([
            'post_title' => $post_title,
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'post_date' => $post_date,
        ]);

        update_post_meta($post_id, 'vaultre_id', $item['id']);

        return $post_id;
    }

    function skip_update($post_id, $item){
        if( !$post_id ) return false;
        $modified_time = get_post_meta($post_id, 'vaultre_modified_time', true);
        if( !$modified_time ) return false;
        return $modified_time >= $item['modified'];
    }

    function get_properties_data($type){

        $request_url = '/properties/residential/sale/available';

        if( $type == 'Sold' ) {
            $request_url = '/properties/residential/sale/sold';
        }

        return $this->get_data($request_url);
    }

    function get_data($request_url){

        list($code, $result) = $this->get($request_url);

        if ($code == 403) {
            echo "HTTP 403: Invalid API Key";
            return false;
        } elseif ($code == 401) {
            echo "HTTP 401: Invalid bearer token\n";
            return false;
        }
        
        $result = json_decode($result, true);
        return $result;
    }

    function get($endpoint) {

        $api_key = get_option('ff_vaultre_api_key');
        $access_token = get_option('ff_vaultre_access_token');
        if( !$api_key || !$access_token ) return;

        $ch = curl_init($this->get_url($endpoint));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Api-Key: ' . $api_key, 'Authorization: Bearer ' . $access_token));
        $result = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($response_code, $result);
    }

    function get_url($url) {
        return (strpos($url, $this->base_url) !== false) ? $url : $this->base_url . $url;
    }

    function get_post_id($id){

        $args = [
            'post_type' => $this->post_type,
            'showposts' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_key' => 'vaultre_id',
            'meta_value' => $id,
        ];
        
        $q = new WP_Query($args);
        if( !$q->posts ) return false;
        return $q->posts[0];
    }

    function upload_image_from_url($image_url, $post_id = '', $description = ''){
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $path_info = pathinfo($image_url);
        $file_name = $path_info['filename'];
        $image_id = $this->get_image_id_by_filename($file_name);

        if( !$image_id ) {
            // only upload if it doesn't exist in the media yet
            $image_id = media_sideload_image( $image_url, $post_id, $description, 'id' );
        }

        return $image_id;
    }

    function get_image_id_by_filename($file_name){
        global $wpdb;
        $query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid LIKE '%s' AND post_type = 'attachment' LIMIT 1;", '%'.$wpdb->esc_like($file_name).'%');
        $result = $wpdb->get_col($query);
        if( $result ) {
            return $result[0];
        }
        return false;
    }
    
    function assign_terms($post_id, $terms, $taxonomy, $append = false) {
        $term_ids = [];
        foreach( $terms as $term_name ) {
            $term = term_exists( $term_name, $taxonomy );
            if( $term ) {
                $term_id = $term['term_id'];
            }
            else {
                $term_id = wp_insert_term( $term_name, $taxonomy );
            }

            if( $term_id ) {
                $term_ids[] = $term_id;
            }
        }
    
        if( $term_ids ) {
            wp_set_post_terms( $post_id, $term_ids, $taxonomy, $append );
        }
    }

    function update_post_metas($post_id, $metas) {
        foreach( $metas as $meta_key => $meta_value ) {
            if( !$meta_value ) continue;
            $current_meta = get_post_meta( $post_id, $meta_key, true);
            if( $current_meta == $meta_value ) continue; // skip, no change
            update_post_meta($post_id, $meta_key, $meta_value);
        }
    }

    function test(){
        pre_debug('test');
    }

    // function get_location_post_id($title){
    //     $args = [
    //         'post_type' => 'location',
    //         'title' => $title,
    //         'showposts' => 1,
    //         'fields' => 'ids',
    //     ];
    //     $q = new WP_Query($args);
    //     if( $q->posts ) {
    //         return $q->posts[0];
    //     }
    //     return false;
    // }

    // function assign_contact_staff($post_id, $item) {
    //     $team_member_ids = $this->get_team_members_cache();

    //     $staff_ids = [];
    //     foreach( $item['contactStaff'] as $staff ) {
    //         $staff_name = $staff['firstName'] . ' ' . $staff['lastName'];
    //         if( isset($team_member_ids[$staff_name]) ) {
    //             $staff_id = $team_member_ids[$staff_name];
    //             $staff_ids[] = $staff_id;
    //         }
    //     }
    //     if( $staff_ids ) {
    //         update_post_meta($post_id, 'contact_staff', $staff_ids);
    //     }
    // }

    // function get_team_members_cache(){
    //     $cache_key = 'team_member_ids';
    //     $cache = get_transient($cache_key);
    //     if( $cache ) {
    //         return $cache;
    //     }

    //     $team_member_ids = $this->get_team_member_ids();
    //     set_transient($cache_key, $team_member_ids, 7200);
    //     return $team_member_ids;
    // }

    // function get_team_member_ids(){
    //     $team = [];
    //     $args = [
    //         'post_type' => 'team',
    //         'showposts' => -1,
    //         'no_found_rows' => true,
    //         'post_status' => [
    //             'publish',
    //             'draft',
    //         ],
    //     ];
    //     $q = new WP_Query($args);
    //     foreach($q->posts as $post){
    //         $team[$post->post_title] = $post->ID;
    //     }
    //     return $team;
    // }

    // function update_property_sale_open_homes( $property_id, $life_id, $post_id ){
    //     if( !$property_id || !$life_id || !$post_id ) return;
    //     $request_url = '/properties/'. $property_id .'/sale/'. $life_id .'/openHomes';
    //     list($code, $result) = $this->get($request_url);
    //     $result = json_decode($result, true);
    //     if( !isset($result['items']) ) return;
    //     update_post_meta( $post_id, 'vaultre_data_open_homes', $result['items'] );
    // }
    
}

$GLOBALS['ff_vaultre'] = new FF_VaultRE();
function ff_vaultre(){
    return $GLOBALS['ff_vaultre'];
}