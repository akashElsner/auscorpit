<?php
/**
 * Auscorp Products Grid Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce products grid with quote button, ratings, and full styling.
 */
class Auscorp_Products_Grid_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_products_grid';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Products Grid', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'auscorp' ];
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'product', 'woocommerce', 'shop', 'grid', 'quote', 'best seller', 'auscorp' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-products-grid' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_query_controls();
		$this->register_layout_controls();
		$this->register_card_content_controls();
		$this->register_card_style_controls();
		$this->register_title_style_controls();
		$this->register_subtitle_style_controls();
		$this->register_meta_style_controls();
		$this->register_button_style_controls();
		$this->register_price_style_controls();
	}

	/**
	 * Product query controls.
	 *
	 * @return void
	 */
	private function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Products Query', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'product_source',
			[
				'label'   => esc_html__( 'Show Products', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'best_seller',
				'options' => [
					'latest'      => esc_html__( 'Latest Products', 'hello-elementor-child' ),
					'best_seller' => esc_html__( 'Best Sellers', 'hello-elementor-child' ),
					'featured'    => esc_html__( 'Featured Products', 'hello-elementor-child' ),
					'on_sale'     => esc_html__( 'On Sale', 'hello-elementor-child' ),
					'top_rated'   => esc_html__( 'Top Rated', 'hello-elementor-child' ),
					'category'    => esc_html__( 'By Category', 'hello-elementor-child' ),
					'custom'      => esc_html__( 'Specific Products', 'hello-elementor-child' ),
				],
			]
		);

		$this->add_control(
			'custom_product_ids',
			[
				'label'       => esc_html__( 'Product IDs', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '12, 45, 78',
				'description' => esc_html__( 'Comma-separated WooCommerce product IDs.', 'hello-elementor-child' ),
				'condition'   => [
					'product_source' => 'custom',
				],
			]
		);

		$this->add_control(
			'product_category',
			[
				'label'     => esc_html__( 'Categories', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT2,
				'multiple'  => true,
				'options'   => $this->get_product_categories(),
				'condition' => [
					'product_source' => 'category',
				],
			]
		);

		$this->add_control(
			'products_count',
			[
				'label'     => esc_html__( 'Number of Products', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 48,
				'condition' => [
					'product_source!' => 'custom',
				],
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'     => esc_html__( 'Order By', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => [
					'date'       => esc_html__( 'Date', 'hello-elementor-child' ),
					'title'      => esc_html__( 'Title', 'hello-elementor-child' ),
					'price'      => esc_html__( 'Price', 'hello-elementor-child' ),
					'rand'       => esc_html__( 'Random', 'hello-elementor-child' ),
					'menu_order' => esc_html__( 'Menu Order', 'hello-elementor-child' ),
				],
				'condition' => [
					'product_source!' => [ 'best_seller', 'top_rated', 'custom' ],
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'     => esc_html__( 'Order', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => [
					'DESC' => esc_html__( 'Descending', 'hello-elementor-child' ),
					'ASC'  => esc_html__( 'Ascending', 'hello-elementor-child' ),
				],
				'condition' => [
					'product_source!' => [ 'best_seller', 'top_rated', 'custom' ],
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Grid layout controls.
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => esc_html__( 'Columns', 'hello-elementor-child' ),
				'type'           => \Elementor\Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'selectors'      => [
					'{{WRAPPER}} .auscorp-products-grid__inner' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'grid_gap',
			[
				'label'      => esc_html__( 'Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 80 ],
				],
				'default'    => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-products-grid__inner' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Card content visibility controls.
	 *
	 * @return void
	 */
	private function register_card_content_controls() {
		$this->start_controls_section(
			'section_card_content',
			[
				'label' => esc_html__( 'Card Content', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'show_image',
			[
				'label'        => esc_html__( 'Show Image', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_subtitle',
			[
				'label'        => esc_html__( 'Show Short Description', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'subtitle_length',
			[
				'label'     => esc_html__( 'Description Length (words)', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 12,
				'min'       => 5,
				'max'       => 40,
				'condition' => [
					'show_subtitle' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_rating',
			[
				'label'        => esc_html__( 'Show Rating', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_sku',
			[
				'label'        => esc_html__( 'Show SKU', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'sku_prefix',
			[
				'label'     => esc_html__( 'SKU Prefix', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'SKU:',
				'condition' => [
					'show_sku' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_price',
			[
				'label'        => esc_html__( 'Show Price', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'show_button',
			[
				'label'        => esc_html__( 'Show Button', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'button_action',
			[
				'label'     => esc_html__( 'Button Action', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'add_to_quote',
				'options'   => [
					'add_to_quote' => esc_html__( 'Add to Quote', 'hello-elementor-child' ),
					'add_to_cart'  => esc_html__( 'Add to Cart', 'hello-elementor-child' ),
					'view_product' => esc_html__( 'View Product', 'hello-elementor-child' ),
				],
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'     => esc_html__( 'Button Text', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'Add to Quote', 'hello-elementor-child' ),
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'     => esc_html__( 'Button Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-th',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Card style controls.
	 *
	 * @return void
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__( 'Card', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_card_style' );

		$this->start_controls_tab(
			'tab_card_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'card_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-product-card',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#ffffff',
					],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .auscorp-product-card',
			]
		);

		$this->add_responsive_control(
			'card_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-product-card',
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Body Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '0',
					'right'    => '20',
					'bottom'   => '20',
					'left'     => '20',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_card_hover',
			[
				'label' => esc_html__( 'Hover', 'hello-elementor-child' ),
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-product-card:hover',
			]
		);

		$this->add_control(
			'card_hover_transform',
			[
				'label'     => esc_html__( 'Lift on Hover', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card:hover' => 'transform: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => 'translateY(-4px)',
					''    => 'none',
				],
			]
		);

		$this->add_control(
			'image_hover_zoom',
			[
				'label'        => esc_html__( 'Image Zoom on Hover', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'image_hover_zoom_scale',
			[
				'label'      => esc_html__( 'Zoom Amount', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 105,
						'max'  => 130,
						'step' => 1,
					],
				],
				'default'    => [
					'size' => 112,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card:hover .auscorp-product-card__image' => 'transform: scale(calc({{SIZE}} / 100));',
				],
				'condition'  => [
					'image_hover_zoom' => 'yes',
				],
			]
		);

		$this->add_control(
			'image_hover_duration',
			[
				'label'      => esc_html__( 'Zoom Duration (ms)', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 200,
						'max'  => 1200,
						'step' => 50,
					],
				],
				'default'    => [
					'size' => 550,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__image' => 'transition-duration: {{SIZE}}ms;',
				],
				'condition'  => [
					'image_hover_zoom' => 'yes',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'image_padding',
			[
				'label'      => esc_html__( 'Image Area Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '24',
					'right'    => '20',
					'bottom'   => '12',
					'left'     => '20',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__image-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'image_area_bg',
			[
				'label'     => esc_html__( 'Image Area Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__image-wrap' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Title style controls.
	 *
	 * @return void
	 */
	private function register_title_style_controls() {
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Product Title', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .auscorp-product-card__title, {{WRAPPER}} .auscorp-product-card__title a',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__title a' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__title a:hover, {{WRAPPER}} .auscorp-product-card__title a:focus' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Subtitle style controls.
	 *
	 * @return void
	 */
	private function register_subtitle_style_controls() {
		$this->start_controls_section(
			'section_style_subtitle',
			[
				'label'     => esc_html__( 'Short Description', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_subtitle' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .auscorp-product-card__subtitle',
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#888888',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__subtitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Rating and SKU style controls.
	 *
	 * @return void
	 */
	private function register_meta_style_controls() {
		$this->start_controls_section(
			'section_style_meta',
			[
				'label' => esc_html__( 'Rating & SKU', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'star_color',
			[
				'label'     => esc_html__( 'Star Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffb400',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__rating .star-rating span::before' => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-product-card__rating .star-rating::before'    => 'color: #e0e0e0;',
				],
				'condition' => [
					'show_rating' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'      => 'rating_count_typography',
				'selector'  => '{{WRAPPER}} .auscorp-product-card__rating-count',
				'condition' => [
					'show_rating' => 'yes',
				],
			]
		);

		$this->add_control(
			'rating_count_color',
			[
				'label'     => esc_html__( 'Review Count Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#888888',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__rating-count' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_rating' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'      => 'sku_typography',
				'selector'  => '{{WRAPPER}} .auscorp-product-card__sku',
				'condition' => [
					'show_sku' => 'yes',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sku_color',
			[
				'label'     => esc_html__( 'SKU Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#aaaaaa',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__sku' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_sku' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Button style controls.
	 *
	 * @return void
	 */
	private function register_button_style_controls() {
		$this->start_controls_section(
			'section_style_button',
			[
				'label'     => esc_html__( 'Button', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .auscorp-product-card__btn, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button',
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__btn' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'button_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-product-card__btn, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#ff7a21',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => esc_html__( 'Hover', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'button_hover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__btn:hover, {{WRAPPER}} .auscorp-product-card__btn:focus' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button:hover' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button:hover' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'button_hover_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-product-card__btn:hover, {{WRAPPER}} .auscorp-product-card__btn:focus, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button:hover, {{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button:hover',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#e86a15',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '12',
					'right'    => '16',
					'bottom'   => '12',
					'left'     => '16',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .auscorp-product-card__footer .addquotelistlink button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'button_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 40 ],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-product-card__btn-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Price style controls.
	 *
	 * @return void
	 */
	private function register_price_style_controls() {
		$this->start_controls_section(
			'section_style_price',
			[
				'label'     => esc_html__( 'Price', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_price' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .auscorp-product-card__price',
			]
		);

		$this->add_control(
			'price_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__price'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-product-card__price .amount' => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-product-card__price ins'    => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'price_old_color',
			[
				'label'     => esc_html__( 'Old Price Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#aaaaaa',
				'selectors' => [
					'{{WRAPPER}} .auscorp-product-card__price del' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get product categories for select control.
	 *
	 * @return array
	 */
	private function get_product_categories() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$cached = wp_cache_get( 'auscorp_product_cats', 'hello-elementor-child' );
		if ( false !== $cached ) {
			return $cached;
		}

		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		wp_cache_set( 'auscorp_product_cats', $options, 'hello-elementor-child', 300 );

		return $options;
	}

	/**
	 * Build WP_Query args from widget settings.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	private function build_query_args( $settings ) {
		$source = $settings['product_source'] ?? 'latest';
		$count  = ! empty( $settings['products_count'] ) ? (int) $settings['products_count'] : 4;

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => $settings['orderby'] ?? 'date',
			'order'          => $settings['order'] ?? 'DESC',
			'no_found_rows'  => true,
		];

		switch ( $source ) {
			case 'best_seller':
				$args['meta_key'] = 'total_sales';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'featured':
				$args['tax_query'] = [
					[
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => 'featured',
					],
				];
				break;

			case 'on_sale':
				if ( function_exists( 'wc_get_product_ids_on_sale' ) ) {
					$args['post__in'] = array_merge( [ 0 ], wc_get_product_ids_on_sale() );
					$args['orderby']  = 'date';
				}
				break;

			case 'top_rated':
				$args['meta_key'] = '_wc_average_rating';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'custom':
				$ids = array_filter( array_map( 'absint', explode( ',', $settings['custom_product_ids'] ?? '' ) ) );
				if ( ! empty( $ids ) ) {
					$args['post__in']       = $ids;
					$args['orderby']        = 'post__in';
					$args['posts_per_page'] = count( $ids );
				}
				break;

			case 'category':
				$cats = $settings['product_category'] ?? [];
				if ( ! empty( $cats ) ) {
					$args['tax_query'] = [
						[
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => (array) $cats,
						],
					];
				}
				break;
		}

		return $args;
	}

	/**
	 * Get product subtitle text.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $settings Widget settings.
	 * @return string
	 */
	private function get_product_subtitle( $product, $settings ) {
		$text = $product->get_short_description();

		if ( empty( $text ) ) {
			$text = get_the_excerpt( $product->get_id() );
		}

		$text = wp_strip_all_tags( $text );
		$text = trim( $text );

		if ( empty( $text ) ) {
			return '';
		}

		$length = ! empty( $settings['subtitle_length'] ) ? (int) $settings['subtitle_length'] : 12;

		return wp_trim_words( $text, $length, '…' );
	}

	/**
	 * Render card action button.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $settings Widget settings.
	 * @return void
	 */
	private function render_card_button( $product, $settings ) {
		$action = $settings['button_action'] ?? 'add_to_quote';
		$text   = ! empty( $settings['button_text'] ) ? $settings['button_text'] : esc_html__( 'Add to Quote', 'hello-elementor-child' );
		$icon   = $settings['button_icon'] ?? [];

		if ( 'add_to_quote' === $action ) {
			$this->render_quote_button( $product, $text, $icon );
			return;
		}

		if ( 'add_to_cart' === $action ) {
			$is_simple = $product->is_type( 'simple' );
			$url       = $is_simple ? $product->add_to_cart_url() : get_permalink( $product->get_id() );
			$classes   = 'auscorp-product-card__btn';

			if ( $is_simple ) {
				$classes .= ' add_to_cart_button ajax_add_to_cart product_type_simple';
			}

			?>
			<a href="<?php echo esc_url( $url ); ?>"
				class="<?php echo esc_attr( $classes ); ?>"
				<?php if ( $is_simple ) : ?>
				data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
				data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
				data-quantity="1"
				<?php endif; ?>>
				<?php $this->render_button_icon( $icon ); ?>
				<span><?php echo esc_html( $text ); ?></span>
			</a>
			<?php
			return;
		}

		?>
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="auscorp-product-card__btn">
			<?php $this->render_button_icon( $icon ); ?>
			<span><?php echo esc_html( $text ); ?></span>
		</a>
		<?php
	}

	/**
	 * Render quote button with plugin compatibility.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $text Button label.
	 * @param array       $icon Icon settings.
	 * @return void
	 */
	private function render_quote_button( $product, $text, $icon ) {
		global $dvin_wcql_obj, $post;

		$product_id = $product->get_id();
		$backup_post = $post;

		if ( class_exists( 'Dvin_Wcql_UI' ) && isset( $dvin_wcql_obj ) && is_object( $dvin_wcql_obj ) ) {
			$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );

			$exists  = $dvin_wcql_obj->isExists( $product_id, '' );
			$type    = $product->is_type( 'variable' ) ? 'variable' : 'simple';
			$quote_html = Dvin_Wcql_UI::get_qlist_shoplink( $dvin_wcql_obj->get_url(), $type, $exists, (string) $product_id );

			echo wp_kses_post( $quote_html );

			wp_reset_postdata();
			$post = $backup_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			return;
		}

		$type_class = $product->is_type( 'variable' ) ? 'product_type_variable' : 'product_type_simple';
		?>
		<button type="button"
			class="auscorp-product-card__btn addquotelistbutton <?php echo esc_attr( $type_class ); ?>"
			data-product_id="<?php echo esc_attr( $product_id ); ?>"
			data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
			data-quantity="1">
			<?php $this->render_button_icon( $icon ); ?>
			<span><?php echo esc_html( $text ); ?></span>
		</button>
		<?php
	}

	/**
	 * Render button icon markup.
	 *
	 * @param array $icon Icon settings.
	 * @return void
	 */
	private function render_button_icon( $icon ) {
		if ( empty( $icon['value'] ) ) {
			return;
		}
		?>
		<span class="auscorp-product-card__btn-icon" aria-hidden="true">
			<?php \Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] ); ?>
		</span>
		<?php
	}

	/**
	 * Render a single product card.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $settings Widget settings.
	 * @return void
	 */
	private function render_product_card( $product, $settings ) {
		$rating_count = $product->get_rating_count();
		$avg_rating   = $product->get_average_rating();
		$sku          = $product->get_sku();
		$subtitle     = $this->get_product_subtitle( $product, $settings );
		?>
		<article class="auscorp-product-card">
			<?php if ( 'yes' === ( $settings['show_image'] ?? 'yes' ) ) : ?>
				<div class="auscorp-product-card__image-wrap">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="auscorp-product-card__image-link" tabindex="-1" aria-hidden="true">
						<?php
						if ( has_post_thumbnail( $product->get_id() ) ) {
							echo get_the_post_thumbnail(
								$product->get_id(),
								'woocommerce_thumbnail',
								[
									'class' => 'auscorp-product-card__image',
									'alt'   => esc_attr( $product->get_name() ),
								]
							);
						} elseif ( function_exists( 'wc_placeholder_img_src' ) ) {
							?>
							<img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" alt="" class="auscorp-product-card__image" />
							<?php
						}
						?>
					</a>
				</div>
			<?php endif; ?>

			<div class="auscorp-product-card__body">
				<h3 class="auscorp-product-card__title">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
						<?php echo esc_html( $product->get_name() ); ?>
					</a>
				</h3>

				<?php if ( 'yes' === ( $settings['show_subtitle'] ?? 'yes' ) && ! empty( $subtitle ) ) : ?>
					<p class="auscorp-product-card__subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( ( 'yes' === ( $settings['show_rating'] ?? 'yes' ) && $rating_count > 0 ) || ( 'yes' === ( $settings['show_sku'] ?? 'yes' ) && ! empty( $sku ) ) ) : ?>
					<div class="auscorp-product-card__meta">
						<?php if ( 'yes' === ( $settings['show_rating'] ?? 'yes' ) && $rating_count > 0 ) : ?>
							<div class="auscorp-product-card__rating">
								<?php echo wc_get_rating_html( $avg_rating, $rating_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="auscorp-product-card__rating-count">(<?php echo absint( $rating_count ); ?>)</span>
							</div>
						<?php else : ?>
							<span></span>
						<?php endif; ?>

						<?php if ( 'yes' === ( $settings['show_sku'] ?? 'yes' ) && ! empty( $sku ) ) : ?>
							<span class="auscorp-product-card__sku">
								<?php
								$prefix = ! empty( $settings['sku_prefix'] ) ? $settings['sku_prefix'] : 'SKU:';
								echo esc_html( trim( $prefix . ' ' . $sku ) );
								?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === ( $settings['show_price'] ?? '' ) ) : ?>
					<div class="auscorp-product-card__price">
						<?php echo wp_kses_post( $product->get_price_html() ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === ( $settings['show_button'] ?? 'yes' ) ) : ?>
					<div class="auscorp-product-card__footer">
						<?php $this->render_card_button( $product, $settings ); ?>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p class="auscorp-products-grid__empty">' . esc_html__( 'WooCommerce is not active.', 'hello-elementor-child' ) . '</p>';
			return;
		}

		$settings = $this->get_settings_for_display();
		$query    = new \WP_Query( $this->build_query_args( $settings ) );

		if ( ! $query->have_posts() ) {
			echo '<p class="auscorp-products-grid__empty">' . esc_html__( 'No products found.', 'hello-elementor-child' ) . '</p>';
			return;
		}

		$grid_classes = [ 'auscorp-products-grid' ];
		if ( 'yes' !== ( $settings['image_hover_zoom'] ?? 'yes' ) ) {
			$grid_classes[] = 'auscorp-products-grid--no-image-zoom';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $grid_classes ) ); ?>">
			<div class="auscorp-products-grid__inner">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( ! $product ) {
						continue;
					}
					$this->render_product_card( $product, $settings );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render widget output in the editor.
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<#
		var cols = settings.columns || '4';
		var showRating = settings.show_rating === 'yes';
		var showSku = settings.show_sku === 'yes';
		var showSubtitle = settings.show_subtitle === 'yes';
		var showButton = settings.show_button === 'yes';
		var showPrice = settings.show_price === 'yes';
		var btnText = settings.button_text || '<?php echo esc_js( __( 'Add to Quote', 'hello-elementor-child' ) ); ?>';
		var gridClass = 'auscorp-products-grid';
		if ( settings.image_hover_zoom !== 'yes' ) {
			gridClass += ' auscorp-products-grid--no-image-zoom';
		}
		#>
		<div class="{{ gridClass }}">
			<div class="auscorp-products-grid__inner" style="display:grid; grid-template-columns: repeat({{ cols }}, 1fr); gap:24px;">
				<# for ( var i = 0; i < 4; i++ ) { #>
				<article class="auscorp-product-card">
					<div class="auscorp-product-card__image-wrap" style="padding:24px 20px 12px; text-align:center; background:#fff;">
						<div style="height:140px; display:flex; align-items:center; justify-content:center; opacity:.25; font-size:48px;">🖨</div>
					</div>
					<div class="auscorp-product-card__body">
						<h3 class="auscorp-product-card__title"><a href="#">Brother LC233 Cyan Ink Cartridge</a></h3>
						<# if ( showSubtitle ) { #>
						<p class="auscorp-product-card__subtitle">Compatible: MFC-J4620DW &amp; more</p>
						<# } #>
						<# if ( showRating || showSku ) { #>
						<div class="auscorp-product-card__meta">
							<# if ( showRating ) { #>
							<div class="auscorp-product-card__rating">
								<span style="color:#ffb400;">★★★★★</span>
								<span class="auscorp-product-card__rating-count">(311)</span>
							</div>
							<# } else { #><span></span><# } #>
							<# if ( showSku ) { #>
							<span class="auscorp-product-card__sku">SKU: LC233C</span>
							<# } #>
						</div>
						<# } #>
						<# if ( showPrice ) { #>
						<div class="auscorp-product-card__price">$29.00</div>
						<# } #>
						<# if ( showButton ) { #>
						<div class="auscorp-product-card__footer">
							<a href="#" class="auscorp-product-card__btn"><span>{{{ btnText }}}</span></a>
						</div>
						<# } #>
					</div>
				</article>
				<# } #>
			</div>
		</div>
		<?php
	}
}
