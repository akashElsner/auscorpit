<?php
/**
 * Auscorp Hero Slider Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hero slider widget with repeater slides, stats bar, and ticker.
 */
class Auscorp_Hero_Slider_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_hero_slider';
	}

	/**
	 * Widget title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Hero Slider', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slides';
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
		return [ 'hero', 'slider', 'banner', 'auscorp', 'carousel' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-hero-slider' ];
	}

	/**
	 * Script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'auscorp-hero-slider' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_slides_controls();
		$this->register_slider_settings_controls();
		$this->register_stats_controls();
		$this->register_ticker_controls();
		$this->register_style_controls();
		$this->register_responsive_style_controls();
	}

	/**
	 * Responsive layout & spacing style controls.
	 *
	 * @return void
	 */
	private function register_responsive_style_controls() {
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'container_max_width',
			[
				'label'      => esc_html__( 'Container Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 600, 'max' => 2000 ],
					'%'  => [ 'min' => 50, 'max' => 100 ],
				],
				'default'    => [
					'size' => 1400,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__container' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hero_min_height',
			[
				'label'      => esc_html__( 'Min Height', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 300, 'max' => 1200 ],
					'vh' => [ 'min' => 30, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'grid_columns',
			[
				'label'                => esc_html__( 'Columns Layout', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => '1fr 1fr',
				'tablet_default'       => '1fr',
				'mobile_default'       => '1fr',
				'options'              => [
					'1fr 1fr'   => esc_html__( 'Two Columns (50/50)', 'hello-elementor-child' ),
					'1.2fr 1fr' => esc_html__( 'Content Wide', 'hello-elementor-child' ),
					'1fr 1.2fr' => esc_html__( 'Image Wide', 'hello-elementor-child' ),
					'1fr'       => esc_html__( 'Single Column', 'hello-elementor-child' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__grid' => 'grid-template-columns: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'grid_gap',
			[
				'label'      => esc_html__( 'Columns Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 120 ],
				],
				'default'    => [
					'size' => 48,
					'unit' => 'px',
				],
				'tablet_default' => [
					'size' => 32,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'visual_order',
			[
				'label'                => esc_html__( 'Image Column Order', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => '0',
				'tablet_default'       => '-1',
				'mobile_default'       => '-1',
				'options'              => [
					'-1' => esc_html__( 'Above Content', 'hello-elementor-child' ),
					'0'  => esc_html__( 'Default (Right)', 'hello-elementor-child' ),
					'1'  => esc_html__( 'Below Content', 'hello-elementor-child' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__visual' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_align',
			[
				'label'     => esc_html__( 'Content Alignment', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__content' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_max_width',
			[
				'label'      => esc_html__( 'Hero Image Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 900 ],
					'%'  => [ 'min' => 30, 'max' => 100 ],
				],
				'default'    => [
					'size' => 100,
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__image' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_spacing',
			[
				'label' => esc_html__( 'Spacing', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'badge_margin',
			[
				'label'      => esc_html__( 'Badge Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 28, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__badge' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Title Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 24, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'description_max_width',
			[
				'label'      => esc_html__( 'Description Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 200, 'max' => 900 ],
					'%'  => [ 'min' => 50, 'max' => 100 ],
				],
				'default'    => [ 'size' => 520, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__description' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'description_margin',
			[
				'label'      => esc_html__( 'Description Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 32, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'actions_margin',
			[
				'label'      => esc_html__( 'Buttons Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 36, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__actions' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stats_margin_top',
			[
				'label'      => esc_html__( 'Stats Top Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 120 ] ],
				'default'    => [ 'size' => 56, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__stats' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stats_padding_top',
			[
				'label'      => esc_html__( 'Stats Top Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 40, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__stats' => 'padding-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dots_margin_top',
			[
				'label'      => esc_html__( 'Dots Top Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 40, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__dots' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_buttons',
			[
				'label' => esc_html__( 'Buttons', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .auscorp-hero__btn',
			]
		);

		$this->add_responsive_control(
			'buttons_gap',
			[
				'label'      => esc_html__( 'Buttons Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'default'    => [ 'size' => 14, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__actions' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'buttons_direction',
			[
				'label'                => esc_html__( 'Buttons Direction', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => 'row',
				'mobile_default'       => 'column',
				'options'              => [
					'row'    => esc_html__( 'Horizontal', 'hello-elementor-child' ),
					'column' => esc_html__( 'Vertical', 'hello-elementor-child' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__actions' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'buttons_align',
			[
				'label'     => esc_html__( 'Buttons Alignment', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-center',
					],
					'stretch'    => [
						'title' => esc_html__( 'Stretch', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__actions' => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__btn'     => 'align-self: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Button Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '16',
					'right'    => '28',
					'bottom'   => '16',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_full_width',
			[
				'label'                => esc_html__( 'Full Width Buttons', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => 'auto',
				'mobile_default'       => '100%',
				'options'              => [
					'auto' => esc_html__( 'Auto', 'hello-elementor-child' ),
					'100%' => esc_html__( 'Full Width', 'hello-elementor-child' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__btn' => 'width: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_features',
			[
				'label' => esc_html__( 'Features', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'feature_typography',
				'selector' => '{{WRAPPER}} .auscorp-hero__feature-text',
			]
		);

		$this->add_responsive_control(
			'features_gap',
			[
				'label'      => esc_html__( 'Features Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'default'    => [ 'size' => 20, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__features' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'features_direction',
			[
				'label'                => esc_html__( 'Features Direction', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => 'row',
				'mobile_default'       => 'column',
				'options'              => [
					'row'    => esc_html__( 'Horizontal', 'hello-elementor-child' ),
					'column' => esc_html__( 'Vertical', 'hello-elementor-child' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__features' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'features_align',
			[
				'label'     => esc_html__( 'Features Alignment', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'hello-elementor-child' ),
						'icon'  => 'eicon-text-align-center',
					],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__features' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'feature_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 14, 'max' => 40 ] ],
				'default'    => [ 'size' => 22, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__feature-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_navigation',
			[
				'label' => esc_html__( 'Slider Navigation', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'arrow_size',
			[
				'label'      => esc_html__( 'Arrow Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 28, 'max' => 64 ] ],
				'default'    => [ 'size' => 44, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_offset',
			[
				'label'      => esc_html__( 'Arrow Side Offset', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => -30, 'max' => 40 ] ],
				'default'    => [ 'size' => -12, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__arrow--prev' => 'left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-hero__arrow--next' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dot_size',
			[
				'label'      => esc_html__( 'Dot Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 6, 'max' => 20 ] ],
				'default'    => [ 'size' => 10, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Slide repeater controls.
	 *
	 * @return void
	 */
	private function register_slides_controls() {
		$this->start_controls_section(
			'section_slides',
			[
				'label' => esc_html__( 'Slides', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'badge_text',
			[
				'label'       => esc_html__( 'Trust Badge Text', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Trusted by 500+ Perth businesses', 'hello-elementor-child' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'show_badge',
			[
				'label'        => esc_html__( 'Show Badge', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater->add_control(
			'headline',
			[
				'label'       => esc_html__( 'Headline', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Office IT That Never Slows You Down.', 'hello-elementor-child' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'headline_highlight',
			[
				'label'       => esc_html__( 'Highlighted Words', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Never Slows', 'hello-elementor-child' ),
				'description' => esc_html__( 'Exact words from the headline to highlight in orange.', 'hello-elementor-child' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'From printer repairs to full IT supply — Auscorp IT keeps your office running with expert service, quality products and 25 years of hands-on experience.', 'hello-elementor-child' ),
				'rows'    => 4,
			]
		);

		$repeater->add_control(
			'primary_btn_heading',
			[
				'label'     => esc_html__( 'Primary Button', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'primary_btn_text',
			[
				'label'   => esc_html__( 'Text', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Book Free Printer Audit', 'hello-elementor-child' ),
			]
		);

		$repeater->add_control(
			'primary_btn_link',
			[
				'label' => esc_html__( 'Link', 'hello-elementor-child' ),
				'type'  => \Elementor\Controls_Manager::URL,
			]
		);

		$repeater->add_control(
			'primary_btn_icon',
			[
				'label'   => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-print',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'show_primary_btn',
			[
				'label'        => esc_html__( 'Show Primary Button', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater->add_control(
			'secondary_btn_heading',
			[
				'label'     => esc_html__( 'Secondary Button', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'secondary_btn_text',
			[
				'label'   => esc_html__( 'Text', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Explore Services', 'hello-elementor-child' ),
			]
		);

		$repeater->add_control(
			'secondary_btn_link',
			[
				'label' => esc_html__( 'Link', 'hello-elementor-child' ),
				'type'  => \Elementor\Controls_Manager::URL,
			]
		);

		$repeater->add_control(
			'secondary_btn_icon',
			[
				'label'   => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'show_secondary_btn',
			[
				'label'        => esc_html__( 'Show Secondary Button', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater->add_control(
			'features_heading',
			[
				'label'     => esc_html__( 'Feature List', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$features_repeater = new \Elementor\Repeater();

		$features_repeater->add_control(
			'feature_text',
			[
				'label'       => esc_html__( 'Feature Text', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Free loan printer', 'hello-elementor-child' ),
				'label_block' => true,
			]
		);

		$features_repeater->add_control(
			'feature_icon',
			[
				'label'   => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-check',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'features',
			[
				'label'       => esc_html__( 'Features', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $features_repeater->get_controls(),
				'default'     => [
					[ 'feature_text' => esc_html__( 'Free loan printer', 'hello-elementor-child' ) ],
					[ 'feature_text' => esc_html__( '48hr turnaround', 'hello-elementor-child' ) ],
					[ 'feature_text' => esc_html__( 'No lock-in contracts', 'hello-elementor-child' ) ],
				],
				'title_field' => '{{{ feature_text }}}',
			]
		);

		$repeater->add_control(
			'hero_image',
			[
				'label'     => esc_html__( 'Hero Image', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'slides',
			[
				'label'       => esc_html__( 'Slides', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'badge_text'         => esc_html__( 'Trusted by 500+ Perth businesses', 'hello-elementor-child' ),
						'headline'           => esc_html__( 'Office IT That Never Slows You Down.', 'hello-elementor-child' ),
						'headline_highlight' => esc_html__( 'Never Slows', 'hello-elementor-child' ),
					],
				],
				'title_field' => '{{{ headline }}}',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Slider settings controls.
	 *
	 * @return void
	 */
	private function register_slider_settings_controls() {
		$this->start_controls_section(
			'section_slider_settings',
			[
				'label' => esc_html__( 'Slider Settings', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'     => esc_html__( 'Autoplay Speed (ms)', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 6000,
				'min'       => 1000,
				'max'       => 20000,
				'step'      => 500,
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => esc_html__( 'Transition Speed (ms)', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 600,
				'min'     => 200,
				'max'     => 3000,
				'step'    => 100,
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
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Statistics controls.
	 *
	 * @return void
	 */
	private function register_stats_controls() {
		$this->start_controls_section(
			'section_stats',
			[
				'label' => esc_html__( 'Statistics', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_stats',
			[
				'label'        => esc_html__( 'Show Statistics Bar', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$stats_repeater = new \Elementor\Repeater();

		$stats_repeater->add_control(
			'stat_number',
			[
				'label'   => esc_html__( 'Number / Value', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '25+',
			]
		);

		$stats_repeater->add_control(
			'stat_label',
			[
				'label'   => esc_html__( 'Label', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Years in operation', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'stats',
			[
				'label'       => esc_html__( 'Statistics Items', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $stats_repeater->get_controls(),
				'default'     => [
					[
						'stat_number' => '25+',
						'stat_label'  => esc_html__( 'Years in operation', 'hello-elementor-child' ),
					],
					[
						'stat_number' => '500+',
						'stat_label'  => esc_html__( 'Business clients served', 'hello-elementor-child' ),
					],
					[
						'stat_number' => '48hr',
						'stat_label'  => esc_html__( 'Average repair turnaround', 'hello-elementor-child' ),
					],
					[
						'stat_number' => '100%',
						'stat_label'  => esc_html__( 'Loan printer guarantee', 'hello-elementor-child' ),
					],
				],
				'title_field' => '{{{ stat_number }}} — {{{ stat_label }}}',
				'condition'   => [
					'show_stats' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Ticker controls.
	 *
	 * @return void
	 */
	private function register_ticker_controls() {
		$this->start_controls_section(
			'section_ticker',
			[
				'label' => esc_html__( 'Ticker / Marquee', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_ticker',
			[
				'label'        => esc_html__( 'Show Ticker', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$ticker_repeater = new \Elementor\Repeater();

		$ticker_repeater->add_control(
			'ticker_text',
			[
				'label'       => esc_html__( 'Text', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'MANAGED IT SERVICES', 'hello-elementor-child' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'ticker_items',
			[
				'label'       => esc_html__( 'Ticker Items', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $ticker_repeater->get_controls(),
				'default'     => [
					[ 'ticker_text' => 'MANAGED IT SERVICES' ],
					[ 'ticker_text' => 'CLOUD INFRASTRUCTURE' ],
					[ 'ticker_text' => 'CYBERSECURITY' ],
					[ 'ticker_text' => 'NETWORK SOLUTIONS' ],
					[ 'ticker_text' => 'IT CONSULTING' ],
					[ 'ticker_text' => '24/7 SUPPORT' ],
					[ 'ticker_text' => 'DISASTER RECOVERY' ],
				],
				'title_field' => '{{{ ticker_text }}}',
				'condition'   => [
					'show_ticker' => 'yes',
				],
			]
		);

		$this->add_control(
			'ticker_speed',
			[
				'label'     => esc_html__( 'Scroll Speed (seconds)', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 30,
				'min'       => 5,
				'max'       => 120,
				'condition' => [
					'show_ticker' => 'yes',
				],
			]
		);

		$this->add_control(
			'ticker_separator',
			[
				'label'     => esc_html__( 'Separator', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '•',
				'condition' => [
					'show_ticker' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_hero',
			[
				'label' => esc_html__( 'Hero', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'background_color',
			[
				'label'     => esc_html__( 'Background Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0a1628',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'background_image',
			[
				'label' => esc_html__( 'Background Pattern Image', 'hello-elementor-child' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'background_video',
			[
				'label'       => esc_html__( 'Background Video', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => [ 'video' ],
				'description' => esc_html__( 'Upload MP4 or WebM. Video plays muted, looped, and autoplay in the background.', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'background_video_url',
			[
				'label'       => esc_html__( 'Background Video URL', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://example.com/video.mp4', 'hello-elementor-child' ),
				'description' => esc_html__( 'Optional external video URL (used if no video is uploaded above).', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'video_overlay_heading',
			[
				'label'     => esc_html__( 'Video Overlay', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'video_overlay_color',
			[
				'label'     => esc_html__( 'Overlay Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0a1628',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__video-overlay' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'video_overlay_opacity',
			[
				'label'      => esc_html__( 'Overlay Opacity', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'default'    => [
					'size' => 0.55,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__video-overlay' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'video_disable_mobile',
			[
				'label'        => esc_html__( 'Disable Video on Mobile', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Turn on only if you want to hide video below 768px and show background color/image instead.', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'accent_color',
			[
				'label'     => esc_html__( 'Accent Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__highlight' => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__badge-dot'   => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__btn--primary' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__feature-icon' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__dot.is-active' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-hero__arrow:hover' => 'border-color: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'hero_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '140',
					'right'    => '32',
					'bottom'   => '60',
					'left'     => '32',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'tablet_default' => [
					'top'      => '120',
					'right'    => '24',
					'bottom'   => '50',
					'left'     => '24',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'mobile_default' => [
					'top'      => '100',
					'right'    => '20',
					'bottom'   => '40',
					'left'     => '20',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_typography',
			[
				'label' => esc_html__( 'Typography', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'headline_typography',
				'label'    => esc_html__( 'Headline', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-hero__title',
			]
		);

		$this->add_control(
			'headline_color',
			[
				'label'     => esc_html__( 'Headline Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'label'    => esc_html__( 'Description', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-hero__description',
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => esc_html__( 'Description Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.85)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_typography',
				'label'    => esc_html__( 'Badge Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-hero__badge-text',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_stats',
			[
				'label'     => esc_html__( 'Statistics', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_stats' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'stats_columns',
			[
				'label'                => esc_html__( 'Columns', 'hello-elementor-child' ),
				'type'                 => \Elementor\Controls_Manager::SELECT,
				'default'              => 'repeat(4, 1fr)',
				'tablet_default'       => 'repeat(2, 1fr)',
				'mobile_default'       => 'repeat(2, 1fr)',
				'options'              => [
					'repeat(1, 1fr)' => '1',
					'repeat(2, 1fr)' => '2',
					'repeat(3, 1fr)' => '3',
					'repeat(4, 1fr)' => '4',
				],
				'selectors'            => [
					'{{WRAPPER}} .auscorp-hero__stats' => 'grid-template-columns: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'stats_gap',
			[
				'label'      => esc_html__( 'Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'default'    => [ 'size' => 0, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__stats' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'stats_number_typography',
				'label'    => esc_html__( 'Number Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-hero__stat-number',
			]
		);

		$this->add_control(
			'stats_number_color',
			[
				'label'     => esc_html__( 'Number Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__stat-number' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'stats_label_typography',
				'label'    => esc_html__( 'Label Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-hero__stat-label',
			]
		);

		$this->add_control(
			'stats_label_color',
			[
				'label'     => esc_html__( 'Label Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.7)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__stat-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_ticker',
			[
				'label'     => esc_html__( 'Ticker', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_ticker' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'ticker_typography',
				'selector' => '{{WRAPPER}} .auscorp-hero__ticker-item',
			]
		);

		$this->add_responsive_control(
			'ticker_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '14',
					'right'    => '0',
					'bottom'   => '14',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-hero__ticker' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ticker_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0d1f3c',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__ticker' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ticker_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.75)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-hero__ticker-item' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Highlight headline text.
	 *
	 * @param string $headline   Full headline.
	 * @param string $highlight  Words to highlight.
	 * @return string
	 */
	private function get_highlighted_headline( $headline, $highlight ) {
		if ( empty( $highlight ) || false === strpos( $headline, $highlight ) ) {
			return esc_html( $headline );
		}

		$parts = explode( $highlight, $headline, 2 );

		return esc_html( $parts[0] ) .
			'<span class="auscorp-hero__highlight">' . esc_html( $highlight ) . '</span>' .
			esc_html( $parts[1] );
	}

	/**
	 * Render button link attributes.
	 *
	 * @param array  $link Link settings.
	 * @param string $key  Unique attribute key.
	 * @return void
	 */
	private function render_link_attributes( $link, $key ) {
		if ( empty( $link['url'] ) ) {
			echo ' href="#"';
			return;
		}

		$this->add_link_attributes( $key, $link );
		echo ' ' . $this->get_render_attribute_string( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides   = ! empty( $settings['slides'] ) ? $settings['slides'] : [];

		if ( empty( $slides ) ) {
			return;
		}

		$widget_id = 'auscorp-hero-' . $this->get_id();
		$bg_image  = ! empty( $settings['background_image']['url'] ) ? $settings['background_image']['url'] : '';
		$bg_video  = ! empty( $settings['background_video']['url'] ) ? $settings['background_video']['url'] : '';
		if ( empty( $bg_video ) && ! empty( $settings['background_video_url']['url'] ) ) {
			$bg_video = $settings['background_video_url']['url'];
		}
		$has_video = ! empty( $bg_video );

		$hero_classes = 'auscorp-hero';
		if ( $has_video ) {
			$hero_classes .= ' auscorp-hero--has-video';
		}
		if ( $has_video && 'yes' === ( $settings['video_disable_mobile'] ?? '' ) ) {
			$hero_classes .= ' auscorp-hero--video-mobile-off';
		}

		$slider_attrs = [
			'data-autoplay'         => ( 'yes' === $settings['autoplay'] ) ? 'true' : 'false',
			'data-autoplay-speed'   => absint( $settings['autoplay_speed'] ),
			'data-transition-speed' => absint( $settings['transition_speed'] ),
			'data-pause-hover'      => ( 'yes' === $settings['pause_on_hover'] ) ? 'true' : 'false',
			'data-infinite'         => ( 'yes' === $settings['infinite_loop'] ) ? 'true' : 'false',
		];

		?>
		<section
			class="<?php echo esc_attr( $hero_classes ); ?>"
			id="<?php echo esc_attr( $widget_id ); ?>"
			<?php if ( $bg_image && ! $has_video ) : ?>
				style="background-image: url('<?php echo esc_url( $bg_image ); ?>');"
			<?php elseif ( $bg_image ) : ?>
				style="--auscorp-hero-fallback-image: url('<?php echo esc_url( $bg_image ); ?>');"
			<?php endif; ?>
		>
			<?php if ( $has_video ) : ?>
				<?php
				$video_bg_image    = get_stylesheet_directory_uri() . '/assets/images/hero-video-bg.png';
				$video_front_image = get_stylesheet_directory_uri() . '/assets/images/hero-video-front.png';
				?>
				<div class="auscorp-hero__video-wrap" aria-hidden="true">
					<div
						class="auscorp-hero__video-bg"
						style="background-image: url('<?php echo esc_url( $video_bg_image ); ?>');"
					></div>
					<video
						class="auscorp-hero__video"
						autoplay
						muted
						loop
						playsinline
						webkit-playsinline
						preload="auto"
						<?php if ( $bg_image ) : ?>
							poster="<?php echo esc_url( $bg_image ); ?>"
						<?php endif; ?>
					>
						<source src="<?php echo esc_url( $bg_video ); ?>" type="video/mp4">
					</video>
					<div
						class="auscorp-hero__video-mid"
						style="background-image: url('<?php echo esc_url( $video_front_image ); ?>');"
					></div>
					<div
						class="auscorp-hero__video-front"
						style="background-image: url('<?php echo esc_url( $video_front_image ); ?>');"
					></div>
					<div class="auscorp-hero__video-light"></div>
					<div class="auscorp-hero__video-overlay"></div>
				</div>
			<?php endif; ?>
			<div class="auscorp-hero__wave" aria-hidden="true"></div>

			<div class="auscorp-hero__container">
				<div class="auscorp-hero__slider" <?php foreach ( $slider_attrs as $attr => $value ) { printf( '%s="%s" ', esc_attr( $attr ), esc_attr( $value ) ); } ?>>
					<div class="auscorp-hero__slides">
						<?php foreach ( $slides as $index => $slide ) : ?>
							<div class="auscorp-hero__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-slide="<?php echo esc_attr( $index ); ?>">
								<div class="auscorp-hero__grid">
									<div class="auscorp-hero__content">
										<?php if ( 'yes' === $slide['show_badge'] && ! empty( $slide['badge_text'] ) ) : ?>
											<div class="auscorp-hero__badge">
												<span class="auscorp-hero__badge-dot" aria-hidden="true"></span>
												<span class="auscorp-hero__badge-text"><?php echo esc_html( $slide['badge_text'] ); ?></span>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $slide['headline'] ) ) : ?>
											<h1 class="auscorp-hero__title">
												<?php
												echo wp_kses_post(
													$this->get_highlighted_headline(
														$slide['headline'],
														$slide['headline_highlight'] ?? ''
													)
												);
												?>
											</h1>
										<?php endif; ?>

										<?php if ( ! empty( $slide['description'] ) ) : ?>
											<p class="auscorp-hero__description"><?php echo esc_html( $slide['description'] ); ?></p>
										<?php endif; ?>

										<div class="auscorp-hero__actions">
											<?php if ( 'yes' === $slide['show_primary_btn'] && ! empty( $slide['primary_btn_text'] ) ) : ?>
												<a class="auscorp-hero__btn auscorp-hero__btn--primary" <?php $this->render_link_attributes( $slide['primary_btn_link'] ?? [], 'primary-link-' . $index ); ?>>
													<?php if ( ! empty( $slide['primary_btn_icon']['value'] ) ) : ?>
														<span class="auscorp-hero__btn-icon auscorp-hero__btn-icon--left">
															<?php \Elementor\Icons_Manager::render_icon( $slide['primary_btn_icon'], [ 'aria-hidden' => 'true' ] ); ?>
														</span>
													<?php endif; ?>
													<span><?php echo esc_html( $slide['primary_btn_text'] ); ?></span>
												</a>
											<?php endif; ?>

											<?php if ( 'yes' === $slide['show_secondary_btn'] && ! empty( $slide['secondary_btn_text'] ) ) : ?>
												<a class="auscorp-hero__btn auscorp-hero__btn--secondary" <?php $this->render_link_attributes( $slide['secondary_btn_link'] ?? [], 'secondary-link-' . $index ); ?>>
													<span><?php echo esc_html( $slide['secondary_btn_text'] ); ?></span>
													<?php if ( ! empty( $slide['secondary_btn_icon']['value'] ) ) : ?>
														<span class="auscorp-hero__btn-icon auscorp-hero__btn-icon--right">
															<?php \Elementor\Icons_Manager::render_icon( $slide['secondary_btn_icon'], [ 'aria-hidden' => 'true' ] ); ?>
														</span>
													<?php endif; ?>
												</a>
											<?php endif; ?>
										</div>

										<?php if ( ! empty( $slide['features'] ) ) : ?>
											<ul class="auscorp-hero__features">
												<?php foreach ( $slide['features'] as $feature ) : ?>
													<?php if ( empty( $feature['feature_text'] ) ) { continue; } ?>
													<li class="auscorp-hero__feature">
														<span class="auscorp-hero__feature-icon">
															<?php
															if ( ! empty( $feature['feature_icon']['value'] ) ) {
																\Elementor\Icons_Manager::render_icon( $feature['feature_icon'], [ 'aria-hidden' => 'true' ] );
															}
															?>
														</span>
														<span class="auscorp-hero__feature-text"><?php echo esc_html( $feature['feature_text'] ); ?></span>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>

									<div class="auscorp-hero__visual">
										<?php if ( ! empty( $slide['hero_image']['url'] ) ) : ?>
											<img
												class="auscorp-hero__image"
												src="<?php echo esc_url( $slide['hero_image']['url'] ); ?>"
												alt="<?php echo esc_attr( $slide['headline'] ?? '' ); ?>"
												loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
											/>
										<?php else : ?>
											<div class="auscorp-hero__image-placeholder">
												<span><?php esc_html_e( 'Upload hero image', 'hello-elementor-child' ); ?></span>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( 'yes' === $settings['show_arrows'] && count( $slides ) > 1 ) : ?>
						<button type="button" class="auscorp-hero__arrow auscorp-hero__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'hello-elementor-child' ); ?>">
							<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
						</button>
						<button type="button" class="auscorp-hero__arrow auscorp-hero__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'hello-elementor-child' ); ?>">
							<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
						</button>
					<?php endif; ?>

					<?php if ( 'yes' === $settings['show_dots'] && count( $slides ) > 1 ) : ?>
						<div class="auscorp-hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Hero slides', 'hello-elementor-child' ); ?>">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<button
									type="button"
									class="auscorp-hero__dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
									data-slide="<?php echo esc_attr( $index ); ?>"
									role="tab"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'hello-elementor-child' ), $index + 1 ) ); ?>"
									aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( 'yes' === $settings['show_stats'] && ! empty( $settings['stats'] ) ) : ?>
					<div class="auscorp-hero__stats">
						<?php foreach ( $settings['stats'] as $stat ) : ?>
							<?php if ( empty( $stat['stat_number'] ) && empty( $stat['stat_label'] ) ) { continue; } ?>
							<div class="auscorp-hero__stat">
								<?php if ( ! empty( $stat['stat_number'] ) ) : ?>
									<div class="auscorp-hero__stat-number"><?php echo esc_html( $stat['stat_number'] ); ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $stat['stat_label'] ) ) : ?>
									<div class="auscorp-hero__stat-label"><?php echo esc_html( $stat['stat_label'] ); ?></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( 'yes' === $settings['show_ticker'] && ! empty( $settings['ticker_items'] ) ) : ?>
				<div class="auscorp-hero__ticker" style="--auscorp-ticker-speed: <?php echo esc_attr( absint( $settings['ticker_speed'] ) ); ?>s;">
					<div class="auscorp-hero__ticker-track">
						<?php for ( $loop = 0; $loop < 2; $loop++ ) : ?>
							<div class="auscorp-hero__ticker-group" <?php echo 1 === $loop ? 'aria-hidden="true"' : ''; ?>>
								<?php foreach ( $settings['ticker_items'] as $item ) : ?>
									<?php if ( empty( $item['ticker_text'] ) ) { continue; } ?>
									<span class="auscorp-hero__ticker-item"><?php echo esc_html( $item['ticker_text'] ); ?></span>
									<span class="auscorp-hero__ticker-sep"><?php echo esc_html( $settings['ticker_separator'] ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endfor; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
