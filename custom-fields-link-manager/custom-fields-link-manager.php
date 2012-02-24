<?php
/*
Plugin Name: Custom Fields Link Manager
Plugin URI: http://www.jennysharps.com/projects/wordpress-custom-fields-link-manager
Description: Searches designated custom fields + main content area for broken links
Version: 1.0
Author: Jenny Lynn Sharps
Author URI: http://www.jennysharps.com
License: GPLv2
*/

/*  Copyright 2011 Jenny_Lynn_Sharps  (email : jsharps85@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/


//installation
register_activation_hook( __FILE__, 'jls_cflm_install' );

function jls_cflm_install() {
	//if ( version_compare( get_bloginfo( 'version' ), '3.1', '<' ) ) {
    //    deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
    //}
    
    $jls_cflm_defaults = array(
    	0 => array(
			'title' => 'Main Content',
			'meta_field' => 'CONTENT',
			'data_type' => 'html'
    	)
    );
    
    add_option( 'jls_cflm_options', $jls_cflm_defaults );
    add_option( 'jls_cflm_count', 1 );
}

//deactivate
//register_activation_hook( __FILE__, 'jls_cflm_deactivate' );
function jls_cflm_deactivate() {
	//deactivation actions
}

include("includes/options.php");
include("includes/SmartDOMDocument.class.php");

// Draw the link management page
function jls_cflm_main_page() {
    ?>
    <div class="wrap">
        <?php screen_icon( 'link-manager' ); ?>
        <h2>Custom Field Links Manager</h2>
           
           
        <ul class="subsubsub">   
    	<?php
    	//set up top menu (custom field titles)
    		$meta_request = $_GET['jls_custom_field'];
    		$fields = get_option('jls_cflm_options');
    		
    		foreach ($fields as $key=>$field) {
    			$last = (count($fields)-1);
    			$title = $field['title'];
    			$meta_key = $field['meta_field'];
    			$url_format = $field['data_type'];
    			if( $field['meta_field'] == $meta_request ) { $current = $field['title']; }
    			$url = remove_query_arg( array( 'jls_main_action' ), add_query_arg( array( 'page'=>'jls_main', 'jls_custom_field' => $meta_key ) ) );
    			$menu_counts = get_option( 'jls_cflm_stored_data' );
    			$b = $menu_counts[$meta_key]['info']['broken'];
    		?>
    		
    		<li>
        		<a href="<?php echo $url; ?>" <?php if ( $title == $current ) { ?>class="current"<?php } ?>><?php echo $title; if ( $b > 0 ) { echo "<span style='color: red;'>*</span>"; } ?></a><?php if ( $key !== $last ) { echo " | "; } ?>
        	</li>
        	
    		<?php
    		}
    	?>
        </ul>  
        <br />       
            
        <?php
        //set up link display sections
        $urls = get_option( 'jls_cflm_stored_data' );
        $updated = $urls[$meta_request]['info']['updated'];
        
        if( $meta_request ):

        $all_urls = $urls[$meta_request]['urls'];
		$broken_count = $urls[$meta_request]['info']['broken'];
		$working_count = $urls[$meta_request]['info']['working'];
		$options = get_option( 'jls_cflm_options' );
		foreach ($options as $key=>$option) {
			foreach ($option as $field) {
				if ($field == $meta_request ) {
					$k = $key;
				}
			}
		}
		$time_ago = human_time_diff( $updated, time() );
        
        include("includes/tables.php");
        
        
        $refresh_stored_url= add_query_arg( array( 'jls_main_action'=>'refresh' ) );
        $nonced_refresh_url= wp_nonce_url( $refresh_stored_url, 'jls_cflm-refresh_link' );
        ?>
        <br /><p>Links last checked <?php echo $time_ago; ?> ago <a class='button' href='<?php echo $nonced_refresh_url; ?>'> Recheck Links </a></p>
        
        
        <?php else: 
        if (  ! empty( $fields ) ) {
        ?><br />
			<h3>Choose a section to manage its links</h3>
			
			<?php
			foreach ($fields as $key=>$field) {
				$title = $field['title'];
				$meta_key = $field['meta_field'];
				$url = add_query_arg( array( 'page'=>'jls_main', 'jls_custom_field' => $meta_key ) );
				$url = remove_query_arg( array( 'jls_main_action' ), $url );
				$menu_counts = get_option( 'jls_cflm_stored_data' );
				$b = $menu_counts[$meta_key]['info']['broken'];
				
				echo "<p><strong><a href='$url'>".$title."</a></strong>, "; echo $b." broken link"; if( $b !== 1 ) { echo "s"; } echo"</p>";
				
			}
		}
		if (  empty( $fields ) ) {
			echo "<h3>No fields added</h3>
				<p>Add fields under <span style='font-style: italic'>Settings&raquo;CFLM Options</span></p>
			";
		}
		?>

        <?php endif; ?>

    </div>
    <?php
}

add_action( 'admin_init', 'jls_cflm_do_main_action' );
// Proceed to requested jls_action if applicable
function jls_cflm_do_main_action() {
    if( ! isset( $_REQUEST['jls_main_action'] ) )
        return;

    if( !current_user_can( 'manage_options' ) )
        wp_die( 'Insufficient privileges!' );
        
    $action = $_REQUEST['jls_main_action'];
    $post_id = $_REQUEST['post_id'];
    $meta_id = $_REQUEST['meta_id'];
    $meta_request = $_GET['jls_custom_field'];
    
    if( $action == 'done' ) {
        add_action( 'admin_notices', 'jls_cflm_message_refresh' );
        return;
    }
    
    check_admin_referer( 'jls_cflm-'.$action.'_link'.$post_id );
    
    switch( $action ) {
		case 'refresh':
			$saved_urls = get_option( 'jls_cflm_stored_data' );
			$saved_urls[$meta_request] = jls_cflm_find_links();
			update_option( 'jls_cflm_stored_data', $saved_urls );
			wp_redirect( add_query_arg( array( 'jls_main_action' => 'done' ) ) );
			break;
		case 'update':
			if ( isset( $_POST['url'] ) ) {
				$new_url = $_POST['url'];
				$old_url = $_POST['old_url'];
				
				global $wpdb;
				if ( $meta_id == "CONTENT" ) {
					$sql = "SELECT posts.post_content, posts.ID FROM {$wpdb->posts} posts
					WHERE posts.ID = '$post_id'";
    			} else {
					$sql = "SELECT postmeta.meta_value FROM {$wpdb->postmeta} postmeta
					WHERE postmeta.meta_id = '$meta_id'";
        		}
        		
				$full_text = $wpdb->get_var( $sql );
				$doc = new SmartDOMDocument();
				$doc->loadHTML( $full_text );
				$xpath = new DOMXpath( $doc );
				foreach ($xpath->query('//a[@href]') as $a) {
					if ( $a->getAttribute('href') == $old_url ) {
						$a->setAttribute('href', $new_url );
						$new_value = $doc->saveHTMLExact();
					}
				}
				if ( ! $new_value ) { $new_value = $new_url; }
				if ( $meta_id == "CONTENT" ) {
					$post_info = array();
					$post_info['ID'] = $post_id;
					$post_info['post_content'] = $new_value;
					wp_update_post( $post_info );
				} else {
					update_post_meta($post_id, $meta_request, $new_value, $full_text); 
				}

				//refresh links
				$saved_urls = get_option( 'jls_cflm_stored_data' );
				$saved_urls[$meta_request] = jls_cflm_find_links();
				update_option( 'jls_cflm_stored_data', $saved_urls );
				wp_redirect( add_query_arg( array( 'jls_main_action' => 'done' ) ) );
				break;
		}
		case 'unlinkred':
			$new_value = '';
			$full_text = '';
			$doc = '';
			if ( isset( $_POST['jls_href_to_remove'] ) ) {
				$old_url = $_POST['jls_href_to_remove'];
							
				global $wpdb;
				if ( $meta_id == "CONTENT" ) {
					$sql = "SELECT posts.post_content, posts.ID FROM {$wpdb->posts} posts
					WHERE posts.ID = '$post_id'";
    			} else {
					$sql = "SELECT postmeta.meta_value FROM {$wpdb->postmeta} postmeta
					WHERE postmeta.meta_id = '$meta_id'";
        		}
        
    			$full_text = $wpdb->get_var( $sql );
				$doc = new SmartDOMDocument();
				$doc->loadHTML( $full_text );
				$xpath = new DOMXpath( $doc );
				foreach ($xpath->query('//a[@href]') as $a) {
					if ( $a->getAttribute('href') == $old_url ) {
						DOMRemove($a);
						$new_value = $doc->saveHTMLExact();
					}
				}
				
				if ( $meta_id == "CONTENT" ) {
					$post_info = array();
					$post_info['ID'] = $post_id;
					$post_info['post_content'] = $new_value;
					wp_update_post( $post_info );
				} else {
					update_post_meta($post_id, $meta_request, $new_value, $full_text); 
				}
				
				//refresh links
				$saved_urls = get_option( 'jls_cflm_stored_data' );
				$saved_urls[$meta_request] = jls_cflm_find_links();
				update_option( 'jls_cflm_stored_data', $saved_urls );
				wp_redirect( add_query_arg( array( 'jls_main_action' => 'done' ) ) );
				break;
			}
	}
}

add_action( 'admin_init', 'jls_cflm_do_update' );
// Proceed to requested jls_action if applicable
function jls_cflm_do_update() {

    if( $_GET['page'] !== 'jls_main' )
    	return;

    if( !current_user_can( 'edit_posts' ) )
    wp_die( 'Insufficient privileges!' );
    
    $meta_request = $_GET['jls_custom_field'];
    $stored_data = get_option( 'jls_cflm_stored_data' );

    if( ! $meta_request ) {
    	$fields = get_option('jls_cflm_options');
    	if ( $fields ) {
			foreach ($fields as $field) {
				$meta_key = $field['meta_field'];
				if ( ! ( $stored_data[$meta_key] ) ){
					$saved_urls = get_option( 'jls_cflm_stored_data' );
					$saved_urls[$meta_key] = jls_cflm_find_links($meta_key);
					update_option( 'jls_cflm_stored_data', $saved_urls );	
					return;
				}
			}
		}
	}
	
	if ( ! ( $stored_data[$meta_request] ) ){
		$saved_urls = get_option( 'jls_cflm_stored_data' );
		$saved_urls[$meta_request] = jls_cflm_find_links();
		update_option( 'jls_cflm_stored_data', $saved_urls );
		return;
	}
	$last_update = $stored_data[$meta_request]['info']['updated'];
	if ( $last_update < ( time() - ( 3600*24 ) ) ){
		$saved_urls = get_option( 'jls_cflm_stored_data' );
		$saved_urls[$meta_request] = jls_cflm_find_links();
		update_option( 'jls_cflm_stored_data', $saved_urls );
		return;
	}
}

// Admin notice
function jls_cflm_message_refresh() {
    echo "<div class='updated'><p>Links updated</p></div>";
}



/**
* Extracts all URLs from an html link 
*
* Used to return all urls extracted from any given string in an array
*
* @param string $string Any string suspected to have one or more URLs 
* @param string $type Type of link(s) contained within the string 
*                      - raw: a single, plaintext URL contained within the field, one URL per string, no extraction performed eg: http://www.fiu.edu
*                      - html: URL contained within markup, eg: I attend <a href='http://www.fiu.edu'>FIU</a> on the weekends and <a href="http://www.mdc.edu">Miami Dade</a> on Thursday night.
* @return array 1-dimensional, contains all URLs extracted from string, eg: [1] http://www.fiu.edu [2] http://www.mdc.edu
*
**/

function jls_cflm_extract_urls( $string, $type = 'html' ){
	$extracted_urls = array();
		if ( $type == 'html' || $type == 'mixed' ) {
			$doc = new SmartDOMDocument();
			$doc->loadHTML( $string );
			$xpath = new DOMXpath( $doc );
			$extracted_urls = array();
			foreach ($xpath->query('//a[@href]') as $a) {
				$extracted_urls[] = $a->getAttribute('href');
			}
			// ^old way
			/*preg_match_all('~href=("|\')(.*?)\1~i', $string, $out);
			$extracted_urls = $out[2];*/
		}
		
		if ( $type == 'raw' ) {  
			$extracted_urls[] = $string;
		}
	return $extracted_urls;
}
 
 
 
/**
* Checks all URLs in an array 
*
* Used to return all URLs in input array ($urls) and breaks them into 2 arrays of broken and working links
*
* @param array $urls 1-dimensional array of urls, eg: [1] http://www.fiu.edu [2] http://www.mdc.edu
*
* @return array 2-dimensional, first dimension keys are [broken] or [working], 2nd dimension contains numeric array of URLs
*
**/ 
function check_urls( $urls ) {
	$broken = array();
	$working = array();

	foreach ( $urls as $url ) {
		// Version 4.x supported
		$handle   = curl_init($url);
		curl_setopt( $handle,  CURLOPT_RETURNTRANSFER, TRUE );
		
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
		
		/* Check code */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		
		$url = array( 'url' => $url, 'code' => $httpCode );
		
		if ( ( $httpCode >= 99 ) && ( $httpCode <= 399 ) ) {
			$working[] = $url;
		} else {
			$broken[] = $url;
		}
		curl_close($handle);
    }
    $checked_links = array( 'broken' => $broken, 'working' => $working);
	return $checked_links;
}



 /**
* Finds all URLs stored in a given field and processes them
*
* Used to retrieve all URLs in given custom field, determine whether they are broken or working,
* and prepare them for storing in jls_cflm_stored_data option
*
* @param string $passed_key Custom field meta_key, not used
*
* @return array $urls[] = array('post_ID' => '', 'meta_ID' => '', 'url' => '', 'status' => '', 'code' => '' );
*             	status: either broken or working
*				code: HTTP/1.1: Status Code ( eg. 404, 200, etc.)
*
**/ 	
function jls_cflm_find_links($passed_key = '') {
    global $wpdb;
    
    $fields = get_option('jls_cflm_options');
    if ( strlen($passed_key) > 0 ) { 
    	$meta_key = $passed_key; 
    } else {
    	$meta_key = $_GET['jls_custom_field'];
  	}
  	
  	if ( $fields ) {
  		foreach ($fields as $key=>$field) {
			if ($field['meta_field'] == $meta_key ) {
				$k = $key;
  			}
  		}

		$field = esc_sql($meta_key);
		$url_type = $fields[$k]['data_type'];
	
	if ($field == 'CONTENT' ) {
		$sql = "SELECT posts.post_content, posts.ID FROM {$wpdb->posts} posts
    	WHERE posts.post_status = 'publish'";
	} else {
     	$sql = "SELECT postmeta.post_id, postmeta.meta_value, postmeta.meta_id FROM {$wpdb->postmeta} postmeta
    	WHERE postmeta.meta_key = '$field'";
    }
    
    $results= $wpdb->get_results( $sql );
    $urls = array();
    $count = 0;
    $count_broken = 0;
    $count_working = 0;
    
    if ( $field == 'CONTENT' ) {
    	foreach ($results as $result) {
		
			$post_id = $result->ID;
			$meta_id = 'CONTENT';
			$all_urls = array_filter( jls_cflm_extract_urls( $result->post_content, 'mixed' ) );
			$checked = check_urls($all_urls);
			$count_broken = $count_broken + count($checked['broken']);
			$count_working = $count_working + count($checked['working']);
			
			foreach ($checked[broken] as $broken_url) {   		
				$urls[] = array('post_ID' => $post_id, 'meta_ID' => $meta_id, 'url' => $broken_url['url'], 'status' => 'broken', 'code' => $broken_url['code'] );
			}
			foreach ($checked[working] as $working_url) {   		
				$urls[] = array('post_ID' => $post_id, 'meta_ID' => $meta_id, 'url' => $working_url['url'], 'status' => 'working', 'code' => $working_url['code'] );
			}
		}
    
    } else {
		foreach ($results as $result) {
		
			$post_id = $result->post_id;
			$meta_id = $result->meta_id;
			$all_urls = array_filter( jls_cflm_extract_urls( $result->meta_value, $url_type ) );
			$checked = check_urls($all_urls);
			$count_broken = $count_broken + count($checked['broken']);
			$count_working = $count_working + count($checked['working']);
			
			foreach ($checked[broken] as $broken_url) {   		
				$urls[] = array('post_ID' => $post_id, 'meta_ID' => $meta_id, 'url' => $broken_url['url'], 'status' => 'broken', 'code' => $broken_url['code'] );
			}
			foreach ($checked[working] as $working_url) {   		
				$urls[] = array('post_ID' => $post_id, 'meta_ID' => $meta_id, 'url' => $working_url['url'], 'status' => 'working', 'code' => $working_url['code'] );
			}
		}
	}
	$info = array( 'broken' => $count_broken, 'working' => $count_working, 'updated' => time() );
	$urls = array( 'urls' => $urls, 'info' => $info );
	return $urls;
	}
}



add_action( 'admin_init', 'jls_js_add_script' );
//add jQuery for inline editing on result pages
function jls_js_add_script() {
	wp_enqueue_script( 'jls_inline_edit', plugins_url( '/js/jls-inline-edit.js', __FILE__ ) );
}


add_action('admin_init', 'jls_add_stylesheet');
//add custom styling for result pages
function jls_add_stylesheet() {
	wp_register_style( 'jlsStyle', plugins_url( '/css/style.css', __FILE__ ) );
	wp_enqueue_style( 'jlsStyle');
}


 /**
* Shorten long URL text
*
* Used to ensure that text in the 'linked text' column is no longer than given number of words
*
* @param string $string Text to be processed
* @param int    $length Max number of words, defaults to 50
* @param string $ellipsis Divider, defaults to "..."
*
* @return string Processed string with excess words trimmed (if there are excess words)
*
**/ 
function wordlimit( $string, $length = 50, $ellipsis = "... " ) { 
   $words = explode(' ', $string); 
   if (count($words) > $length) 
       return rtrim (rtrim( (implode(' ', array_slice($words, 0, $length))), ',' ), '-' ) . $ellipsis; 
   else 
       return $string; 
} 


 /**
*
* Removes given node and returns only inner value
*
* To be used while in PHP DOMDocument with a node defined
*
* @param node $from Node to be removed
*
* @return Processes node directly
*
**/ 
function DOMRemove(DOMNode $from) {
    $sibling = $from->firstChild;
    do {
        $next = $sibling->nextSibling;
        $from->parentNode->insertBefore($sibling, $from);
    } while ($sibling = $next);
    $from->parentNode->removeChild($from);    
}