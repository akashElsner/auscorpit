<?php
/**
 * Auscorp Services Grid Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service cards grid with icon upload and hover states.
 */
class Auscorp_Services_Grid_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_services_grid';
	}

	/**
	 * Widget title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Services Grid', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
		return [ 'services', 'grid', 'cards', 'auscorp', 'icon' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-services-grid' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_services_controls();
		$this->register_card_content_controls();
		$this->register_icon_settings_controls();
		$this->register_card_style_controls();
		$this->register_layout_controls();
	}

	/**
	 * Services repeater controls.
	 *
	 * @return void
	 */
	private function register_services_controls() {
		$this->start_controls_section(
			'section_services',
			[
				'label' => esc_html__( 'Services', 'hello-elementor-child' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'icon_heading',
			[
				'label' => esc_html__( 'Card Icon', 'hello-elementor-child' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			]
		);

		$repeater->add_control(
			'show_icon',
			[
				'label'        => esc_html__( 'Show Icon', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater->add_control(
			'icon_source',
			[
				'label'   => esc_html__( 'Icon Source', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'elementor_icon',
				'options' => [
					'elementor_icon' => esc_html__( 'Icon / SVG Library', 'hello-elementor-child' ),
					'custom_image'   => esc_html__( 'Upload Image / SVG', 'hello-elementor-child' ),
				],
			]
		);

		$repeater->add_control(
			'service_icon',
			[
				'label'     => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-server',
					'library' => 'fa-solid',
				],
				'condition' => [
					'icon_source' => 'elementor_icon',
					'show_icon'   => 'yes',
				],
			]
		);

		$repeater->add_control(
			'custom_icon',
			[
				'label'       => esc_html__( 'Upload Icon', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => [ 'image', 'svg' ],
				'description' => esc_html__( 'SVG icons change color on hover. PNG/JPG icons invert to white on hover.', 'hello-elementor-child' ),
				'condition'   => [
					'icon_source' => 'custom_image',
					'show_icon'   => 'yes',
				],
			]
		);

		$repeater->add_control(
			'show_number',
			[
				'label'        => esc_html__( 'Show Index Number', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater->add_control(
			'card_number',
			[
				'label'       => esc_html__( 'Index Number', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '01',
				'description' => esc_html__( 'Leave empty to auto-number (01, 02, 03…).', 'hello-elementor-child' ),
				'condition'   => [
					'show_number' => 'yes',
				],
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Managed IT Services', 'hello-elementor-child' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Proactive monitoring, maintenance, and support to keep your systems running smoothly around the clock.', 'hello-elementor-child' ),
				'rows'    => 4,
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'link_text',
			[
				'label'   => esc_html__( 'Link Text', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Learn More', 'hello-elementor-child' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Link', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'hello-elementor-child' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'show_link',
			[
				'label'        => esc_html__( 'Show Link', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'services',
			[
				'label'       => esc_html__( 'Service Cards', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'title'       => esc_html__( 'Managed IT Services', 'hello-elementor-child' ),
						'description' => esc_html__( 'Proactive monitoring, maintenance, and support to keep your systems running smoothly around the clock.', 'hello-elementor-child' ),
						'service_icon' => [
							'value'   => 'fas fa-server',
							'library' => 'fa-solid',
						],
						'card_number' => '01',
					],
					[
						'title'       => esc_html__( 'Cloud Infrastructure', 'hello-elementor-child' ),
						'description' => esc_html__( 'Scalable cloud solutions that grow with your business — secure, reliable, and cost-effective.', 'hello-elementor-child' ),
						'service_icon' => [
							'value'   => 'fas fa-cloud',
							'library' => 'fa-solid',
						],
						'card_number' => '02',
					],
					[
						'title'       => esc_html__( 'Cyber Security', 'hello-elementor-child' ),
						'description' => esc_html__( 'Multi-layered protection against threats with advanced security tools and expert monitoring.', 'hello-elementor-child' ),
						'service_icon' => [
							'value'   => 'fas fa-shield-halved',
							'library' => 'fa-solid',
						],
						'card_number' => '03',
					],
					[
						'title'       => esc_html__( 'IT Support', 'hello-elementor-child' ),
						'description' => esc_html__( 'Fast, friendly technical support when you need it — on-site or remote, we have you covered.', 'hello-elementor-child' ),
						'service_icon' => [
							'value'   => 'fas fa-headset',
							'library' => 'fa-solid',
						],
						'card_number' => '04',
					],
				],
				'title_field' => '{{{ title }}}',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Card content layout controls.
	 *
	 * @return void
	 */
	private function register_card_content_controls() {
		$this->start_controls_section(
			'section_card_content',
			[
				'label' => esc_html__( 'Card Layout', 'hello-elementor-child' ),
			]
		);

		$this->add_responsive_control(
			'card_content_align',
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
					'{{WRAPPER}} .auscorp-services-card' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'align-items: flex-start; text-align: left;',
					'center' => 'align-items: center; text-align: center;',
					'right'  => 'align-items: flex-end; text-align: right;',
				],
			]
		);

		$this->add_control(
			'link_arrow_change',
			[
				'label'        => esc_html__( 'Change Arrow on Card Hover', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'link_arrow_normal',
			[
				'label'     => esc_html__( 'Normal Arrow Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				],
				'condition' => [
					'link_arrow_change' => 'yes',
				],
			]
		);

		$this->add_control(
			'link_arrow_hover',
			[
				'label'     => esc_html__( 'Hover Arrow Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-arrow-up-right',
					'library' => 'fa-solid',
				],
				'condition' => [
					'link_arrow_change' => 'yes',
				],
			]
		);

		$this->add_control(
			'link_arrow_static',
			[
				'label'     => esc_html__( 'Link Arrow Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				],
				'condition' => [
					'link_arrow_change!' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'link_arrow_icon_size',
			[
				'label'      => esc_html__( 'Arrow Icon Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 40 ],
					'em' => [ 'min' => 0.5, 'max' => 2.5 ],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__link-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-services-card__link-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'index_number_heading',
			[
				'label'     => esc_html__( 'Index Number', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_index_numbers',
			[
				'label'        => esc_html__( 'Show Index Numbers', 'hello-elementor-child' ),
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
	 * Icon selection and styling controls.
	 *
	 * @return void
	 */
	private function register_icon_settings_controls() {
		$this->start_controls_section(
			'section_icon_settings',
			[
				'label' => esc_html__( 'Icon Settings', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'icon_settings_note',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Pick a different icon per card under Services → each service item. Use the options below to style all card icons.', 'hello-elementor-child' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'use_same_icon',
			[
				'label'        => esc_html__( 'Use Same Icon for All Cards', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'global_icon_source',
			[
				'label'     => esc_html__( 'Icon Source', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'elementor_icon',
				'options'   => [
					'elementor_icon' => esc_html__( 'Icon / SVG Library', 'hello-elementor-child' ),
					'custom_image'   => esc_html__( 'Upload Image / SVG', 'hello-elementor-child' ),
				],
				'condition' => [
					'use_same_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'global_service_icon',
			[
				'label'     => esc_html__( 'Select Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-desktop',
					'library' => 'fa-solid',
				],
				'condition' => [
					'use_same_icon'   => 'yes',
					'global_icon_source' => 'elementor_icon',
				],
			]
		);

		$this->add_control(
			'global_custom_icon',
			[
				'label'       => esc_html__( 'Upload Icon', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => [ 'image', 'svg' ],
				'description' => esc_html__( 'SVG icons change color on hover. PNG/JPG icons invert to white on hover.', 'hello-elementor-child' ),
				'condition'   => [
					'use_same_icon'      => 'yes',
					'global_icon_source' => 'custom_image',
				],
			]
		);

		$this->add_responsive_control(
			'icon_box_size',
			[
				'label'      => esc_html__( 'Icon Box Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 32, 'max' => 96 ],
				],
				'default'    => [
					'size' => 58,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__( 'Icon / SVG Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 12, 'max' => 56 ],
				],
				'default'    => [
					'size' => 28,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-services-card__icon-media' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-services-card__icon-img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .auscorp-services-card__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_box_radius',
			[
				'label'      => esc_html__( 'Icon Box Radius', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [
					'size' => 12,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_spacing',
			[
				'label'      => esc_html__( 'Icon Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'    => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_align',
			[
				'label'     => esc_html__( 'Icon Alignment', 'hello-elementor-child' ),
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
					'{{WRAPPER}} .auscorp-services-card__icon' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'align-self: flex-start;',
					'center' => 'align-self: center;',
					'right'  => 'align-self: flex-end;',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_icon_style' );

		$this->start_controls_tab(
			'tab_icon_normal',
			[
				'label' => esc_html__( 'Normal', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'icon_bg_color',
			[
				'label'     => esc_html__( 'Icon Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fff0e6',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => esc_html__( 'Icon / SVG Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_border_color',
			[
				'label'     => esc_html__( 'Icon Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_border_width',
			[
				'label'      => esc_html__( 'Icon Border Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 6 ],
				],
				'default'    => [
					'size' => 1,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__icon' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_icon_hover',
			[
				'label' => esc_html__( 'Hover', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'icon_hover_bg_color',
			[
				'label'     => esc_html__( 'Icon Background', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.12)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__icon' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_hover_color',
			[
				'label'     => esc_html__( 'Icon / SVG Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_hover_border_color',
			[
				'label'     => esc_html__( 'Icon Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.25)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__icon' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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
				'label' => esc_html__( 'Service Card', 'hello-elementor-child' ),
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
					'{{WRAPPER}} .auscorp-services-card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-services-card',
			]
		);

		$this->add_control(
			'number_color',
			[
				'label'     => esc_html__( 'Index Number Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 122, 33, 0.18)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__number' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_title_color',
			[
				'label'     => esc_html__( 'Title Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_desc_color',
			[
				'label'     => esc_html__( 'Description Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b7280',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__desc' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_link_color',
			[
				'label'     => esc_html__( 'Link Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card__link' => 'color: {{VALUE}};',
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
				'default'   => '#0d2d5e',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .auscorp-services-card:hover',
			]
		);

		$this->add_control(
			'card_hover_lift',
			[
				'label'     => esc_html__( 'Hover Lift', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off' => esc_html__( 'No', 'hello-elementor-child' ),
				'default'   => 'yes',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover' => 'transform: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => 'translateY(-4px)',
					''    => 'none',
				],
			]
		);

		$this->add_control(
			'number_hover_color',
			[
				'label'     => esc_html__( 'Index Number Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.18)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__number' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_title_hover_color',
			[
				'label'     => esc_html__( 'Title Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_desc_hover_color',
			[
				'label'     => esc_html__( 'Description Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.82)',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__desc' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_link_hover_color',
			[
				'label'     => esc_html__( 'Link Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-services-card:hover .auscorp-services-card__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => '32',
					'right'    => '28',
					'bottom'   => '28',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'card_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '16',
					'right'    => '16',
					'bottom'   => '16',
					'left'     => '16',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'card_title_typography',
				'label'    => esc_html__( 'Title Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-services-card__title',
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'card_desc_typography',
				'label'    => esc_html__( 'Description Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-services-card__desc',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'card_link_typography',
				'label'    => esc_html__( 'Link Typography', 'hello-elementor-child' ),
				'selector' => '{{WRAPPER}} .auscorp-services-card__link',
			]
		);

		$this->add_responsive_control(
			'number_font_size',
			[
				'label'      => esc_html__( 'Index Number Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 24, 'max' => 120 ],
				],
				'default'    => [
					'size' => 72,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__number' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'number_position',
			[
				'label'      => esc_html__( 'Index Number Position', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '16',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__number' => 'top: {{TOP}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'number_side_offset',
			[
				'label'      => esc_html__( 'Index Side Offset', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 80 ],
				],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services-card__number--right' => 'right: {{SIZE}}{{UNIT}}; left: auto;',
					'{{WRAPPER}} .auscorp-services-card__number--left'  => 'left: {{SIZE}}{{UNIT}}; right: auto;',
				],
			]
		);

		$this->add_responsive_control(
			'number_align',
			[
				'label'     => esc_html__( 'Index Number Side', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'right' => [
						'title' => esc_html__( 'Top Right', 'hello-elementor-child' ),
						'icon'  => 'eicon-h-align-right',
					],
					'left'  => [
						'title' => esc_html__( 'Top Left', 'hello-elementor-child' ),
						'icon'  => 'eicon-h-align-left',
					],
				],
				'default'   => 'right',
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
			'section_style_layout',
			[
				'label' => esc_html__( 'Grid Layout', 'hello-elementor-child' ),
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
				],
				'selectors'      => [
					'{{WRAPPER}} .auscorp-services__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
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
				'tablet_default' => [
					'size' => 20,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-services__grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Format card index number.
	 *
	 * @param string $number  Manual number.
	 * @param int    $index   Card index.
	 * @return string
	 */
	private function get_card_number( $number, $index ) {
		if ( ! empty( $number ) ) {
			return esc_html( $number );
		}

		return esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) );
	}

	/**
	 * Whether index number should render for a card.
	 *
	 * @param array $item     Service item settings.
	 * @param array $settings Widget settings.
	 * @return bool
	 */
	private function should_show_number( $item, $settings ) {
		if ( 'yes' !== ( $settings['show_index_numbers'] ?? 'yes' ) ) {
			return false;
		}

		return 'yes' === ( $item['show_number'] ?? 'yes' );
	}

	/**
	 * Index number horizontal side.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function get_number_side( $settings ) {
		$side = $settings['number_align'] ?? 'right';

		return in_array( $side, [ 'left', 'right' ], true ) ? $side : 'right';
	}

	/**
	 * Check if media item is SVG.
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
	 * Render service card icon.
	 *
	 * @param array $item Service item settings.
	 * @return void
	 */
	private function render_card_icon( $item ) {
		$source = $item['icon_source'] ?? 'elementor_icon';

		echo '<div class="auscorp-services-card__icon">';

		if ( 'custom_image' === $source && ! empty( $item['custom_icon']['url'] ) ) {
			$url = $item['custom_icon']['url'];

			if ( $this->is_svg_media( $item['custom_icon'] ) ) {
				printf(
					'<span class="auscorp-services-card__icon-media" style="--icon-url: url(%s);" aria-hidden="true"></span>',
					esc_url( $url )
				);
			} else {
				printf(
					'<img class="auscorp-services-card__icon-img" src="%1$s" alt="%2$s" loading="lazy" />',
					esc_url( $url ),
					esc_attr( $item['title'] ?? '' )
				);
			}
		} elseif ( ! empty( $item['service_icon']['value'] ) ) {
			\Elementor\Icons_Manager::render_icon(
				$item['service_icon'],
				[
					'aria-hidden' => 'true',
				]
			);
		}

		echo '</div>';
	}

	/**
	 * Default link arrow icon settings.
	 *
	 * @param string $type Icon type: normal, hover, static.
	 * @return array
	 */
	private function get_default_link_arrow_icon( $type ) {
		$icons = [
			'normal' => [
				'value'   => 'fas fa-arrow-right',
				'library' => 'fa-solid',
			],
			'hover'  => [
				'value'   => 'fas fa-arrow-up-right',
				'library' => 'fa-solid',
			],
			'static' => [
				'value'   => 'fas fa-arrow-right',
				'library' => 'fa-solid',
			],
		];

		return $icons[ $type ] ?? $icons['normal'];
	}

	/**
	 * Render a link arrow icon.
	 *
	 * @param array  $icon  Icon settings.
	 * @param string $class CSS class.
	 * @return void
	 */
	private function render_link_arrow_icon( $icon, $class ) {
		if ( empty( $icon['value'] ) ) {
			return;
		}

		echo '<span class="auscorp-services-card__link-icon ' . esc_attr( $class ) . '" aria-hidden="true">';
		\Elementor\Icons_Manager::render_icon(
			$icon,
			[
				'aria-hidden' => 'true',
			]
		);
		echo '</span>';
	}

	/**
	 * Render card link arrows.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_card_link_arrows( $settings ) {
		if ( 'yes' === ( $settings['link_arrow_change'] ?? 'yes' ) ) {
			$normal = ! empty( $settings['link_arrow_normal']['value'] )
				? $settings['link_arrow_normal']
				: $this->get_default_link_arrow_icon( 'normal' );
			$hover  = ! empty( $settings['link_arrow_hover']['value'] )
				? $settings['link_arrow_hover']
				: $this->get_default_link_arrow_icon( 'hover' );

			$this->render_link_arrow_icon( $normal, 'auscorp-services-card__link-icon--normal' );
			$this->render_link_arrow_icon( $hover, 'auscorp-services-card__link-icon--hover' );
			return;
		}

		$static = ! empty( $settings['link_arrow_static']['value'] )
			? $settings['link_arrow_static']
			: $this->get_default_link_arrow_icon( 'static' );

		$this->render_link_arrow_icon( $static, 'auscorp-services-card__link-icon--single' );
	}

	/**
	 * Card CSS class list.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function get_card_class( $settings ) {
		$classes = [ 'auscorp-services-card' ];

		if ( 'yes' === ( $settings['link_arrow_change'] ?? 'yes' ) ) {
			$classes[] = 'auscorp-services-card--arrow-hover';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Render card link.
	 *
	 * @param array $item     Service item settings.
	 * @param int   $index    Card index.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_card_link( $item, $index, $settings ) {
		if ( 'yes' !== ( $item['show_link'] ?? 'yes' ) || empty( $item['link_text'] ) ) {
			return;
		}

		$link_key = 'service-link-' . $index;

		if ( ! empty( $item['link']['url'] ) ) {
			$this->add_link_attributes( $link_key, $item['link'] );
		} else {
			$this->add_render_attribute( $link_key, 'href', '#' );
		}

		$this->add_render_attribute( $link_key, 'class', 'auscorp-services-card__link' );

		?>
		<a <?php $this->print_render_attribute_string( $link_key ); ?>>
			<span class="auscorp-services-card__link-text"><?php echo esc_html( $item['link_text'] ); ?></span>
			<?php $this->render_card_link_arrows( $settings ); ?>
		</a>
		<?php
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$services = ! empty( $settings['services'] ) ? $settings['services'] : [];

		if ( empty( $services ) ) {
			return;
		}

		$card_class = $this->get_card_class( $settings );
		$number_side = $this->get_number_side( $settings );

		?>
		<section class="auscorp-services">
			<div class="auscorp-services__grid">
				<?php foreach ( $services as $index => $item ) : ?>
					<article class="<?php echo esc_attr( $card_class ); ?>">
						<?php if ( $this->should_show_number( $item, $settings ) ) : ?>
							<span class="auscorp-services-card__number auscorp-services-card__number--<?php echo esc_attr( $number_side ); ?>">
								<?php echo $this->get_card_number( $item['card_number'] ?? '', $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						<?php endif; ?>

						<?php $this->render_card_icon( $item ); ?>

						<?php if ( ! empty( $item['title'] ) ) : ?>
							<h3 class="auscorp-services-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $item['description'] ) ) : ?>
							<p class="auscorp-services-card__desc"><?php echo esc_html( $item['description'] ); ?></p>
						<?php endif; ?>

						<?php $this->render_card_link( $item, $index, $settings ); ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
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
		if ( ! settings.services || ! settings.services.length ) {
			return;
		}

		var cardClass = 'auscorp-services-card';
		if ( 'yes' === settings.link_arrow_change ) {
			cardClass += ' auscorp-services-card--arrow-hover';
		}

		var defaultNormalIcon = { value: 'fas fa-arrow-right', library: 'fa-solid' };
		var defaultHoverIcon = { value: 'fas fa-arrow-up-right', library: 'fa-solid' };
		var defaultStaticIcon = { value: 'fas fa-arrow-right', library: 'fa-solid' };
		var numberSide = settings.number_align || 'right';
		if ( 'left' !== numberSide && 'right' !== numberSide ) {
			numberSide = 'right';
		}
		#>
		<section class="auscorp-services">
			<div class="auscorp-services__grid">
				<# _.each( settings.services, function( item, index ) {
					var cardNumber = item.card_number;
					if ( ! cardNumber ) {
						cardNumber = String( index + 1 ).padStart( 2, '0' );
					}
					var iconHTML = '';
					if ( 'custom_image' === item.icon_source && item.custom_icon && item.custom_icon.url ) {
						var iconUrl = item.custom_icon.url;
						var isSvg = /\.svg($|\?)/i.test( iconUrl );
						if ( isSvg ) {
							iconHTML = '<span class="auscorp-services-card__icon-media" style="--icon-url: url(\'' + iconUrl + '\');" aria-hidden="true"></span>';
						} else {
							iconHTML = '<img class="auscorp-services-card__icon-img" src="' + iconUrl + '" alt="" loading="lazy" />';
						}
					} else if ( item.service_icon && item.service_icon.value ) {
						iconHTML = elementor.helpers.renderIcon( view, item.service_icon, { 'aria-hidden': true }, 'i', 'object' ).value;
					}

					var arrowHTML = '';
					if ( 'yes' === settings.link_arrow_change ) {
						var normalIcon = settings.link_arrow_normal && settings.link_arrow_normal.value ? settings.link_arrow_normal : defaultNormalIcon;
						var hoverIcon = settings.link_arrow_hover && settings.link_arrow_hover.value ? settings.link_arrow_hover : defaultHoverIcon;
						var normalIconHTML = elementor.helpers.renderIcon( view, normalIcon, { 'aria-hidden': true }, 'i', 'object' );
						var hoverIconHTML = elementor.helpers.renderIcon( view, hoverIcon, { 'aria-hidden': true }, 'i', 'object' );
						arrowHTML = '<span class="auscorp-services-card__link-icon auscorp-services-card__link-icon--normal" aria-hidden="true">' + normalIconHTML.value + '</span>' +
							'<span class="auscorp-services-card__link-icon auscorp-services-card__link-icon--hover" aria-hidden="true">' + hoverIconHTML.value + '</span>';
					} else {
						var staticIcon = settings.link_arrow_static && settings.link_arrow_static.value ? settings.link_arrow_static : defaultStaticIcon;
						var staticIconHTML = elementor.helpers.renderIcon( view, staticIcon, { 'aria-hidden': true }, 'i', 'object' );
						arrowHTML = '<span class="auscorp-services-card__link-icon auscorp-services-card__link-icon--single" aria-hidden="true">' + staticIconHTML.value + '</span>';
					}
				#>
					<article class="{{ cardClass }}">
						<# if ( 'yes' === settings.show_index_numbers && 'yes' === item.show_number ) { #>
							<span class="auscorp-services-card__number auscorp-services-card__number--{{ numberSide }}">{{{ cardNumber }}}</span>
						<# } #>

						<# if ( iconHTML ) { #>
							<div class="auscorp-services-card__icon">{{{ iconHTML }}}</div>
						<# } #>

						<# if ( item.title ) { #>
							<h3 class="auscorp-services-card__title">{{{ item.title }}}</h3>
						<# } #>

						<# if ( item.description ) { #>
							<p class="auscorp-services-card__desc">{{{ item.description }}}</p>
						<# } #>

						<# if ( 'yes' === item.show_link && item.link_text ) { #>
							<a class="auscorp-services-card__link" href="{{ item.link && item.link.url ? item.link.url : '#' }}">
								<span class="auscorp-services-card__link-text">{{{ item.link_text }}}</span>
								{{{ arrowHTML }}}
							</a>
						<# } #>
					</article>
				<# } ); #>
			</div>
		</section>
		<?php
	}
}
