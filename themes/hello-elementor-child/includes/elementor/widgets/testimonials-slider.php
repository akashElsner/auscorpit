<?php
/**
 * Auscorp Testimonials Slider Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Testimonial cards slider with center highlight and full styling options.
 */
class Auscorp_Testimonials_Slider_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_testimonials_slider';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Testimonials Slider', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-testimonial-carousel';
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
		return [ 'testimonial', 'review', 'slider', 'carousel', 'quote', 'auscorp' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-testimonials-slider' ];
	}

	/**
	 * Script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'auscorp-testimonials-slider' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_testimonials_controls();
		$this->register_slider_settings_controls();
		$this->register_section_style_controls();
		$this->register_quote_style_controls();
		$this->register_card_style_controls();
		$this->register_active_card_style_controls();
		$this->register_stars_style_controls();
		$this->register_content_style_controls();
		$this->register_navigation_style_controls();
		$this->register_dots_style_controls();
	}

	/**
	 * Testimonials repeater.
	 *
	 * @return void
	 */
	private function register_testimonials_controls() {
		$this->start_controls_section(
			'section_testimonials',
			[
				'label' => esc_html__( 'Testimonials', 'hello-elementor-child' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'rating',
			[
				'label'   => esc_html__( 'Rating', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 5,
				'step'    => 1,
				'default' => 5,
			]
		);

		$repeater->add_control(
			'content',
			[
				'label'       => esc_html__( 'Content', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Yet preference connection unpleasant yet melancholy but end appearance. And excellence partiality estimating terminated day everything.', 'hello-elementor-child' ),
				'rows'        => 5,
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'author_name',
			[
				'label'       => esc_html__( 'Name', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Sam', 'hello-elementor-child' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'author_title',
			[
				'label'       => esc_html__( 'Title / Company', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Founder @ Migelka', 'hello-elementor-child' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'testimonials',
			[
				'label'       => esc_html__( 'Items', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'author_name'  => esc_html__( 'Sabo Masties', 'hello-elementor-child' ),
						'author_title' => esc_html__( 'Founder @ Rolex', 'hello-elementor-child' ),
						'rating'       => 5,
					],
					[
						'author_name'  => esc_html__( 'Sam', 'hello-elementor-child' ),
						'author_title' => esc_html__( 'Founder @ Migelka', 'hello-elementor-child' ),
						'rating'       => 5,
					],
					[
						'author_name'  => esc_html__( 'Mansur', 'hello-elementor-child' ),
						'author_title' => esc_html__( 'Founder @ Google', 'hello-elementor-child' ),
						'rating'       => 5,
					],
				],
				'title_field' => '{{{ author_name }}}',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Slider settings.
	 *
	 * @return void
	 */
	private function register_slider_settings_controls() {
		$this->start_controls_section(
			'section_slider_settings',
			[
				'label' => esc_html__( 'Slider Settings', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'initial_active_index',
			[
				'label'       => esc_html__( 'Initial Active Slide', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => 1,
				'description' => esc_html__( '0-based index. Use 1 to highlight the second testimonial on load.', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'center_highlight',
			[
				'label'        => esc_html__( 'Center Highlight', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_responsive_control(
			'slides_per_view',
			[
				'label'              => esc_html__( 'Slides Per View', 'hello-elementor-child' ),
				'type'               => \Elementor\Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 4,
				'default'            => 3,
				'tablet_default'     => 2,
				'mobile_default'     => 1,
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'slide_gap',
			[
				'label'      => esc_html__( 'Slide Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'    => [
					'size' => 24,
					'unit' => 'px',
				],
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => esc_html__( 'Autoplay', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'     => esc_html__( 'Autoplay Speed (ms)', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => esc_html__( 'Pause on Hover', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'infinite_loop',
			[
				'label'        => esc_html__( 'Infinite Loop', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_arrows',
			[
				'label'        => esc_html__( 'Show Arrows', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_dots',
			[
				'label'        => esc_html__( 'Show Dots', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Section style controls.
	 *
	 * @return void
	 */
	private function register_section_style_controls() {
		$this->start_controls_section(
			'section_style_wrapper',
			[
				'label' => esc_html__( 'Section', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'section_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-testimonials',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#fdf6f0',
					],
				],
			]
		);

		$this->add_responsive_control(
			'section_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '80',
					'right'    => '24',
					'bottom'   => '80',
					'left'     => '24',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Quote decoration style.
	 *
	 * @return void
	 */
	private function register_quote_style_controls() {
		$this->start_controls_section(
			'section_style_quote',
			[
				'label' => esc_html__( 'Quote Decoration', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'show_quote_decoration',
			[
				'label'        => esc_html__( 'Show Quote Mark', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'quote_source',
			[
				'label'     => esc_html__( 'Quote Type', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'character',
				'options'   => [
					'character'      => esc_html__( 'Text Character', 'hello-elementor-child' ),
					'elementor_icon' => esc_html__( 'Icon / SVG Library', 'hello-elementor-child' ),
					'custom_image'   => esc_html__( 'Upload Image / SVG', 'hello-elementor-child' ),
				],
				'condition' => [
					'show_quote_decoration' => 'yes',
				],
			]
		);

		$this->add_control(
			'quote_text',
			[
				'label'     => esc_html__( 'Quote Character', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '“',
				'condition' => [
					'show_quote_decoration' => 'yes',
					'quote_source'          => 'character',
				],
			]
		);

		$this->add_control(
			'quote_icon',
			[
				'label'     => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-quote-left',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_quote_decoration' => 'yes',
					'quote_source'          => 'elementor_icon',
				],
			]
		);

		$this->add_control(
			'quote_custom_image',
			[
				'label'       => esc_html__( 'Upload Icon', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => [ 'image', 'svg' ],
				'description' => esc_html__( 'SVG images inherit the quote color. PNG/JPG images display as uploaded.', 'hello-elementor-child' ),
				'condition'   => [
					'show_quote_decoration' => 'yes',
					'quote_source'          => 'custom_image',
				],
			]
		);

		$this->add_control(
			'quote_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 122, 33, 0.12)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__quote' => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-testimonials__quote-media' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_quote_decoration' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'quote_size',
			[
				'label'      => esc_html__( 'Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 40, 'max' => 300 ],
				],
				'default'    => [
					'size' => 180,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__quote' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_quote_decoration' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'quote_offset',
			[
				'label'      => esc_html__( 'Position', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '0',
					'right'    => 'auto',
					'bottom'   => 'auto',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__quote' => 'top: {{TOP}}{{UNIT}}; right: {{RIGHT}}{{UNIT}}; bottom: {{BOTTOM}}{{UNIT}}; left: {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'show_quote_decoration' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Card normal/hover style.
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

		$this->add_control(
			'card_bg_color',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'           => 'card_border',
				'selector'       => '{{WRAPPER}} .auscorp-testimonials__card',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width'  => [
						'default' => [
							'top'      => '2',
							'right'    => '2',
							'bottom'   => '2',
							'left'     => '2',
							'isLinked' => true,
						],
					],
					'color'  => [
						'default' => 'transparent',
					],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__card',
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '32',
					'right'    => '28',
					'bottom'   => '32',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
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
					'{{WRAPPER}} .auscorp-testimonials__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_min_height',
			[
				'label'      => esc_html__( 'Min Height', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 500 ],
				],
				'default'    => [
					'size' => 220,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__card' => 'min-height: {{SIZE}}{{UNIT}};',
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

		$this->add_control(
			'card_hover_bg_color',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide:not(.is-active) .auscorp-testimonials__card:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide:not(.is-active) .auscorp-testimonials__card:hover' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__slide:not(.is-active) .auscorp-testimonials__card:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Active (center) card style.
	 *
	 * @return void
	 */
	private function register_active_card_style_controls() {
		$this->start_controls_section(
			'section_style_active_card',
			[
				'label' => esc_html__( 'Active Card', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'active_card_bg_color',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0d2d5e',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'active_card_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__card' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'active_card_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color on Hover', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.35)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__card:hover' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'active_card_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__card',
			]
		);

		$this->add_control(
			'active_card_lift',
			[
				'label'     => esc_html__( 'Lift Amount', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => [
					'px' => [ 'min' => 0, 'max' => 20 ],
				],
				'default'   => [
					'size' => 2,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__card' => 'transform: translateY(-{{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Stars style.
	 *
	 * @return void
	 */
	private function register_stars_style_controls() {
		$this->start_controls_section(
			'section_style_stars',
			[
				'label' => esc_html__( 'Stars', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'stars_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__stars' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'stars_size',
			[
				'label'      => esc_html__( 'Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 32 ],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__stars' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stars_gap',
			[
				'label'      => esc_html__( 'Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 20 ],
				],
				'default'    => [
					'size' => 4,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__stars' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stars_spacing',
			[
				'label'      => esc_html__( 'Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'    => [
					'size' => 20,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__stars' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Text content style.
	 *
	 * @return void
	 */
	private function register_content_style_controls() {
		$this->start_controls_section(
			'section_style_content',
			[
				'label' => esc_html__( 'Content', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'content_heading',
			[
				'label' => esc_html__( 'Testimonial Text', 'hello-elementor-child' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__content',
			]
		);

		$this->start_controls_tabs( 'tabs_content_colors' );

		$this->start_controls_tab(
			'tab_content_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#4b5563',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_content_active',
			[
				'label' => esc_html__( 'Active', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'active_content_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.88)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'name_heading',
			[
				'label'     => esc_html__( 'Author Name', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__name',
			]
		);

		$this->start_controls_tabs( 'tabs_name_colors' );

		$this->start_controls_tab(
			'tab_name_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_name_active',
			[
				'label' => esc_html__( 'Active', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'active_name_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'title_heading',
			[
				'label'     => esc_html__( 'Author Title', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'author_title_typography',
				'selector' => '{{WRAPPER}} .auscorp-testimonials__title',
			]
		);

		$this->start_controls_tabs( 'tabs_author_title_colors' );

		$this->start_controls_tab(
			'tab_author_title_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'author_title_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#9ca3af',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_author_title_active',
			[
				'label' => esc_html__( 'Active', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'active_author_title_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.65)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__slide.is-active .auscorp-testimonials__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Navigation arrows style.
	 *
	 * @return void
	 */
	private function register_navigation_style_controls() {
		$this->start_controls_section(
			'section_style_navigation',
			[
				'label'     => esc_html__( 'Navigation Arrows', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_arrows' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'nav_spacing',
			[
				'label'      => esc_html__( 'Top Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'default'    => [
					'size' => 32,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__nav' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_size',
			[
				'label'      => esc_html__( 'Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 24, 'max' => 80 ],
				],
				'default'    => [
					'size' => 44,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_gap',
			[
				'label'      => esc_html__( 'Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
				],
				'default'    => [
					'size' => 12,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__nav' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_border_radius',
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
					'{{WRAPPER}} .auscorp-testimonials__arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default'    => [
					'top'      => '2',
					'right'    => '2',
					'bottom'   => '2',
					'left'     => '2',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__arrow' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; border-style: solid;',
				],
			]
		);

		$this->add_control(
			'prev_arrow_heading',
			[
				'label'     => esc_html__( 'Previous Arrow', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'prev_arrow_bg',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prev_arrow_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prev_arrow_color',
			[
				'label'     => esc_html__( 'Icon Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prev_arrow_hover_heading',
			[
				'label'     => esc_html__( 'Previous Arrow Hover', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'prev_arrow_hover_bg',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fff5ee',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--prev:focus-visible:not(:disabled)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prev_arrow_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--prev:focus-visible:not(:disabled)' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prev_arrow_hover_color',
			[
				'label'     => esc_html__( 'Icon Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--prev:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--prev:focus-visible:not(:disabled)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_heading',
			[
				'label'     => esc_html__( 'Next Arrow', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'next_arrow_bg',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_color',
			[
				'label'     => esc_html__( 'Icon Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_hover_heading',
			[
				'label'     => esc_html__( 'Next Arrow Hover', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'next_arrow_hover_bg',
			[
				'label'     => esc_html__( 'Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e86a15',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--next:focus-visible:not(:disabled)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--next:focus-visible:not(:disabled)' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'next_arrow_hover_color',
			[
				'label'     => esc_html__( 'Icon Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__arrow--next:hover:not(:disabled), {{WRAPPER}} .auscorp-testimonials__arrow--next:focus-visible:not(:disabled)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Dots style.
	 *
	 * @return void
	 */
	private function register_dots_style_controls() {
		$this->start_controls_section(
			'section_style_dots',
			[
				'label'     => esc_html__( 'Dots', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_dots' => 'yes',
				],
			]
		);

		$this->add_control(
			'dot_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 122, 33, 0.25)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__dot' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dot_active_color',
			[
				'label'     => esc_html__( 'Active Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-testimonials__dot.is-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'dot_size',
			[
				'label'      => esc_html__( 'Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 4, 'max' => 20 ],
				],
				'default'    => [
					'size' => 10,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-testimonials__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Whether uploaded media is SVG.
	 *
	 * @param array $media Media control value.
	 * @return bool
	 */
	private function is_svg_media( $media ) {
		if ( empty( $media['url'] ) ) {
			return false;
		}

		if ( ! empty( $media['id'] ) ) {
			$mime = get_post_mime_type( (int) $media['id'] );
			if ( 'image/svg+xml' === $mime ) {
				return true;
			}
		}

		return (bool) preg_match( '/\.svg(\?.*)?$/i', $media['url'] );
	}

	/**
	 * Render quote decoration markup.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_quote_decoration( $settings ) {
		if ( 'yes' !== ( $settings['show_quote_decoration'] ?? 'yes' ) ) {
			return;
		}

		$source = $settings['quote_source'] ?? 'character';

		echo '<div class="auscorp-testimonials__quote" aria-hidden="true">';

		if ( 'custom_image' === $source && ! empty( $settings['quote_custom_image']['url'] ) ) {
			$url = $settings['quote_custom_image']['url'];

			if ( $this->is_svg_media( $settings['quote_custom_image'] ) ) {
				printf(
					'<span class="auscorp-testimonials__quote-media" style="--quote-url: url(%s);"></span>',
					esc_url( $url )
				);
			} else {
				printf(
					'<img class="auscorp-testimonials__quote-img" src="%s" alt="" loading="lazy" />',
					esc_url( $url )
				);
			}
		} elseif ( 'elementor_icon' === $source && ! empty( $settings['quote_icon']['value'] ) ) {
			echo '<span class="auscorp-testimonials__quote-icon">';
			\Elementor\Icons_Manager::render_icon(
				$settings['quote_icon'],
				[
					'aria-hidden' => 'true',
				]
			);
			echo '</span>';
		} else {
			echo esc_html( $settings['quote_text'] ?? '“' );
		}

		echo '</div>';
	}

	/**
	 * Render star icons for a rating value.
	 *
	 * @param int $rating Rating from 0-5.
	 * @return void
	 */
	private function render_stars( $rating ) {
		$rating = max( 0, min( 5, (int) $rating ) );

		if ( $rating <= 0 ) {
			return;
		}

		echo '<div class="auscorp-testimonials__stars" aria-label="' . esc_attr( sprintf( /* translators: %d: star rating */ _n( '%d star', '%d stars', $rating, 'hello-elementor-child' ), $rating ) ) . '">';

		for ( $i = 0; $i < $rating; $i++ ) {
			echo '<i class="fas fa-star" aria-hidden="true"></i>';
		}

		echo '</div>';
	}

	/**
	 * Build slider data attributes.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	private function get_slider_data_attributes( $settings ) {
		$gap = isset( $settings['slide_gap']['size'] ) ? (int) $settings['slide_gap']['size'] : 24;

		return [
			'data-active-index'     => max( 0, (int) ( $settings['initial_active_index'] ?? 1 ) ),
			'data-slides-desktop'   => max( 1, (int) ( $settings['slides_per_view'] ?? 3 ) ),
			'data-slides-tablet'    => max( 1, (int) ( $settings['slides_per_view_tablet'] ?? 2 ) ),
			'data-slides-mobile'    => max( 1, (int) ( $settings['slides_per_view_mobile'] ?? 1 ) ),
			'data-gap'              => $gap,
			'data-autoplay'         => ( 'yes' === ( $settings['autoplay'] ?? '' ) ) ? 'true' : 'false',
			'data-autoplay-speed'   => max( 1000, (int) ( $settings['autoplay_speed'] ?? 5000 ) ),
			'data-pause-hover'      => ( 'yes' === ( $settings['pause_on_hover'] ?? 'yes' ) ) ? 'true' : 'false',
			'data-infinite'         => ( 'yes' === ( $settings['infinite_loop'] ?? 'yes' ) ) ? 'true' : 'false',
			'data-center-highlight' => ( 'yes' === ( $settings['center_highlight'] ?? 'yes' ) ) ? 'true' : 'false',
		];
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['testimonials'] ) ) {
			return;
		}

		$this->add_render_attribute( 'wrapper', 'class', 'auscorp-testimonials' );

		foreach ( $this->get_slider_data_attributes( $settings ) as $attr => $value ) {
			$this->add_render_attribute( 'wrapper', $attr, (string) $value );
		}

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php $this->render_quote_decoration( $settings ); ?>

			<div class="auscorp-testimonials__inner">
				<div class="auscorp-testimonials__viewport">
					<div class="auscorp-testimonials__track">
						<?php foreach ( $settings['testimonials'] as $index => $item ) : ?>
							<?php
							if ( empty( $item['content'] ) && empty( $item['author_name'] ) ) {
								continue;
							}

							$active_index = max( 0, (int) ( $settings['initial_active_index'] ?? 1 ) );
							$slide_class  = 'auscorp-testimonials__slide' . ( $index === $active_index ? ' is-active' : '' );
							?>
							<div class="<?php echo esc_attr( $slide_class ); ?>" data-index="<?php echo esc_attr( (string) $index ); ?>">
								<div class="auscorp-testimonials__card">
									<?php $this->render_stars( $item['rating'] ?? 5 ); ?>

									<?php if ( ! empty( $item['content'] ) ) : ?>
										<p class="auscorp-testimonials__content"><?php echo esc_html( $item['content'] ); ?></p>
									<?php endif; ?>

									<div class="auscorp-testimonials__author">
										<?php if ( ! empty( $item['author_name'] ) ) : ?>
											<div class="auscorp-testimonials__name"><?php echo esc_html( $item['author_name'] ); ?></div>
										<?php endif; ?>

										<?php if ( ! empty( $item['author_title'] ) ) : ?>
											<div class="auscorp-testimonials__title"><?php echo esc_html( $item['author_title'] ); ?></div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( 'yes' === ( $settings['show_arrows'] ?? 'yes' ) ) : ?>
					<div class="auscorp-testimonials__nav">
						<button type="button" class="auscorp-testimonials__arrow auscorp-testimonials__arrow--prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'hello-elementor-child' ); ?>">
							<i class="fas fa-arrow-left" aria-hidden="true"></i>
						</button>
						<button type="button" class="auscorp-testimonials__arrow auscorp-testimonials__arrow--next" aria-label="<?php esc_attr_e( 'Next testimonial', 'hello-elementor-child' ); ?>">
							<i class="fas fa-arrow-right" aria-hidden="true"></i>
						</button>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === ( $settings['show_dots'] ?? '' ) ) : ?>
					<div class="auscorp-testimonials__dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonial pagination', 'hello-elementor-child' ); ?>">
						<?php foreach ( $settings['testimonials'] as $index => $item ) : ?>
							<button
								type="button"
								class="auscorp-testimonials__dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
								data-slide="<?php echo esc_attr( (string) $index ); ?>"
								role="tab"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to testimonial %d', 'hello-elementor-child' ), $index + 1 ) ); ?>"
								aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
							></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
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
		if ( ! settings.testimonials || ! settings.testimonials.length ) {
			return;
		}

		var gap = settings.slide_gap && settings.slide_gap.size ? settings.slide_gap.size : 24;
		var wrapperAttrs = {
			class: 'auscorp-testimonials',
			'data-active-index': settings.initial_active_index || 0,
			'data-slides-desktop': settings.slides_per_view || 3,
			'data-slides-tablet': settings.slides_per_view_tablet || 2,
			'data-slides-mobile': settings.slides_per_view_mobile || 1,
			'data-gap': gap,
			'data-autoplay': settings.autoplay === 'yes' ? 'true' : 'false',
			'data-autoplay-speed': settings.autoplay_speed || 5000,
			'data-pause-hover': settings.pause_on_hover === 'yes' ? 'true' : 'false',
			'data-infinite': settings.infinite_loop === 'yes' ? 'true' : 'false',
			'data-center-highlight': settings.center_highlight === 'yes' ? 'true' : 'false'
		};

		function renderStars(rating) {
			rating = Math.max(0, Math.min(5, parseInt(rating, 10) || 0));
			if (!rating) {
				return '';
			}

			var html = '<div class="auscorp-testimonials__stars">';
			for (var i = 0; i < rating; i++) {
				html += '<i class="fas fa-star" aria-hidden="true"></i>';
			}
			html += '</div>';
			return html;
		}

		function renderQuote() {
			if ('yes' !== settings.show_quote_decoration) {
				return '';
			}

			var source = settings.quote_source || 'character';
			var quoteHTML = '';

			if ('custom_image' === source && settings.quote_custom_image && settings.quote_custom_image.url) {
				var quoteUrl = settings.quote_custom_image.url;
				var isSvg = /\.svg($|\?)/i.test(quoteUrl);

				if (isSvg) {
					quoteHTML = '<span class="auscorp-testimonials__quote-media" style="--quote-url: url(\'' + quoteUrl + '\');"></span>';
				} else {
					quoteHTML = '<img class="auscorp-testimonials__quote-img" src="' + quoteUrl + '" alt="" loading="lazy" />';
				}
			} else if ('elementor_icon' === source && settings.quote_icon && settings.quote_icon.value) {
				var iconHTML = elementor.helpers.renderIcon(view, settings.quote_icon, { 'aria-hidden': true }, 'i', 'object');
				quoteHTML = '<span class="auscorp-testimonials__quote-icon">' + iconHTML.value + '</span>';
			} else {
				quoteHTML = settings.quote_text || '“';
			}

			return '<div class="auscorp-testimonials__quote" aria-hidden="true">' + quoteHTML + '</div>';
		}
		#>
		<div
			class="{{ wrapperAttrs.class }}"
			data-active-index="{{ wrapperAttrs['data-active-index'] }}"
			data-slides-desktop="{{ wrapperAttrs['data-slides-desktop'] }}"
			data-slides-tablet="{{ wrapperAttrs['data-slides-tablet'] }}"
			data-slides-mobile="{{ wrapperAttrs['data-slides-mobile'] }}"
			data-gap="{{ wrapperAttrs['data-gap'] }}"
			data-autoplay="{{ wrapperAttrs['data-autoplay'] }}"
			data-autoplay-speed="{{ wrapperAttrs['data-autoplay-speed'] }}"
			data-pause-hover="{{ wrapperAttrs['data-pause-hover'] }}"
			data-infinite="{{ wrapperAttrs['data-infinite'] }}"
			data-center-highlight="{{ wrapperAttrs['data-center-highlight'] }}"
		>
			{{{ renderQuote() }}}

			<div class="auscorp-testimonials__inner">
				<div class="auscorp-testimonials__viewport">
					<div class="auscorp-testimonials__track">
						<# _.each( settings.testimonials, function(item, index) { #>
							<div class="auscorp-testimonials__slide" data-index="{{ index }}">
								<div class="auscorp-testimonials__card">
									{{{ renderStars(item.rating) }}}
									<# if ( item.content ) { #>
										<p class="auscorp-testimonials__content">{{{ item.content }}}</p>
									<# } #>
									<div class="auscorp-testimonials__author">
										<# if ( item.author_name ) { #>
											<div class="auscorp-testimonials__name">{{{ item.author_name }}}</div>
										<# } #>
										<# if ( item.author_title ) { #>
											<div class="auscorp-testimonials__title">{{{ item.author_title }}}</div>
										<# } #>
									</div>
								</div>
							</div>
						<# }); #>
					</div>
				</div>

				<# if ( 'yes' === settings.show_arrows ) { #>
					<div class="auscorp-testimonials__nav">
						<button type="button" class="auscorp-testimonials__arrow auscorp-testimonials__arrow--prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'hello-elementor-child' ); ?>">
							<i class="fas fa-arrow-left" aria-hidden="true"></i>
						</button>
						<button type="button" class="auscorp-testimonials__arrow auscorp-testimonials__arrow--next" aria-label="<?php esc_attr_e( 'Next testimonial', 'hello-elementor-child' ); ?>">
							<i class="fas fa-arrow-right" aria-hidden="true"></i>
						</button>
					</div>
				<# } #>

				<# if ( 'yes' === settings.show_dots ) { #>
					<div class="auscorp-testimonials__dots" role="tablist">
						<# _.each( settings.testimonials, function(item, index) { #>
							<button type="button" class="auscorp-testimonials__dot<# if ( 0 === index ) { #> is-active<# } #>" data-slide="{{ index }}" role="tab" aria-selected="{{ 0 === index ? 'true' : 'false' }}"></button>
						<# }); #>
					</div>
				<# } #>
			</div>
		</div>
		<?php
	}
}
