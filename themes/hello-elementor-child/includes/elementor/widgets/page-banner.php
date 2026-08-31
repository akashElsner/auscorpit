<?php
/**
 * Auscorp Page Banner Elementor Widget.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full-width page banner with background, overlay, and breadcrumbs.
 */
class Auscorp_Page_Banner_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'auscorp_page_banner';
	}

	/**
	 * Widget title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Auscorp Page Banner', 'hello-elementor-child' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-banner';
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
		return [ 'banner', 'page', 'hero', 'breadcrumb', 'title', 'auscorp' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'auscorp-page-banner' ];
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
				'label' => esc_html__( 'Banner Content', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Leave empty to use current page title', 'hello-elementor-child' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => esc_html__( 'HTML Tag', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
					'p'   => 'p',
				],
				'default' => 'h1',
			]
		);

		$this->add_control(
			'background_image',
			[
				'label'   => esc_html__( 'Background Image', 'hello-elementor-child' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => '',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_breadcrumbs',
			[
				'label' => esc_html__( 'Breadcrumbs', 'hello-elementor-child' ),
			]
		);

		$this->add_control(
			'show_breadcrumbs',
			[
				'label'        => esc_html__( 'Show Breadcrumbs', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'home_label',
			[
				'label'       => esc_html__( 'Home Label', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Home / site name', 'hello-elementor-child' ),
				'description' => esc_html__( 'Used for automatic breadcrumbs. Leave empty to use the site name.', 'hello-elementor-child' ),
				'condition'   => [
					'show_breadcrumbs' => 'yes',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'separator',
			[
				'label'     => esc_html__( 'Separator', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '>',
				'condition' => [
					'show_breadcrumbs' => 'yes',
				],
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'crumb_name',
			[
				'label'       => esc_html__( 'Name', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'crumb_link',
			[
				'label'       => esc_html__( 'Link', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'hello-elementor-child' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'breadcrumb_items',
			[
				'label'       => esc_html__( 'Breadcrumb Items', 'hello-elementor-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ crumb_name }}}',
				'condition'   => [
					'show_breadcrumbs' => 'yes',
				],
				'description' => esc_html__( 'Add custom breadcrumb names and links. If empty, breadcrumbs auto-generate as: Home > Current Page.', 'hello-elementor-child' ),
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
			'section_style_overlay',
			[
				'label' => esc_html__( 'Overlay', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'show_overlay',
			[
				'label'        => esc_html__( 'Show Overlay', 'hello-elementor-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hello-elementor-child' ),
				'label_off'    => esc_html__( 'No', 'hello-elementor-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'overlay_color',
			[
				'label'     => esc_html__( 'Overlay Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__overlay' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_overlay' => 'yes',
				],
			]
		);

		$this->add_control(
			'overlay_opacity',
			[
				'label'      => esc_html__( 'Overlay Opacity', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
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
					'{{WRAPPER}} .auscorp-page-banner__overlay' => 'opacity: {{SIZE}};',
				],
				'condition'  => [
					'show_overlay' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Title', 'hello-elementor-child' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .auscorp-page-banner__title',
			]
		);

		$this->add_responsive_control(
			'title_spacing',
			[
				'label'      => esc_html__( 'Bottom Spacing', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 80 ],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-page-banner__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_breadcrumbs',
			[
				'label'     => esc_html__( 'Breadcrumbs', 'hello-elementor-child' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_breadcrumbs' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'breadcrumb_typography',
				'selector' => '{{WRAPPER}} .auscorp-page-banner__breadcrumbs',
			]
		);

		$this->add_control(
			'breadcrumb_link_color',
			[
				'label'     => esc_html__( 'Link Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ff7a21',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__crumb-link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'breadcrumb_link_hover_color',
			[
				'label'     => esc_html__( 'Link Hover Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__crumb-link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'breadcrumb_current_color',
			[
				'label'     => esc_html__( 'Current / Text Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__crumb-current' => 'color: {{VALUE}};',
					'{{WRAPPER}} .auscorp-page-banner__crumb-text'    => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'breadcrumb_separator_color',
			[
				'label'     => esc_html__( 'Separator Color', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__separator' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'breadcrumb_gap',
			[
				'label'      => esc_html__( 'Item Gap', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
				],
				'default'    => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-page-banner__breadcrumbs' => 'gap: {{SIZE}}{{UNIT}};',
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
			'banner_min_height',
			[
				'label'      => esc_html__( 'Min Height', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 120, 'max' => 800 ],
					'vh' => [ 'min' => 10, 'max' => 100 ],
				],
				'default'    => [
					'size' => 280,
					'unit' => 'px',
				],
				'tablet_default' => [
					'size' => 220,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 180,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-page-banner' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'banner_padding',
			[
				'label'      => esc_html__( 'Padding', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '60',
					'right'    => '24',
					'bottom'   => '60',
					'left'     => '24',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'tablet_default' => [
					'top'      => '48',
					'right'    => '20',
					'bottom'   => '48',
					'left'     => '20',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'mobile_default' => [
					'top'      => '40',
					'right'    => '16',
					'bottom'   => '40',
					'left'     => '16',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-page-banner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__inner' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'text-align: left; align-items: flex-start;',
					'center' => 'text-align: center; align-items: center;',
					'right'  => 'text-align: right; align-items: flex-end;',
				],
			]
		);

		$this->add_responsive_control(
			'content_max_width',
			[
				'label'      => esc_html__( 'Content Max Width', 'hello-elementor-child' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 200, 'max' => 1400 ],
					'%'  => [ 'min' => 40, 'max' => 100 ],
				],
				'default'    => [
					'size' => 1100,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .auscorp-page-banner__inner' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'background_position',
			[
				'label'     => esc_html__( 'Background Position', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => [
					'center center' => esc_html__( 'Center Center', 'hello-elementor-child' ),
					'center top'    => esc_html__( 'Center Top', 'hello-elementor-child' ),
					'center bottom' => esc_html__( 'Center Bottom', 'hello-elementor-child' ),
					'left center'   => esc_html__( 'Left Center', 'hello-elementor-child' ),
					'right center'  => esc_html__( 'Right Center', 'hello-elementor-child' ),
				],
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__bg' => 'background-position: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'background_size',
			[
				'label'     => esc_html__( 'Background Size', 'hello-elementor-child' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					'cover'   => esc_html__( 'Cover', 'hello-elementor-child' ),
					'contain' => esc_html__( 'Contain', 'hello-elementor-child' ),
					'auto'    => esc_html__( 'Auto', 'hello-elementor-child' ),
				],
				'selectors' => [
					'{{WRAPPER}} .auscorp-page-banner__bg' => 'background-size: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Resolve the banner title.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function get_banner_title( $settings ) {
		if ( ! empty( $settings['title'] ) ) {
			return $settings['title'];
		}

		return $this->get_current_page_title();
	}

	/**
	 * Get current page / post / archive title.
	 *
	 * @return string
	 */
	private function get_current_page_title() {
		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_home() && ! is_front_page() ) {
			$posts_page_id = (int) get_option( 'page_for_posts' );
			if ( $posts_page_id ) {
				return get_the_title( $posts_page_id );
			}
			return esc_html__( 'Blog', 'hello-elementor-child' );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return single_term_title( '', false );
		}

		if ( is_post_type_archive() ) {
			return post_type_archive_title( '', false );
		}

		if ( is_search() ) {
			return sprintf(
				/* translators: %s: search query */
				esc_html__( 'Search: %s', 'hello-elementor-child' ),
				get_search_query()
			);
		}

		if ( is_author() ) {
			return get_the_author();
		}

		if ( is_404() ) {
			return esc_html__( 'Page Not Found', 'hello-elementor-child' );
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return woocommerce_page_title( false );
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * Resolve home breadcrumb label.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function get_home_label( $settings ) {
		if ( ! empty( $settings['home_label'] ) ) {
			return $settings['home_label'];
		}

		$site_name = get_bloginfo( 'name' );
		if ( ! empty( $site_name ) ) {
			return $site_name;
		}

		return esc_html__( 'Home', 'hello-elementor-child' );
	}

	/**
	 * Build breadcrumb items (manual or automatic).
	 *
	 * @param array  $settings     Widget settings.
	 * @param string $banner_title Resolved banner title.
	 * @return array
	 */
	private function get_breadcrumb_items( $settings, $banner_title ) {
		$manual = [];

		if ( ! empty( $settings['breadcrumb_items'] ) && is_array( $settings['breadcrumb_items'] ) ) {
			foreach ( $settings['breadcrumb_items'] as $item ) {
				$name = isset( $item['crumb_name'] ) ? trim( (string) $item['crumb_name'] ) : '';
				if ( '' === $name ) {
					continue;
				}

				$manual[] = [
					'name' => $name,
					'link' => $item['crumb_link'] ?? [],
				];
			}
		}

		if ( ! empty( $manual ) ) {
			return $manual;
		}

		return [
			[
				'name' => $this->get_home_label( $settings ),
				'link' => [
					'url' => home_url( '/' ),
				],
			],
			[
				'name' => $banner_title,
				'link' => [],
			],
		];
	}

	/**
	 * Render a single breadcrumb item.
	 *
	 * @param array  $item       Crumb data.
	 * @param bool   $is_last    Whether this is the last crumb.
	 * @param string $separator  Separator character.
	 * @param int    $index      Item index for attribute keys.
	 * @return void
	 */
	private function render_breadcrumb_item( $item, $is_last, $separator, $index ) {
		$name = $item['name'] ?? '';
		$link = $item['link'] ?? [];
		$url  = ! empty( $link['url'] ) ? $link['url'] : '';

		echo '<li class="auscorp-page-banner__crumb">';

		if ( ! $is_last && $url ) {
			$attr_key = 'crumb-link-' . $index;
			$this->add_link_attributes( $attr_key, $link );
			$this->add_render_attribute( $attr_key, 'class', 'auscorp-page-banner__crumb-link' );

			echo '<a ';
			$this->print_render_attribute_string( $attr_key );
			echo '>';
			echo esc_html( $name );
			echo '</a>';
		} elseif ( $is_last ) {
			echo '<span class="auscorp-page-banner__crumb-current" aria-current="page">';
			echo esc_html( $name );
			echo '</span>';
		} else {
			echo '<span class="auscorp-page-banner__crumb-text">';
			echo esc_html( $name );
			echo '</span>';
		}

		echo '</li>';

		if ( ! $is_last ) {
			echo '<li class="auscorp-page-banner__separator" aria-hidden="true">';
			echo esc_html( $separator );
			echo '</li>';
		}
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings     = $this->get_settings_for_display();
		$banner_title = $this->get_banner_title( $settings );
		$tag          = $settings['html_tag'] ?? 'h1';
		$bg_url       = ! empty( $settings['background_image']['url'] ) ? $settings['background_image']['url'] : '';
		$show_overlay = ( 'yes' === ( $settings['show_overlay'] ?? 'yes' ) );
		$show_crumbs  = ( 'yes' === ( $settings['show_breadcrumbs'] ?? 'yes' ) );
		$separator    = isset( $settings['separator'] ) && '' !== $settings['separator']
			? $settings['separator']
			: '>';

		$this->add_render_attribute( 'banner', 'class', 'auscorp-page-banner' );

		if ( $bg_url ) {
			$this->add_render_attribute(
				'bg',
				[
					'class' => 'auscorp-page-banner__bg',
					'style' => 'background-image: url(' . esc_url( $bg_url ) . ');',
				]
			);
		} else {
			$this->add_render_attribute( 'bg', 'class', 'auscorp-page-banner__bg auscorp-page-banner__bg--empty' );
		}

		?>
		<section <?php $this->print_render_attribute_string( 'banner' ); ?>>
			<div <?php $this->print_render_attribute_string( 'bg' ); ?>></div>

			<?php if ( $show_overlay ) : ?>
				<div class="auscorp-page-banner__overlay"></div>
			<?php endif; ?>

			<div class="auscorp-page-banner__inner">
				<?php if ( $banner_title ) : ?>
					<<?php echo tag_escape( $tag ); ?> class="auscorp-page-banner__title">
						<?php echo esc_html( $banner_title ); ?>
					</<?php echo tag_escape( $tag ); ?>>
				<?php endif; ?>

				<?php if ( $show_crumbs ) : ?>
					<?php
					$crumbs = $this->get_breadcrumb_items( $settings, $banner_title );
					$count  = count( $crumbs );
					?>
					<?php if ( $count > 0 ) : ?>
						<nav class="auscorp-page-banner__nav" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'hello-elementor-child' ); ?>">
							<ol class="auscorp-page-banner__breadcrumbs">
								<?php
								foreach ( $crumbs as $index => $item ) {
									$this->render_breadcrumb_item(
										$item,
										( $index === $count - 1 ),
										$separator,
										$index
									);
								}
								?>
							</ol>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
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
		$page_title = esc_js( $this->get_current_page_title() );
		$home_label = esc_js( get_bloginfo( 'name' ) ?: __( 'Home', 'hello-elementor-child' ) );
		$home_url   = esc_url( home_url( '/' ) );
		?>
		<#
		var bannerTitle = settings.title ? settings.title : '<?php echo $page_title; ?>';
		var tag = settings.html_tag || 'h1';
		var bgUrl = settings.background_image && settings.background_image.url ? settings.background_image.url : '';
		var showOverlay = 'yes' === settings.show_overlay;
		var showCrumbs = 'yes' === settings.show_breadcrumbs;
		var separator = settings.separator !== undefined && settings.separator !== '' ? settings.separator : '>';
		var homeLabel = settings.home_label ? settings.home_label : '<?php echo $home_label; ?>';
		var crumbs = [];

		if ( settings.breadcrumb_items && settings.breadcrumb_items.length ) {
			_.each( settings.breadcrumb_items, function( item ) {
				if ( item.crumb_name && item.crumb_name.trim() ) {
					crumbs.push( item );
				}
			} );
		}

		if ( ! crumbs.length ) {
			crumbs = [
				{ crumb_name: homeLabel, crumb_link: { url: '<?php echo $home_url; ?>' } },
				{ crumb_name: bannerTitle, crumb_link: {} }
			];
		}
		#>
		<section class="auscorp-page-banner">
			<# if ( bgUrl ) { #>
				<div class="auscorp-page-banner__bg" style="background-image: url({{ bgUrl }});"></div>
			<# } else { #>
				<div class="auscorp-page-banner__bg auscorp-page-banner__bg--empty"></div>
			<# } #>

			<# if ( showOverlay ) { #>
				<div class="auscorp-page-banner__overlay"></div>
			<# } #>

			<div class="auscorp-page-banner__inner">
				<# if ( bannerTitle ) { #>
					<{{ tag }} class="auscorp-page-banner__title">{{{ bannerTitle }}}</{{ tag }}>
				<# } #>

				<# if ( showCrumbs && crumbs.length ) { #>
					<nav class="auscorp-page-banner__nav" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'hello-elementor-child' ); ?>">
						<ol class="auscorp-page-banner__breadcrumbs">
							<# _.each( crumbs, function( item, index ) {
								var isLast = index === crumbs.length - 1;
								var url = item.crumb_link && item.crumb_link.url ? item.crumb_link.url : '';
							#>
								<li class="auscorp-page-banner__crumb">
									<# if ( ! isLast && url ) { #>
										<a class="auscorp-page-banner__crumb-link" href="{{ url }}">{{{ item.crumb_name }}}</a>
									<# } else if ( isLast ) { #>
										<span class="auscorp-page-banner__crumb-current" aria-current="page">{{{ item.crumb_name }}}</span>
									<# } else { #>
										<span class="auscorp-page-banner__crumb-text">{{{ item.crumb_name }}}</span>
									<# } #>
								</li>
								<# if ( ! isLast ) { #>
									<li class="auscorp-page-banner__separator" aria-hidden="true">{{{ separator }}}</li>
								<# } #>
							<# } ); #>
						</ol>
					</nav>
				<# } #>
			</div>
		</section>
		<?php
	}
}
