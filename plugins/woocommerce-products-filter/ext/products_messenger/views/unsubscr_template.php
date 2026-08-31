<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php // Inline styles are required here: this template renders a standalone unsubscribe confirmation page outside of any WordPress theme context. ?>
<style>
	.mdf_notice{
		padding: 30px;
		background: #ececec;
		margin: 0 auto;
		margin-top: 8%;
		max-width: 400px;
		text-align: center;
	}
</style>
<div class="mdf_notice"><span>
		<?php echo wp_kses_post( wp_unslash( $text ) ); ?>
	</span>        
</div>



