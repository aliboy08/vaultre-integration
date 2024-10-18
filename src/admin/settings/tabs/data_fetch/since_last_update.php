<?php
$last_check = get_option('ff_vaultre_last_check');
if( !$last_check ) return;
?>

<button class="button button-primary vaultre_get_data" data-last_update="<?php echo $last_check; ?>">
    Get data since last update (<?php echo $last_check; ?>)
</button> 

<br/><br/><hr/><br/>