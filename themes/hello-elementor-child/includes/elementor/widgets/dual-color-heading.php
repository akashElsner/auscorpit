<?php
/**
 * Auscorp Dual Color Heading Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section heading with two separately styled text parts.
 */
class Auscorp_Dual_Color_Heading_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_dual_color_heading';
	}

	/**
	 * Widget title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Dual Color Heading', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-t-letter';
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
		return [ 'heading', 'title', 'dual', 'color', 'auscorp', 'highlight' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-dual-color-heading' ];
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
				'label' => esc_html__( 'Heading', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => esc_html__( 'HTML Tag', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
					'div' => 'div',
					'span' => 'span',
					'p'  => 'p',
				],
				'default' => 'h2',
			]
		);

		$this->add_control(
			'text_part_1',
			[
				'label'       => esc_html__( 'Text Part 1', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Core', 'hello-elementor-child' ),
				'placeholder' => esc_html__( 'First part', 'hello-elementor-child' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'text_part_2',
			[
				'label'       => esc_html__( 'Text Part 2', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Services', 'hello-elementor-child' ),
				'placeholder' => esc_html__( 'Second part (accent color)', 'hello-elementor-child' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'parts_gap',
			[
				'label'   => esc_html__( 'Space Between Parts', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'space',
				'options' => [
					'none'  => esc_html__( 'No Space', 'hello-elementor-child' ),
					'space' => esc_html__( 'Single Space', 'hello-elementor-child' ),
				],
			]
		);

		$this->add_control(
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

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_heading',
			[
				'label' => esc_html__( 'Heading', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .auscorp-dual-heading',
			]
		);

		$this->add_control(
			'part_1_color',
			[
				'label'     => esc_html__( 'Part 1 Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => [
					'{{WRAPPER}} .auscorp-dual-heading__part--primary' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'part_2_color',
			[
				'label'     => esc_html__( 'Part 2 Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-dual-heading__part--accent' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'heading_margin',
			[
				'label'      => esc_html__( 'Margin', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-dual-heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'heading_text_shadow',
				'selector' => '{{WRAPPER}} .auscorp-dual-heading',
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
			'heading_align',
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
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .auscorp-dual-heading__wrapper' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'heading_max_width',
			[
				'label'      => esc_html__( 'Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 200, 'max' => 1400 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-dual-heading__wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'parts_layout',
			[
				'label'     => esc_html__( 'Parts Layout', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'inline',
				'options'   => [
					'inline' => esc_html__( 'Inline (Same Line)', 'hello-elementor-child' ),
					'stack'  => esc_html__( 'Stacked (Two Lines)', 'hello-elementor-child' ),
				],
				'selectors' => [
					'{{WRAPPER}} .auscorp-dual-heading' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'inline' => 'display: inline;',
					'stack'  => 'display: inline-flex; flex-direction: column; align-items: inherit;',
				],
			]
		);

		$this->add_responsive_control(
			'stacked_gap',
			[
				'label'      => esc_html__( 'Stacked Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'    => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-dual-heading' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render heading inner content.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_heading_content( $settings ) {
		$part_1 = $settings['text_part_1'] ?? '';
		$part_2 = $settings['text_part_2'] ?? '';
		$gap    = $settings['parts_gap'] ?? 'space';

		if ( ! empty( $part_1 ) ) {
			echo '<span class="auscorp-dual-heading__part auscorp-dual-heading__part--primary">';
			echo esc_html( $part_1 );
			echo '</span>';
		}

		if ( ! empty( $part_1 ) && ! empty( $part_2 ) && 'space' === $gap ) {
			echo '<span class="auscorp-dual-heading__space"> </span>';
		}

		if ( ! empty( $part_2 ) ) {
			echo '<span class="auscorp-dual-heading__part auscorp-dual-heading__part--accent">';
			echo esc_html( $part_2 );
			echo '</span>';
		}
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['text_part_1'] ) && empty( $settings['text_part_2'] ) ) {
			return;
		}

		$tag = $settings['html_tag'] ?? 'h2';

		$this->add_render_attribute( 'wrapper', 'class', 'auscorp-dual-heading__wrapper' );
		$this->add_render_attribute( 'heading', 'class', 'auscorp-dual-heading' );

		$has_link = ! empty( $settings['link']['url'] );

		if ( $has_link ) {
			$this->add_link_attributes( 'heading-link', $settings['link'] );
			$this->add_render_attribute( 'heading-link', 'class', 'auscorp-dual-heading__link' );
		}

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php if ( $has_link ) : ?>
				<a <?php $this->print_render_attribute_string( 'heading-link' ); ?>>
			<?php endif; ?>

			<<?php echo tag_escape( $tag ); ?> <?php $this->print_render_attribute_string( 'heading' ); ?>>
				<?php $this->render_heading_content( $settings ); ?>
			</<?php echo tag_escape( $tag ); ?>>

			<?php if ( $has_link ) : ?>
				</a>
			<?php endif; ?>
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
		if ( ! settings.text_part_1 && ! settings.text_part_2 ) {
			return;
		}

		var tag = settings.html_tag || 'h2';
		var hasLink = settings.link && settings.link.url;
		var gapSpace = 'space' === settings.parts_gap ? ' ' : '';
		#>
		<div class="auscorp-dual-heading__wrapper">
			<# if ( hasLink ) { #>
				<a href="{{ settings.link.url }}" class="auscorp-dual-heading__link">
			<# } #>

			<{{ tag }} class="auscorp-dual-heading">
				<# if ( settings.text_part_1 ) { #>
					<span class="auscorp-dual-heading__part auscorp-dual-heading__part--primary">{{{ settings.text_part_1 }}}</span>
				<# } #>
				<# if ( settings.text_part_1 && settings.text_part_2 && 'space' === settings.parts_gap ) { #>
					<span class="auscorp-dual-heading__space"> </span>
				<# } #>
				<# if ( settings.text_part_2 ) { #>
					<span class="auscorp-dual-heading__part auscorp-dual-heading__part--accent">{{{ settings.text_part_2 }}}</span>
				<# } #>
			</{{ tag }}>

			<# if ( hasLink ) { #>
				</a>
			<# } #>
		</div>
		<?php
	}
}
