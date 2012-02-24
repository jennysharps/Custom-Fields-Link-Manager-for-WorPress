<?php
//If uninstall not called outside of WordPress, exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();
	
//Delete option from options table
delete_option( 'jls_cflm_options' );
delete_option( 'jls_cflm_stored_data' );
delete_option( 'jls_cflm_count' );

?>