<?php
/*
 * Plugin name: FF VaultRE
 * Version: 1.0
 * Description: VaultRE Integration
 * Plugin URI: https://www.fivebyfive.com.au/
 * Author: Five by Five
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

namespace FFPlugin\VaultRE;

define('FF_VAULTRE_PATH', __DIR__);

class Plugin {
    
    public $plugin_name;
    public $plugin_slug;
    public $plugin_url;
    public $plugin_path;
    public $vite;

    public function __construct() {

        $this->plugin_name = 'FF VaultRE';
        $this->plugin_slug = 'ff_vaultre';
        $this->plugin_url = plugins_url('/', __FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);

        add_action('init', [ $this, 'init' ]);
    }

    public function init(){

        $this->vite = new \FF_Vite([
            'id' => $this->plugin_slug,
            'url' => $this->plugin_url,
            'path' => $this->plugin_path,
        ]);

        // admin settings page
        // /wp-admin/admin.php?page=plugin_slug
        add_action('admin_menu', function(){
            add_submenu_page( 'fivebyfive', $this->plugin_name, $this->plugin_name, 'manage_options', $this->plugin_slug, function(){
                include 'src/admin/settings/settings.php';
            });
        });
    }
    
}

new Plugin();

// include_once 'src/test/init.php';
include_once 'class-ff-vaultre.php';
include_once 'cron.php';

if( is_admin() ) {
    include_once 'admin-functions.php';
}