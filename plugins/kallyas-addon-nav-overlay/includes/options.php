<?php if(! defined('ABSPATH')){ return; }

/**
 * Theme options > Header Overlay Navigation Options
 */
if( class_exists( 'znSideHeader' ) ){
	$warning = sprintf(
		__( 'This plugin cannot work with <strong>Kallyas Addon - Side Header Plugin</strong>. To solve this issue please access <a href="%s">Plugins page</a> and disable Kallyas Addon - Side Header Plugin.', 'kallyas-addon-side-header' ),
		admin_url('plugins.php')
	);
	$admin_options[] = array (
		'slug'        => 'head_nav_overlay_options',
		'parent'      => 'general_options',
		"name"        => __( '<strong style="font-size:120%">Warning!</strong>', 'kallyas-addon-side-header' ),
		"description" => $warning,
		'type'  => 'zn_message',
		'id'    => 'zn_error_notice',
		'show_blank'  => 'true',
		'supports'  => 'warning'
	);
}


$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( 'OVERLAY NAVIGATION OPTIONS', 'kallyas-addon-nav-overlay' ),
	"description" => sprintf( __( 'These are options to customize the Overlay navigation. To activate the "Hamburger" menu, please access <a target="_blank" href="%s">Header - Navigation Options</a> and increase the responsive breakpoint-width as much as you want, <a href="http://hogash.d.pr/fvO7" target="_blank">screenshot here</a>.', 'kallyas-addon-nav-overlay' ), admin_url('admin.php?page=zn_tp_general_options#nav_options')) ,
	"id"          => "hovrl_t2",
	"type"        => "zn_title",
	"class"       => "zn_full zn-custom-title-large zn-toptabs-margin"
);

$admin_options[] = array (
'slug'        => 'head_nav_overlay_options',
				'parent'      => 'general_options',
				"name"        => __( 'LAYOUT OPTIONS', 'kallyas-addon-nav-overlay' ),
				"description" => __( 'These options are dedicated dedicated to customize the looks of the overlay navigation.', 'kallyas-addon-nav-overlay' ),
				"id"          => "hd_title1",
				"type"        => "zn_title",
				"class"       => "zn_full zn-custom-title-large zn-top-separator"
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Layout", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Please choose the desired overlay layout.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_layout",
	"std"         => "S1",
	"type"        => "radio_image",
	"class"        => "ri-hover-line ri-3",
	"options"     => array(
		array(
			'value' => 'S1',
			'name'  => __( 'Style #1', 'kallyas-addon-nav-overlay' ),
			'image' => $this->url .'/admin/style1.png'
		),
		array(
			'value' => 'S2',
			'name'  => __( 'Style #2', 'kallyas-addon-nav-overlay' ),
			'image' => $this->url .'/admin/style2.png'
		),
		array(
			'value' => 'S3',
			'name'  => __( 'Style #3', 'kallyas-addon-nav-overlay' ),
			'image' => $this->url .'/admin/style3.png'
		),
	)
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Entrance Animation", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Select the entrance animation of the overlay.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_anim",
	"std"         => "anim1",
	'type'        => 'select',
	'options'        => array(
		'1' => __( "Slide Down Animation #1", 'kallyas-addon-nav-overlay' ),
		'2' => __( "Side Slide Animation #2", 'kallyas-addon-nav-overlay' ),
		'3' => __( "Fade In Animation #3", 'kallyas-addon-nav-overlay' ),

		// TODO: http://codepen.io/Carlos1162/pen/qZNrpx
		// '4' => __( "Sliding Bars Animation #4", 'kallyas-addon-nav-overlay' ),

		// TODO: http://codepen.io/lefoy/pen/aOGGRe
		// '5' => __( " Animation #5", 'kallyas-addon-nav-overlay' ),
	),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Close Button Position", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Select the close button position.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_close_pos",
	"std"         => "trSmall",
	'type'        => 'select',
	'options'        => array(
		'trSmall' => __( "Top RIGHT v1 (30px distance from edge)", 'kallyas-addon-nav-overlay' ),
		'trLarge' => __( "Top RIGHT v2 (80px distance from edge)", 'kallyas-addon-nav-overlay' ),
		'tlSmall' => __( "Top LEFT v1 (30px distance from edge)", 'kallyas-addon-nav-overlay' ),
		'tlLarge' => __( "Top LEFT v2 (80px distance from edge)", 'kallyas-addon-nav-overlay' ),
	),
);


$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Background Color", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Select the background color", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_bgcolor",
	"std"         => "rgba(0,0,0,0.9)",
	"type"        => "colorpicker",
	"alpha"       => true,
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Menu Typography settings", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Adjust the typography of the menu items links as you want on any breakpoint", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_font_breakpoints",
	"std"         => "lg",
	"tabs"        => true,
	"type"        => "zn_radio",
	"options"     => array (
		"lg"        => __( "LARGE", 'kallyas-addon-nav-overlay' ),
		"md"        => __( "MEDIUM", 'kallyas-addon-nav-overlay' ),
		"sm"        => __( "SMALL", 'kallyas-addon-nav-overlay' ),
		"xs"        => __( "EXTRA SMALL", 'kallyas-addon-nav-overlay' ),
	),
	"class"       => "zn_full zn_breakpoints"
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Menu Typography Settings", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the menu.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_typo",
	"std"         => '',
	'supports'   => array( 'size', 'font', 'style', 'line', 'weight', 'spacing', 'case' ),
	"type"        => "font",
	"dependency"  => array( 'element' => 'hovrl_font_breakpoints' , 'value'=> array('lg') ),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Menu Typography Settings (MD)", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the menu.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_typo_md",
	"std"         => '',
	'supports'   => array( 'size', 'line', 'spacing' ),
	"type"        => "font",
	"dependency"  => array( 'element' => 'hovrl_font_breakpoints' , 'value'=> array('md') ),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Menu Typography Settings (SM)", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the menu.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_typo_sm",
	"std"         => '',
	'supports'   => array( 'size', 'line', 'spacing' ),
	"type"        => "font",
	"dependency"  => array( 'element' => 'hovrl_font_breakpoints' , 'value'=> array('sm') ),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Menu Typography Settings (XS)", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the menu.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_typo_xs",
	"std"         => '',
	'supports'   => array( 'size', 'line', 'spacing' ),
	"type"        => "font",
	"dependency"  => array( 'element' => 'hovrl_font_breakpoints' , 'value'=> array('xs') ),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "SubMenus Typography Settings", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the submenu items. These options are not recommended unless the purpose is for overriding the menu typography options.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_typo_submenu",
	"std"         => '',
	'supports'   => array( 'size', 'style', 'line', 'weight', 'spacing', 'case' ),
	"type"        => "font",
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Content Color Theme", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Select the content color theme.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_menu_theme",
	"std"         => "light",
	'type'        => 'select',
	'options'        => array(
		'light' => __( "Light", 'kallyas-addon-nav-overlay' ),
		'dark' => __( "Dark", 'kallyas-addon-nav-overlay' ),
	),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Hover / Active Menu items", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Choose a color for the hover & active menu item.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_menu_hover",
	"std"         => "",
	"type"        => "colorpicker",
	"alpha"       => "true",
);

$admin_options[] = array (
				'slug'        => 'head_nav_overlay_options',
				'parent'      => 'general_options',
				"name"        => __( 'CONTENT OPTIONS', 'kallyas-addon-nav-overlay' ),
				"description" => __( 'These options are dedicated dedicated to customize the <em>contents</em> of the overlay navigation.', 'kallyas-addon-nav-overlay' ),
				"id"          => "hd_title1",
				"type"        => "zn_title",
				"class"       => "zn_full zn-custom-title-large zn-top-separator"
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Custom Logo Image", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Add a custom logo image.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_logo",
	"std"         => "",
	"type"        => "media",
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Display Social Icons", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Display the social icons.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_social",
	"std"         => "no",
	'type'        => 'zn_radio',
	'options'        => array(
		'yes' => __( "Yes", 'kallyas-addon-nav-overlay' ),
		'no' => __( "No", 'kallyas-addon-nav-overlay' ),
	),
	'class'        => 'zn_radio--yesno',
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Footer Text", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Add some custom text. Supports HTML or Shortcodes.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_footertext",
	"std"         => "",
	"type"        => "textarea",
);



$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Custom Text", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Add some custom text. Supports HTML or Shortcodes.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_ctext",
	"std"         => "",
	"type"        => "textarea",
	"dependency"  => array( 'element' => 'hovrl_layout' , 'value'=> array('S2', 'S3') ),
);

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( "Custom Text Typography", 'kallyas-addon-nav-overlay' ),
	"description" => __( "Specify the typography properties for the custom text.", 'kallyas-addon-nav-overlay' ),
	"id"          => "hovrl_ctext_typo",
	"std"         => array(
		'font-size' => '10px',
		'letter-spacing' => '2px',
	),
	'supports'   => array( 'size', 'font', 'style', 'line', 'weight', 'spacing', 'case', 'color' ),
	"type"        => "font",
	"dependency"  => array( 'element' => 'hovrl_layout' , 'value'=> array('S2', 'S3') ),
);


/**
 * CONTENT
 */


/**
 *************** HELP FIELDS FROM HERE
 */

$admin_options[] = array (
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
	"name"        => __( '<span class="dashicons dashicons-editor-help"></span> HELP:', 'kallyas-addon-nav-overlay' ),
	"description" => __( 'Below you can find quick access to documentation, video documentation or our support forum.', 'kallyas-addon-nav-overlay' ),
	"id"          => "ho_title",
	"type"        => "zn_title",
	"class"       => "zn_full zn-custom-title-md zn-top-separator zn-sep-dark"
);

// $admin_options[] = zn_options_video_link_option( 'http://support.hogash.com/kallyas-videos/#TuXcJu9jl7c', __( "Click here to access the video tutorial for this section's options.", 'kallyas-addon-nav-overlay' ), array(
// 	'slug'        => 'head_nav_overlay_options',
// 	'parent'      => 'general_options'
// ));

$admin_options[] = wp_parse_args( znpb_general_help_option( 'zn-admin-helplink' ), array(
	'slug'        => 'head_nav_overlay_options',
	'parent'      => 'general_options',
));
