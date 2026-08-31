<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

global $wp_roles;
$roles = array( 0 => esc_html__( 'all', 'woocommerce-products-filter' ) );
foreach ( $wp_roles->get_names() as $key => $value ) {
	$roles[ $key ] = $value;
}

return array(
	array(
		'title'       => esc_html__( 'Placeholder text', 'woocommerce-products-filter' ),
		'description' => esc_html__( 'First drop-down option placeholder text OR title for checkboxes', 'woocommerce-products-filter' ),
		'element'     => 'text',
		'field'       => 'placeholder',
		'value'       => '',
	),
	array(
		'title'       => esc_html__( 'Role', 'woocommerce-products-filter' ),
		'description' => esc_html__( 'Which users by the selected role to show', 'woocommerce-products-filter' ),
		'element'     => 'select',
		'field'       => 'role',
		'value'       => array(
			'value'   => '0',
			'options' => $roles,
		),
	),
	array(
		'title'       => esc_html__( 'View', 'woocommerce-products-filter' ),
		'description' => esc_html__( 'How to display search by author filter section', 'woocommerce-products-filter' ),
		'element'     => 'select',
		'field'       => 'view',
		'value'       => array(
			'value'   => 'drop-down',
			'options' => array(
				'drop-down' => esc_html__( 'Drop-down', 'woocommerce-products-filter' ),
				'checkbox'  => esc_html__( 'Checkbox', 'woocommerce-products-filter' ),
			),
		),
	),
);
