<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}
// View is managed by the Data!
return array(
	'prefix'                 => 'sw_', // for keep in db only
	'templates'              => array(
		0 => array(
			'title'        => esc_html__( 'Template #1', 'woocommerce-products-filter' ),
			'use_subterms' => 1,
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
				'class'  => 'woof-sd-table-terms',
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
					'vertex_enabled_bg_color'         => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#79b8ff',
							),
						), // cell
						array(
							'value' => esc_html__( 'Vertex enabled background color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'vertex_enabled_bg_image'         => array(
						array(
							'value'  => array(
								'element' => 'image',
								'value'   => '',
							),
							'before' => 'url(',
							'after'  => ')',
						), // cell
						array( 'value' => esc_html__( 'Vertex enabled background image', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_enabled_border_color'     => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#79b8ff',
							),
						), // cell
						array(
							'value' => esc_html__( 'Vertex enabled border color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'vertex_enabled_border_style'     => array(
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
						array( 'value' => 'Vertex enabled border style' ), // cell
					),
					'vertex_disabled_bg_color'        => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#ffffff',
							),
						), // cell
						array( 'value' => esc_html__( 'Vertex disabled background color', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_disabled_bg_image'        => array(
						array(
							'value'  => array(
								'element' => 'image',
								'value'   => '',
							),
							'before' => 'url(',
							'after'  => ')',
						), // cell
						array( 'value' => esc_html__( 'Vertex disabled background image', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_disabled_border_color'    => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#ffffff',
							),
						), // cell
						array(
							'value' => esc_html__( 'Vertex disabled border color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'vertex_disabled_border_style'    => array(
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
						array( 'value' => 'Vertex disabled border style' ), // cell
					),
					'vertex_border_width'             => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 1,
								'min'     => 0,
								'max'     => 10,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Vertex border width', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_size'                     => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 20,
								'min'     => 15,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Vertex size (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_top'                      => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 0,
								'min'     => -50,
								'max'     => 50,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Vertex top (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'vertex_border_radius'            => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 50,
								'min'     => 0,
								'max'     => 50,
							),
							'measure' => '%',
						), // cell
						array( 'value' => esc_html__( 'Vertex border radius (%)', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_enabled_bg_color'      => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#c8e1ff',
							),
						), // cell
						array( 'value' => esc_html__( 'Substrate enabled background color', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_enabled_bg_image'      => array(
						array(
							'value'  => array(
								'element' => 'image',
								'value'   => '',
							),
							'before' => 'url(',
							'after'  => ')',
						), // cell
						array( 'value' => esc_html__( 'Substrate enabled background image', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_enabled_border_color'  => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#c8e1ff',
							),
						), // cell
						array(
							'value' => esc_html__( 'Substrate enabled border color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'substrate_enabled_border_style'  => array(
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
						array( 'value' => 'Substrate enabled border style' ), // cell
					),
					'substrate_disabled_bg_color'     => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#9a9999',
							),
						), // cell
						array( 'value' => esc_html__( 'Substrate disabled background color', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_disabled_bg_image'     => array(
						array(
							'value'  => array(
								'element' => 'image',
								'value'   => '',
							),
							'before' => 'url(',
							'after'  => ')',
						), // cell
						array( 'value' => esc_html__( 'Substrate disabled background image', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_disabled_border_color' => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#9a9999',
							),
						), // cell
						array(
							'value' => esc_html__( 'Substrate disabled border color', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'substrate_disabled_border_style' => array(
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
						array( 'value' => 'Substrate disabled border style' ), // cell
					),
					'substrate_border_radius'         => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 8,
								'min'     => 0,
								'max'     => 50,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Substrate border radius (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_border_width'          => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 1,
								'min'     => 0,
								'max'     => 10,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Substrate border width', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_width'                 => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 34,
								'min'     => 30,
								'max'     => 200,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Substrate width (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'substrate_height'                => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 14,
								'min'     => 0,
								'max'     => 80,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Substrate height (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'label_font_color'                => array(
						array(
							'value' => array(
								'element' => 'color',
								'value'   => '#6d6d6d',
							),
						), // cell
						array( 'value' => esc_html__( 'Text color', 'woocommerce-products-filter' ) ), // cell
					),
					'label_font_size'                 => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 16,
								'min'     => 8,
								'max'     => 48,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Text font size (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'label_line_height'               => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 23,
								'min'     => 8,
								'max'     => 48,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Text line height (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'label_font_family'               => array(
						array(
							'value'   => array(
								'element' => 'text',
								'value'   => 'inherit',
							),
							'measure' => '',
						), // cell
						array(
							'value' => esc_html__( 'Text font family (theme must support)', 'woocommerce-products-filter' ),
							'help'  => 'https://products-filter.com/extencion/smart-designer/#tips-about-customizations',
						), // cell
					),
					'label_font_weight'               => array(
						array(
							'value' => array(
								'element'    => 'select',
								'value'      => 400,
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
								'conditions' => array(),
							),
						),
						array( 'value' => 'Text font weight' ), // cell
					),
					'label_left'                      => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 15,
								'min'     => 0,
								'max'     => 60,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Text left (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'label_top'                       => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => -16,
								'min'     => -100,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Text top (px)', 'woocommerce-products-filter' ) ), // cell
					),
					'margin_bottom'                   => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 5,
								'min'     => 0,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Margin bottom', 'woocommerce-products-filter' ) ), // cell
					),
					'childs_left_shift'               => array(
						array(
							'value'   => array(
								'element' => 'ranger',
								'value'   => 15,
								'min'     => 0,
								'max'     => 100,
							),
							'measure' => 'px',
						), // cell
						array( 'value' => esc_html__( 'Child terms left shift', 'woocommerce-products-filter' ) ), // cell
					),
				),
			),
		),
		array(
			'title' => esc_html__( 'Counter', 'woocommerce-products-filter' ),
			'table' => array(
				'class'  => 'woof-sd-table-counter',
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
								'value'      => -3,
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
								'value'      => -3,
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
