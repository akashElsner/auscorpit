<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}


if ( isset( woof()->settings['by_text'] ) and woof()->settings['by_text']['show'] ) {
	if ( isset( woof()->settings['by_text']['title'] ) and ! empty( woof()->settings['by_text']['title'] ) ) {
		?>
		<!-- <<?php echo esc_attr( apply_filters( 'woof_title_tag', 'h4' ) ); ?>><?php echo esc_html( woof()->settings['by_text']['title'] ); ?></<?php echo esc_attr( apply_filters( 'woof_title_tag', 'h4' ) ); ?>> -->
		<?php
	}
	echo do_shortcode( '[woof_text_filter]' );
}


