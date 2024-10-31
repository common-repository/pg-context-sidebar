<?php
/*
Plugin Name: PG Context Sensitive Sidebar
Plugin URI: http://www.peoplesgeek.com/plugins/context-sensitive-sidebar/
Tags: post sidebar, page sidebar
Description: This plugin allows you to display context sensitive information in the sidebar for each page or post that you want
Version: 2.1
Author: PeoplesGeek
Author URI: http://www.peoplesgeek.com
Text Domain: pgeek-cs
Domain Path: /languages

	Copyright 2012 Brian Reddick 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
    
    http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
    
    TODO: Find a better way to display pages to copy for blogs with a lot of pages
    TODO: Convert to a class to simplify namespace

*/
load_plugin_textdomain( 'pgeek-cs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

register_activation_hook(__FILE__, 'pgeek_cs_install');
add_action('admin_init', 'pgeek_cs_enable_meta_box');
add_action('admin_init', 'pgeek_cs_admin_register_settings');
add_action('save_post','pgeek_cs_save_meta');
add_action('widgets_init','pgeek_cs_register_widget');
add_action('admin_menu','pgeek_cs_add_admin_page');
add_action('admin_enqueue_scripts', 'pgeek_cs_add_admin_scripts');

if ( '2.0.0' != get_option('pgeek_cs_option_ver') )
	add_action('admin_init', 'pgeek_cs_upgrade_check');

/* Customise Columns */
if (is_admin() ){
	$options = get_option('pgeek_cs_options');
	$page_icons = isset($options['show-icons-for-post-type']) ? $options['show-icons-for-post-type']:'';
	if (is_array($page_icons)){
		// set up column overrides for each
		foreach ($page_icons as $page_type => $value) { //only selected types will be in this array so enable all without test
			switch ($page_type) {
				case 'page':
					add_filter( 'manage_pages_columns',  'pgeek_cs_add_columns'  );
				break;
				
				case 'post':
					add_filter( 'manage_posts_columns',  'pgeek_cs_add_columns' ,10 , 2 );
				break;
				
				default:
					add_filter( "manage_{$page_type}_posts_columns",  'pgeek_cs_add_columns', 20  );
					
				break;
			}
			// it seems the populate columns runs for all page types and the post for all post types
			// setting the filter "manage_{$page_type}_posts_custom_column" causes the population of the CSS colument to happen multiple times
			add_action( 'manage_pages_custom_column',  'pgeek_cs_populate_columns' , 20, 2 );
			add_action( 'manage_posts_custom_column',  'pgeek_cs_populate_columns' , 20, 2 );
		}
	}
}


function pgeek_cs_register_widget(){
	register_widget('pgeek_cs_widget');
}

function pgeek_cs_enable_meta_box() {
	// only show the meta box for the types of pages that have been selected
	$options = get_option('pgeek_cs_options');
	$page_types = isset( $options['show-meta-for-post-type']) ? $options['show-meta-for-post-type'] : '';
	if (!empty($page_types)) {
		foreach ($page_types as $key => $value) {
			if ( $value == true ){
				add_meta_box('pgeek_cs_meta', __('Context Sensitve Sidebar', 'pgeek-cs'), 'pgeek_cs_meta_box', $key, 'normal', 'high'); 
			}
		}
	}
}

function pgeek_cs_save_meta($post_id){
	// Don't save metadata when saving revisions, it makes the logic too hard
	if ( get_post_type($post_id) == 'revision')
		return;
	
	// The post_id here may be a revision, we need the original/parent id for post_meta purposes
	$base_post_id = wp_is_post_revision($post_id);
	$base_post_id = ($base_post_id == false ) ? $post_id : $base_post_id;

	// Verify the metadata is set and whether the user has permission for unfiltered html, clear the metadata if it is not set
	if (isset($_POST['pgeek_cs_title'])){
		update_post_meta($post_id, '_pgeek_cs_title', strip_tags($_POST['pgeek_cs_title']));
	} 
	
	if (isset($_POST['pgeek_cs_content'])){
		if ( current_user_can('unfiltered_html') ){
			update_post_meta($post_id, '_pgeek_cs_content', $_POST['pgeek_cs_content']);
		} else{
			update_post_meta($post_id, '_pgeek_cs_content', stripslashes( wp_filter_post_kses( addslashes($_POST['pgeek_cs_content']) ))); // wp_filter_post_kses() expects slashed
		}
	} 
	
	// see if we are copying from another field
	if ( isset($_POST['pgeek_cs_copy_from'] ) && $_POST['pgeek_cs_copy_from'] != -1 ) {
		update_post_meta($post_id, '_pgeek_cs_title', get_post_meta($_POST['pgeek_cs_copy_from'],'_pgeek_cs_title',true) );
		update_post_meta($post_id, '_pgeek_cs_content', get_post_meta($_POST['pgeek_cs_copy_from'],'_pgeek_cs_content',true) );
	}	
	
	// see if we have started/changed/stopped displaying from another page and update it's template property as appropriate
	if ( isset($_POST['pgeek_cs_display_from'] ) ) {
		$new_display_from = $_POST['pgeek_cs_display_from'];
		// safety check that current page is not a template for others (this is a safety check here as the drop down should not populate if it is a template)
		$template = get_post_meta($post_id, '_pgeek_cs_template_for',true );
		if ( !empty( $template ) )	
			$new_display_from = -1;
		
		$old_display_from = get_post_meta($post_id, '_pgeek_cs_display_from', true );
		$old_display_from = ('' == $old_display_from )? -1 : $old_display_from ;
		
		if ($old_display_from != $new_display_from ){
			// remove from old, add to new (unless either is -1)
			// remove from old
			if ($old_display_from != -1){
				$template = get_post_meta($old_display_from, '_pgeek_cs_template_for',true );
				if (is_array($template)){
					$key = array_search($base_post_id, $template);
						if($key!==false){
						    unset($template[$key]);
						    update_post_meta($old_display_from, '_pgeek_cs_template_for', $template);
						}
				}
			} // end of remove old if not -1
			// add to new
			if ($new_display_from != -1){
				$template = get_post_meta($new_display_from,'_pgeek_cs_template_for',true );
				if (is_array($template)){
					$template = array_merge($template, (array)$base_post_id );
				} else {
					$template = (array)$base_post_id;
				}
				update_post_meta($new_display_from, '_pgeek_cs_template_for', $template);
									
			} // end of add to new if not -1

		}
		
		update_post_meta($post_id, '_pgeek_cs_display_from', $new_display_from );

	}
}

function pgeek_cs_meta_box($post) {

    $title= get_post_meta($post->ID,'_pgeek_cs_title',true);
    $content= get_post_meta($post->ID,'_pgeek_cs_content',true);
    $display_from = get_post_meta($post->ID,'_pgeek_cs_display_from',true);
    $hide_inputs = ( $display_from != -1 && $display_from !='');
    $options = get_option('pgeek_cs_options');
    $hide_copy = isset($options['hide-copy-for-post-type']) ? $options['hide-copy-for-post-type']:'';
	?>
	<p><?php
				$status = pgeek_get_status($post->ID);
				echo '<div class="pgeek-cs-status-img '. $status['class'] . '" alt="'. $status['description'] . '" title="'. $status['description'] . '"></div>';
			 _e('This content is shown in the sidebar by the PG Context Sensitive Sidebar widget. It is only displayed in the sidebar when this page is shown.', 'pgeek-cs') ?></p>
	<table class='widefat settings post'>
			<tr>
				<td><strong><?php _e('Sidebar Title:', 'pgeek-cs') ?></strong></td>
				<td><input name='pgeek_cs_title' id='pgeek-cs-title' type=text size=80 value='<?php echo esc_attr( $title );?>'>
				<div id="pgeek-cs-title-cloned"><?php _e('The title will stay in sync with the selected page/post','pgeek-cs'); ?></div>
				<div id="pgeek-cs-title-copied"><?php _e('The title will be taken from the selected page/post','pgeek-cs'); ?></div></td>
			</tr>
			<tr>
				<td><strong><?php _e('Sidebar Content:', 'pgeek-cs' );?></strong><?php 
					?></td>
				<td><textarea id="pgeek-cs-content" name='pgeek_cs_content' rows=8 cols=90 ><?php echo esc_attr( $content ); ?></textarea>
							<div id="pgeek-cs-content-cloned"><?php _e('The content will stay in sync with the selected page/post','pgeek-cs'); ?></div>
							<div id="pgeek-cs-content-copied"><?php _e('The content will be copied from the selected page/post','pgeek-cs'); ?></div></td>
			</tr>
			<tr>
				<td><strong><?php _e('Once off copy from another page/post:', 'pgeek-cs' )?></strong></td>
				<td>
					<select name="pgeek_cs_copy_from" id="pgeek-cs-copy-from">
					<?php
						if (isset($hide_copy[$post->post_type] ) ){
							echo '<option value="-1" selected="selected" >-- ' . __('copying from other pages is disabled for this post type', 'pgeek-cs') . ' --</option>';
						} else {

							$args = array(	'posts_per_page'=> -1,
											'order' => 'ASC',
											'orderby' => 'title',
											'hierarchical' => 0,
											'post__not_in' => array( $post->ID ),
											'meta_key' => '_pgeek_cs_content',
											//'meta_key' => '_pgeek_cs_content', 'meta_value' => '_wp_zero_value', 'meta_compare' => '!=' ,
											'post_type' =>  get_post_types(), //array('post', 'page', 'movie', 'book' ),//'page',//array('page', 'post'),
											'post_status' => array('publish', 'inherit', 'pending', 'private', 'future', 'draft')
										);
							
							$page_query = new WP_Query();
							$pages = $page_query->query($args);
							
							if ( $pages ) {
								echo '<option value="-1" selected="selected" >-- ' . __('select page/post to copy Sidebar title and content', 'pgeek-cs') . ' --</option>';
								foreach ($pages as $page) {
									$status = ( $page->post_status == 'publish') ? '':' - ('.$page->post_status.')';
									$content= get_post_meta( $page->ID,'_pgeek_cs_content',true);
									if ( !empty($content) ) 
										echo '<option value="'.$page->ID.'" >[' .$page->post_type .'] '. $page->post_title . $status . '</option>';
								}
							} else {
								echo '<option value="-1" selected="selected" >-- ' . __('there is no other page/post with content to copy', 'pgeek-cs') . ' --</option>';
							} 
						
						} ?>
					</select>
					<div id="pgeek-cs-copy-from-hidden"><?php _e('If you wish to copy then deselect the page/post below','pgeek-cs'); ?></div>
				</td>
			</tr>
			<tr>
				<td><strong><?php _e('Ongoing display of another page/post (stays in sync):', 'pgeek-cs' )?></strong></td>
				<td>
					<select name="pgeek_cs_display_from" id="pgeek-cs-display-from">
					<?php
						if (isset($hide_copy[$post->post_type] ) ){
							echo '<option value="-1" selected="selected" >-- ' . __('displaying content from other pages is disabled for this post type', 'pgeek-cs') . ' --</option>';
							echo '</select>';
						} else {
							
							// for templates you can't choose a template
							$template = get_post_meta($post->ID, '_pgeek_cs_template_for',true );
							if ( !empty( $template ) )	{
								echo '<option value="-1" selected="selected" >-- ' . __('Not available for pages that are already templates for others', 'pgeek-cs') . ' --</option>';
								echo '</select><br />';
								_e('Jump to edit the page that uses this template as its source', 'pgeek-cs');
								$link = '<br/><a href="' .get_admin_url(). 'post.php?post=%1$s&action=edit">%2$s</a>';
								foreach ($template as $template_target) {
									echo sprintf($link ,$template_target, get_the_title($template_target)) ;
								}
								
							} else {
											
								if ( $pages ) {
									echo '<option value="-1" selected="selected" >-- ' . __('select page/post of Sidebar title and content to display', 'pgeek-cs') . ' --</option>';
									foreach ($pages as $page) {
										$status = ( $page->post_status == 'publish') ? '':' - ('.$page->post_status.')';
										$selected = ($page->ID == $display_from )? "selected='selected'" : "";
										$content= get_post_meta( $page->ID,'_pgeek_cs_content',true);
										if ( !empty($content) ) 
											echo '<option value="'.$page->ID.'" ' . $selected . '>[' .$page->post_type .'] '. $page->post_title . $status . '</option>';
									}
									echo '</select>';
								} else {
									echo '<option value="-1" selected="selected" >-- ' . __('there is no other page/post with content to display', 'pgeek-cs') . ' --</option>';
									echo '</select>';
								}
							}
					}?>
				</td>
			</tr>			
	</table>
	<?php
	
}

function pgeek_cs_add_admin_scripts(){
	wp_enqueue_script( 'pg-cs-admin', plugins_url('/js/pg-cs-admin.js', __FILE__)	);
}

/* ============  Custom Column Support ============ */

	/**
	 * Specify the custom columns that appear for the custom post in the back end
	 */
	function pgeek_cs_add_columns( $cols, $post_type = '' ) {
		$options = get_option('pgeek_cs_options');
		$page_icons = isset($options['show-icons-for-post-type']) ? $options['show-icons-for-post-type']: array();
		
		if ( ''==$post_type || array_key_exists( $post_type, $page_icons ) )
			$cols['pgeek-cs'] = __('Context Sidebar', 'pgeek-cs');
			
	  	return $cols;
	}

	/**
	 * Specify the content of SCC column to show usage

	 */
	function pgeek_cs_populate_columns( $column, $post_id ) {
		//TODO: consider transients to improve the performance of this when we are doing sorting etc - particularly the image existence checks
		switch ( $column ) {
			case "pgeek-cs":
				$status = pgeek_get_status($post_id);
				echo '<div class="pgeek-cs-status-img '. $status['class'] . '" alt="'. $status['description'] . '" title="'. $status['description'] . '"></div>';
			break;
			default:
			break;
	      }
	}
	/**
	 * Helper function to get the status info for the post/page
	 * none      - no page content will be displayed (empty) 
	 * redisplay - Displays another post ie uses a template
	 * original  - An original and source - not used by other pages (ie is not a template)
	 * template  - Is the source for other posts (ie is a template)
	 * problem   - probably a redisplay where the template is gone or empty
	 */
	function pgeek_get_status($post_id = -1){
		$status = array('class'=>'problem', 'description' => __('A problem occurred determining status, please edit and check this page', 'pgeek-cs') );
		$template = get_post_meta($post_id, '_pgeek_cs_template_for',true );
		if ( !empty( $template ) )
			return array('class'=>'template', 'description' => __('This is a template for another page/post', 'pgeek-cs') );
		
		$display_from = get_post_meta($post_id,'_pgeek_cs_display_from',true);
		if ($display_from != "" && $display_from != -1)
			return array('class'=>'redisplay', 'description' => __('Redisplay the content of another page/post (and stays in sync)', 'pgeek-cs') );
		$title= get_post_meta( $post_id,'_pgeek_cs_title',true);
	    $content= get_post_meta( $post_id,'_pgeek_cs_content',true);
		if ( !empty($title) || !empty($content) ) 
			return array('class'=>'original', 'description' => __('Display original content and is not used as a template for another page/post', 'pgeek-cs') );
		return  array('class'=>'none', 'description' => __('No content so no widget will be displayed when this page is shown', 'pgeek-cs') );
	}
/* ============  Admin Page Support ============ */
	
function pgeek_cs_add_admin_page(){
	
	add_options_page(__('PG Context Sensitive Sidebar', 'pgeek-cs'), __('PG Context Sidebar', 'pgeek-cs'), 'manage_options', __FILE__, 'pgeek_cs_admin_page');
	wp_enqueue_style('pg-cs-admin', plugin_dir_url( __FILE__ ) . 'css/pg-cs-admin.css');
}

function pgeek_cs_admin_register_settings(){
	
	register_setting('pgeek_cs_options', 'pgeek_cs_options');
	
	add_settings_section('pgeek_cs_types', __('Which pages should allow entry of content for sidebar and display of status icons', 'pgeek-cs'), 'pgeek_cs_text_types', __FILE__);
	add_settings_field('pgeek_cs_types', __('Page / Post Types', 'pgeek-cs'), 'pgeek_cs_types_input', __FILE__, 'pgeek_cs_types');
	
	add_settings_section('pgeek_cs_data', __('Remove data on uninstall', 'pgeek-cs'), 'pgeek_cs_text_data', __FILE__);
	add_settings_field('pgeek_cs_data', __('Option Settings', 'pgeek-cs'), 'pgeek_cs_data_input', __FILE__, 'pgeek_cs_data');

}

function pgeek_cs_text_types(){
	echo __('Select the types of post and page where you wish to be able to enter context sensitive information for display in the widget', 'pgeek-cs') . '<br />';
	_e('You can also choose to display status icons on the summary page (ie all pages, all posts etc)', 'pgeek-cs'). '<br />';
	_e('On a very large blog there may be too many posts to display in the copy list so you can disable this feature for a post type', 'pgeek-cs');
}

function pgeek_cs_text_data(){
	_e('Option to remove plugin settings on uninstall (content for posts is not deleted)', 'pgeek-cs');
}

function pgeek_cs_types_input(){
	
	$options = get_option('pgeek_cs_options');
	$page_types = $options['show-meta-for-post-type'];
	$page_icons = isset($options['show-icons-for-post-type']) ? $options['show-icons-for-post-type']:'';
	$page_copy = isset($options['hide-copy-for-post-type']) ? $options['hide-copy-for-post-type']:'';
	
	$all_types = array_merge( array('post' ,'page') ,get_post_types(  array('public'=> true,'_builtin' => false ) ) );
	
	echo '<table><tr><td> </td><td>' . __('Allow on edit', 'pgeek-cs') .'</td><td>'. __('Show summary icons', 'pgeek-cs').'</td><td>'. __('Disable copy list', 'pgeek-cs').'</td></tr>';
	foreach ($all_types as $post_type ) {
		$typeObj = get_post_type_object($post_type);
		$checked = ( isset( $page_types[ $post_type ] ) && $page_types[ $post_type ] )?"checked='yes'":'';
		echo '<tr><td>' .$typeObj->labels->name . '</td>';
		echo "<td><input type='checkbox' name='pgeek_cs_options[show-meta-for-post-type][$post_type]' value ='true' $checked />  </td>"; 
		$checked = ( isset( $page_icons[ $post_type ] ) && $page_icons[ $post_type ] )?"checked='yes'":'';
		echo "<td><input type='checkbox' name='pgeek_cs_options[show-icons-for-post-type][$post_type]' value ='true' $checked /></td>"; 
		$checked = ( isset( $page_copy[ $post_type ] ) && $page_copy[ $post_type ] )?"checked='yes'":'';
		echo "<td><input type='checkbox' name='pgeek_cs_options[hide-copy-for-post-type][$post_type]' value ='true' $checked /></td></tr>";
	}
	echo '</table>';
	
}

function pgeek_cs_data_input(){
	
	$options = get_option('pgeek_cs_options');
	$checked = ( isset( $options['delete-settings-on-uninstall'] ) && $options['delete-settings-on-uninstall'] )?"checked='yes'":'';
	echo "<input type='checkbox' name='pgeek_cs_options[delete-settings-on-uninstall]' $checked /> " . __('Delete Plugin Settings on uninstall', 'pgeek-cs') . "<br />\n"; 
}

function pgeek_cs_admin_page(){
	?>
	<div class="wrap"><?php screen_icon();?>
		<h2><?php _e('PG Context Sensitive Sidebar', 'pgeek-cs') ?></h2>
		<h3><?php _e('Usage', 'pgeek-cs') ?></h3>
		<p><?php _e('The Context Sensitive Sidebar allows you to add content to a new metabox on your posts or pages.', 'pgeek-cs') ?><br/>
			<?php _e('This content will be displayed by the PG Context Sensitive Sidebar widget whenever that page or post is displayed.', 'pgeek-cs') ?></p>
		<p><?php _e('You must enter content on pages and posts AND place the widget into a sidebar in your theme', 'pgeek-cs') ?></p>
		<p><?php _e('You can also choose to copy the content from another page/post once off, or to use another page/post as a sort of template where changes made to that one page are reflected on other pages automatically. Use the list boxes on the edit pages to select either a once off copy or an ongoing display.', 'pgeek-cs') ?></p>
		<form action="options.php" method="post">
			<?php settings_fields('pgeek_cs_options');?>
			<?php do_settings_sections(__FILE__);?>
			<br/>
			<input name="Submit" type="submit" value="<?php _e('Save Changes', 'pgeek-cs') ?>" class="button-primary" />
		</form>
	</div>
	<?php 
}

function pgeek_cs_install(){
	//set up the defaults to show the meta box on all the post types by default (find newly registered types that are not built in to base WordPress)
	
	$defaults = array_merge( array('post' ,'page') ,get_post_types(  array('public'=> true,'_builtin' => false ) ) );
	foreach ($defaults as $post_type ) {
		$pgeek_cs_options['show-meta-for-post-type'][ $post_type ] = true;
		$pgeek_cs_options['show-icons-for-post-type'][ $post_type ] = true;
	}
	$pgeek_cs_options['pgeek_cs_option_ver'] = '2.0.0';
	$pgeek_cs_options['delete-settings-on-uninstall'] = false;
	$pgeek_cs_options['delete-data-on-uninstall'] = false;
	
	update_option('pgeek_cs_options', $pgeek_cs_options);
	
}

function pgeek_cs_upgrade_check(){

	$pgeek_cs_options = get_option('pgeek_cs_options');

	$defaults = array_merge( array('post' ,'page') ,get_post_types(  array('public'=> true,'_builtin' => false ) ) );
	foreach ($defaults as $post_type ) {
		$pgeek_cs_options['show-icons-for-post-type'][ $post_type ] = true;
	}
	update_option('pgeek_cs_options', $pgeek_cs_options);
	update_option('pgeek_cs_option_ver', '2.0.0');
	
}


class pgeek_cs_widget extends WP_Widget{
	
	function __construct(){
		// setup the new widget
		$widget_ops = array('classname' => 'pgeek_cs_widget_class',
							'description' => __('Display context that is related to the current post or page, or nothing if no context is set', 'pgeek-cs') );
		parent::__construct('pgeek_cs_widget', __('PG Context Sidebar', 'pgeek-cs'), $widget_ops);
	}
	
	function form ($instance){
		// display the widget form in the admin dashboard
		
		_e('Enter the title and content that you wish to display in this widget on the page or post in the PG context sidebar metabox', 'pgeek-cs');
	}
	
	function widget($args, $instance){
		// display the widget
		global $post;
		
		// work out if we display from the context infor in this post or another
		$display_from = get_post_meta($post->ID,'_pgeek_cs_display_from',true) ;
		$display_from = ( $display_from == '' or $display_from == -1 )? $post->ID : $display_from;
		
	    $title= get_post_meta( $display_from,'_pgeek_cs_title',true);
	    $content= get_post_meta( $display_from,'_pgeek_cs_content',true);
	
		if (is_single() || is_page()) {
			extract($args);
	
			if ( !empty($title) || !empty($content) ) {
	
				echo $before_widget;
	
				if ( $title != '') {
					echo $before_title;
					$title = apply_filters('widget_title', $title);
					echo $title;
					echo $after_title;
				}
	
				if ( $content != '' ) {
					$content=nl2br($content);
					$content = apply_filters('widget_text',$content);
					$content = do_shortcode($content);
					echo $content;
				}
	
				echo $after_widget;
			}
		}		
	}
}
