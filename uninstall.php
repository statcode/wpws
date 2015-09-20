<?php
//remove any additonal options and custom tables

require plugin_dir_path( __FILE__ ) . '/wpws_plugin.php';

// Check that file was called from WordPress admin
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

//Call static function uninstall
$wpws->uninstall();


?>