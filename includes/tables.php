<?php 
/*
function jls_cflm_rstrstr($haystack,$needle, $start=0) {
	return substr($haystack, $start,strpos($haystack, $needle));
}
*/

echo '<br /><h3>'.$current.'</h3>'.array_sum(array($broken_count, $working_count)).' Links Found
<h4>Broken ('.$broken_count.')</h4>';
 if ( $broken_count > 0 ) {
?>

<table class="wp-list-table widefat fixed posts" cellspacing="0">
	<thead>
		<tr>
			<th scope='col'  class='manage-column column-count'  style=""><span>#</span></th>
			<th scope='col'  class='manage-column column-title raw-url'  style="">URL</th>
			<th scope='col'  class='manage-column column-coauthors'  style="">Associated Post</th>
			<th scope='col'  class='manage-column column-title'  style="">Link Text</th>
		</tr>

	</thead>

	<tfoot>
		<tr>
			<th scope='col'  class='manage-column column-count'  style=""><span>#</span></th>
			<th scope='col'  class='manage-column column-title raw-url'  style="">URL</th>
			<th scope='col'  class='manage-column column-coauthors'  style="">Associated Post</th>
			<th scope='col'  class='manage-column column-title'  style="">Link Text</th>
		</tr>
	</tfoot>
	<tbody id="the-list">
<?php        
$x = 0;
$re = 0;
 foreach ( $all_urls as $key=>$broken_url ){
 $error = 0;
 
	$post_id = $broken_url['post_ID'];
	$post_title = get_the_title($post_id);
	$meta_id = $broken_url['meta_ID'];
	$url = $broken_url['url'];
	$code = $broken_url['code'];
	if ( ( $code >= 300 ) && ( $code <= 399 ) ) { $redirect = " redirect"; $re++; } else { $redirect = false; }		
		
	if ($broken_url['status'] == 'broken') {
		$delete_url= add_query_arg( array('jls_action'=>'delete','id'=>$post_id) );
		$nonced_url= wp_nonce_url( $delete_url, 'jls_utags-delete_tag'.$post_id );        
?>
			<?php 
			global $wpdb;
			if ( $meta_id == 'CONTENT' ) {
				$post = get_post( $post_id ); 
				$full_text = $post->post_content;
			} else {
				$sql = "SELECT postmeta.meta_value FROM {$wpdb->postmeta} postmeta
				WHERE postmeta.meta_id = '$meta_id'";
				$full_text = $wpdb->get_var( $sql );
			}
			
			
			if ( ! $full_text ) { $error++; }
			$dom = new DOMDocument();
			@$dom->loadHTML( $full_text );
			$embedded = false;
			$linked = false;
			$just_text = "";
			$just_href = "";
			$original = "";
			foreach ( $dom->getElementsByTagName('a') as $node ) {
				if ( $node->getAttribute("href") == $url  ) {
					$linked = true;
			  		$just_text = $node->nodeValue;
			  		$just_href = $node->getAttribute("href");
			  		$original = $dom->saveXML($node);
			  		if ( stristr( $original, '<img' ) ) { $embedded = true; }
			  		if ( ! $just_text && ! $embedded ) { $error++; }
			  		$hilighted_url = '<a href="'.$just_href.'" class="redtext" target="_blank" >'.$just_text.'</a>';
			  	}
			}
			if ( $embedded ) { 
				$hilighted = $full_text; 
			} else {
				$hilighted = str_replace( $original, $hilighted_url, $full_text ); 
			}

			$text = $just_text;
			
			if ( $embedded ) { 
				$text = '<span style="font-style: italic;">Embedded Content</span>'; 
			} 
			if ( $error > 0 ) { 
				$text = '<span style="color:red; font-weight: bold;">*ERROR: Perhaps something has changed.<br />Please Recheck Links.</span>'; 		
			} else {
				if ( $error < 1  && ! $just_text && ! $just_href ) { 
					$text = "N/A"; $just_href = $full_text; 
				}
			}
			
			if ( str_word_count( $text, 0 ) > 15 ) {
				$text = wordlimit( $text, '11', "..." );
			}
						
			$x = ($x + 1); 
			?>
				
		<tr id="url-<?php echo "$key"; ?>" class="<?php if ( $x % 2 ) { echo "alternate"; } ?> author-self status-publish format-default iedit" valign="top">
			<th scope="row" class="column-count"><span><?php echo $x."."; ?></span></th>
				<td class="post-title page-title column-title"><strong><a class="row-title<?php echo $redirect; ?>" target="_blank" href="<?php echo $url; ?>" title="<?php echo $url; ?>"><?php echo $url; ?></a></strong>
					<div class="row-actions">						
						<?php if ( $full_text && $error < 1  ) { ?><span class='inline hide-if-no-js'><a href="#inline-edit" class="editinline jlsinlineshow" title="Edit this URL inline">Quick&nbsp;Edit</a> | </span> <?php } ?>
						<span class='view'><a class='submitdelete' title='Go to Edit Page for &#8220;<?php echo $post_title; ?>&#8221' href='post.php?post=<?php echo $post_id; ?>&action=edit'>Edit&nbsp;Post</a> | </span>
						<span class='view'><a href="<?php echo get_permalink( $post_id ); ?>" title="View &#8220;<?php echo $post_title; ?>&#8221;" rel="permalink">View&nbsp;Post</a></span>
					</div>
				</td>
				<td class="coauthors column-coauthors"><a href="post.php?post=<?php echo $post_id; ?>&action=edit"><?php echo $post_title; ?></a></td>
				<td class="post-title page-title column-title"><?php echo $text; ?></td>
		</tr>
		<?php if ( $full_text ) { ?>
			<tr id="edit-<?php echo "$key"; ?>" class="inline-edit-row inline-edit-row-post inline-edit-post quick-edit-row quick-edit-row-post inline-edit-post <?php if ( $x % 2 ) { echo "alternate"; } ?> inline-editor" style="display: none">
			<td class="colspanchange" colspan="4">
				<form action="" method="post" >
				<?php if ( $embedded ){ ?>
					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php echo $x.". "; ?>Quick Edit</h4>
							<p>Embedded content URLs can be updated from the <a class='submitdelete' title='Go to Edit Page for &#8220;<?php echo $post_title; ?>&#8221' href='post.php?post=<?php echo $post_id; ?>&action=edit'>Edit</a> page for this post.</p>
						</div>
					</fieldset>
					<fieldset class="inline-edit-col-center inline-edit-categories">
						<h4>&nbsp;</h4>
					</fieldset>
				<?php } else {
				?>
					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php echo $x.". "; ?>Quick Edit</h4>
							<label>
								<span class="title"> URL </span>
								<span class="input-text-wrap">
										<?php wp_nonce_field( 'jls_cflm-update_link'.$post_id ); ?>
										<input type="hidden" name="jls_main_action" value="update" />
										<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
										<input type="hidden" name="meta_id" value="<?php echo $meta_id; ?>" />
										<input type="hidden" name="old_url" value="<?php echo $just_href; ?>" />
										<input type="text" name="url" value="<?php echo $just_href; ?>" />
								</span>
							</label>
						</div>
					</fieldset>
					<fieldset class="inline-edit-col-center inline-edit-categories">
						<h4>&nbsp;</h4>
						<input type="submit" class="button-primary" value=" Update URL "/>
					</fieldset>
				<?php } ?>
				</form>
				<fieldset class="inline-edit-col-right">
					<div class="inline-edit-col">
						<label> 
							<br /><span class="title"> Associated Post </span>
						</label>
						<div><a href="post.php?post=<?php echo $post_id; ?>&action=edit"> &nbsp;<?php echo $post_title; ?></a></div>
						<br /><label class="inline-edit-tags">
							<span class="title"> Field Content </span>
						</label>
						<br /><div class="jls-full-text"><?php echo $hilighted; ?></div>
					</div>
				</fieldset>
				<p class="jlsubmit submit inline-edit-save">
					<a style='float: left; position: relative; bottom: 5px' href="#inline-edit" class="button jlsinline" > Cancel </a>
					<span class="jls-padding alignright">
						<form action="" method="post" >
							<?php wp_nonce_field( 'jls_cflm-unlinkred_link'.$post_id ); ?>
							<input type="hidden" name="jls_main_action" value="unlinkred"/>
							<input type="hidden" name="jls_href_to_remove" value="<?php echo $just_href ?>"/>
							<input type="hidden" name="meta_id" value="<?php echo $meta_id; ?>"/>
							<input type="hidden" name="post_id" value="<?php echo $post_id; ?>"/>
							<input style='float: right; position: relative; bottom: 7px;' class="button button-highlighted" <?php if ( $embedded || ! $linked ) { echo 'type="hidden" ';} else { echo 'type="submit" '; } ?>value="Unlink Text"/>
						</form>
					</span>
				</p>
			</td>
		</tr>
	<?php
			}
		}
	}
	?>
	
	</tbody>
</table>

<?php } ?>

        
        <?php
        echo '<br /><h4>Working ('.$working_count.')</h4>';
        if ( $working_count > 0 ) {
        ?>
<table class="wp-list-table widefat fixed posts" cellspacing="0">
	<thead>
		<tr>
			<th scope='col'  class='manage-column column-count'  style=""><span>#</span></th>
			<th scope='col'  class='manage-column column-title raw-url'  style="">URL</th>
			<th scope='col'  class='manage-column column-coauthors'  style="">Associated Post</th>
			<th scope='col'  class='manage-column column-title'  style="">Link Text</th>
		</tr>

	</thead>

	<tfoot>
		<tr>
			<th scope='col'  class='manage-column column-count'  style=""><span>#</span></th>
			<th scope='col'  class='manage-column column-title raw-url'  style="">URL</th>
			<th scope='col'  class='manage-column column-coauthors'  style="">Associated Post</th>
			<th scope='col'  class='manage-column column-title'  style="">Link Text</th>
		</tr>
	</tfoot>
	<tbody id="the-list">
<?php        
$x = 0;
$re = 0;
 foreach ( $all_urls as $key=>$working_url ){
 $error = 0;
	$post_id = $working_url['post_ID'];
	$post_title = get_the_title($post_id);
	$meta_id = $working_url['meta_ID'];
	$url = $working_url['url'];
	$code = $working_url['code'];
	if ( ( $code >= 300 ) && ( $code <= 399 ) ) { $redirect = " redirect"; $re++; } else { $redirect = false; }		
		
	if ($working_url['status'] == 'working') {
		$delete_url= add_query_arg( array('jls_action'=>'delete','id'=>$post_id) );
		$nonced_url= wp_nonce_url( $delete_url, 'jls_utags-delete_tag'.$post_id );        
?>
			<?php 
			global $wpdb;
			if ( $meta_id == 'CONTENT' ) {
				$post = get_post( $post_id ); 
				$full_text = $post->post_content;
			} else {
				$sql = "SELECT postmeta.meta_value FROM {$wpdb->postmeta} postmeta
				WHERE postmeta.meta_id = '$meta_id'";
				$full_text = $wpdb->get_var( $sql );
			}
			
			if ( ! $full_text ) { $error++; }
			$dom = new DOMDocument();
			@$dom->loadHTML( $full_text );
			$embedded = false;
			$linked = false;
			$just_text = "";
			$just_href = "";
			$original = "";
			foreach ( $dom->getElementsByTagName('a') as $node ) {
				if ( $node->getAttribute("href") == $url  ) {
					$linked = true;
			  		$just_text = $node->nodeValue;
			  		$just_href = $node->getAttribute("href");
			  		$original = $dom->saveXML($node);
			  		if ( stristr( $original, '<img' ) ) { $embedded = true; }
			  		if ( ! $just_text && ! $embedded ) { $error++; }
			  		$hilighted_url = '<a href="'.$just_href.'" class="redtext" target="_blank" >'.$just_text.'</a>';
			  	}
			}
			if ( $embedded ) { 
				$hilighted = $full_text; 
			} else {
				$hilighted = str_replace( $original, $hilighted_url, $full_text ); 
			}

			$text = $just_text;
			
			if ( $embedded ) { 
				$text = '<span style="font-style: italic;">Embedded Content</span>'; 
			} 
			if ( $error > 0 ) { 
				$text = '<span style="color:red; font-weight: bold;">*ERROR: Perhaps something has changed.<br />Please Recheck Links.</span>'; 		
			} else {
				if ( $error < 1 && ! $just_text && ! $just_href ) { 
					$text = "N/A"; $just_href = $full_text; 
				}
			}

			if ( str_word_count( $text, 0 ) > 15 ) {
				$text = wordlimit( $text, '11', "..." );
			}

			$x = ($x + 1); 			
			?>
		<tr id="url-<?php echo "$key"; ?>" class="<?php if ( $x % 2 ) { echo "alternate"; } ?> author-self status-publish format-default iedit" valign="top">
			<th scope="row" class="column-count"><span><?php echo $x."."; ?></span></th>
				<td class="post-title page-title column-title"><strong><a class="row-title<?php echo $redirect; ?>" target="_blank" href="<?php echo $url; ?>" title="<?php echo $url; ?>"><?php echo $url; ?></a></strong>
					<div class="row-actions">						
						<?php if ( $full_text && $error < 1 ) { ?><span class='inline hide-if-no-js'><a href="#inline-edit" class="editinline jlsinlineshow" title="Edit this URL inline">Quick&nbsp;Edit</a> | </span> <?php } ?>
						<span class='view'><a class='submitdelete' title='Go to Edit Page for &#8220;<?php echo $post_title; ?>&#8221' href='post.php?post=<?php echo $post_id; ?>&action=edit'>Edit&nbsp;Post</a> | </span>
						<span class='view'><a href="<?php echo get_permalink( $post_id ); ?>" title="View &#8220;<?php echo $post_title; ?>&#8221;" rel="permalink">View&nbsp;Post</a></span>
					</div>
				</td>
				<td class="coauthors column-coauthors"><a href="post.php?post=<?php echo $post_id; ?>&action=edit"><?php echo $post_title; ?></a></td>
				<td class="post-title page-title column-title"><?php echo $text; ?></td>
		</tr>
		<?php if ( $full_text ) { ?>
			<tr id="edit-<?php echo "$key"; ?>" class="inline-edit-row inline-edit-row-post inline-edit-post quick-edit-row quick-edit-row-post inline-edit-post <?php if ( $x % 2 ) { echo "alternate"; } ?> inline-editor" style="display: none">
			<td class="colspanchange" colspan="4">
				<form action="" method="post" >
				<?php if ( $embedded ){ ?>
					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php echo $x.". "; ?>Quick Edit</h4>
							<p>Embedded content URLs can be updated from the <a class='submitdelete' title='Go to Edit Page for &#8220;<?php echo $post_title; ?>&#8221' href='post.php?post=<?php echo $post_id; ?>&action=edit'>Edit</a> page for this post.</p>
						</div>
					</fieldset>
					<fieldset class="inline-edit-col-center inline-edit-categories">
						<h4>&nbsp;</h4>
					</fieldset>
				<?php } else {
				?>
					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php echo $x.". "; ?>Quick Edit</h4>
							<label>
								<span class="title"> URL </span>
								<span class="input-text-wrap">
										<?php wp_nonce_field( 'jls_cflm-update_link'.$post_id ); ?>
										<input type="hidden" name="jls_main_action" value="update" />
										<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
										<input type="hidden" name="meta_id" value="<?php echo $meta_id; ?>" />
										<input type="hidden" name="old_url" value="<?php echo $just_href; ?>" />
										<input type="text" name="url" value="<?php echo $just_href; ?>" />
								</span>
							</label>
						</div>
					</fieldset>
					<fieldset class="inline-edit-col-center inline-edit-categories">
						<h4>&nbsp;</h4>
						<input type="submit" class="button-primary" value=" Update URL "/>
					</fieldset>
				<?php } ?>
				</form>
				<fieldset class="inline-edit-col-right">
					<div class="inline-edit-col">
						<label> 
							<br /><span class="title"> Associated Post </span>
						</label>
						<div><a href="post.php?post=<?php echo $post_id; ?>&action=edit"> &nbsp;<?php echo $post_title; ?></a></div>
						<br /><label class="inline-edit-tags">
							<span class="title"> Field Content </span>
						</label>
						<br /><div class="jls-full-text"><?php echo $hilighted; ?></div>
					</div>
				</fieldset>
				<p class="jlsubmit submit inline-edit-save">
					<a style='float: left; position: relative; bottom: 5px' href="#inline-edit" class="button jlsinline" > Cancel </a>
					<span class="jls-padding alignright">
						<form action="" method="post" >
							<?php wp_nonce_field( 'jls_cflm-unlinkred_link'.$post_id ); ?>
							<input type="hidden" name="jls_main_action" value="unlinkred"/>
							<input type="hidden" name="jls_href_to_remove" value="<?php echo $just_href ?>"/>
							<input type="hidden" name="meta_id" value="<?php echo $meta_id; ?>"/>
							<input type="hidden" name="post_id" value="<?php echo $post_id; ?>"/>
							<input style='float: right; position: relative; bottom: 7px;' class="button button-highlighted" <?php if ( $embedded || ! $linked ) { echo 'type="hidden" ';} else { echo 'type="submit" '; } ?>value="Unlink Text"/>
						</form>
					</span>
				</p>
			</td>
		</tr>
	<?php
			}
		}
	}
	?>
	
	</tbody>
</table>

<?php } ?>
<br />
<?php if ( $re > 0 ) { ?>
<div class='redirect' style='float: right'><span style='font-weight: bold'>*Redirected URLs</span> will appear in green</div> 
<?php } ?>