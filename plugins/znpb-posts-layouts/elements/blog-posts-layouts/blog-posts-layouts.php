<?php if(! defined('ABSPATH')){ return; }
/*
	Name: Blog-Posts Layouts Element
	Description: This element will generate various layouts of blog posts
	Class: ZnBlogPostsLayouts
	Category: content
	Keywords: posts, blog, archive, latest
	Level: 3
	Style: true
*/


class ZnBlogPostsLayouts extends ZnElements {

	public static function getName(){
		return __( "Blog-Posts Layouts Element", 'znpb-posts-layouts' );
	}
	function options() {

		$uid = $this->data['uid'];

		$get_tags = $this->getBlogTags();
		if( method_exists('WpkZn','getBlogTags') ){
			$get_tags = WpkZn::getBlogTags();
		}


		$options = array(
			'has_tabs'  => true,

			'general' => array(
				'title' => 'Content',
				'options' => array(

					array (
						"name"        => __( "Blog Category", 'znpb-posts-layouts' ),
						"description" => __( "Select the blog category to show items", 'znpb-posts-layouts' ),
						"id"          => "blog_categories",
						"multiple"    => true,
						"std"         => "",
						"type"        => "select",
						"options"     => WpkZn::getBlogCategories()
					),

					array (
						"name"        => __( "Blog Tags", 'znpb-posts-layouts' ),
						"description" => __( "Select the blog tags to show items", 'znpb-posts-layouts' ),
						"id"          => "blog_tags",
						"multiple"    => true,
						"std"         => "",
						"type"        => "select",
						"options"     => $get_tags
					),

				),
			),

			'styles' => array(
				'title' => 'Styles',
				'options' => array(

					// style select
					array (
						"name"        => __( "Select Style", 'znpb-posts-layouts' ),
						"description" => __( "Select the style of this element", 'znpb-posts-layouts' ),
						"id"          => "style",
						"std"         => "default",
						'type'        => 'select',
						'options'        => array(
							'default' => __( "Default", 'znpb-posts-layouts' ),
							'masonry' => __( "Masonry (since Kallyas v4.12.0)", 'znpb-posts-layouts' ),
							'large_carousel' => __( "Large Carousel (since Kallyas v4.12.0)", 'znpb-posts-layouts' ),
						),
					),

					array (
						"name"        => __( "Posts to load", 'znpb-posts-layouts' ),
						"description" => __( "Please select how many posts to load (except main featured item).", 'znpb-posts-layouts' ),
						"id"          => "per_page_small",
						"std"         => "10",
						"type"        => "text",
						"class"       => "zn_input_xs",
						"numeric"        => true,
						"helpers"        => array(
							"min" => 1,
							"max" => 20,
							"step" => 1,
						),
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
					),

					array (
						"name"        => __( "Masonry - Columns", 'znpb-posts-layouts' ),
						"description" => __( "Select how many columns you want to display the items.", 'znpb-posts-layouts' ),
						"id"          => "columns",
						"std"         => "6",
						'type'        => 'select',
						'options'        => array(
							'12' => __( "1 Column", 'znpb-posts-layouts' ),
							'6' => __( "2 Columns", 'znpb-posts-layouts' ),
							'4' => __( "3 Columns", 'znpb-posts-layouts' ),
						),
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry') ),
					),

					array(
						"name"        => __( "Masonry - Gutter Size", 'znpb-posts-layouts' ),
						"description" => __( "Select the gutter distance between columns.", 'znpb-posts-layouts' ),
						'id'          => 'gutter_size',
						"std"         => "",
						"type"        => "select",
						"options"     => array (
							'' => __( 'Default (15px)', 'znpb-posts-layouts' ),
							'gutter-xs' => __( 'Extra Small (5px)', 'znpb-posts-layouts' ),
							'gutter-sm' => __( 'Small (10px)', 'znpb-posts-layouts' ),
							'gutter-md' => __( 'Medium (25px)', 'znpb-posts-layouts' ),
							'gutter-lg' => __( 'Large (40px)', 'znpb-posts-layouts' ),
							// 'gutter-0' => __( 'No distance - 0px', 'znpb-posts-layouts' ),
						),
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry') ),
					),

					array(
						"name"        => __( "Masonry - Content Alignment", 'znpb-posts-layouts' ),
						"description" => __( "Select the content alignment.", 'znpb-posts-layouts' ),
						'id'          => 'align',
						"std"         => "center",
						"type"        => "select",
						"options"     => array (
							'left' => __( 'Left', 'znpb-posts-layouts' ),
							'center' => __( 'Center', 'znpb-posts-layouts' ),
							'right' => __( 'Right', 'znpb-posts-layouts' ),
						),
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry') ),
					),

					array (
						// "name"        => __( "Title Typography settings", 'znpb-posts-layouts' ),
						// "description" => __( "Adjust the typography of the title as you want on any breakpoint", 'znpb-posts-layouts' ),
						"id"          => "font_breakpoints",
						"std"         => "lg",
						"tabs"        => true,
						"type"        => "zn_radio",
						"options"     => array (
							"lg"        => __( "LARGE", 'znpb-posts-layouts' ),
							"md"        => __( "MEDIUM", 'znpb-posts-layouts' ),
							"sm"        => __( "SMALL", 'znpb-posts-layouts' ),
							"xs"        => __( "EXTRA SMALL", 'znpb-posts-layouts' ),
						),
						"class"       => "zn_full zn_breakpoints",
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
					),

					array (
						"name"        => __( "Title Typography settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "title_typo_lg",
						"std"         => '',
						'supports'   => array( 'size', 'font', 'style', 'line', 'color', 'weight', 'spacing', 'case' ),
						"type"        => "font",
						"type"        => "font",
							'live'        => array(
							'multiple' => array(
								array(
									'type'      => 'font',
									'css_class' => '.'.$uid.' .znBpl-bPost-title',
								),
								array(
									'type'      => 'font',
									'css_class' => '.'.$uid.' .znBpl-bPost-title a',
								),
							)
						),
						"dependency"  => array(
							array( 'element' => 'font_breakpoints' , 'value'=> array('lg') ),
							array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
						),
					),

					array (
						"name"        => __( "Title settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "title_typo_md",
						"std"         => '',
						'supports'   => array( 'size', 'line', 'spacing' ),
						"type"        => "font",
						"dependency"  => array(
							array( 'element' => 'font_breakpoints' , 'value'=> array('md') ),
							array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
						),
					),

					array (
						"name"        => __( "Title settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "title_typo_sm",
						"std"         => '',
						'supports'   => array( 'size', 'line', 'spacing' ),
						"type"        => "font",
						"dependency"  => array(
							array( 'element' => 'font_breakpoints' , 'value'=> array('sm') ),
							array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
						),
					),

					array (
						"name"        => __( "Title settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "title_typo_xs",
						"std"         => '',
						'supports'   => array( 'size', 'line', 'spacing' ),
						"type"        => "font",
						"dependency"  => array(
							array( 'element' => 'font_breakpoints' , 'value'=> array('xs') ),
							array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
						),
					),

					array (
						"name"        => __( "Title Hover Color", 'znpb-posts-layouts' ),
						"description" => __( "Select the title's hover color.", 'znpb-posts-layouts' ),
						"id"          => "title_hover_color",
						"std"         => "",
						"type"        => "colorpicker",
						"alpha"       => "true",
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
					),

					array (
						"name"        => __( "Post Info - Typography settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "info_typo",
						"std"         => '',
						'supports'   => array( 'size', 'font', 'style', 'line', 'color', 'weight', 'spacing', 'case' ),
						"type"        => "font",
							'live'        => array(
							'multiple' => array(
								array(
									'type'      => 'font',
									'css_class' => '.'.$uid.' .znBpl-bPost-info',
								),
								array(
									'type'      => 'font',
									'css_class' => '.'.$uid.' .znBpl-bPost-info a',
								),
							)
						),
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
					),

					array (
						"name"        => __( "Show Excerpt", 'znpb-posts-layouts' ),
						"description" => __( "Choose if you want to display the excerpt.", 'znpb-posts-layouts' ),
						"id"          => "show_excerpt",
						"std"         => "yes",
						'type'        => 'zn_radio',
						'options'        => array(
							'yes' => __( "Yes", 'znpb-posts-layouts' ),
							'no' => __( "No", 'znpb-posts-layouts' ),
						),
						'class'        => 'zn_radio--yesno',
						"dependency"  => array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
					),

					array (
						"name"        => __( "Post Excerpt - Typography settings", 'znpb-posts-layouts' ),
						"description" => __( "Specify the typography properties for the title.", 'znpb-posts-layouts' ),
						"id"          => "excerpt_typo",
						"std"         => '',
						'supports'   => array( 'size', 'font', 'style', 'line', 'color', 'weight', 'spacing', 'case' ),
						"type"        => "font",
							'live'        => array(
							'multiple' => array(
								array(
									'type'      => 'font',
									'css_class' => '.'.$uid.' .znBpl-bPost-excerpt',
								),
							)
						),
						"dependency"  => array(
							array( 'element' => 'show_excerpt' , 'value'=> array('yes') ),
							array( 'element' => 'style' , 'value'=> array('masonry', 'large_carousel') ),
						),
					),

					/**
					 * Carousel options
					 */
					array (
						"name"        => __( "AutoPlay Carousel?", 'znpb-posts-layouts' ),
						"description" => __( "Autoplay the carousel?", 'znpb-posts-layouts' ),
						"id"          => "autoplay",
						"std"         => "yes",
						'type'        => 'zn_radio',
						'options'        => array(
							'yes' => __( "Yes", 'znpb-posts-layouts' ),
							'no' => __( "No", 'znpb-posts-layouts' ),
						),
						'class'        => 'zn_radio--yesno',
						"dependency" => array( 'element' => 'style' , 'value'=> array('large_carousel') ),
					),
					array (
						"name"        => __( "Autoplay Speed", 'znpb-posts-layouts' ),
						"description" => __( "Choose the autoplay speed seconds interval.", 'znpb-posts-layouts' ),
						"id"          => "autoplaySpeed",
						"std"         => "5",
						"type"        => "text",
						"class"       => "zn_input_xs",
						"numeric"        => true,
						"helpers"        => array(
							"min" => 0,
							"max" => 20,
							"step" => 1,
						),
						"dependency"  => array(
							array( 'element' => 'style' , 'value'=> array('large_carousel') ),
							array( 'element' => 'autoplay' , 'value'=> array('yes') )
						),
					),

					array (
						"name"        => __( "Display Arrows?", 'znpb-posts-layouts' ),
						"description" => __( "Display Arrows navigation?.", 'znpb-posts-layouts' ),
						"id"          => "arrows",
						"std"         => "yes",
						'type'        => 'zn_radio',
						'options'        => array(
							'yes' => __( "Yes", 'znpb-posts-layouts' ),
							'no' => __( "No", 'znpb-posts-layouts' ),
						),
						'class'        => 'zn_radio--yesno',
						"dependency" => array( 'element' => 'style' , 'value'=> array('large_carousel') ),
					),
					array (
						"name"        => __( "Arrows Offset", 'znpb-posts-layouts' ),
						"description" => __( "Choose an offset for the arrows.", 'znpb-posts-layouts' ),
						"id"          => "arrows_offset",
						"std"         => -100,
						"type"        => "text",
						"class"       => "zn_input_xs",
						"numeric"        => true,
						"helpers"        => array(
							"min" => -150,
							"max" => 150,
							"step" => 1,
						),
						"dependency"  => array(
							array( 'element' => 'style' , 'value'=> array('large_carousel') ),
							array( 'element' => 'arrows' , 'value'=> array('yes') )
						),
					),
					array (
						"name"        => __( "Display Dots?", 'znpb-posts-layouts' ),
						"description" => __( "Display Dots navigation?.", 'znpb-posts-layouts' ),
						"id"          => "dots",
						"std"         => "yes",
						'type'        => 'zn_radio',
						'options'        => array(
							'yes' => __( "Yes", 'znpb-posts-layouts' ),
							'no' => __( "No", 'znpb-posts-layouts' ),
						),
						'class'        => 'zn_radio--yesno',
						"dependency" => array( 'element' => 'style' , 'value'=> array('large_carousel') ),
					),

					/**
					 * Margins and padding
					 */
					array (
						"name"        => __( "Edit element padding & margins for each device breakpoint. ", 'znpb-posts-layouts' ),
						"description" => __( "This will enable you to have more control over the padding of the container on each device. Click to see <a href='http://hogash.d.pr/1f0nW' target='_blank'>how box-model works</a>.", 'znpb-posts-layouts' ),
						"id"          => "spacing_breakpoints",
						"std"         => "lg",
						"tabs"        => true,
						"type"        => "zn_radio",
						"options"     => array (
							"lg"        => __( "LARGE", 'znpb-posts-layouts' ),
							"md"        => __( "MEDIUM", 'znpb-posts-layouts' ),
							"sm"        => __( "SMALL", 'znpb-posts-layouts' ),
							"xs"        => __( "EXTRA SMALL", 'znpb-posts-layouts' ),
						),
						"class"       => "zn_full zn_breakpoints"
					),
					// MARGINS
					array(
						'id'          => 'margin_lg',
						'name'        => 'Margin (Large Breakpoints)',
						'description' => 'Select the margin (in percent % or px) for this container. Accepts negative margin.',
						'type'        => 'boxmodel',
						'std'	  => '',
						'placeholder' => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('lg') ),
						'live' => array(
							'type'		=> 'boxmodel',
							'css_class' => '.'.$uid,
							'css_rule'	=> 'margin',
						),
					),
					array(
						'id'          => 'margin_md',
						'name'        => 'Margin (Medium Breakpoints)',
						'description' => 'Select the margin (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('md') ),
					),
					array(
						'id'          => 'margin_sm',
						'name'        => 'Margin (Small Breakpoints)',
						'description' => 'Select the margin (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('sm') ),
					),
					array(
						'id'          => 'margin_xs',
						'name'        => 'Margin (Extra Small Breakpoints)',
						'description' => 'Select the margin (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('xs') ),
					),
					// PADDINGS
					array(
						'id'          => 'padding_lg',
						'name'        => 'Padding (Large Breakpoints)',
						'description' => 'Select the padding (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						"allow-negative" => false,
						'std'	  => '',
						'placeholder' => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('lg') ),
						'live' => array(
							'type'		=> 'boxmodel',
							'css_class' => '.'.$uid,
							'css_rule'	=> 'padding',
						),
					),
					array(
						'id'          => 'padding_md',
						'name'        => 'Padding (Medium Breakpoints)',
						'description' => 'Select the padding (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						"allow-negative" => false,
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('md') ),
					),
					array(
						'id'          => 'padding_sm',
						'name'        => 'Padding (Small Breakpoints)',
						'description' => 'Select the padding (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						"allow-negative" => false,
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('sm') ),
					),
					array(
						'id'          => 'padding_xs',
						'name'        => 'Padding (Extra Small Breakpoints)',
						'description' => 'Select the padding (in percent % or px) for this container.',
						'type'        => 'boxmodel',
						"allow-negative" => false,
						'std'	  => 	'',
						'placeholder'        => '0px',
						"dependency"  => array( 'element' => 'spacing_breakpoints' , 'value'=> array('xs') ),
					),


				),
			),

		);

		return $options;
	}

	function element() {

		$options = $this->data['options'];

		//Class
		$classes[] 	= $uid = $this->data['uid'];
		$classes[] 	= zn_get_element_classes($options);

		$attributes = zn_get_element_attributes($options);

		$layout = $this->opt('style', 'default');
		$classes[] 	= 'znBpl--'.$layout;


		?>

		<div class="znBpl <?php echo implode(' ', $classes); ?>" <?php echo $attributes; ?>>
		<?php

			$path = dirname(__FILE__). '/layouts/'.$layout.'/index.php';
			$path = apply_filters('zn_blogpostlayouts_el_layout', $path, $layout);
			if(is_file($path)){
				include( $path );
			}
		?>
		</div><!-- /.znBpl -->

		<?php

	}

	function css(){

		$uid = $this->data['uid'];
		$css = '';
		$layout = $this->opt('style', 'default');


		// Margin
		$margins = array();
		$margins['lg'] = $this->opt('margin_lg', '' );
		$margins['md'] = $this->opt('margin_md', '' );
		$margins['sm'] = $this->opt('margin_sm', '' );
		$margins['xs'] = $this->opt('margin_xs', '' );
		if( !empty($margins) ){
			$margins['selector'] = '.'.$uid;
			$margins['type'] = 'margin';
			$css .= zn_push_boxmodel_styles( $margins );
		}

		// Padding
		$paddings = array();
		$paddings['lg'] = $this->opt('padding_lg', '' );
		$paddings['md'] = $this->opt('padding_md', '' );
		$paddings['sm'] = $this->opt('padding_sm', '' );
		$paddings['xs'] = $this->opt('padding_xs', '' );
		if( !empty($paddings) ){
			$paddings['selector'] = '.'.$uid;
			$paddings['type'] = 'padding';
			$css .= zn_push_boxmodel_styles( $paddings );
		}

		// Masonry Options
		if( $layout == 'masonry' || $layout == 'large_carousel' ){

			// Title Typo
			$typo = array();
			if($this->opt('title_typo_lg', '' )) $typo['lg'] = $this->opt('title_typo_lg');
			if($this->opt('title_typo_md', '' )) $typo['md'] = $this->opt('title_typo_md');
			if($this->opt('title_typo_sm', '' )) $typo['sm'] = $this->opt('title_typo_sm');
			if($this->opt('title_typo_xs', '' )) $typo['xs'] = $this->opt('title_typo_xs');
			if( !empty($typo) ){
				$typo['selector'] = '.'.$uid.' .znBpl-bPost-title, .'.$uid.' .znBpl-bPost-title a';
				$css .= zn_typography_css( $typo );
			}

			// Title hover
			if( $title_hover_color = $this->opt('title_hover_color','') ){
				$css .= '.'.$uid.' .znBpl-bPost-titleLink:hover {color:'.$title_hover_color.'}';
			}

			// Info Typo
			$info_typo = array();
			if($this->opt('info_typo', '' )) $info_typo['lg'] = $this->opt('info_typo');
			if( !empty($info_typo) ){
				$info_typo['selector'] = '.'.$uid.' .znBpl-bPost-info, .'.$uid.' .znBpl-bPost-info a';
				$css .= zn_typography_css( $info_typo );
			}

			// Excerpt Typo
			$excerpt_typo = array();
			if($this->opt('excerpt_typo', '' )) $excerpt_typo['lg'] = $this->opt('excerpt_typo');
			if( !empty($excerpt_typo) ){
				$excerpt_typo['selector'] = '.'.$uid.' .znBpl-bPost-excerpt';
				$css .= zn_typography_css( $excerpt_typo );
			}
		}

		if( $layout == 'large_carousel' && $this->opt('arrows', 'yes') == 'yes' ){
			$arrows_offset = $this->opt('arrows_offset', -100);
			if($arrows_offset != -100){
				$css .= '
					.'.$uid.' .znBpl-carouselNav .znSlickNav-arr.znSlickNav-prev {left:'.$arrows_offset.'px;}
					.'.$uid.' .znBpl-carouselNav .znSlickNav-arr.znSlickNav-next {right:'.$arrows_offset.'px;}';
			}
		}


		return $css;

	}


	/**
	 * Load dependant resources
	 */
	function scripts(){

		wp_enqueue_script( 'isotope', ZNHGFW()->getFwUrl('assets/dist/js/jquery.isotope.min.js'), 'jquery', '', true );

		$layout = $this->opt('style', 'default');
		wp_enqueue_style( 'blogposts_element_'.$layout, plugin_dir_url( __FILE__ ) . 'layouts/'.$layout.'/style.css', array('kallyas-styles'), ZN_FW_VERSION );

	}

	public static function getBlogTags(){

		$tags = get_tags( array(
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => false,
		) );

		if(! $tags || is_wp_error($tags)){
			return array();
		}
		$temp = array();
		foreach($tags as $tag){
			$temp[$tag->term_id] = esc_attr($tag->name);
		}
		return $temp;
	}


}
