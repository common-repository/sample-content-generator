<?php
/**
 * Plugin Name: Sample Content Generator
 * Plugin URI: http://wordpress.org/extend/plugins/sample-content-generator/
 * Description: Generate sample lorem ipsum content with tiny MCE button.
 * Author: Jean-Pascal Moreau
 * Author URI: https://twitter.com/yukuyu
 * Version: 0.3
 * License: GPL2
 *
 * Copyright 2014  Jean-Pascal Moreau (email : pacomoreau@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( !class_exists( 'Sample_Content_Generator' ) ) {

	define( 'SAMPLE_CONTENT_GENERATOR_VERSION', '0.2' );
	define( 'SAMPLE_CONTENT_GENERATOR_ROOT', dirname( __FILE__ ) );
	define( 'SAMPLE_CONTENT_GENERATOR_URL', plugin_dir_url( __FILE__ ) );
	define( 'SAMPLE_CONTENT_GENERATOR_CURRENT_PAGE', basename( $_SERVER['PHP_SELF'] ) );

	require_once( SAMPLE_CONTENT_GENERATOR_ROOT . '/scg-helpers.php' );

	class Sample_Content_Generator
	{

		/**
		 * Construct the plugin.
		 */
		function __construct() {

			add_action( 'admin_init',	array( $this, 'admin_init' ) );

		}

		/**
		 * Initialize the plugin.
		 */
		function admin_init() {

			// Locales
			load_plugin_textdomain( 'sample-content-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Ajax actions
			add_action( 'wp_ajax_sample_content', array( $this, 'sample_content' ) );

			$is_post_edit_page = in_array( SAMPLE_CONTENT_GENERATOR_CURRENT_PAGE, array( 'post.php', 'page.php', 'page-new.php', 'post-new.php' ) );
			if( ! $is_post_edit_page )
				return;

			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				// Add Styles
				wp_enqueue_style( 'sample_content_generator', SAMPLE_CONTENT_GENERATOR_URL . 'css/scg.css', array(), SAMPLE_CONTENT_GENERATOR_VERSION);
				// Register JS script
				wp_enqueue_script( 'sample_content_generator', SAMPLE_CONTENT_GENERATOR_URL . 'js/scg.js', array( 'jquery' ), SAMPLE_CONTENT_GENERATOR_VERSION, true );
				wp_localize_script( 'sample_content_generator', 'scg_constants', array(
					'version'	=> SAMPLE_CONTENT_GENERATOR_VERSION,
					'pluginurl' => SAMPLE_CONTENT_GENERATOR_URL,
					'ajaxurl'	=> admin_url('admin-ajax.php'),
					'locales'	=> array(
						'plugintitle'	=> __( 'Sample Content Generator', 'sample-content-generator' ),
					),
				));

				// Add Tiny MCE button
				add_action( 'media_buttons', array( $this, 'mce_add_button' ), 20);
				add_action( 'admin_footer', array( $this, 'mce_add_thickbox' ) );

			}

		}

		/**
		 * Generate sample content with loremipsum.net API.
		 *
		 * @return mixed
		 */
		public static function sample_content() {
			$lorem_url = 'http://loripsum.net/api';

			$lorem_args = array(
				'scg_number_of_paragraphs'	=>	scg_post( 'scg_number_of_paragraphs', '/', 5 ),
				'scg_headers'				        =>	scg_post( 'scg_headers', '/', '', 'headers' ),
				'scg_paragraphs_length'		  =>  scg_post( 'scg_paragraphs_length', '/', 'medium' ),
				'scg_decorate'           	  => 	scg_post( 'scg_decorate', '/', '', 'decorate' ),
				'scg_links'           		  => 	scg_post( 'scg_links', '/', '', 'link' ),
				'scg_ul'                    => 	scg_post( 'scg_ul', '/', '', 'ul' ),
				'scg_ol'                    => 	scg_post( 'scg_ol', '/', '', 'ol' ),
				'scg_dl'                    => 	scg_post( 'scg_dl', '/', '', 'dl' ),
				'scg_code'                  => 	scg_post( 'scg_code', '/', '', 'code' ),
				'scg_bq'                    => 	scg_post( 'scg_bq', '/', '', 'bq' ),
			);

			$imgTags = scg_post( 'scg_img' );
			$replace_b_i = scg_post( 'scg_replace_b_i', '', false, true );

			foreach ($lorem_args as $arg) {
				$lorem_url .= $arg;
			}

			if ( WP_DEBUG )
				error_log( sprintf( "[ SCG %s ] %s : %s", SAMPLE_CONTENT_GENERATOR_VERSION, __FUNCTION__, $lorem_url ) );

			$response = wp_remote_get( $lorem_url );
			if ( is_wp_error( $response ) ) {
				if ( WP_DEBUG ) {
					$error = sprintf( "[ SCG %s ] %s : %s", SAMPLE_CONTENT_GENERATOR_VERSION, __FUNCTION__, $response->get_error_message() );
					error_log( $error );
					echo "<< $error >>";
				}
			}
			else {
				$html =  wp_remote_retrieve_body( $response );
				if ( $replace_b_i && '/decorate' == $lorem_args['scg_decorate'] ) {
					// Replacing <b> <i> by <strong> <em>
					$html = preg_replace( array( '/<b>(.+?)<\/b>/' , '/<i>(.+?)<\/i>/' ), array( '<strong>$1</strong>', '<em>$1</em>' ), $html );
				}
				if ( $imgTags ) {
					// Add <img> tags in paragraphs with http://dummyimage.com
					global $thumbnail, $medium;
					$thumbnail = get_option( 'thumbnail_size_w' ) . 'x' . get_option( 'thumbnail_size_h' );
					$medium = get_option( 'medium_size_w' ) . 'x' . get_option( 'medium_size_h' );
					$html = preg_replace_callback( "/<p>(.+?)<\/p>/", function( $match ) {
						global $thumbnail, $medium;
						$align = rand( 0, 1 ) ? 'alignleft' : 'alignright';
						$size = $thumbnail;
						return "<p><img src='http://dummyimage.com/$size/000/ddd.jpg' class='$align'>" . $match[1] . "</p>";
					}, $html );
				}
				echo $html;
			}
			die();

		}

		/**
		 * Add Tiny MCE button.
		 *
		 * @return mixed
		 */
		public static function mce_add_button() {

			echo '<a href="#TB_inline?width=640&height=650&inlineId=scg_content" class="thickbox button scg_media_link" id="add_scgform" title="' . __("Generate content", 'sample-content-generator') . '"><span class="scg_media_icon "></span> ' . __("Generate content", "sample-content-generator") . '</a>';

		}

		/**
		 * Add ThickBox Content.
		 */
		public static function mce_add_thickbox() {
		?>

		<div id="scg_content">
			<div class="wrap">
				<h2><?php _e( 'Choose your options', 'sample-content-generator' ); ?></h2>
				<table class="form-table scg">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="scg_number_of_paragraphs"><?php _e( 'Number of paragraphs', 'sample-content-generator' ); ?></label></th>
							<td><input type="number" id="scg_number_of_paragraphs" value="5" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_paragraphs_length"><?php _e( 'Paragraph length', 'sample-content-generator' ); ?></label></th>
							<td>
								<select id="scg_paragraphs_length">
									<option value="short"><?php _e( 'Short', 'sample-content-generator' ); ?></option>
									<option value="medium" selected="selected"><?php _e( 'Medium', 'sample-content-generator' ); ?></option>
									<option value="long"><?php _e( 'Long', 'sample-content-generator' ); ?></option>
									<option value="verylong"><?php _e( 'Very long', 'sample-content-generator' ); ?></option>
								</select>
							</td>
						</tr>
            <tr valign="top">
              <th scope="row"><label for="scg_img"><?php _e( 'Add &lthX&gt; tags',	'sample-content-generator' ); ?></label></th>
              <td><input type="checkbox" id="scg_headers" checked="checked" /></td>
            </tr>
						<tr valign="top">
							<th scope="row"><label for="scg_img"><?php _e( 'Add &ltimg&gt; tags',	'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_img" checked="checked" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_decorate"><?php _e( 'Add &ltb&gt;, &lti&gt;, &ltmark&gt; tags',	'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_decorate" checked="checked" /></td>
						</tr>
						<tr valign="top" class="scg_replace_b_i">
							<th scope="row"><label for="scg_replace_b_i"><?php _e( 'Replace &ltb&gt; and &lti&gt; tags by &ltstrong&gt; and &ltem&gt;',	'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_replace_b_i" checked="checked" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_links"><?php _e( 'Add &lta&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_links" checked="checked" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_ul"><?php _e( 'Add &lt;ul&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_ul" checked="checked" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_ol"><?php _e( 'Add &lt;ol&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_ol" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_dl"><?php _e( 'Add &lt;dl&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_dl" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_code"><?php _e( 'Add &lt;code&gt; and &lt;pre&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_code" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="scg_bq"><?php _e( 'Add &lt;blockquote&gt; tags', 'sample-content-generator' ); ?></label></th>
							<td><input type="checkbox" id="scg_bq" /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<span class="spinner scg_spinner"></span>
					<input type="button" class="button-primary generate" value="<?php _e( 'Generate Content', 'sample-content-generator' ); ?>" />&nbsp;&nbsp;&nbsp;
					<input type="button" class="button cancel" value="<?php _e( 'Cancel', 'sample-content-generator' ); ?>" />
				</p>
			</div>
		</div>

		<?php
		}

	}

}

global $sample_content_generator;
$sample_content_generator = new Sample_Content_Generator();