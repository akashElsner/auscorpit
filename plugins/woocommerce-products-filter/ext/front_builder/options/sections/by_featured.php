<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

return array(
	array(
		'title'       => esc_html__( 'View', 'woocommerce-products-filter' ),
		'description' => esc_html__( 'How to show: checkbox or switcher', 'woocommerce-products-filter' ),
		'element'     => 'select',
		'field'       => 'view',
		'value'       => array(
			'value'   => 'switcher',
			'options' => array(
				'checkbox' => esc_html__( 'Checkbox', 'woocommerce-products-filter' ),
				'switcher' => esc_html__( 'Switcher', 'woocommerce-products-filter' ),
			),
		),
	),
);
