<?php
ff_modules_load_settings_page();
$settings = new FF_Modules_Settings_Page();
?>

<form action="" method="POST">

    <?php
    $settings->input_text([
        'key' => 'ff_vaultre_api_key',
        'label' => 'VaultRE API Key',
    ]);

    $settings->input_text([
        'key' => 'ff_vaultre_access_token',
        'label' => 'VaultRE Access Token',
    ]);

    $settings->select([
        'key' => 'ff_vaultre_post_type',
        'label' => 'Select Post Type',
        'description' => 'Select post type to attach VaultRE data into',
        'options' => get_post_types(),
    ]);

    $settings->checkbox([
        'key' => 'ff_vaultre_enable_cron',
        'label' => 'Enable cron',
    ]);
    
    $settings->save_button();
    ?>
    
</form>