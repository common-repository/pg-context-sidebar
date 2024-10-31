<?php
	// If uninstall is not calledcrom WordPress then exit
	if ( !defined( 'WP_UNINSTALL_PLUGIN') )
		exit;
	
	
	$options = get_option('pgeek_cs_options');
	if ( $options['delete-settings-on-uninstall'] == true){
		delete_option('pgeek_cs_options');
		delete_option('pgeek_cs_option_ver');
	}
	
	//TODO: write code to remove all meta data from posts

