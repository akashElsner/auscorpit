<?php

$bg_color = zget_option( 'hovrl_bgcolor', 'general_options', false, 'rgba(0,0,0,0.9)' );

$css .= '.znNavOvr {background-color:'.$bg_color.'}';

// Typography
$typo = array();
$typo['lg'] = zget_option( 'hovrl_typo', 'general_options', false, '' );
$typo['md'] = zget_option( 'hovrl_typo_md', 'general_options', false, '' );
$typo['sm'] = zget_option( 'hovrl_typo_sm', 'general_options', false, '' );
$typo['xs'] = zget_option( 'hovrl_typo_xs', 'general_options', false, '' );
if( !empty($typo) ){
	$typo['selector'] = '.znNavOvr--layout'. zget_option( 'hovrl_layout', 'general_options', false, 'S1' ) .' .znNavOvr-menu';
	$css .= zn_typography_css( $typo );
}
// SubMenus Typography
$submenu_typo = array();
$submenu_typo['lg'] = zget_option( 'hovrl_typo_submenu', 'general_options', false, '' );
if( !empty($submenu_typo) ){
	$submenu_typo['selector'] = '.znNavOvr .znNavOvr-menu ul.sub-menu, .znNavOvr .znNavOvr-menu .zn_mega_container';
	$css .= zn_typography_css( $submenu_typo );
}

// Bg Color
$zn_body_def_color = zget_option( 'zn_body_def_color', 'color_options', false, '#f5f5f5' );
$css .= '.znNavOvr.znNavOvr--animation2 ~ #page_wrapper { background-color:'.$zn_body_def_color.';}';

// Custom Text
$ctext_typo = array();
$ctext_typo['lg'] = zget_option( 'hovrl_ctext_typo', 'general_options', false, array('font-size' => '10px', 'letter-spacing' => '2px') );
if( !empty($ctext_typo) ){
	$ctext_typo['selector'] = '.znNavOvr-customText';
	$css .= zn_typography_css( $ctext_typo );
}

if( $zn_hover_menu_color = zget_option( 'hovrl_menu_hover', 'general_options', false, '' ) ){
	$css .= '.znNavOvr .znNavOvr-menu .active > .main-menu-link, .znNavOvr .znNavOvr-menu .main-menu-link:hover {color:'.$zn_hover_menu_color.';}';
}