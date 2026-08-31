<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}
// View is managed by the Data!
return array(
	'prefix'                 => 'clr_', // for keep in db only
	'templates'              => array(
		0 => array(
			'title'        => esc_html__( 'Template #1 (as checkbox)', 'woocommerce-products-filter' ),
			'use_subterms' => 0,
		),
		1 => array(
			'title'        => esc_html__( 'Template #2 (as radio)', 'woocommerce-products-filter' ),
			'use_subterms' => 0,
		),
	),
	'template'               => 0,
	'demo_taxonomies'        => array(
		0 => esc_html__( 'Example terms by real taxonomy', 'woocommerce-products-filter' ),
	),
	'selected_demo_taxonomy' => 0, // demo data for visor
	'sections'               => array(
		array(
			'title' => esc_html__( 'Terms', 'woocommerce-products-filter' ),
			'table' => array(
				'header' => array(
					array(
						'value'  => esc_html__( 'Options', 'woocommerce-products-filter' ),
						'width'  => '50%',
						'action' => 'save_custom_element_option',
					),
					array(
						'value' => esc_html__( 'Description', 'woocommerce-products-filter' ),
						'width' => '50%',
					),
				),
				'rows'   => array(
					'width'              => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 60,
								'min'        => 10,
								'max'        => 500,
								'conditions' => array(),
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Min width', 'woocommerce-products-filter' ) ), // cell
					),
					'height'             => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 60,
								'min'     => 10,
								'max'     => 500,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Height', 'woocommerce-products-filter' ) ), // cell
					),
					'color'              => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#000000',
							),
						), // cell
						array( 'value' => esc_html__( 'Default color', 'woocommerce-products-filter' ) ), // cell
					),
					'border_width'       => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 1,
								'min'     => 0,
								'max'     => 20,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Border width', 'woocommerce-products-filter' ) ), // cell
					),
					'border_radius'      => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 50,
								'min'     => 0,
								'max'     => 50,
							),
							'measure' => '%',
						), // cell
						array( 'value' => esc_html__( 'Border radius', 'woocommerce-products-filter' ) ), // cell
					),
					'border_color'       => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#79b8ff',
							),
						), // cell
						array(
							'value' => esc_html__( 'Border color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'border_style'       => array(
						array(
							'value' => array(
								'element' => 'select',
								'value'   => 'solid',
								'options' => array(
									'dotted' => 'dotted',
									'dashed' => 'dashed',
									'solid'  => 'solid',
									'double' => 'double',
									'groove' => 'groove',
									'ridge'  => 'ridge',
									'inset'  => 'inset',
									'outset' => 'outset',
									'none'   => 'none',
									'hidden' => 'hidden',
								),
							),
						),
						array( 'value' => 'Border style' ), // cell
					),
					'margin_right'       => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 9,
								'min'     => 0,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Margin right', 'woocommerce-products-filter' ) ), // cell
					),
					'margin_bottom'      => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 11,
								'min'     => 0,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Margin bottom', 'woocommerce-products-filter' ) ), // cell
					),
					'transition'         => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 300,
								'min'     => 0,
								'max'     => 1000,
							),
							'measure' => 's',
						), // cell
						array( 'value' => esc_html__( 'Transition (ms)', 'woocommerce-products-filter' ) ), // cell
					),
					'show_tooltip'       => array(
						array(
							'value' => array(
								'element' => 'switcher',
								'value'   => 'none',
								'yes'     => 'block',
								'no'      => 'none',
							),
						), // cell
						array( 'value' => esc_html__( 'Show tooltip on hover', 'woocommerce-products-filter' ) ), // cell
					),
					'show_tooltip_count' => array(
						array(
							'value' => array(
								'element'    => 'switcher',
								'value'      => 'block-inline',
								'yes'        => 'block-inline',
								'no'         => 'none',
								'conditions' => array(
									'hide'          => array(
										'show_tooltip' => 'none', // if this selected element will be hidden
									),
									'forced_change' => array(),
								),
							),
						), // cell
						array( 'value' => esc_html__( 'Show count inside tooltip', 'woocommerce-products-filter' ) ), // cell
					),
				),
			),
		),
		array(
			'title' => esc_html__( 'Selected/Hovered Terms', 'woocommerce-products-filter' ),
			'table' => array(
				'header' => array(
					array(
						'value'  => esc_html__( 'Options', 'woocommerce-products-filter' ),
						'width'  => '50%',
						'action' => 'save_custom_element_option',
					),
					array(
						'value' => esc_html__( 'Description', 'woocommerce-products-filter' ),
						'width' => '50%',
					),
				),
				'rows'   => array(
					'hover_border_color'    => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#79b8ff',
							),
						), // cell
						array( 'value' => esc_html__( 'Hover border color', 'woocommerce-products-filter' ) ), // cell
					),
					'selected_border_color' => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#79b8ff',
							),
						), // cell
						array( 'value' => esc_html__( 'Selected border color', 'woocommerce-products-filter' ) ), // cell
					),
					'hover_border_style'    => array(
						array(
							'value' => array(
								'element' => 'select',
								'value'   => 'dashed',
								'options' => array(
									'dotted' => 'dotted',
									'dashed' => 'dashed',
									'solid'  => 'solid',
									'double' => 'double',
									'groove' => 'groove',
									'ridge'  => 'ridge',
									'inset'  => 'inset',
									'outset' => 'outset',
									'none'   => 'none',
									'hidden' => 'hidden',
								),
							),
						),
						array( 'value' => 'Hover border style' ), // cell
					),
					'selected_border_style' => array(
						array(
							'value' => array(
								'element' => 'select',
								'value'   => 'dashed',
								'options' => array(
									'dotted' => 'dotted',
									'dashed' => 'dashed',
									'solid'  => 'solid',
									'double' => 'double',
									'groove' => 'groove',
									'ridge'  => 'ridge',
									'inset'  => 'inset',
									'outset' => 'outset',
									'none'   => 'none',
									'hidden' => 'hidden',
								),
							),
						),
						array( 'value' => 'Selected border style' ), // cell
					),
					'hover_border_width'    => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 1,
								'min'     => 0,
								'max'     => 20,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Hover border width', 'woocommerce-products-filter' ) ), // cell
					),
					'selected_border_width' => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 1,
								'min'     => 0,
								'max'     => 20,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Selected border width', 'woocommerce-products-filter' ) ), // cell
					),
					'hover_scale'           => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 110,
								'min'     => 50,
								'max'     => 200,
							),
							'measure' => '',
						), // cell
						array( 'value' => esc_html__( 'Hover scale', 'woocommerce-products-filter' ) ), // cell
					),
					'selected_scale'        => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 110,
								'min'     => 50,
								'max'     => 200,
							),
							'measure' => '',
						), // cell
						array( 'value' => esc_html__( 'Selected scale', 'woocommerce-products-filter' ) ), // cell
					),
				),
			),
		),
		array(
			'title' => esc_html__( 'Counter', 'woocommerce-products-filter' ),
			'table' => array(
				'header' => array(
					array(
						'value'  => esc_html__( 'Options', 'woocommerce-products-filter' ),
						'width'  => '50%',
						'action' => 'save_custom_element_option',
					),
					array(
						'value' => esc_html__( 'Description', 'woocommerce-products-filter' ),
						'width' => '50%',
					),
				),
				'rows'   => array(
					'counter_show'          => array(
						array(
							'value' => array(
								'element' => 'switcher',
								'value'   => 'inline-flex',
								'yes'     => 'inline-flex',
								'no'      => 'none',
							),
						), // cell
						array( 'value' => esc_html__( 'Show counter', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_width'         => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 18,
								'min'        => 10,
								'max'        => 72,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						),
						array( 'value' => esc_html__( 'Min width', 'woocommerce-products-filter' ) ),
					),
					'counter_height'        => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 18,
								'min'        => 10,
								'max'        => 72,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						),
						array( 'value' => esc_html__( 'Min height', 'woocommerce-products-filter' ) ),
					),
					'counter_top'           => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 4,
								'min'        => -100,
								'max'        => 100,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Top', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_right'         => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 0,
								'min'        => -100,
								'max'        => 100,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						),
						array( 'value' => esc_html__( 'Right', 'woocommerce-products-filter' ) ),
					),
					'counter_font_size'     => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 9,
								'min'        => 8,
								'max'        => 48,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Text font size', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_color'         => array(
						array(
							'value' => array(
								'element'    => 'color',
								'value'      => '#477bff',
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
						), // cell
						array( 'value' => esc_html__( 'Text color', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_font_family'   => array(
						array(
							'value'   => array(
								'element'    => 'text',
								'value'      => 'consolas',
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => '',
						), // cell
						array( 'value' => esc_html__( 'Text font family (theme must support)', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_font_weight'   => array(
						array(
							'value' => array(
								'element'    => 'select',
								'value'      => 500,
								'options'    => array(
									'100' => '100',
									'200' => '200',
									'300' => '300',
									'400' => '400',
									'500' => '500',
									'600' => '600',
									'700' => '700',
									'800' => '800',
								),
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
						),
						array( 'value' => 'Text font weight' ), // cell
					),
					'counter_side_padding'  => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 0,
								'min'        => 0,
								'max'        => 48,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Side padding', 'woocommerce-products-filter' ) ), // cell
					),

					'counter_bg_color'      => array(
						array(
							'value' => array(
								'element'    => 'color',
								'value'      => '#ffffff',
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
						), // cell
						array( 'value' => esc_html__( 'Background color', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_bg_image'      => array(
						array(
							'value'  => array(
								'element'    => 'image',
								'value'      => '',
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'before' => 'url(',
							'after'  => ')',
						), // cell
						array( 'value' => esc_html__( 'Background image', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_border_width'  => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 1,
								'min'        => 0,
								'max'        => 10,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Border width', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_border_radius' => array(
						array(
							'value'   => array(
								'element'    => 'ranger',
								'value'      => 50,
								'min'        => 0,
								'max'        => 50,
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
							'measure' => '%',
						), // cell
						array( 'value' => esc_html__( 'Border radius', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_border_color'  => array(
						array(
							'value' => array(
								'element'    => 'color',
								'value'      => '#477bff',
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
						), // cell
						array( 'value' => esc_html__( 'Border color', 'woocommerce-products-filter' ) ), // cell
					),
					'counter_border_style'  => array(
						array(
							'value' => array(
								'element'    => 'select',
								'value'      => 'solid',
								'options'    => array(
									'dotted' => 'dotted',
									'dashed' => 'dashed',
									'solid'  => 'solid',
									'double' => 'double',
									'groove' => 'groove',
									'ridge'  => 'ridge',
									'inset'  => 'inset',
									'outset' => 'outset',
									'none'   => 'none',
									'hidden' => 'hidden',
								),
								'conditions' => array(
									'hide' => array(
										'counter_show' => 'none', // if this selected element will be hidden
									),
								),
							),
						),
						array( 'value' => 'Border style' ), // cell
					),
				),
			),
		),
	),
);
