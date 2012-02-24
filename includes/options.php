<?php

// Add a menu for our option page to the options menu
// Add a menu for our management page to the Posts menu
add_action('admin_menu', 'jls_cflm_add_page');
function jls_cflm_add_page() {
	add_options_page( 'Custom Plugin Page', 'CFLM Options', 'manage_options', 'jls_cflm', 'jls_cflm_options_page' );
	add_posts_page( 'CF Link Manager', 'CF Link Manager', 'manage_options','jls_main', 'jls_cflm_main_page' );
}


// Draw the option page
function jls_cflm_options_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>Custom Fields Link Manager Options</h2>
		<form action="options.php" method="post">
			<?php settings_fields('jls_cflm_options'); ?>
			<?php do_settings_sections('jls_cflm'); ?>
			<?php 	
				$add_url = remove_query_arg( array('field_id') );
				$add_url = add_query_arg( array('jls_action'=>'add'), $add_url );
				$nonced_url_add = wp_nonce_url( $add_url, 'jls_cflm-add_field' ); 
			?>
			<p>Save changes before adding another field. Unsaved changes will be lost.</p>
			<input class='button-primary' name="Submit" type="submit" value=" Save Changes " /> &nbsp; <a class='button' href='<?php echo $nonced_url_add; ?>'> Add Another Field </a>
		</form>
	</div>
	<?php
}


// Catch any action parameter in query string
add_action( 'admin_init', 'jls_cflm_do_action' );

// Proceed to requested jls_action if applicable
function jls_cflm_do_action() {
    if( !isset( $_REQUEST['jls_action'] ) )
        return;

    if( !current_user_can( 'manage_options' ) )
        wp_die( 'Insufficient privileges!' );
        
    $field_id     = $_REQUEST['field_id'];
    $action = $_REQUEST['jls_action'];
    
    if( $action == 'done' ) {
        add_action( 'admin_notices', 'jls_cflm_message' );
        return;
    }
    
    check_admin_referer( 'jls_cflm-'.$action.'_field'.$field_id );
    
    switch( $action ) {
        case 'add':
			//add code here
			$options = get_option('jls_cflm_count');
			if ( ! $options ) { update_option( 'jls_cflm_count', 0 ); }
			update_option( 'jls_cflm_count', trim(preg_replace( "/[^0-9']/", "", ++$options)) );
            wp_redirect( add_query_arg( array( 'jls_action' => 'done' ) ) );
            break;
        case 'remove':
            $options = get_option('jls_cflm_options');
            $field_name = $options[$field_id]['meta_field'];
            unset($options[$field_id]);
            update_option( 'jls_cflm_options', array_values($options) );
            update_option( 'jls_cflm_count', ((get_option('jls_cflm_count'))-1));
            //remove stored data for this field
            $stored = get_option( 'jls_cflm_stored_data' );
            unset($stored[$field_name]);
            update_option( 'jls_cflm_stored_data', $stored );
            wp_redirect( add_query_arg( array( 'jls_action' => 'done' ) ) );
            break;
    }
}

// Admin notice
function jls_cflm_message() {
    echo "<div class='updated'><p>Action completed</p></div>";
}



// Register and define the settings
add_action('admin_init', 'jls_cflm_admin_init');
function jls_cflm_admin_init(){
	register_setting( 
		'jls_cflm_options', 
		'jls_cflm_options', 
		'jls_cflm_options_validate' 
	);
	add_settings_section(
		'jls_cflm_main', 
		'Main Settings', 
		'jls_cflm_section_text', 
		'jls_cflm'
	);
	
	$times = get_option('jls_cflm_count');
	$x = 0;
	
	while ($x < $times) {	
		add_settings_field(
			'jls_cflm_meta_field'.$x, 
			 ($x+1).'. Meta key of custom field', 
			'jls_cflm_setting_string', 
			'jls_cflm', 
			'jls_cflm_main',
			 $x
		);
		add_settings_field(
			'jls_cflm_title'.$x, 
			' &nbsp; &nbsp; Display name for field', 
			'jls_cflm_setting_title', 
			'jls_cflm', 
			'jls_cflm_main',
			 $x
		);
		add_settings_field(
			'jls_cflm_data_type'.$x, 
			' &nbsp; &nbsp; URL format to search for', 
			'jls_cflm_setting_data_type', 
			'jls_cflm', 
			'jls_cflm_main',
			 $x
		);
		++$x;
	}
}


// Draw the section header
function jls_cflm_section_text() {
	echo '<p>Please choose your settings below.</p>
		  <p style="position: relative; top: -10px; " >(The word <span style="font-weight: bold;">CONTENT</span> is reserved for the default Wordpress Post Content Field)</p>';	
}

// Display and fill the form field
function jls_cflm_setting_string($x) {
	$options = get_option('jls_cflm_options');
	echo "<input id='jls_cflm_meta_field".$x."' name='jls_cflm_options[".$x."][meta_field]' size='50' type='text' value='"; if ( $options ) { echo "{$options[$x]['meta_field']}"; } echo "' />";
}

// Display and fill the form field
function jls_cflm_setting_title($x) {
	$options = get_option('jls_cflm_options');
	echo "<input id='jls_cflm_title".$x."' name='jls_cflm_options[".$x."][title]' size='25' type='text' value='";  if ( $options ) { echo "{$options[$x]['title']}"; } echo"' />";
}

// Display and fill the form field
function jls_cflm_setting_data_type($x) {
	$options = get_option('jls_cflm_options');
	$delete_url = add_query_arg( array('jls_action'=>'remove','field_id'=>$x) );
	$nonced_url = wp_nonce_url( $delete_url, 'jls_cflm-remove_field'.$x );
	echo "<select id='jls_cflm_data_type".$x."' name='jls_cflm_options[".$x."][data_type]'>
			<option value=''>--Select--</option>
			<option value='raw'"; if ( $options ) { if ($options[$x]['data_type'] == 'raw') { echo "selected='selected'"; } } echo ">raw url</option>
			<option value='html'"; if ( $options ) { if ($options[$x]['data_type'] == 'html') { echo "selected='selected'"; } } echo ">extract from html</option>
		</select><br />
		<a href='".$nonced_url."'>Remove field</a><br /><br />";
}

// Validate user input (we want text only)
function jls_cflm_options_validate($input) {
	$options = get_option('jls_cflm_options');
	
	$times = get_option('jls_cflm_count');
	$x = 0;
	
	while ($x < $times) {	
	
	if (isset($input[$x]['title'])) {
		$options[$x]['title'] = trim(preg_replace( "/[^0-9a-zA-Z ->']/", "", $input[$x]['title']));
	}
	if (isset($input[$x]['meta_field'])) {
		$valid[$x]['meta_field'] = preg_replace( '/[^a-zA-Z_]/', '', $input[$x]['meta_field']);
		if( $valid[$x]['meta_field'] != $input[$x]['meta_field'] ) {
			add_settings_error(
			'jls_cflm_meta_field['.$x.']',
			'jls_cflm_texterror',
			'Incorrect value entered for meta_field '.$x.'!',
			'error'
			);
		}
			$options[$x]['meta_field'] = $input[$x]['meta_field'];
	}

	// data_type: arbitrary number of possible values, default to 'unknown'
	switch( $input[$x]['data_type'] ) {
		case 'raw':
		case 'html':
		$options[$x]['data_type'] = $input[$x]['data_type'];
		break;
	default:
		$options[$x]['data_type'] = '';
	}
	if ($options[$x]['data_type'] == '') {
		add_settings_error(
		'jls_cflm_data_type',
		'jls_cflm_texterror',
		'Data type required for custom field '.($x+1).'!',
		'error'
		);
	}
	++$x;
}	
	return $options;
}

?>