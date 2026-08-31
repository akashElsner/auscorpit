<?php

/**
 * Plugin Name: Kallyas Addon - Overlay Navigation
 * Plugin URI: http://hogash.com
 * Description: This plugin will replace the default Kallyas theme side panel navigation with an overlay panel.
 * Version: 1.0.12
 * Author: Hogash Studio
 * Author URI: http://hogash.com
 * License: GPL2
 */


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class znNavOverlay{

	/**
	 * Holds the plugin current version
	 * @var string
	 */
	var $version = '1.0.10';

	/**
	 * Holds the plugin url
	 * @var string
	 */
	var $url = '';

	/**
	 * Holds the plugin path
	 * @var string
	 */
	var $path = '';


	/**
	 * Holds a refference of the class instance
	 */
	static $instance = null;


	/**
	 * Returns an instance of the classs
	 * @return [type] [description]
	 */
	static public function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Main class constructor
	 */
	function __construct(){

		// Set plugin paths
		$this->url       = plugin_dir_url( __FILE__ );
		$this->path      = plugin_dir_path( __FILE__ );

		add_action( 'after_setup_theme', array( $this, 'init' ) );

		// load text domain
		load_plugin_textdomain('kallyas-addon-nav-overlay', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Fire up the plugin or show a notice in case Kallyas theme is not installed
	 */
	function init(){
		if( ! function_exists( 'ZNPB' ) ){
			add_action( 'admin_notices', array( $this, 'showAdminNotice' ) );
		}
		else {
			if( class_exists( 'znSideHeader' ) ){
				add_action( 'admin_notices', array( $this, 'showAdminNotice_conflict' ) );
			}
			// Add options
			add_filter( 'zn_theme_pages', array( $this, 'addPageOptions' ) );
			add_filter( 'zn_theme_options', array( $this, 'addThemeOptions' ) );

			// Add Assets
			add_action( 'wp_enqueue_scripts', array( $this, 'addAssets' ), 50 );

			add_action( 'zn_after_body', array( $this, 'addNavMarkup' ), 90 );		// Load Markup
			add_filter( 'zn_dynamic_css', array( $this, 'addDynamicCSS' ), 110 );	// Add dynamic CSS

			// Change Site-Nav class
			add_filter( 'zn_mainnav_type', array( $this, 'changeNavClass' ) );

			// Add Settings button in plugin
			$plugin_basename = plugin_basename( __FILE__ );
			add_filter("plugin_action_links_$plugin_basename", array( $this, 'settingsLink') );

			// Add admin CSS
			add_action('admin_head', array( $this, 'addAdminCSS' ), 130);
		}
	}

	/**
	 * Add settings link on plugin page
	 * @param  array $links existing plugins links
	 * @return array        modified list
	 */
	function settingsLink($links) {
		$settings_link = '<a href="'. admin_url( 'admin.php?page=zn_tp_general_options#head_nav_overlay_options' ) .'">'.__( 'Settings', 'kallyas-addon-nav-overlay' ).'</a>';
		array_push($links, $settings_link);
		return $links;
	}

	/**
	 * Shows an admin notice telling you that the Kallyas theme is not installed.
	 */
	function showAdminNotice(){

		$class = 'notice notice-error is-dismissible';
		$buy_kallyas_url = 'https://themeforest.net/item/kallyas-responsive-multipurpose-wordpress-theme/4091658';
		$buy_link = sprintf( '<a class="button button button-primary" href="%1$s" target="_blank">%2$s</a>', $buy_kallyas_url, __( 'Get Kallyas Theme!', 'kallyas-addon-nav-overlay' ) );
		$message = __( 'Kallyas theme is not installed! Overlay Navigation Plugin only works with Kallyas theme.', 'kallyas-addon-nav-overlay' );

		printf( '<div class="%1$s"><p>%2$s</p><p>%3$s</p></div>', $class, $message, $buy_link );

	}

	function showAdminNotice_conflict(){

		$message = __( '<strong>Kallyas Addon - Overlay Plugin</strong> cannot work with <strong>Side Header Plugin</strong>. To solve this issue please disable Side Header Plugin.', 'kallyas-addon-side-header' );

		printf( '<div class="notice notice-error"><p>%s</p></div>', $message );

	}

	/**
	 * Load the options slugs and hook to Kallyas theme options
	 * @param array $admin_pages Existing slugs
	 */
	function addPageOptions($admin_pages){

		$nav_options = array(
			'slug' => 'head_nav_overlay_options',
			'title' =>  'Header - Overlay Navigation'
		);

		if(isset($admin_pages['general_options']['submenus'])){
			$admin_pages['general_options']['submenus'][] = $nav_options;
		}

		return $admin_pages;
	}

	/**
	 * Load the options that are displayed in Kallyas options > General options > Header - Overlay navigation
	 * @param array $admin_options Existing options
	 */
	function addThemeOptions($admin_options){

		if ( ! is_admin() )
			return;

		include( $this->path . '/includes/options.php' );

		return $admin_options;
	}

	/**
	 * Load Assets
	 */
	function addAssets() {

		$assets = $this->url . 'assets/';
		$suffix = ( (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) || defined( 'ZN_FW_DEBUG' ) && ZN_FW_DEBUG == true ) ? '' : '.min';

		wp_enqueue_style( 'kallyas-addon-nav-overlay-css', $assets . 'styles'.$suffix.'.css', false, $this->version );
		wp_enqueue_script( 'kallyas-addon-nav-overlay-js', $assets . 'app'.$suffix.'.js', array ( 'zn-script' ), $this->version, true );

	}

	/**
	 * Change Main Menu's Class
	 */
	function changeNavClass(){
		return 'mainnav--overlay';
	}

	/**
	 * Load the HTML Markup
	 */
	function addNavMarkup(){

		if ( is_admin() ) return;

		include_once $this->path . '/includes/markup.php';
	}


	/**
	 * Cache Custom CSS
	 */
	function addDynamicCSS( $css ){

		include( $this->path . '/includes/dynamic_css.php' );

		return $css;
	}

	/*
	 * Admin CSS for highlighting the menu item in theme options
	 */
	function addAdminCSS() {
		echo '<style>
			#head_nav_overlay_options_menu_item {background-color: #202529;}
			#head_nav_overlay_options_menu_item.wp-ui-highlight {color: #fff; background-color: #2bbb23;}
			.zn_option_container[data-optionid="mobile_menu_theme"] {display:none;}
		</style>';
	}


}
znNavOverlay::get_instance();