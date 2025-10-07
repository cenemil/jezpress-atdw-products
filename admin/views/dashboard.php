<?php
/**
 * Dashboard View
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_key_set = ! empty( get_option( 'atdw_live_api_key' ) );
$org_number = get_option( 'atdw_live_org_number' );
?>

<div class="wrap atdw-live-dashboard">
	<h1><?php esc_html_e( 'ATDW Live Products Dashboard', 'atdw-live-products' ); ?></h1>

	<?php settings_errors( 'atdw_live_settings' ); ?>

	<!-- API Status -->
	<div class="atdw-card atdw-card-full">
		<h2><?php esc_html_e( 'API Status', 'atdw-live-products' ); ?></h2>
		<div class="atdw-card-content">
			<?php if ( $api_key_set ) : ?>
				<p class="atdw-status atdw-status-success">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'API Key Configured', 'atdw-live-products' ); ?>
				</p>
				<?php if ( ! empty( $org_number ) ) : ?>
					<p class="atdw-status" style="margin-top: 10px; color: #2271b1;">
						<span class="dashicons dashicons-filter"></span>
						<?php
						/* translators: %s: Organisation number */
						printf( esc_html__( 'Filtering products by organisation: %s', 'atdw-live-products' ), '<strong>' . esc_html( $org_number ) . '</strong>' );
						?>
					</p>
				<?php endif; ?>
				<p style="margin-top: 15px;">
					<button type="button" class="button button-primary" id="atdw-live-test-connection">
						<?php esc_html_e( 'Test Connection', 'atdw-live-products' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live-settings' ) ); ?>" class="button">
						<?php esc_html_e( 'Settings', 'atdw-live-products' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live-logs' ) ); ?>" class="button">
						<?php esc_html_e( 'View Logs', 'atdw-live-products' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p class="atdw-status atdw-status-error">
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'API Key Not Configured', 'atdw-live-products' ); ?>
				</p>
				<p class="description" style="margin: 10px 0;">
					<?php esc_html_e( 'Please configure your ATDW API key to start displaying tourism products.', 'atdw-live-products' ); ?>
				</p>
				<p style="margin-top: 15px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Configure API Key', 'atdw-live-products' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Shortcode Examples -->
	<div class="atdw-card atdw-card-full">
		<h2><?php esc_html_e( 'Shortcode Examples', 'atdw-live-products' ); ?></h2>
		<div class="atdw-card-content">
			<h3><?php esc_html_e( 'Display Products Grid', 'atdw-live-products' ); ?></h3>
			<p><?php esc_html_e( 'Basic usage:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'Filtered by state:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="NSW" size="6"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With region and columns:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="QLD" region="Gold Coast" columns="4"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'List layout without pagination:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products type="ACCOMM" layout="list" show_pagination="no"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With custom view link:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="VIC" cats="Wine" size="12" view_slug="listing"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With specific fields only (reduced response):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products fl="productName,productImage,productDescription" st="NSW" size="10"]</code>

			<h3 style="margin-top: 20px;"><?php esc_html_e( 'Display Single Product', 'atdw-live-products' ); ?></h3>
			<code>[atdw_live_product id="56b23f7e57010b977310e2ba"]</code>
			<p class="description"><?php esc_html_e( 'Replace with actual ATDW product ID', 'atdw-live-products' ); ?></p>

			<h3 style="margin-top: 20px;"><?php esc_html_e( 'Search Form', 'atdw-live-products' ); ?></h3>
			<p><?php esc_html_e( 'Basic search (default filters):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With specific cities:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search ct="Dungog,Clarence Town,Stroud"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With custom placeholder:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search placeholder="Search tourism attractions" ct="Dungog"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'Custom sort options:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search sort="date,asc,desc,random"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'With product type filtering:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search type="ACCOMM" show_types="yes"]</code>

			<p style="margin-top: 10px;"><?php esc_html_e( 'Minimal (keyword only):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search show_suburb="no" show_sort="no" show_types="no"]</code>

			<h3 style="margin-top: 20px;"><?php esc_html_e( 'Available Parameters', 'atdw-live-products' ); ?></h3>
			<h4><?php esc_html_e( 'Product Grid ([atdw_live_products]):', 'atdw-live-products' ); ?></h4>
			<ul style="margin-left: 20px;">
				<li><strong>st</strong> - State code (NSW, VIC, QLD, SA, WA, TAS, NT, ACT)</li>
				<li><strong>cats</strong> - Category filter (comma-separated)</li>
				<li><strong>region</strong> - Region/area name</li>
				<li><strong>location</strong> - Location filter</li>
				<li><strong>type</strong> - Product type filter</li>
				<li><strong>fl</strong> - Field List - specify which fields to return from API response (comma-separated, e.g., "productName,productImage,productDescription"). Reduces response size for better performance</li>
				<li><strong>size</strong> - Number of products per page (default: 12)</li>
				<li><strong>pge</strong> - Page number for pagination (default: 1)</li>
				<li><strong>layout</strong> - grid or list (default: grid)</li>
				<li><strong>columns</strong> - 2, 3, or 4 (default: 3)</li>
				<li><strong>show_image</strong> - yes or no (default: yes)</li>
				<li><strong>show_excerpt</strong> - yes or no (default: yes)</li>
				<li><strong>show_pagination</strong> - yes or no (default: yes)</li>
				<li><strong>view_label</strong> - Button text (default: "View Details")</li>
				<li><strong>view_slug</strong> - Single product page slug (default: "detail")</li>
			</ul>
			<h4 style="margin-top: 15px;"><?php esc_html_e( 'Search Form ([atdw_live_search]):', 'atdw-live-products' ); ?></h4>
			<ul style="margin-left: 20px;">
				<li><strong>ct</strong> - Comma-separated city/suburb options (e.g., "Dungog,Stroud"). If empty, no dropdown shown</li>
				<li><strong>sort</strong> - Comma-separated sort options: date, asc, desc, price_asc, price_desc, drt, random. If empty, shows default options</li>
				<li><strong>type</strong> - Product type/category to filter (used for loading type checkboxes)</li>
				<li><strong>placeholder</strong> - Search input placeholder (default: "Search keyword")</li>
				<li><strong>show_suburb</strong> - Show suburb/city dropdown: yes or no (default: yes)</li>
				<li><strong>show_sort</strong> - Show sort dropdown: yes or no (default: yes)</li>
				<li><strong>show_types</strong> - Show type checkboxes: yes or no (default: yes)</li>
				<li><strong>columns</strong> - Results grid columns: 2, 3, or 4 (default: 3)</li>
			</ul>
		</div>
	</div>

	<!-- System Info -->
	<div class="atdw-card atdw-card-full">
		<h2><?php esc_html_e( 'System Information', 'atdw-live-products' ); ?></h2>
		<div class="atdw-card-content">
			<table class="widefat">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Version:', 'atdw-live-products' ); ?></strong></td>
						<td><?php echo esc_html( ATDW_LIVE_VERSION ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Version:', 'atdw-live-products' ); ?></strong></td>
						<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version:', 'atdw-live-products' ); ?></strong></td>
						<td><?php echo esc_html( PHP_VERSION ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Fetch Method:', 'atdw-live-products' ); ?></strong></td>
						<td><?php esc_html_e( 'Client-side JavaScript (AJAX)', 'atdw-live-products' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
