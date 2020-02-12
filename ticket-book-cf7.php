<?php
/**
 * The plugin bootstrap file
 *
 * @link              http://manojsinghrwt.wordpress.com/
 * @since             1.0.0
 * @package           Ticket_Book_Cf7
 *
 * @wordpress-plugin
 * Plugin Name:       Ticket Book for CF7
 * Plugin URI:        #
 * Description:       This is a Ticket Booking add-on for Contact Form 7.
 * Version:           1.0.0
 * Author:            Manoj Singh
 * Author URI:        http://manojsinghrwt.wordpress.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ticket-book-cf7
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Currently plugin version.
 */
define( 'TICKET_BOOK_CF7_VERSION', '1.0.0' );

/*-------------------------------------------------------------------------------------------------------*/
class Ticket_Book_Cf7 {
	public function __construct() {
		register_activation_hook( __FILE__, array($this,'intall_db_ticket_book_cf7'));
		add_action( 'admin_init', array($this,'is_contact_form_7_active_ticket_book_cf7') );
		add_action( 'deactivated_plugin', array($this,'detect_deactivate_ticket_book_cf7'), 10, 2 );
		add_action('wpcf7_init', array($this,'wpcf7_add_form_tag_ticket_book_cf7'), 10);
		add_action("wpcf7_before_send_mail", array($this,"save_ticket_book_cf7"));
		add_action( 'wpcf7_admin_init', array($this,'wpcf7_add_tag_generator_ticket_book_cf7'), 35 );
	}

	function is_contact_form_7_active_ticket_book_cf7() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			add_action( 'admin_notices', array($this,'error_msg_is_cf7_ticket_book_cf7') );
			deactivate_plugins( plugin_basename( __FILE__ ) ); 
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	function error_msg_is_cf7_ticket_book_cf7() { ?>
		<div class="error">
			<p>
				<?php printf(
					__('<b style="color:red">%s</b> must be installed and activated for the Ticket Book for CF7 plugin to work', 'ticket-book-cf7'),
					'<a href="'.admin_url('plugin-install.php?tab=search&s=contact+form+7').'">Contact Form 7</a>'
				); ?>
			</p>
		</div>
		<?php
	}
	/**
	 *  Deactivation plugin when contact form 7 deactivated
	 */
	function detect_deactivate_ticket_book_cf7( $plugin, $network_activation ) {
	    if ($plugin=="contact-form-7/wp-contact-form-7.php")
	    {
	        deactivate_plugins(plugin_basename(__FILE__));
	    }
	}
	/*
	*
	* Contact Form 7 tag: ticket_book_cf7/ Front-End Output
	*/
	function wpcf7_add_form_tag_ticket_book_cf7() {
		if (function_exists('wpcf7_add_form_tag')) {
			wpcf7_add_form_tag( 
				'ticket_book_cf7', 
				array($this,'wpcf7_ticket_book_cf7_formtag_handler'),
				array( 
					'name-attr' => true, 
					'do-not-store' => true,
					'not-for-mail' => true
				)
			);
		} else {
			wpcf7_add_shortcode( 'ticket_book_cf7', array($this,'wpcf7_ticket_book_cf7_formtag_handler'),true);
		}
	}
	function wpcf7_ticket_book_cf7_formtag_handler($tag){
		global $wpdb;
		$pid 		= get_the_ID();
		$table 		= $wpdb->prefix . 'ticket_book_cf7';
		$result		= $wpdb->get_row("SELECT * FROM ".$table." WHERE post_id =".$pid ,ARRAY_N);
		$tag 		= (class_exists('WPCF7_FormTag')) ? new WPCF7_FormTag( $tag ) : new WPCF7_Shortcode( $tag );

		if ( empty( $tag->name ) )
			return '';
		
		$class 				= wpcf7_form_controls_class( 'text' );
		$atts 				= array();
		$atts['class'] 		= $tag->get_class_option( $class );
		$atts['id'] 		= $tag->get_option( 'id', 'id', true );
		$atts['wrapper_id'] = $tag->get_option('wrapper-id');
		$wrapper_id 		= (!empty($atts['wrapper_id'])) ? reset($atts['wrapper_id']) : uniqid('wpcf7-');
		$atts['message'] 	= apply_filters('wpcf7_ticket_book_cf7_accessibility_message', __('Please leave this field empty.','contact-form-7-ticket_book_cf7'));
		$atts['name'] 		= $tag->name;
		$atts['type'] 		= $tag->type;
		$atts['css'] 		= apply_filters('wpcf7_ticket_book_cf7_container_css', 'display:none !important; visibility:hidden !important;');
		$inputid 			= (!empty($atts['id'])) ? 'id="'.$atts['id'].'" ' : '';
		$inputid_for 		= ($inputid) ? 'for="'.$atts['id'].'" ' : '';
		$el_css 			= 'style="'.$atts['css'].'"';
		$html 				= '<span id="'.$wrapper_id.'" class="wpcf7-form-control-wrap ' . $atts['name'] . '-wrap" '.$el_css.'>';
		$html .= '<style>span.ticket_book{display:inline-table;min-width:130px;}</style><p><span '. $inputid .' class="wpcf7-form-control wpcf7-checkbox ' . $atts['class'] . '">';
			for($i=1; $i<=100; $i++){
				$checked 	= 	$result[$i] ? ' checked disabled ': '' ;
			 	$html 	.= '<span class="ticket_book" ><input name="ticket_book[tk'.$i.']" value="1" type="checkbox" '.$checked.'><span class="wpcf7-list-item-label">Ticket ['.$i.']</span></span>';
			}
		 $html .= '</span>
			<input type="hidden" name="page_id" value="'.$pid.'"/>		
			</p>';
		
		$html .='</span>';
		return apply_filters('wpcf7_tickett_book_html_output',$html, $atts);
	}
	/**
	* Save on Sumbit (Front End)
	*/
	function save_ticket_book_cf7($WPCF7_ContactForm){
		global $wpdb;
		$wpcf7      	= WPCF7_ContactForm::get_current();
		$submission 	= WPCF7_Submission::get_instance();
		if ($submission){
			$data = $submission->get_posted_data();
			if (empty($data))
				return;
		   	$ticket_book   	= isset($data['ticket_book']) ? $data['ticket_book'] : "";
			$page_id   		= isset($data['page_id']) ? $data['page_id'] : "";
		  	$table 			= $wpdb->prefix.'ticket_book_cf7';
			$result 		= $wpdb->get_results('SELECT sum(post_id) as result_value FROM '.$table.' WHERE post_id='.$page_id.'');
			
			if($result[0]->result_value){
				$wpdb->update( $table, $ticket_book, array( 'post_id' => $page_id ), array( '%d', ),array( '%d' ));
			}
			else{
				$ticket_book['post_id'] = $page_id ;
				$wpdb->insert($table, $ticket_book, array('%d'));
			}
		}
	}

	/**
	* Create Table
	*/
	public function intall_db_ticket_book_cf7(){
		global $wpdb;
		$charset_collate 	= $wpdb->get_charset_collate();
		$table 		= $wpdb->prefix . 'ticket_book_cf7';
		for($i=1;$i<=100;$i++ ) {
			$tk[] = 'tk'.$i.' tinyint(1)';
		}
		$val = implode(', ' ,$tk);
		$sql = "CREATE TABLE $table (id mediumint(9) NOT NULL AUTO_INCREMENT,".$val .",post_id mediumint(9) NOT NULL,UNIQUE KEY id (id)) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	/**
	*
	* Contact Form 7 Form Tag Generator
	*/
	function wpcf7_add_tag_generator_ticket_book_cf7() {
		if (class_exists('WPCF7_TagGenerator')) {
			$tag_generator = WPCF7_TagGenerator::get_instance();
			$tag_generator->add( 'ticket_book_cf7', __( 'Ticket Book', 'contact-form-7-ticket_book_cf7' ), array($this,'wpcf7_tg_pane_ticket_book_cf7') );
		} else if (function_exists('wpcf7_add_tag_generator')) {
			wpcf7_add_tag_generator( 'ticket_book_cf7', __( 'Ticket Book', 'contact-form-7-ticket_book_cf7' ),	'wpcf7-tg-pane-ticket_book_cf7', array($this,'wpcf7_tg_pane_ticket_book_cf7') );
		}
	}
	function wpcf7_tg_pane_ticket_book_cf7($contact_form, $args = '') {
		if (class_exists('WPCF7_TagGenerator')) {
			$args = wp_parse_args( $args, array() );
			$description = __( "Generate a form-tag for a Ticket Booking field.", 'contact-form-7-ticket_book_cf7' );
			$desc_link = '<a href="#" target="_blank">'.__( 'CF7 Ticket Booking', 'contact-form-7-ticket_book_cf7' ).'</a>';
			?>
			<div class="control-box">
				<fieldset>
					<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

					<table class="form-table"><tbody>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7-ticket_book_cf7' ) ); ?></label></th>
							<td>
								<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /><br>
								<em><?php echo esc_html( __( 'This can be anything, but should be changed from the default generated "ticket_book_cf7". For better security, change "ticket_book_cf7" to something else.', 'ticket-book-cf7' ) ); ?></em>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'ID (optional)', 'ticket-book-cf7' ) ); ?></label></th>
							<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class (optional)', 'ticket-book-cf7' ) ); ?></label></th>
							<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-wrapper-id' ); ?>"><?php echo esc_html( __( 'Wrapper ID (optional)', 'ticket-book-cf7' ) ); ?></label></th>
							<td><input type="text" name="wrapper-id" class="wrapper-id-value oneline option" id="<?php echo esc_attr( $args['content'] . '-wrapper-id' ); ?>" /><br><em><?php echo esc_html( __( 'By default the markup that wraps this form item has a random ID. You can customize it here. If you are unsure, leave blank.', 'ticket-book-cf7' ) ); ?></em></td>
						</tr>

						

					</tbody></table>
				</fieldset>
			</div>

			<div class="insert-box">
				<input type="text" name="ticket_book_cf7" class="tag code" readonly="readonly" onfocus="this.select()" />
				<div class="submitbox"><input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'ticket-book-cf7' ) ); ?>" /></div><br class="clear" />
			</div>
		<?php } else { ?>
			<div id="wpcf7-tg-pane-ticket_book_cf7" class="hidden">
				<form action="">
					<table>
						<tr>
							<td>
								<?php echo esc_html( __( 'Name', 'ticket-book-cf7' ) ); ?><br />
								<input type="text" name="name" class="tg-name oneline" /><br />
								<em><small><?php echo esc_html( __( 'For better security, change "ticket_book_cf7" to something less bot-recognizable.', 'ticket-book-cf7' ) ); ?></small></em>
							</td>
							<td></td>
						</tr>
						<tr><td colspan="2"><hr></td></tr>
						<tr>
							<td><?php echo esc_html( __( 'ID (optional)', 'ticket-book-cf7' ) ); ?><br /><input type="text" name="id" class="idvalue oneline option" /></td>
							<td><?php echo esc_html( __( 'Class (optional)', 'ticket-book-cf7' ) ); ?><br /><input type="text" name="class" class="classvalue oneline option" /></td>
						</tr>
						<tr><td colspan="2"><hr></td></tr>			
					</table>
					<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'ticket-book-cf7' ) ); ?><br /><input type="text" name="ticket_book_cf7" class="tag" readonly="readonly" onfocus="this.select()" /></div>
				</form>
			</div>
		<?php }

	}

}
/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
$plugin = new Ticket_Book_Cf7();