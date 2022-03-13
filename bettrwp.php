<?php
/**
 * Plugin Name:       BettrWP by WPZOOM
 * Plugin URI:        https://wpzoom.com/plugins/
 * Description:       A plugin that will make your life much easy and it is created by the WPZOOM team
 * Version:           1.0.0
 * Author:            WPZOOM
 * Author URI:        https://www.wpzoom.com/
 * Text Domain:       bettrwp
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.2
 * Tested up to:      5.8
 *
 * @package BettrWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WPZOOM_BETTRWP_VER', '1.0.0' );

define( 'WPZOOM_BETTRWP__FILE__', __FILE__ );
define( 'WPZOOM_BETTRWP_PLUGIN_BASE', plugin_basename( WPZOOM_BETTRWP__FILE__ ) );
define( 'WPZOOM_BETTRWP_PLUGIN_DIR', dirname( WPZOOM_BETTRWP_PLUGIN_BASE ) );

define( 'WPZOOM_BETTRWP_PATH', plugin_dir_path( WPZOOM_BETTRWP__FILE__ ) );
define( 'WPZOOM_BETTRWP_URL', plugin_dir_url( WPZOOM_BETTRWP__FILE__ ) );


// Instance the plugin
WPZOOM_BettrWP::instance();

/**
 * Main WPZOOM BettrWP Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPZOOM_BettrWP {

	/**
	 * Instance
	 *
	 * @var WPZOOM_BettrWP The single instance of the class.
	 * @since 1.0.0
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return WPZOOM_BettrWP An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'i18n' ) );
		
		add_filter( 'page_row_actions', array( $this, 'filter_admin_row_actions' ), 11, 2 );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_css' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ), 99 );

		add_action( 'wp_ajax_wpzoom_set_frontpage', array( $this, 'set_page_as_frontpage' ) );	
		
		add_action( 'enqueue_block_editor_assets', array( $this, 'custom_link_injection_to_gutenberg_toolbar' ) );
	
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'bettrwp', false, WPZOOM_BETTRWP_PLUGIN_DIR . '/languages' );
	}

	/**
	 * Add/Remove edit link in dashboard.
	 *
	 * Add or remove an edit link to the post/page action links on the post/pages list table.
	 *
	 * Fired by `post_row_actions` and `page_row_actions` filters.
	 *
	 * @access public
	 *
	 * @param array    $actions An array of row action links.
	 *
	 * @return array An updated array of row action links.
	 */
	public function filter_admin_row_actions( $actions, $post ) {

		//Make sure the page is published
		if( 'publish' !== $post->post_status ) {
			return $actions;
		}
		
		//Check if the page is not frontpage already
		if( 'page' == get_option( 'show_on_front' ) && $post->ID == get_option( 'page_on_front' ) ) {
			return $actions;
		}

		//Add our link
		$actions['wpzoom_set_as_frontpage'] = sprintf(			
			'<a class="wpzoom-bettrwp" href="#" data-page-id="%1$d" data-nonce="%2$s">%3$s</a>',
			$post->ID,
			wp_create_nonce( 'wpzoom-set-frontpage' ),
			esc_html__( 'Set as Front Page', 'bettrwp' )
		);

		return $actions;

	}

	public function set_page_as_frontpage() {

		if( ! wp_verify_nonce( $_POST['nonce'], 'wpzoom-set-frontpage' ) ) {
			return;
		}

		if( isset( $_POST['page_id'] ) ) {
			$page_id = $_POST['page_id'];
		}

		if( !current_user_can( 'edit_pages' ) ) {
			return;
		}

		if( !empty( $page_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', absint( $page_id ) );
		}

		wp_send_json_success();

	}

	private function is_pages_dashboard() {
		
		global $pagenow;

		if ( ( $pagenow == 'edit.php' ) && isset( $_GET['post_type'] ) && ( 'page' == $_GET['post_type'] ) ) {
			return true;
		}
		return false;

	}

	/**
	 * Enqueue plugin styles.
	 */
	public function enqueue_css() {
	
		wp_enqueue_style( 
			'bettrwp', 
			WPZOOM_BETTRWP_URL . 'assets/css/bettrwp.css', 
			WPZOOM_BETTRWP_VER 
		);
	
	}
 
	public function custom_link_injection_to_gutenberg_toolbar(){

		global $post;

		$screen = get_current_screen();

		if ( 'page' === $screen->post_type ){
			wp_enqueue_script( 
				'sethomepage-button-in-toolbar', 
				WPZOOM_BETTRWP_URL . 'assets/js/bettrwp-button-in-toolbar.js', 
				array( 'jquery' ), 
				WPZOOM_BETTRWP_VER,
				true 
			);
			wp_localize_script(
				'sethomepage-button-in-toolbar',
				'WPZOOMBettrEditor',
				array(
					'frontpage_id'        => get_option( 'page_on_front' ),
					'post_id'             => get_the_ID(),
					'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'          => wp_create_nonce( 'wpzoom-set-frontpage' ),
					'labelSetPageAsFront' => esc_html__( 'Set as Front Page', 'bettrwp' ),
					'labelPageIsFront'    => esc_html__( 'This is Front Page', 'bettrwp' ),
					'confirmText'         => esc_html__( 'Do you really want to set this page as Front Page?', 'bettrwp' ),
				)
			);
		}
	   
	}

	/**
	 * Enqueue plugin scripts.
	 */
	public function enqueue_js() {

		wp_enqueue_script( 
			'bettrwp-js', 
			WPZOOM_BETTRWP_URL . 'assets/js/bettrwp.js', 
			array( 'jquery' ), 
			WPZOOM_BETTRWP_VER,
			true 
		);

		wp_localize_script(
			'bettrwp-js',
			'WPZOOM_Bettrwp',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'wpzoom-set-frontpage' ),
			)
		);
	
	}


}