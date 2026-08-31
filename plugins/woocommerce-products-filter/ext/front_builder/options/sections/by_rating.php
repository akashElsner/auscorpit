<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

return array(
	array(
		'title'       => esc_html__( 'Stars', 'woocommerce-products-filter' ),
		'description' => esc_html__( 'Show stars in drop-down', 'woocommerce-products-filter' ),
		'element'     => 'select',
		'field'       => 'use_star',
		'value'       => array(
			'value'   => 0,
			'options' => array(
				0 => esc_html__( 'No', 'woocommerce-products-filter' ),
				1 => esc_html__( 'Yes', 'woocommerce-products-filter' ),
			),
		),
	),
);
