<?php
/**
 * Logs View
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$errors = ATDW_Live()->api_proxy->get_errors( 50 );
?>

<div class="wrap atdw-live-logs">
	<h1><?php esc_html_e( 'API Logs & Errors', 'atdw-live-products' ); ?></h1>

	<div class="atdw-card">
		<h2><?php esc_html_e( 'Recent API Errors', 'atdw-live-products' ); ?></h2>
		<div class="atdw-card-content">
			<?php if ( ! empty( $errors ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'Message', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'URL', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'Timestamp', 'atdw-live-products' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $errors as $error ) : ?>
							<tr>
								<td><?php echo esc_html( $error['type'] ); ?></td>
								<td><?php echo esc_html( $error['message'] ); ?></td>
								<td><code><?php echo esc_html( wp_trim_words( $error['url'], 10, '...' ) ); ?></code></td>
								<td><?php echo esc_html( $error['timestamp'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<button type="button" class="button" id="atdw-live-clear-errors">
						<?php esc_html_e( 'Clear Errors', 'atdw-live-products' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'No errors logged.', 'atdw-live-products' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
