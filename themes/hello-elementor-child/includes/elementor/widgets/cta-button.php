<?php
/**
 * Auscorp CTA Button Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standalone CTA button with icon placement and responsive options.
 */
class Auscorp_CTA_Button_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_cta_button';
	}

	/**
	 * Widget title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp CTA Button', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-button';
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
		return [ 'button', 'cta', 'link', 'auscorp', 'icon' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-cta-button' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
		$this->register_responsive_controls();
	}

	/**
	 * Content tab controls.
	 *
	 * @return void
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Button', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'   => esc_html__( 'Text', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Book Free Printer Audit', 'hello-elementor-child' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'button_link',
			[
				'label'       => esc_html__( 'Link', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'hello-elementor-child' ),
				'default'     => [
					'url' => '#',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'icon_position',
			[
				'label'   => esc_html__( 'Icon Position', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'before',
				'options' => [
					'none'   => esc_html__( 'No Icon', 'hello-elementor-child' ),
					'before' => esc_html__( 'Before Text', 'hello-elementor-child' ),
					'after'  => esc_html__( 'After Text', 'hello-elementor-child' ),
				],
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'     => esc_html__( 'Icon', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-clipboard-list',
					'library' => 'fa-solid',
				],
				'condition' => [
					'icon_position!' => 'none',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Button', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_type',
			[
				'label'        => esc_html__( 'Type', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SELECT,
				'default'      => 'filled',
				'options'      => [
					'filled'  => esc_html__( 'Filled', 'hello-elementor-child' ),
					'outline' => esc_html__( 'Outline', 'hello-elementor-child' ),
					'simple'  => esc_html__( 'Simple', 'hello-elementor-child' ),
				],
				'prefix_class' => 'auscorp-cta-btn-type-',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .auscorp-cta-btn',
			]
		);

		$this->start_controls_tabs( 'tabs_button_colors' );

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
					'{{WRAPPER}} .auscorp-cta-btn' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'filled',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'button_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-cta-btn',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#ff7a21',
					],
				],
				'condition'      => [
					'button_type' => 'filled',
				],
			]
		);

		$this->add_control(
			'outline_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'outline',
				],
			]
		);

		$this->add_control(
			'simple_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'simple',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'button_box_shadow',
				'selector'  => '{{WRAPPER}} .auscorp-cta-btn',
				'condition' => [
					'button_type!' => 'simple',
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
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'filled',
				],
			]
		);

		$this->add_control(
			'outline_hover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'outline',
				],
			]
		);

		$this->add_control(
			'simple_hover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e86a15',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type' => 'simple',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'button_hover_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#e86a15',
					],
				],
				'condition'      => [
					'button_type' => 'filled',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'outline_hover_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'      => [
						'default' => '#ff7a21',
					],
				],
				'condition'      => [
					'button_type' => 'outline',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e86a15',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'border-color: {{VALUE}} !important;',
				],
				'condition' => [
					'button_type!' => 'simple',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'button_hover_box_shadow',
				'selector'  => '{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus',
				'condition' => [
					'button_type!' => 'simple',
				],
			]
		);

		$this->add_control(
			'button_hover_transform',
			[
				'label'     => esc_html__( 'Hover Lift', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off' => esc_html__( 'No', 'hello-elementor-child' ),
				'default'   => 'yes',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'transform: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => 'translateY(-2px)',
					''    => 'none',
				],
				'condition' => [
					'button_type!' => 'simple',
				],
			]
		);

		$this->add_control(
			'simple_hover_underline',
			[
				'label'     => esc_html__( 'Underline on Hover', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off' => esc_html__( 'No', 'hello-elementor-child' ),
				'default'   => 'yes',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn:hover, {{WRAPPER}} .auscorp-cta-btn:focus' => 'text-decoration: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => 'underline',
					''    => 'none',
				],
				'condition' => [
					'button_type' => 'simple',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'           => 'button_border',
				'selector'       => '{{WRAPPER}} .auscorp-cta-btn',
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
						'default' => '#ff7a21',
					],
				],
				'condition'      => [
					'button_type!' => 'simple',
				],
				'separator'      => 'before',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '16',
					'right'    => '28',
					'bottom'   => '16',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => esc_html__( 'Margin', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn__wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '100',
					'right'    => '100',
					'bottom'   => '100',
					'left'     => '100',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_gap',
			[
				'label'      => esc_html__( 'Icon Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [
					'size' => 10,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'icon_position!' => 'none',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 60 ],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'icon_position!' => 'none',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Responsive layout controls.
	 *
	 * @return void
	 */
	private function register_responsive_controls() {
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_align',
			[
				'label'     => esc_html__( 'Alignment', 'hello-elementor-child' ),
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
					'{{WRAPPER}} .auscorp-cta-btn__wrapper' => 'justify-content: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'left'   => 'flex-start',
					'center' => 'center',
					'right'  => 'flex-end',
				],
			]
		);

		$this->add_responsive_control(
			'button_full_width',
			[
				'label'     => esc_html__( 'Full Width', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off' => esc_html__( 'No', 'hello-elementor-child' ),
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'width: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => '100%',
					''    => 'auto',
				],
			]
		);

		$this->add_responsive_control(
			'button_min_width',
			[
				'label'      => esc_html__( 'Min Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 600 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_max_width',
			[
				'label'      => esc_html__( 'Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 800 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-cta-btn' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Whether the button should show an icon.
	 *
	 * @param array $settings Widget settings.
	 * @return bool
	 */
	private function has_icon( $settings ) {
		return 'none' !== ( $settings['icon_position'] ?? 'none' )
			&& ! empty( $settings['button_icon']['value'] );
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['button_text'] ) ) {
			return;
		}

		$show_icon     = $this->has_icon( $settings );
		$icon_position = $settings['icon_position'] ?? 'none';

		$this->add_render_attribute( 'wrapper', 'class', 'auscorp-cta-btn__wrapper' );
		$this->add_render_attribute( 'button', 'class', 'auscorp-cta-btn' );
		$this->add_link_attributes( 'button', $settings['button_link'] );

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<a <?php $this->print_render_attribute_string( 'button' ); ?>>
				<?php if ( $show_icon && 'before' === $icon_position ) : ?>
					<span class="auscorp-cta-btn__icon auscorp-cta-btn__icon--before" aria-hidden="true">
						<?php \Elementor\Icons_Manager::render_icon( $settings['button_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					</span>
				<?php endif; ?>

				<span class="auscorp-cta-btn__text"><?php echo esc_html( $settings['button_text'] ); ?></span>

				<?php if ( $show_icon && 'after' === $icon_position ) : ?>
					<span class="auscorp-cta-btn__icon auscorp-cta-btn__icon--after" aria-hidden="true">
						<?php \Elementor\Icons_Manager::render_icon( $settings['button_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					</span>
				<?php endif; ?>
			</a>
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
		if ( ! settings.button_text ) {
			return;
		}

		var showIcon = 'none' !== settings.icon_position && settings.button_icon && settings.button_icon.value;
		var iconHTML = '';

		if ( showIcon ) {
			iconHTML = elementor.helpers.renderIcon( view, settings.button_icon, { 'aria-hidden': true }, 'i', 'object' );
		}
		#>
		<div class="auscorp-cta-btn__wrapper">
			<a class="auscorp-cta-btn" href="{{ settings.button_link.url || '#' }}">
				<# if ( showIcon && 'before' === settings.icon_position ) { #>
					<span class="auscorp-cta-btn__icon auscorp-cta-btn__icon--before" aria-hidden="true">{{{ iconHTML.value }}}</span>
				<# } #>

				<span class="auscorp-cta-btn__text">{{{ settings.button_text }}}</span>

				<# if ( showIcon && 'after' === settings.icon_position ) { #>
					<span class="auscorp-cta-btn__icon auscorp-cta-btn__icon--after" aria-hidden="true">{{{ iconHTML.value }}}</span>
				<# } #>
			</a>
		</div>
		<?php
	}
}
