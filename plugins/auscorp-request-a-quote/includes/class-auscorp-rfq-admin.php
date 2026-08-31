<?php
/**
 * Admin dashboard: quote list table columns, status control, and the
 * customer/items detail view on each quote's edit screen.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Admin
 */
class Auscorp_RFQ_Admin {

	const POST_TYPE = Auscorp_RFQ_Post_Type::POST_TYPE;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'views_edit-' . self::POST_TYPE, [ $this, 'status_views' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_by_status' ] );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_status' ] );

		add_action( 'wp_ajax_auscorp_rfq_update_status', [ $this, 'ajax_update_status' ] );

		add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the list-table status dropdown script on this post type's admin screens only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		unset( $hook );

		wp_enqueue_script( 'auscorp-rfq-admin', AUSCORP_RFQ_URL . 'assets/js/quote-admin.js', [ 'jquery' ], AUSCORP_RFQ_VERSION, true );
	}

	/**
	 * Get the status meta for a quote, defaulting to "pending".
	 *
	 * @param int $post_id Quote post ID.
	 * @return string
	 */
	public static function get_status( $post_id ) {
		$status = get_post_meta( $post_id, '_quote_status', true );

		return $status ? $status : 'pending';
	}

	/**
	 * Available statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses() {
		return [
			'pending' => __( 'Pending', 'auscorp-rfq' ),
			'done'    => __( 'Done', 'auscorp-rfq' ),
		];
	}

	/**
	 * Define the list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$new = [
			'cb'          => $columns['cb'],
			'title'       => __( 'Quote', 'auscorp-rfq' ),
			'customer'    => __( 'Customer', 'auscorp-rfq' ),
			'items'       => __( 'Items', 'auscorp-rfq' ),
			'note'        => __( 'Note', 'auscorp-rfq' ),
			'quote_status' => __( 'Status', 'auscorp-rfq' ),
			'date'        => $columns['date'],
		];

		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'customer':
				$name  = get_post_meta( $post_id, '_customer_name', true );
				$email = get_post_meta( $post_id, '_customer_email', true );

				echo '<strong>' . esc_html( $name ) . '</strong><br />';
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				break;

			case 'items':
				$items = get_post_meta( $post_id, '_quote_items', true );
				$items = is_array( $items ) ? $items : [];

				$count = 0;
				foreach ( $items as $item ) {
					$count += (int) $item['quantity'];
				}

				echo esc_html(
					sprintf(
						/* translators: 1: number of items, 2: number of lines */
						_n( '%1$d item (%2$d line)', '%1$d items (%2$d lines)', $count, 'auscorp-rfq' ),
						$count,
						count( $items )
					)
				);
				break;

			case 'note':
				$note = get_post_meta( $post_id, '_customer_note', true );
				echo $note ? esc_html( wp_trim_words( $note, 12 ) ) : '&#8212;';
				break;

			case 'quote_status':
				$status = self::get_status( $post_id );
				?>
				<select class="auscorp-rfq-status-select" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'auscorp_rfq_status_' . $post_id ) ); ?>">
					<?php foreach ( self::get_statuses() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="auscorp-rfq-status-saved" style="display:none;"><?php esc_html_e( 'Saved', 'auscorp-rfq' ); ?></span>
				<?php
				break;
		}
	}

	/**
	 * Add "Pending"/"Done" filter links above the list table.
	 *
	 * @param array $views Existing views.
	 * @return array
	 */
	public function status_views( $views ) {
		global $wpdb;

		foreach ( self::get_statuses() as $key => $label ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					WHERE pm.meta_key = '_quote_status' AND pm.meta_value = %s
					AND p.post_type = %s AND p.post_status = 'publish'",
					$key,
					self::POST_TYPE
				)
			);

			$url            = add_query_arg( [ 'post_type' => self::POST_TYPE, 'quote_status' => $key ], admin_url( 'edit.php' ) );
			$current_status = isset( $_GET['quote_status'] ) ? sanitize_key( wp_unslash( $_GET['quote_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$class          = ( $current_status === $key ) ? ' class="current"' : '';

			$views[ $key ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( $url ),
				$class,
				esc_html( $label ),
				(int) $count
			);
		}

		return $views;
	}

	/**
	 * Apply the quote_status query var to the admin list query.
	 *
	 * @param WP_Query $query Current query.
	 * @return void
	 */
	public function filter_by_status( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( empty( $_GET['quote_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$query->set(
			'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				[
					'key'   => '_quote_status',
					'value' => sanitize_key( wp_unslash( $_GET['quote_status'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				],
			]
		);
	}

	/**
	 * Register the customer + items meta boxes on the quote edit screen.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'auscorp_rfq_customer',
			__( 'Customer Details', 'auscorp-rfq' ),
			[ $this, 'render_customer_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'auscorp_rfq_items',
			__( 'Requested Items', 'auscorp-rfq' ),
			[ $this, 'render_items_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'auscorp_rfq_status',
			__( 'Status', 'auscorp-rfq' ),
			[ $this, 'render_status_meta_box' ],
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the read-only customer details meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_customer_meta_box( $post ) {
		$name  = get_post_meta( $post->ID, '_customer_name', true );
		$email = get_post_meta( $post->ID, '_customer_email', true );
		$note  = get_post_meta( $post->ID, '_customer_note', true );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Name', 'auscorp-rfq' ); ?></th>
				<td><?php echo esc_html( $name ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email', 'auscorp-rfq' ); ?></th>
				<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Note', 'auscorp-rfq' ); ?></th>
				<td><?php echo $note ? esc_html( $note ) : '&#8212;'; ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the requested items table.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_items_meta_box( $post ) {
		$items = get_post_meta( $post->ID, '_quote_items', true );
		$items = is_array( $items ) ? $items : [];
		$total = 0.0;
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'auscorp-rfq' ); ?></th>
					<th><?php esc_html_e( 'SKU', 'auscorp-rfq' ); ?></th>
					<th><?php esc_html_e( 'Price', 'auscorp-rfq' ); ?></th>
					<th><?php esc_html_e( 'Qty', 'auscorp-rfq' ); ?></th>
					<th><?php esc_html_e( 'Subtotal', 'auscorp-rfq' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $items as $item ) :
					$subtotal = (float) $item['price'] * (int) $item['quantity'];
					$total   += $subtotal;
					$edit_link = $item['product_id'] ? get_edit_post_link( $item['product_id'] ) : '';
					?>
					<tr>
						<td>
							<?php if ( $edit_link ) : ?>
								<a href="<?php echo esc_url( $edit_link ); ?>" target="_blank"><?php echo esc_html( $item['name'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $item['name'] ); ?>
							<?php endif; ?>
							<?php if ( ! empty( $item['variation_text'] ) ) : ?>
								<br /><small><?php echo esc_html( $item['variation_text'] ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $item['sku'] ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $item['price'] ) ); ?></td>
						<td><?php echo esc_html( $item['quantity'] ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="4" style="text-align:right;"><?php esc_html_e( 'Total', 'auscorp-rfq' ); ?></th>
					<th><?php echo wp_kses_post( wc_price( $total ) ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Render the status meta box (side column) with a save-on-post-save select.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_status_meta_box( $post ) {
		$status = self::get_status( $post->ID );
		wp_nonce_field( 'auscorp_rfq_save_status', 'auscorp_rfq_status_nonce' );
		?>
		<select name="auscorp_rfq_status" style="width:100%;">
			<?php foreach ( self::get_statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Save the status meta box value.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_status( $post_id ) {
		if ( ! isset( $_POST['auscorp_rfq_status_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['auscorp_rfq_status_nonce'] ), 'auscorp_rfq_save_status' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST['auscorp_rfq_status'] ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_POST['auscorp_rfq_status'] ) );

		if ( array_key_exists( $status, self::get_statuses() ) ) {
			update_post_meta( $post_id, '_quote_status', $status );
		}
	}

	/**
	 * Update status inline from the list table dropdown.
	 *
	 * @return void
	 */
	public function ajax_update_status() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! check_ajax_referer( 'auscorp_rfq_status_' . $post_id, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'auscorp-rfq' ) ] );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'auscorp-rfq' ) ] );
		}

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid status.', 'auscorp-rfq' ) ] );
		}

		update_post_meta( $post_id, '_quote_status', $status );

		wp_send_json_success( [ 'status' => $status ] );
	}

	/**
	 * Drop the default "Quick Edit" row action — it doesn't apply to this
	 * post type since editing happens through the status meta box instead.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( self::POST_TYPE === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}
}
