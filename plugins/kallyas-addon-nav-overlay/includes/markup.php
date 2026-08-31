<?php if(! defined('ABSPATH')){ return; }

$nav_classes = array();

// Layout
$layout = zget_option( 'hovrl_layout', 'general_options', false, 'S1' );
$nav_classes[] = 'znNavOvr--layout' . $layout;

// Animation
$nav_classes[] = $anim = 'znNavOvr--animation' . zget_option( 'hovrl_anim', 'general_options', false, '1' );

// Menu Theme Color
$nav_classes[] = $anim = 'znNavOvr--theme-' . zget_option( 'hovrl_menu_theme', 'general_options', false, 'light' );


/**
 * Logo
 */
$logo_output = '';
if( $logo = zget_option( 'hovrl_logo', 'general_options', false, '' ) ):
$logo_output .= '<div class="znNavOvr-logo znNavOvr-opEffect">';
	$logo_output .= '<a href="'. esc_url( home_url( '/' ) ) .'">';
		$logo_output .= '<img src="'. $logo.'" alt="'. get_bloginfo('name').'" title="'. get_bloginfo('description').'">';
	$logo_output .= '</a>';
$logo_output .= '</div>';
endif;

/**
 * Menu
 */
$menu_output = '<div class="znNavOvr-menuWrapper"></div>';

/**
 * Footer Text
 */
$footer_text_output = '';
if( $footer_text = zget_option( 'hovrl_footertext', 'general_options', false, '' ) ):
	$footer_text_output .= '<div class="znNavOvr-copyText-wrapper znNavOvr-opEffect">';
		$footer_text_output .= '<div class="znNavOvr-copyText">';
		$footer_text_output .= do_shortcode( $footer_text );
		$footer_text_output .= '</div>';
	$footer_text_output .= '</div>';
endif;

/**
 * Custom Text
 */
$custom_text_output = '';
if( $custom_text = zget_option( 'hovrl_ctext', 'general_options', false, '' ) ):
	$custom_text_output .= '<div class="znNavOvr-customText-wrapper znNavOvr-opEffect">';
		$custom_text_output .= '<div class="znNavOvr-customText">';
		$custom_text_output .= do_shortcode( $custom_text );
		$custom_text_output .= '</div>';
	$custom_text_output .= '</div>';
endif;


/**
 * Social Icons
 */
$sicons_output = '';
if (
	zget_option( 'hovrl_social', 'general_options', false, 'no' ) == 'yes' &&
	$header_social_icons = zget_option( 'header_social_icons', 'general_options', false, array() )
):
	$sicons_output .= '<div class="znNavOvr-socialIcons-wrapper znNavOvr-opEffect">';
		$sicons_output .= '<ul class="znNavOvr-socialIcons">';

		foreach ( $header_social_icons as $key => $icon ):
			$link = '';
			$target = '';
			if ( isset ( $icon['header_social_link'] ) && is_array( $icon['header_social_link'] ) ) {
				$link = $icon['header_social_link']['url'];
				$target = 'target="' . $icon['header_social_link']['target'] . '"';
			}
			$sicons_output .= '<li>';
			if( !empty( $icon['header_social_icon'] ) ) {
				$sicons_output .= '<a href="' . $link . '" '.zn_generate_icon( $icon['header_social_icon'] ).' ' . $target . ' title="' . $icon['header_social_title'] . '"></a>';
			}
			$sicons_output .= '</li>';
		endforeach;
		$sicons_output .= '</ul>';
	$sicons_output .= '</div>';
endif;

// Inner Class
$s1_inner_class = $layout == 'S1' && empty($footer_text_output) && empty($sicons_output) ? 'is-empty' : '';
?>

<div id="zn-nav-overlay" class="znNavOvr <?php echo implode(' ', $nav_classes); ?>">

	<div class="znNavOvr-inner <?php echo $s1_inner_class; ?>">

		<?php
		// Display components
		switch( $layout ):

			case"S1":
				echo $logo_output;
				echo $menu_output;
				echo $footer_text_output;
				echo $sicons_output;
			break;

			case"S2":
				echo $logo_output;
				echo $menu_output;
				echo $custom_text_output;
				echo $sicons_output;
				echo $footer_text_output;
			break;

			case"S3":
				echo '<div class="znNavOvr-s3-left">';
					echo '<div class="hidden-lg">';
						echo $logo_output;
					echo '</div>';
					echo $menu_output;
				echo '</div>';
				echo '<div class="znNavOvr-s3-right">';
					echo '<div class="znNavOvr-s3-rightTop">';
						echo '<div class="visible-lg">';
							echo $logo_output;
						echo '</div>';
						echo $custom_text_output;
					echo '</div>';
					echo '<div class="znNavOvr-s3-rightBottom">';
						echo $sicons_output;
						echo $footer_text_output;
					echo '</div>';
				echo '</div>';
			break;

		endswitch;

		?>

	</div>

	<a href="#" class="znNavOvr-close znNavOvr-close--<?php echo zget_option( 'hovrl_close_pos', 'general_options', false, 'trSmall' ); ?>" id="znNavOvr-close">
		<span></span>
		<svg x="0px" y="0px" width="54px" height="54px" viewBox="0 0 54 54">
			<circle fill="transparent" stroke="#656e79" stroke-width="1" cx="27" cy="27" r="25" stroke-dasharray="157 157" stroke-dashoffset="157"></circle>
		</svg>
	</a>
</div>