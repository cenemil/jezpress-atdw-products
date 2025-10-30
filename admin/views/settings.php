<?php
/**
 * Settings View
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = ATDW_Live()->settings;
$market_variants = $settings->get_market_variants();
$output_formats = $settings->get_output_formats();
?>

<div class="wrap atdw-live-settings">
	<h1><?php esc_html_e( 'ATDW Live Settings', 'atdw-live-products' ); ?></h1>

	<?php settings_errors( 'atdw_live_settings' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'atdw_live_settings_save', 'atdw_live_settings_nonce' ); ?>

		<!-- API Settings -->
		<div class="atdw-card">
			<h2><?php esc_html_e( 'API Settings', 'atdw-live-products' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="atdw_live_api_key"><?php esc_html_e( 'API Key', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="text" id="atdw_live_api_key" name="atdw_live_api_key" value="<?php echo esc_attr( $settings->get( 'atdw_live_api_key' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Enter your ATDW API key.', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_api_base_url"><?php esc_html_e( 'API Base URL', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="url" id="atdw_live_api_base_url" name="atdw_live_api_base_url" value="<?php echo esc_url( $settings->get( 'atdw_live_api_base_url' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Default: https://atlas.atdw-online.com.au/api/atlas', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_org_name"><?php esc_html_e( 'Organisation Name', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="text" id="atdw_live_org_name" name="atdw_live_org_name" value="<?php echo esc_attr( $settings->get( 'atdw_live_org_name' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Enter your organisation name as registered with ATDW.', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_org_number"><?php esc_html_e( 'Organisation Number', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="text" id="atdw_live_org_number" name="atdw_live_org_number" value="<?php echo esc_attr( $settings->get( 'atdw_live_org_number' ) ); ?>" class="regular-text" placeholder="e.g., NSW2420" />
						<p class="description">
							<?php esc_html_e( 'Enter your ATDW organisation number (e.g., NSW2420).', 'atdw-live-products' ); ?><br>
							<strong><?php esc_html_e( 'Note:', 'atdw-live-products' ); ?></strong> <?php esc_html_e( 'When set, all product queries will be filtered to show only products from your organisation.', 'atdw-live-products' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_market_variant"><?php esc_html_e( 'Market Variant', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<select id="atdw_live_market_variant" name="atdw_live_market_variant">
							<?php foreach ( $market_variants as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings->get( 'atdw_live_market_variant' ), $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select the language/market variant for product data.', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_output_format"><?php esc_html_e( 'Output Format', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<select id="atdw_live_output_format" name="atdw_live_output_format">
							<?php foreach ( $output_formats as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings->get( 'atdw_live_output_format' ), $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select the API response format.', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Google Maps Settings -->
		<div class="atdw-card">
			<h2><?php esc_html_e( 'Google Maps Settings', 'atdw-live-products' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="atdw_live_gmaps_api_key"><?php esc_html_e( 'Google Maps API Key', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="text" id="atdw_live_gmaps_api_key" name="atdw_live_gmaps_api_key" value="<?php echo esc_attr( $settings->get( 'atdw_live_gmaps_api_key' ) ); ?>" class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Enter your Google Maps API key. Required for displaying maps.', 'atdw-live-products' ); ?><br>
							<?php
							printf(
								/* translators: %s: Google Cloud Console URL */
								esc_html__( 'Get your API key from the %s.', 'atdw-live-products' ),
								'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google Cloud Console', 'atdw-live-products' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_gmaps_base_url"><?php esc_html_e( 'Google Maps API Base URL', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="url" id="atdw_live_gmaps_base_url" name="atdw_live_gmaps_base_url" value="<?php echo esc_url( $settings->get( 'atdw_live_gmaps_base_url' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Default: https://maps.googleapis.com/maps/api/js', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Image Settings -->
		<div class="atdw-card">
			<h2><?php esc_html_e( 'Image Settings', 'atdw-live-products' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="atdw_live_listing_width"><?php esc_html_e( 'Listing Image Width (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_listing_width" name="atdw_live_listing_width" value="<?php echo esc_attr( $settings->get( 'atdw_live_listing_width' ) ); ?>" class="small-text" min="400" step="1" placeholder="400" required />
						<p class="description"><?php esc_html_e( 'Width in pixels for listing images. Minimum: 400, Default: 400', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_listing_height"><?php esc_html_e( 'Listing Image Height (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_listing_height" name="atdw_live_listing_height" value="<?php echo esc_attr( $settings->get( 'atdw_live_listing_height' ) ); ?>" class="small-text" min="300" step="1" placeholder="300" required />
						<p class="description"><?php esc_html_e( 'Height in pixels for listing images. Minimum: 300, Default: 300', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_gallery_width"><?php esc_html_e( 'Gallery Image Width (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_gallery_width" name="atdw_live_gallery_width" value="<?php echo esc_attr( $settings->get( 'atdw_live_gallery_width' ) ); ?>" class="small-text" min="100" step="1" placeholder="600" />
						<p class="description"><?php esc_html_e( 'Width in pixels for lightbox gallery images. Default: 600', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_gallery_height"><?php esc_html_e( 'Gallery Image Height (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_gallery_height" name="atdw_live_gallery_height" value="<?php echo esc_attr( $settings->get( 'atdw_live_gallery_height' ) ); ?>" class="small-text" min="100" step="1" placeholder="400" />
						<p class="description"><?php esc_html_e( 'Height in pixels for lightbox gallery images. Default: 400', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_thumbnail_width"><?php esc_html_e( 'Thumbnail Width (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_thumbnail_width" name="atdw_live_thumbnail_width" value="<?php echo esc_attr( $settings->get( 'atdw_live_thumbnail_width' ) ); ?>" class="small-text" min="50" step="1" placeholder="150" />
						<p class="description"><?php esc_html_e( 'Width in pixels for gallery thumbnails. Default: 150', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="atdw_live_thumbnail_height"><?php esc_html_e( 'Thumbnail Height (px)', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<input type="number" id="atdw_live_thumbnail_height" name="atdw_live_thumbnail_height" value="<?php echo esc_attr( $settings->get( 'atdw_live_thumbnail_height' ) ); ?>" class="small-text" min="50" step="1" placeholder="150" />
						<p class="description"><?php esc_html_e( 'Height in pixels for gallery thumbnails. Default: 150', 'atdw-live-products' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Custom CSS -->
		<div class="atdw-card">
			<h2><?php esc_html_e( 'Custom CSS', 'atdw-live-products' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="atdw_live_custom_css"><?php esc_html_e( 'Custom CSS', 'atdw-live-products' ); ?></label>
					</th>
					<td>
						<textarea id="atdw_live_custom_css" name="atdw_live_custom_css" rows="15" class="large-text code" style="font-family: monospace;"><?php echo esc_textarea( $settings->get( 'atdw_live_custom_css' ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Add custom CSS to override the default plugin styles. This CSS will be loaded after the default stylesheet and will take priority.', 'atdw-live-products' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<input type="submit" name="atdw_live_settings_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'atdw-live-products' ); ?>" />
		</p>
	</form>
</div>
