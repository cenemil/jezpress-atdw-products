/**
 * ATDW Live Products - Frontend JavaScript
 *
 * @package ATDW_Live_Products
 */

(function($) {
	'use strict';

	/**
	 * ATDW Live API Client
	 */
	var ATDWLive = {

		// Cache for fetched products
		productCache: {},

		/**
		 * Initialize
		 */
		init: function() {
			this.initProductContainers();
			this.initSingleContainers();
			this.initSearchContainers();
			this.initMetaContainers();
		},

		/**
		 * Initialize product list containers
		 */
		initProductContainers: function() {
			$('.atdw-live-container').each(function() {
				var $container = $(this);
				var endpoint = $container.data('endpoint') || '/products';

				// Get size from shortcode parameter, default to 12
				var size = parseInt($container.data('size')) || 12;
				var initialPage = parseInt($container.data('pge')) || 1;

				// Store params for pagination (using ATDW API parameter names)
				$container.data('params', {
					size: size,
					st: $container.data('st') || '',  // Changed from 'state' to 'st'
					cats: $container.data('cats') || '',
					area: $container.data('region') || '',
					ptype: $container.data('type') || '',
					loc: $container.data('location') || '',
					fl: $container.data('fl') || '',
				ct: $container.data('ct') || '',  // City/town filter
					order: $container.data('order') || ''  // Sort order from shortcode
				});

				// Set current page
				$container.data('current-page', initialPage);

				// Initialize pagination if enabled
				if ($container.data('show-pagination') === 'yes') {
					ATDWLive.initPagination($container);
				}

				// Load first page with proper pagination
				ATDWLive.loadPage($container, initialPage);
			});
		},

		/**
		 * Initialize single product containers
		 */
		initSingleContainers: function() {
			$('.atdw-live-single-container').each(function() {
				var $container = $(this);
				var endpoint = $container.data('endpoint') || '/product';
				var productId = $container.data('product-id');

				if (!productId) {
					return;
				}

				var params = {
					productId: productId
				};

				// Fetch and display single product
				ATDWLive.fetchSingleProduct(endpoint, params, $container);
			});
		},

		/**
		 * Initialize search containers
		 */
		initSearchContainers: function() {
			$('.atdw-live-search-container').each(function() {
				var $container = $(this);

				// Load product types from API if type parameter is set
				var $typesRow = $container.find('.atdw-types-row');
				if ($typesRow.length > 0) {
					var cats = $typesRow.data('cats');
					if (cats) {
						ATDWLive.loadProductTypes($typesRow, cats);
					}
				}

				// Handle types toggle button
				$container.find('.atdw-types-toggle').on('click', function() {
					var $button = $(this);
					var $checkboxes = $container.find('.atdw-types-checkboxes');
					var $toggleText = $button.find('.atdw-toggle-text');

					if ($checkboxes.is(':visible')) {
						$checkboxes.slideUp();
						$toggleText.text('Select types');
					} else {
						$checkboxes.slideDown();
						$toggleText.text('Hide types');
					}
				});

				// Handle search button click
				$container.find('.atdw-search-button').on('click', function() {
					ATDWLive.performSearch($container);
				});

				// Handle enter key in search input
				$container.find('.atdw-search-input').on('keypress', function(e) {
					if (e.which === 13) {
						ATDWLive.performSearch($container);
					}
				});
			});
		},

		/**
		 * Initialize meta field containers
		 */
		initMetaContainers: function() {
			var $containers = $('.atdw-live-meta-container');

			// Check if there's an 'id' query parameter in the URL
			var urlParams = new URLSearchParams(window.location.search);
			var urlProductId = urlParams.get('id');

			// If there's an id in URL but no containers, fetch the product anyway for potential use
			if (urlProductId && $containers.length === 0) {
				ATDWLive.fetchProductForMetaContainers(urlProductId, []);
				return;
			}

			if ($containers.length === 0) {
				return;
			}

			// Group containers by product ID
			var productGroups = {};

			$containers.each(function() {
				var $container = $(this);
				var productId = $container.data('product-id');
				var metaKey = $container.data('meta-key');

				if (!productId || !metaKey) {
					$container.find('.atdw-live-loading').hide();
					$container.find('.atdw-live-error').show();
					return;
				}

				// Group by product ID
				if (!productGroups[productId]) {
					productGroups[productId] = [];
				}
				productGroups[productId].push($container);
			});

			// Fetch each unique product once and populate all its containers
			$.each(productGroups, function(productId, containers) {
				ATDWLive.fetchProductForMetaContainers(productId, containers);
			});
		},

		/**
		 * Fetch product once and populate all meta containers for that product
		 */
		fetchProductForMetaContainers: function(productId, containers) {
			// Check if product is already cached
			if (ATDWLive.productCache[productId]) {
				var product = ATDWLive.productCache[productId];
				// Populate all containers for this product
				$.each(containers, function(index, $container) {
					$container.find('.atdw-live-loading').hide();
					ATDWLive.displayMetaField(product, $container);
				});
				console.log('ATDW: Using cached product data for ID:', productId);
				return;
			}

			var params = {
				productId: productId
			};

			console.log('ATDW: Fetching product data for ID:', productId);

			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: '/product',
					params: params
				},
				success: function(response) {
					if (response.success && response.data) {
						var product = response.data.product || response.data;

						// Store in cache
						ATDWLive.productCache[productId] = product;
						console.log('ATDW: Product data cached for ID:', productId);

						// Make product data globally accessible
						if (typeof window.atdwProduct === 'undefined') {
							window.atdwProduct = {};
						}
						window.atdwProduct[productId] = product;

						// Populate all containers for this product (if any)
						if (containers.length > 0) {
							$.each(containers, function(index, $container) {
								$container.find('.atdw-live-loading').hide();
								ATDWLive.displayMetaField(product, $container);
							});
						}
					} else {
						// Show error in all containers (if any)
						var errorMsg = response.data && response.data.message ? response.data.message : 'Error loading product';
						if (containers.length > 0) {
							$.each(containers, function(index, $container) {
								$container.find('.atdw-live-loading').hide();
								$container.find('.atdw-live-error').find('p').text(errorMsg);
								$container.find('.atdw-live-error').show();
							});
						}
						console.error('ATDW API Error:', response);
					}
				},
				error: function(xhr, status, error) {
					// Show error in all containers (if any)
					if (containers.length > 0) {
						$.each(containers, function(index, $container) {
							$container.find('.atdw-live-loading').hide();
							$container.find('.atdw-live-error').show();
						});
					}
					console.error('AJAX Error:', status, error);
				}
			});
		},

		/**
		 * Display a specific meta field from product data
		 */
		displayMetaField: function(product, $container) {
			var $content = $container.find('.atdw-live-meta-content');
			var metaKey = $container.data('meta-key');
			var format = $container.data('format') || 'html';
			var before = $container.data('before') || '';
			var after = $container.data('after') || '';
			var size = $container.data('size') || 'medium';
			var columns = parseInt($container.data('columns')) || 3;
			var link = $container.data('link') !== 'no';
			var label = $container.data('label') || '';
		var galleryLayout = $container.data('gallery-layout') || 'inline';
		var attributeType = $container.data('attribute-type') || '';

		// Handle special 'address' key - extract from addresses array
		if (metaKey.toLowerCase() === 'address') {
			var value = ATDWLive.extractFormattedAddress(product);
			var fieldName = 'address';
		}
		// Handle special 'map' key - extract coordinates from addresses array
		else if (metaKey.toLowerCase() === 'map') {
			var value = ATDWLive.extractMapCoordinates(product);
			var fieldName = 'map';
		}
		// Handle special 'gallery'/'images' key - extract from multimedia array
		else if (metaKey.toLowerCase() === 'gallery' || metaKey.toLowerCase() === 'images') {
			var value = ATDWLive.extractGalleryFromMultimedia(product);
			var fieldName = 'gallery';
		}
		// Handle special 'attributes' key - extract from attributes array by type
		else if (metaKey.toLowerCase() === 'attributes') {
			var value = ATDWLive.extractAttributesByType(product, attributeType);
			var fieldName = 'attributes';
		}
		// Try to use the exact API field name first (case-sensitive)
		else {

			var value = product[metaKey];
			var fieldName = metaKey;

			// If not found, try friendly aliases (for backward compatibility)
			if (!value) {
				var fieldMapping = {
					'name': 'productName',
					'title': 'productName',
					'description': 'productDescription',
					'phone': 'phone',
					'email': 'email',
					'website': 'websiteUrl',
					'address': 'address',
					'locality': 'locality',
					'area': 'area',
					'state': 'state',
					'postcode': 'postcode',
					'country': 'country',
					'gallery': 'images',
					'images': 'images',
					'image': 'productImage',
					'featured_image': 'productImage',
					'price': 'rate_from',
					'rate': 'rate_from'
				};

				var mappedField = fieldMapping[metaKey.toLowerCase()];
				if (mappedField) {
					value = product[mappedField];
					fieldName = mappedField;
				}
		}

			// Priority override: check communication array for phone, email, website
			if (metaKey.toLowerCase() === 'phone') {
				var commPhone = ATDWLive.extractPhoneFromCommunication(product);
				if (commPhone) {
					value = commPhone;
					fieldName = 'phone';
				}
			} else if (metaKey.toLowerCase() === 'email') {
				var commEmail = ATDWLive.extractEmailFromCommunication(product);
				if (commEmail) {
					value = commEmail;
					fieldName = 'email';
				}
			} else if (metaKey.toLowerCase() === 'website') {
				var commWebsite = ATDWLive.extractWebsiteFromCommunication(product);
				if (commWebsite) {
					value = commWebsite;
					fieldName = 'websiteUrl';
				}
			}
			}

			if (!value) {
				// Hide the entire container if no value exists
				$container.hide();
				return;
			}

			var html = before;

			// Handle different field types
			if (metaKey.toLowerCase() === 'gallery' || metaKey.toLowerCase() === 'images') {
				// Gallery display
				html += ATDWLive.renderGallery(value, size, columns, link, product.productName || '', galleryLayout);
			} else if (metaKey.toLowerCase() === 'image' || metaKey.toLowerCase() === 'featured_image') {
				// Single image
				html += ATDWLive.renderImage(value, size, link, product.productName || '');
			} else if (metaKey.toLowerCase() === 'phone') {
				// Phone with link
				var linkText = label || value;
				if (format === 'html') {
					html += '<a href="tel:' + ATDWLive.escapeHtml(value) + '" class="atdw-meta-phone">' + ATDWLive.escapeHtml(linkText) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'email') {
				// Email with link
				var linkText = label || value;
				if (format === 'html') {
					html += '<a href="mailto:' + ATDWLive.escapeHtml(value) + '" class="atdw-meta-email">' + ATDWLive.escapeHtml(linkText) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'website' || fieldName === 'websiteUrl') {
				// Website with link - ensure protocol is present
				var websiteUrl = ATDWLive.ensureUrlProtocol(value);
				var linkText = label || value;
				if (format === 'html') {
					html += '<a href="' + ATDWLive.escapeHtml(websiteUrl) + '" target="_blank" rel="noopener noreferrer" class="atdw-meta-website">' + ATDWLive.escapeHtml(linkText) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'map') {
			// Map display with Google Maps JavaScript API
			if (value && value.latitude && value.longitude) {
				var mapWidth = $container.data('map-width') || '100%';
				var mapHeight = $container.data('map-height') || '400px';
				var mapZoom = $container.data('map-zoom') || 15;
				var gmapsApiKey = atdwLive.gmapsApiKey || '';

				if (format === 'html') {
					if (gmapsApiKey) {
						// Generate unique map ID
						var mapId = 'atdw-map-' + Math.random().toString(36).substr(2, 9);

						// Render Google Maps container with explicit dimensions
						// Don't use escapeHtml on CSS values to preserve proper formatting
						html += '<div id="' + mapId + '" class="atdw-meta-map-js" style="width: ' + mapWidth + '; height: ' + mapHeight + '; min-height: ' + mapHeight + ';" data-lat="' + ATDWLive.escapeHtml(value.latitude) + '" data-lng="' + ATDWLive.escapeHtml(value.longitude) + '" data-zoom="' + mapZoom + '" data-product-name="' + ATDWLive.escapeHtml(product.productName || 'Location') + '"></div>';

						// Initialize map after DOM is ready
						ATDWLive.initGoogleMap(mapId, value.latitude, value.longitude, mapZoom, product.productName || 'Location', gmapsApiKey);
					} else {
						// Fallback: show coordinates only (no link)
						html += '<div class="atdw-meta-map" data-lat="' + ATDWLive.escapeHtml(value.latitude) + '" data-lng="' + ATDWLive.escapeHtml(value.longitude) + '">';
						html += '<span class="atdw-map-coords">Lat: ' + ATDWLive.escapeHtml(value.latitude) + ', Lng: ' + ATDWLive.escapeHtml(value.longitude) + '</span>';
						html += '</div>';
					}
				} else {
					html += value.latitude + ', ' + value.longitude;
				}
			}
				} else if (metaKey.toLowerCase() === 'description' || fieldName === 'productDescription') {
				// Description
				if (format === 'text') {
					// Strip HTML if format is text
					var tempDiv = document.createElement('div');
					tempDiv.innerHTML = value;
					html += ATDWLive.escapeHtml(tempDiv.textContent || tempDiv.innerText || '');
				} else if (format === 'raw') {
					// Raw HTML (use carefully)
					html += value;
				} else {
					// Escaped HTML with line breaks preserved (default)
					html += '<div class="atdw-meta-description">' + ATDWLive.nl2br(ATDWLive.escapeHtml(value)) + '</div>';
				}
			} else if (metaKey.toLowerCase() === 'price' || metaKey.toLowerCase() === 'rate') {
				// Price with formatting
				if (format === 'html') {
					html += '<span class="atdw-meta-price">$' + ATDWLive.escapeHtml(value) + '</span>';
				} else {
					html += '$' + ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'attributes' && Array.isArray(value)) {
				// Attributes - display array of descriptions separated by line breaks
				if (format === 'html') {
					// Join with <br> tags for HTML display
					html += '<div class="atdw-meta-attributes">' + value.map(function(desc) {
						return ATDWLive.escapeHtml(desc);
					}).join('<br>') + '</div>';
				} else if (format === 'text') {
					// Join with newlines for text display
					html += value.map(function(desc) {
						return ATDWLive.escapeHtml(desc);
					}).join('\n');
				} else {
					// Default: same as HTML
					html += '<div class="atdw-meta-attributes">' + value.map(function(desc) {
						return ATDWLive.escapeHtml(desc);
					}).join('<br>') + '</div>';
				}
			} else {
				// Default text display
				if (format === 'raw') {
					html += value;
				} else {
					html += '<span class="atdw-meta-value">' + ATDWLive.escapeHtml(value) + '</span>';
				}
			}

			html += after;

			$content.html(html);

		// Initialize GLightbox for dynamically loaded galleries
		if (metaKey.toLowerCase() === 'gallery' || metaKey.toLowerCase() === 'images') {
			ATDWLive.initGLightbox();
		}
		},
	/**
	 * Render gallery of images
	 */
	renderGallery: function(images, size, columns, link, altText, layout) {
		if (!Array.isArray(images)) {
			images = [images];
		}

		if (images.length === 0) {
			return '<p class="atdw-no-value">No images available</p>';
		}

		// Get gallery settings from atdwLive config (as integers)
		var galleryWidth = atdwLive.galleryWidth || 600;
		var galleryHeight = atdwLive.galleryHeight || 400;
		var thumbnailWidth = atdwLive.thumbnailWidth || 150;
		var thumbnailHeight = atdwLive.thumbnailHeight || 150;

		// Default layout to 'inline' if not provided
		layout = layout || 'inline';

		// Generate unique gallery ID for GLightbox grouping
		var galleryId = 'gallery-' + Math.random().toString(36).substr(2, 9);

		var html = '';

		if (layout === 'grid') {
			// Grid layout with CSS Grid
			var gridStyle = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(' + thumbnailWidth + 'px, 1fr)); gap: 10px;';
			html += '<div class="atdw-meta-gallery atdw-gallery-grid atdw-gallery-columns-' + columns + '" style="' + gridStyle + '">';

			// Grid: use width and height from settings
			var thumbnailStyles = 'width: 100%; height: ' + thumbnailHeight + 'px; object-fit: cover; display: block;';

			for (var i = 0; i < images.length; i++) {
				var imageItem = images[i];
				// Support both object format {url, altText} and legacy string format
				var imageUrl = typeof imageItem === 'object' ? imageItem.url : imageItem;
				var imageAltText = typeof imageItem === 'object' && imageItem.altText ? imageItem.altText : altText;

				if (imageUrl) {
					// Strip query string
					imageUrl = imageUrl.split('?')[0];

					// Create full-size URL with width and height parameters for lightbox (only if values are valid)
					var fullImageParams = [];
					if (galleryWidth > 0) fullImageParams.push('w=' + galleryWidth);
					if (galleryHeight > 0) fullImageParams.push('h=' + galleryHeight);
					var fullImageUrl = fullImageParams.length > 0 ? imageUrl + '?' + fullImageParams.join('&') : imageUrl;

					// Create thumbnail URL with width and height parameters (only if values are valid)
					var thumbnailParams = [];
					if (thumbnailWidth > 0) thumbnailParams.push('w=' + thumbnailWidth);
					if (thumbnailHeight > 0) thumbnailParams.push('h=' + thumbnailHeight);
					var thumbnailUrl = thumbnailParams.length > 0 ? imageUrl + '?' + thumbnailParams.join('&') : imageUrl;

					html += '<div class="atdw-gallery-item">';
					html += '<a href="' + ATDWLive.escapeHtml(fullImageUrl) + '" class="glightbox atdw-gallery-link" data-gallery="' + galleryId + '" data-glightbox="description:' + ATDWLive.escapeHtml(imageAltText) + ';">';
					html += '<img src="' + ATDWLive.escapeHtml(thumbnailUrl) + '" alt="' + ATDWLive.escapeHtml(imageAltText) + '" style="' + thumbnailStyles + '" loading="lazy" />';
					html += '</a>';
					html += '</div>';
				}
			}

			html += '</div>';
		} else {
			// Inline layout (default) - simple display with inline-block
			html += '<div class="atdw-meta-gallery atdw-gallery-inline" style="display: flex; flex-wrap: wrap; gap: 10px;">';

			// Inline: use max-width and max-height from settings, maintain aspect ratio (only if values are valid)
			var inlineStyleParts = [];
			if (thumbnailWidth > 0) inlineStyleParts.push('max-width: ' + thumbnailWidth + 'px');
			if (thumbnailHeight > 0) inlineStyleParts.push('max-height: ' + thumbnailHeight + 'px');
			inlineStyleParts.push('height: auto');
			inlineStyleParts.push('display: block');
			var inlineStyles = inlineStyleParts.join('; ') + ';';

			for (var i = 0; i < images.length; i++) {
				var imageItem = images[i];
				// Support both object format {url, altText} and legacy string format
				var imageUrl = typeof imageItem === 'object' ? imageItem.url : imageItem;
				var imageAltText = typeof imageItem === 'object' && imageItem.altText ? imageItem.altText : altText;

				if (imageUrl) {
					// Strip query string
					imageUrl = imageUrl.split('?')[0];

					// Create full-size URL with width and height parameters for lightbox (only if values are valid)
					var fullImageParams = [];
					if (galleryWidth > 0) fullImageParams.push('w=' + galleryWidth);
					if (galleryHeight > 0) fullImageParams.push('h=' + galleryHeight);
					var fullImageUrl = fullImageParams.length > 0 ? imageUrl + '?' + fullImageParams.join('&') : imageUrl;

					// Create thumbnail URL with width and height parameters (only if values are valid)
					var thumbnailParams = [];
					if (thumbnailWidth > 0) thumbnailParams.push('w=' + thumbnailWidth);
					if (thumbnailHeight > 0) thumbnailParams.push('h=' + thumbnailHeight);
					var thumbnailUrl = thumbnailParams.length > 0 ? imageUrl + '?' + thumbnailParams.join('&') : imageUrl;

					html += '<div class="atdw-gallery-item" style="flex: 0 0 auto;">';
					html += '<a href="' + ATDWLive.escapeHtml(fullImageUrl) + '" class="glightbox atdw-gallery-link" data-gallery="' + galleryId + '" data-glightbox="description:' + ATDWLive.escapeHtml(imageAltText) + ';">';
					html += '<img src="' + ATDWLive.escapeHtml(thumbnailUrl) + '" alt="' + ATDWLive.escapeHtml(imageAltText) + '" style="' + inlineStyles + '" loading="lazy" />';
					html += '</a>';
					html += '</div>';
				}
			}

			html += '</div>';
		}

		return html;
	},


		/**
		 * Render single image
		 */
		renderImage: function(imageUrl, size, link, altText) {
			if (!imageUrl) {
				return '<p class="atdw-no-value">No image available</p>';
			}

			// Strip query string
			imageUrl = imageUrl.split('?')[0];

			// Get gallery settings from atdwLive config
			var galleryWidth = (atdwLive.galleryWidth || 600) + 'px';
			var galleryHeight = (atdwLive.galleryHeight || 400) + 'px';
			var thumbnailWidth = (atdwLive.thumbnailWidth || 150) + 'px';
			var thumbnailHeight = (atdwLive.thumbnailHeight || 150) + 'px';

			// Determine image styles based on size parameter
			var imageStyles = '';
			var displayUrl = imageUrl;

			if (size === 'thumbnail') {
				// Apply thumbnail dimensions from settings
				imageStyles = 'width: ' + thumbnailWidth + '; height: ' + thumbnailHeight + '; object-fit: cover;';
			} else if (size === 'full' || size === 'large') {
				// Apply full gallery dimensions from settings
				imageStyles = 'width: ' + galleryWidth + '; height: ' + galleryHeight + '; object-fit: cover;';
			} else {
				// Medium size (default) - use gallery dimensions
				imageStyles = 'width: ' + galleryWidth + '; height: ' + galleryHeight + '; object-fit: cover;';
			}

			var html = '<div class="atdw-meta-image">';
			if (link) {
				html += '<a href="' + ATDWLive.escapeHtml(imageUrl) + '" target="_blank" class="atdw-image-link">';
			}
			html += '<img src="' + ATDWLive.escapeHtml(displayUrl) + '" alt="' + ATDWLive.escapeHtml(altText) + '" style="' + imageStyles + '" />';
			if (link) {
				html += '</a>';
			}
			html += '</div>';
			return html;
		},

		/**
		 * Fetch products from API
		 */
		fetchProducts: function(endpoint, params, $container) {
			var $loading = $container.find('.atdw-live-loading');
			var $products = $container.find('.atdw-live-products');
			var $error = $container.find('.atdw-live-error');

			$loading.show();
			$products.empty();
			$error.hide();

			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: endpoint,
					params: params
				},
				success: function(response) {
					$loading.hide();

					if (response.success && response.data) {
						// Pass full response data including numberOfResults
						ATDWLive.displayProducts(response.data, $container);
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : atdwLive.strings.error;
						$error.find('p').text(errorMsg);
						$error.show();
						console.error('ATDW API Error:', response);
					}
				},
				error: function(xhr, status, error) {
					$loading.hide();
					$error.show();
					console.error('AJAX Error:', status, error);
				}
			});
		},

		/**
		 * Fetch single product from API
		 */
		fetchSingleProduct: function(endpoint, params, $container) {
			var $loading = $container.find('.atdw-live-loading');
			var $product = $container.find('.atdw-live-single-product');
			var $error = $container.find('.atdw-live-error');

			$loading.show();
			$product.empty();
			$error.hide();

			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: endpoint,
					params: params
				},
				success: function(response) {
					$loading.hide();

					if (response.success && response.data) {
						ATDWLive.displaySingleProduct(response.data, $product);
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : atdwLive.strings.error;
						$error.find('p').text(errorMsg);
						$error.show();
						console.error('ATDW API Error:', response);
					}
				},
				error: function(xhr, status, error) {
					$loading.hide();
					$error.show();
					console.error('AJAX Error:', status, error);
				}
			});
		},

		/**
		 * Display products in grid/list
		 */
		displayProducts: function(data, $container) {
			var $products = $container.find('.atdw-live-products');
			var products = data.products || data.product || [];
			var showImage = $container.data('show-image') !== 'no';
			var showExcerpt = $container.data('show-excerpt') !== 'no';
			var descriptionLength = parseInt($container.data('description-length')) || 60;
			var priceBefore = $container.data('price-before') || '';
			var priceAfter = $container.data('price-after') || '';
			var viewLabel = $container.data('view-label') || 'View Details';
			var viewSlug = $container.data('view-slug') || 'detail';

			// Handle single product in array
			if (!Array.isArray(products) && typeof products === 'object') {
				products = [products];
			}

			if (!products || products.length === 0) {
				$products.html('<p>' + atdwLive.strings.noProducts + '</p>');
				// Hide results count if no products
				$container.find('.atdw-results-count').hide();
				return;
			}

			// Display results count - use numberOfResults if available (total), otherwise count of products
			var totalResults = data.numberOfResults || products.length;
			var resultsText = totalResults === 1 ? 'listing' : 'listings';
			var $resultsCount = $container.find('.atdw-results-count');

			if ($resultsCount.length === 0) {
				// Create results count element if it doesn't exist
				$resultsCount = $('<div class="atdw-results-count"></div>');
				$products.before($resultsCount);
			}

		// Build results count HTML with inline navigation
		var currentPage = $container.data('current-page') || 1;
		var pageSize = $container.data('params').size || 12;
		var showPagination = $container.data('show-pagination') === 'yes';

		// Determine if there are more pages based on products returned
		// If we got a full page of results, there might be more
		var hasMore = products.length >= pageSize;

		// Check if we have previous or next pages
		var hasPrevious = currentPage > 1;
		var hasNext = hasMore;

		// If we have totalResults from API, use it to calculate exact hasNext
		if (data.numberOfResults && data.numberOfResults > 0) {
			var totalPages = Math.ceil(data.numberOfResults / pageSize);
			hasNext = currentPage < totalPages;
		}

		var resultsHtml = '<span class="atdw-results-text">Found <strong>' + totalResults + '</strong> ' + resultsText + '</span>';

		// Add inline prev/next buttons if pagination is enabled and there are pages to navigate
		if (showPagination && (hasPrevious || hasNext)) {
			resultsHtml += '<span class="atdw-results-nav">';

			// Previous button - only show if there are previous pages
			if (hasPrevious) {
				resultsHtml += '<button class="atdw-results-prev" data-page="' + (currentPage - 1) + '">Previous</button>';
			}

			// Next button - only show if there are next pages
			if (hasNext) {
				resultsHtml += '<button class="atdw-results-next" data-page="' + (currentPage + 1) + '">Next</button>';
			}

			resultsHtml += '</span>';
		}

		$resultsCount.html(resultsHtml).show();

		// Attach click handlers to inline navigation buttons
		$resultsCount.find('.atdw-results-prev, .atdw-results-next').on('click', function() {
			var page = parseInt($(this).data('page'));
			if ($container.data('is-search-result')) {
				ATDWLive.loadSearchPage($container, page);
			} else {
				ATDWLive.loadPage($container, page);
			}
		});

			var html = '';

			$.each(products, function(index, product) {
				html += ATDWLive.renderProductCard(product, showImage, showExcerpt, descriptionLength, priceBefore, priceAfter, viewLabel, viewSlug);
			});

			$products.html(html);
		},

		/**
		 * Display single product
		 */
		displaySingleProduct: function(data, $product) {
			var product = data.product || data;

			if (!product) {
				$product.html('<p>' + atdwLive.strings.error + '</p>');
				return;
			}

			var html = ATDWLive.renderSingleProduct(product);
			$product.html(html);
		},

		/**
		 * Render product card HTML
		 */
		renderProductCard: function(product, showImage, showExcerpt, descriptionLength, priceBefore, priceAfter, viewLabel, viewSlug) {
			// Get product ID
			var productId = product.productId || product.id || '';

			// Default values
			viewLabel = viewLabel || 'View Details';
			viewSlug = viewSlug || 'detail';

			var html = '<div class="atdw-product-card" data-product-id="' + this.escapeHtml(productId) + '">';

			// Image - check for productImage first, then fallback to images array
			if (showImage) {
				var imageUrl = null;

				// Priority 1: productImage from API
				if (product.productImage) {
					imageUrl = product.productImage;
				}
				// Priority 2: images array
				else if (product.images && product.images.length > 0) {
					imageUrl = product.images[0];
				}

				if (imageUrl) {
					// Strip query string (everything after ?)
					imageUrl = imageUrl.split('?')[0];

					// Append width and height parameters for listing images (only if values are valid)
					var listingWidth = atdwLive.listingWidth || 400;
					var listingHeight = atdwLive.listingHeight || 300;
					var imageParams = [];
					if (listingWidth > 0) imageParams.push('w=' + listingWidth);
					if (listingHeight > 0) imageParams.push('h=' + listingHeight);
					var imageUrlWithParams = imageParams.length > 0 ? imageUrl + '?' + imageParams.join('&') : imageUrl;

					html += '<div class="atdw-product-image">';
					html += '<img src="' + this.escapeHtml(imageUrlWithParams) + '" alt="' + this.escapeHtml(product.productName || '') + '" loading="lazy" />';

					// Add price overlay if rate_from exists
					if (product.rate_from || product.rateFrom) {
						var price = product.rate_from || product.rateFrom;
						var priceDisplay = '';

						// Add price_before text
						if (priceBefore) {
							priceDisplay += this.escapeHtml(priceBefore);
						}

						// Add dollar sign and price
						priceDisplay += '$' + this.escapeHtml(price);

						// Add price_after text
						if (priceAfter) {
							priceDisplay += this.escapeHtml(priceAfter);
						}

						html += '<div class="atdw-product-price">' + priceDisplay + '</div>';
					}

					html += '</div>';
				}
			}

			// Content
			html += '<div class="atdw-product-content">';
			html += '<h3 class="atdw-product-title">' + this.escapeHtml(product.productName || product.name || '') + '</h3>';

			// Date - use nextOccurrence if available, otherwise startDate
			var dateValue = product.nextOccurrence || product.startDate;
			if (dateValue) {
				html += '<div class="atdw-product-date">';
				html += this.escapeHtml(dateValue);
				html += '</div>';
			}

			if (showExcerpt && product.productDescription) {
				// Use word-based truncation with descriptionLength parameter
				var words = product.productDescription.split(/\s+/);
				var wordLimit = descriptionLength || 60; // Default to 60 words if not specified
				var excerpt = '';

				if (words.length > wordLimit) {
					excerpt = words.slice(0, wordLimit).join(' ') + '...';
				} else {
					excerpt = product.productDescription;
				}

				html += '<div class="atdw-product-excerpt">' + this.escapeHtml(excerpt) + '</div>';
			}

			// Location
			if (product.address || product.locality || product.area) {
				html += '<div class="atdw-product-location">';
				html += '<span class="dashicons dashicons-location"></span> ';
				html += this.escapeHtml(product.locality || product.area || product.address || '');
				html += '</div>';
			}

			// Footer with link
			html += '<div class="atdw-product-footer">';
			if (productId) {
				var listingUrl;

				// If view_slug is provided, use it as the base URL
				if (viewSlug) {
					// Remove leading/trailing slashes and construct URL
					var slug = viewSlug.replace(/^\/+|\/+$/g, '');
					listingUrl = window.location.origin + '/' + slug + '?id=' + encodeURIComponent(productId);
				} else {
					// Use current page URL without query string
					listingUrl = window.location.origin + window.location.pathname + '?id=' + encodeURIComponent(productId);
				}

				html += '<a href="' + this.escapeHtml(listingUrl) + '" class="atdw-button atdw-view-listing">' + this.escapeHtml(viewLabel) + '</a>';
			}
			html += '</div>';

			html += '</div>'; // content
			html += '</div>'; // card

			return html;
		},

		/**
		 * Render single product HTML
		 */
		renderSingleProduct: function(product) {
			var html = '<div class="atdw-single-product">';

			// Featured image
			if (product.images && product.images.length > 0) {
				var imageUrl = product.images[0];
				// Strip query string (everything after ?)
				imageUrl = imageUrl.split('?')[0];

				// Use gallery dimensions for single product featured image (larger than listing)
				var galleryWidth = atdwLive.galleryWidth || 600;
				var galleryHeight = atdwLive.galleryHeight || 400;
				var imageParams = [];
				if (galleryWidth > 0) imageParams.push('w=' + galleryWidth);
				if (galleryHeight > 0) imageParams.push('h=' + galleryHeight);
				var imageUrlWithParams = imageParams.length > 0 ? imageUrl + '?' + imageParams.join('&') : imageUrl;

				html += '<div class="atdw-product-featured-image">';
				html += '<img src="' + this.escapeHtml(imageUrlWithParams) + '" alt="' + this.escapeHtml(product.productName || '') + '" />';
				html += '</div>';
			}

			// Title
			html += '<h2 class="atdw-product-title">' + this.escapeHtml(product.productName || product.name || '') + '</h2>';

			// Description
			if (product.productDescription) {
				html += '<div class="atdw-product-description">' + this.escapeHtml(product.productDescription) + '</div>';
			}

			// Meta information
			html += '<div class="atdw-product-meta">';

			if (product.address) {
				html += '<div class="atdw-meta-item"><strong>Address:</strong> ' + this.escapeHtml(product.address) + '</div>';
			}

			if (product.phone) {
				html += '<div class="atdw-meta-item"><strong>Phone:</strong> <a href="tel:' + product.phone + '">' + this.escapeHtml(product.phone) + '</a></div>';
			}

			if (product.email) {
				html += '<div class="atdw-meta-item"><strong>Email:</strong> <a href="mailto:' + product.email + '">' + this.escapeHtml(product.email) + '</a></div>';
			}

			if (product.websiteUrl) {
				var websiteUrl = ATDWLive.ensureUrlProtocol(product.websiteUrl);
				html += '<div class="atdw-meta-item"><strong>Website:</strong> <a href="' + this.escapeHtml(websiteUrl) + '" target="_blank">' + this.escapeHtml(product.websiteUrl) + '</a></div>';
			}

			html += '</div>'; // meta
			html += '</div>'; // single-product

			return html;
		},

		/**
		 * Load product types from API
		 */
		loadProductTypes: function($typesRow, cats) {
			var $checkboxes = $typesRow.find('.atdw-types-checkboxes');
			var $loading = $checkboxes.find('.atdw-types-loading');

			// Build API URL
			var apiUrl = atdwLive.apiBaseUrl + '/producttypes';
			var params = {
				key: atdwLive.apiKey,
				out: atdwLive.outputFormat || 'json',
				mv: (atdwLive.marketVariant || 'ENGLISH') + '-T',
				cats: cats
			};

			// Make direct API call
			$.ajax({
				url: apiUrl,
				type: 'GET',
				data: params,
				dataType: 'json',
				success: function(response) {
					$loading.hide();

					// Handle the response - ATDW API returns an array of product types
					var productTypes = response.product_type || response.productTypes || response || [];

					if (!Array.isArray(productTypes)) {
						console.error('Unexpected API response format:', response);
						$checkboxes.html('<p style="padding: 10px;">Error loading types.</p>');
						return;
					}

					if (productTypes.length === 0) {
						$checkboxes.html('<p style="padding: 10px;">No types available.</p>');
						return;
					}

					// Build checkboxes HTML
					var html = '';
					$.each(productTypes, function(index, type) {
						// Use ProductTypeId as value and Description as label
						var typeId = type.ProductTypeId || type.productTypeId || type.id;
						var description = type.Description || type.description || type.name || typeId;

						if (typeId) {
							html += '<label class="atdw-type-checkbox">';
							html += '<input type="checkbox" value="' + ATDWLive.escapeHtml(typeId) + '" class="atdw-type-option">';
							html += '<span>' + ATDWLive.escapeHtml(description) + '</span>';
							html += '</label>';
						}
					});

					$checkboxes.html(html);
				},
				error: function(xhr, status, error) {
					$loading.hide();
					console.error('Error loading product types:', status, error);
					$checkboxes.html('<p style="padding: 10px;">Error loading types. Please try again.</p>');
				}
			});
		},

		/**
		 * Load categories for filter
		 */
		loadCategories: function($container) {
			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: '/categories',
					params: {}
				},
				success: function(response) {
					if (response.success && response.data && response.data.categories) {
						var $select = $container.find('.atdw-filter-categories');
						$.each(response.data.categories, function(index, category) {
							$select.append('<option value="' + category.id + '">' + category.name + '</option>');
						});
					}
				}
			});
		},

		/**
		 * Load regions for filter
		 */
		loadRegions: function($container) {
			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: '/regions',
					params: {}
				},
				success: function(response) {
					if (response.success && response.data && response.data.regions) {
						var $select = $container.find('.atdw-filter-regions');
						$.each(response.data.regions, function(index, region) {
							$select.append('<option value="' + region.id + '">' + region.name + '</option>');
						});
					}
				}
			});
		},

		/**
		 * Perform search
		 */
		performSearch: function($container) {
			// Find the target products container on the page
			var $targetContainer = $('.atdw-live-container').first();

			if ($targetContainer.length === 0) {
				console.error('No .atdw-live-container found on page for search results');
				return;
			}

			// Start with existing container parameters (from shortcode attributes)
			var containerParams = $targetContainer.data('params') || {};
			var params = {};

			// Copy existing parameters from container
			if (containerParams.st) params.st = containerParams.st;
			if (containerParams.cats) params.cats = containerParams.cats;
			if (containerParams.area) params.area = containerParams.area;
			if (containerParams.ptype) params.ptype = containerParams.ptype;
			if (containerParams.loc) params.loc = containerParams.loc;
			if (containerParams.fl) params.fl = containerParams.fl;
			if (containerParams.ct) params.ct = containerParams.ct;

			// Use container's default sort order if set
			if (containerParams.order) params.order = ATDWLive.mapSortOrder(containerParams.order);

			// Use container's size or default to 20
			params.size = parseInt(containerParams.size) || 20;

			// Get search keyword (overrides any existing query)
			var searchTerm = $container.find('.atdw-search-input').val();
			if (searchTerm) {
				params.term = searchTerm;  // Use term parameter for keyword search
			}

			// Get suburb/city filter from dropdown (overrides container ct)
			var suburb = $container.find('.atdw-filter-suburb').val();
			if (suburb) {
				params.ct = suburb;  // Use ct parameter for ATDW API
			}

			// Get sort option from search form (overrides container default sort)
			var sortBy = $container.find('.atdw-filter-sort').val();
			if (sortBy) {
				// Use order parameter for ATDW API sorting
				params.order = ATDWLive.mapSortOrder(sortBy);
			}

			// Get selected types (ProductTypeId values) - overrides container ptype
			var selectedTypes = [];
			$container.find('.atdw-type-option:checked').each(function() {
				selectedTypes.push($(this).val());
			});
			if (selectedTypes.length > 0) {
				params.cla = selectedTypes.join(',');  // Use cla parameter for product type classifications
			}

			// Show loading and clear previous results
			var $loading = $targetContainer.find('.atdw-live-loading');
			var $products = $targetContainer.find('.atdw-live-products');
			var $error = $targetContainer.find('.atdw-live-error');
			var $resultsCount = $targetContainer.find('.atdw-results-count');
			var $pagination = $targetContainer.find('.atdw-pagination');

			$loading.show();
			$products.empty();
			$error.hide();
			if ($resultsCount.length > 0) {
				$resultsCount.hide();
			}
			// Hide pagination during search
			if ($pagination.length > 0) {
				$pagination.hide();
			}

			// Fetch products
			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: '/products',
					params: params
				},
				success: function(response) {
					$loading.hide();

					if (response.success && response.data) {
						// Pass full response data including numberOfResults
						// displayProducts will respect container's layout, columns, show-image, show-excerpt settings
						ATDWLive.displayProducts(response.data, $targetContainer);

						// For search results, replace pagination with search-specific pagination
						if ($targetContainer.data('show-pagination') === 'yes') {
							var products = response.data.products || response.data.product || [];
							if (!Array.isArray(products)) {
								products = [products];
							}

							// Store search params for pagination
							$targetContainer.data('search-params', params);
							$targetContainer.data('is-search-result', true);
							$targetContainer.data('search-current-page', 1);

							// Check if there are more results
							var hasMore = products.length >= params.size;

							// Update pagination for search results
							ATDWLive.updateSearchPagination($targetContainer, 1, hasMore, params.size);
						}
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : atdwLive.strings.error;
						$error.find('p').text(errorMsg);
						$error.show();
					}
				},
				error: function() {
					$loading.hide();
					$error.show();
				}
			});
		},

		/**
		 * Initialize pagination controls
		 */
		initPagination: function($container) {
			var $pagination = $container.find('.atdw-pagination');
			var $prevBtn = $pagination.find('.atdw-prev-page');
			var $nextBtn = $pagination.find('.atdw-next-page');

			// Previous page button
			$prevBtn.on('click', function() {
				// Check if this is a search result
				if ($container.data('is-search-result')) {
					var currentPage = parseInt($container.data('search-current-page')) || 1;
					if (currentPage > 1) {
						ATDWLive.loadSearchPage($container, currentPage - 1);
					}
				} else {
					var currentPage = parseInt($container.data('current-page')) || 1;
					if (currentPage > 1) {
						ATDWLive.loadPage($container, currentPage - 1);
					}
				}
			});

			// Next page button
			$nextBtn.on('click', function() {
				// Check if this is a search result
				if ($container.data('is-search-result')) {
					var currentPage = parseInt($container.data('search-current-page')) || 1;
					ATDWLive.loadSearchPage($container, currentPage + 1);
				} else {
					var currentPage = parseInt($container.data('current-page')) || 1;
					ATDWLive.loadPage($container, currentPage + 1);
				}
			});
		},

		/**
		 * Load a specific page using ATDW API pagination
		 */
		loadPage: function($container, page) {
			var params = $container.data('params');
			var apiParams = {};

			// Copy valid ATDW API parameters
			if (params.st) apiParams.st = params.st;
			if (params.cats) apiParams.cats = params.cats;
			if (params.area) apiParams.area = params.area;
			if (params.ptype) apiParams.ptype = params.ptype;
			if (params.loc) apiParams.loc = params.loc;
			if (params.fl) apiParams.fl = params.fl;
			if (params.ct) apiParams.ct = params.ct;
			if (params.order) apiParams.order = ATDWLive.mapSortOrder(params.order);  // Sort order with mapping

			// Add pagination parameters - using ATDW API format
			apiParams.size = parseInt(params.size) || 12;  // Dynamic size from shortcode
			apiParams.pge = parseInt(page) || 1;           // Page number

			// Fetch products for this page
			ATDWLive.fetchPagedProducts($container, '/products', apiParams, page);
		},

		/**
		 * Fetch products for a specific page
		 */
		fetchPagedProducts: function($container, endpoint, params, page) {
			var $loading = $container.find('.atdw-live-loading');
			var $products = $container.find('.atdw-live-products');
			var $error = $container.find('.atdw-live-error');
			var $pagination = $container.find('.atdw-pagination');
			var $resultsCount = $container.find('.atdw-results-count');

			$loading.show();
			$products.empty();
			$error.hide();
			$pagination.hide();
			$resultsCount.hide();

			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: endpoint,
					params: params
				},
				success: function(response) {
					$loading.hide();

					if (response.success && response.data) {
						var products = response.data.products || response.data.product || [];

						// Handle single product as array
						if (!Array.isArray(products)) {
							products = [products];
						}

						// Store total results for pagination info
						if (response.data.numberOfResults) {
							$container.data('total-results', response.data.numberOfResults);
						}

						// Update current page
						$container.data('current-page', page);

						// Display products with full response data (including numberOfResults)
						ATDWLive.displayProducts(response.data, $container);

						// Update pagination display
						// If we got less than requested size, we're on the last page
						var hasMore = products.length >= params.size;
						ATDWLive.updatePaginationDisplay($container, hasMore);
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : atdwLive.strings.error;
						$error.find('p').text(errorMsg);
						$error.show();
						// Hide results count on error
						$container.find('.atdw-results-count').hide();
						console.error('ATDW API Error:', response);
					}
				},
				error: function(xhr, status, error) {
					$loading.hide();
					$error.show();
					console.error('AJAX Error:', status, error);
				}
			});
		},


		/**
		 * Update pagination display
		 */
	/**
	 * Update pagination display
	 */
	updatePaginationDisplay: function($container, hasMore) {
		var $pagination = $container.find('.atdw-pagination');
		var currentPage = $container.data('current-page') || 1;
		var pageSize = $container.data('params').size || 12;
		var totalResults = $container.data('total-results') || 0;
		var paginationType = $container.data('pagination-type') || 'number';

		// Clear existing pagination content
		$pagination.empty();

		// Hide pagination if total results <= page size OR if on page 1 with no more pages
		if (totalResults > 0 && totalResults <= pageSize) {
			$pagination.hide();
			return;
		} else if (currentPage === 1 && !hasMore) {
			$pagination.hide();
			return;
		}

		if (paginationType === 'prevnext') {
			// Render prev/next style pagination
			var prevDisabled = currentPage <= 1 ? ' disabled' : '';
			var nextDisabled = !hasMore ? ' disabled' : '';

			var html = '<button class="atdw-prev-page"' + prevDisabled + '>' + 'Previous' + '</button>';
			html += '<span class="atdw-page-info">Page <span class="atdw-current-page">' + currentPage + '</span></span>';
			html += '<button class="atdw-next-page"' + nextDisabled + '>' + 'Next' + '</button>';

			$pagination.html(html).show();

			// Attach click handlers for prev/next buttons
			$pagination.find('.atdw-prev-page').on('click', function() {
				if (currentPage > 1) {
					ATDWLive.loadPage($container, currentPage - 1);
				}
			});

			$pagination.find('.atdw-next-page').on('click', function() {
				if (hasMore) {
					ATDWLive.loadPage($container, currentPage + 1);
				}
			});
		} else {
			// Render number style pagination (inline numbers without prev/next)
			var html = '<div class="atdw-page-numbers">';

			// Calculate total number of pages based on results
			var totalPages = 0;
			if (totalResults > 0 && pageSize > 0) {
				totalPages = Math.ceil(totalResults / pageSize);
			} else if (hasMore) {
				// If we don't know total results but hasMore is true, estimate
				totalPages = currentPage + 1; // At least one more page
			} else {
				// Current page is the last page
				totalPages = currentPage;
			}

			// Calculate page range to display (show 5 pages at a time)
			var maxPagesToShow = 5;
			var startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
			var endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

			// Adjust startPage if we're near the end
			if (endPage - startPage + 1 < maxPagesToShow) {
				startPage = Math.max(1, endPage - maxPagesToShow + 1);
			}

			// Show previous pages if available
			if (currentPage > 1) {
				for (var i = startPage; i < currentPage; i++) {
					html += '<button class="atdw-page-number" data-page="' + i + '">' + i + '</button>';
				}
			}

			// Current page (highlighted)
			html += '<button class="atdw-page-number atdw-current" data-page="' + currentPage + '">' + currentPage + '</button>';

			// Show next pages if available
			for (var i = currentPage + 1; i <= endPage; i++) {
				html += '<button class="atdw-page-number" data-page="' + i + '">' + i + '</button>';
			}

			html += '</div>';
			$pagination.html(html).show();

			// Attach click handlers to page number buttons
			$pagination.find('.atdw-page-number').on('click', function() {
				var page = parseInt($(this).data('page'));
				if (page !== currentPage) {
					ATDWLive.loadPage($container, page);
				}
			});
		}
	},

		/**
		 * Load search results page
		 */
		loadSearchPage: function($container, page) {
			var searchParams = $container.data('search-params');
			if (!searchParams) {
				console.error('No search params found');
				return;
			}

			// Update page number in search params
			searchParams.pge = parseInt(page) || 1;

			var $loading = $container.find('.atdw-live-loading');
			var $products = $container.find('.atdw-live-products');
			var $error = $container.find('.atdw-live-error');
			var $pagination = $container.find('.atdw-pagination');
			var $resultsCount = $container.find('.atdw-results-count');

			$loading.show();
			$products.empty();
			$error.hide();
			$pagination.hide();
			$resultsCount.hide();

			// Fetch products with search params
			$.ajax({
				url: atdwLive.ajaxUrl,
				type: 'POST',
				data: {
					action: 'atdw_live_fetch',
					nonce: atdwLive.nonce,
					endpoint: '/products',
					params: searchParams
				},
				success: function(response) {
					$loading.hide();

					if (response.success && response.data) {
						var products = response.data.products || response.data.product || [];
						if (!Array.isArray(products)) {
							products = [products];
						}

				// Store total results for pagination info
				if (response.data.numberOfResults) {
					$container.data('total-results', response.data.numberOfResults);
				}

				// Update current page BEFORE displaying products (so inline nav buttons get correct page)
						$container.data('search-current-page', page);
				$container.data('current-page', page);

				// Display products
						ATDWLive.displayProducts(response.data, $container);

						// Check if there are more results
						var hasMore = products.length >= searchParams.size;

						// Update pagination
						ATDWLive.updateSearchPagination($container, page, hasMore, searchParams.size);
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : atdwLive.strings.error;
						$error.find('p').text(errorMsg);
						$error.show();
						$container.find('.atdw-results-count').hide();
					}
				},
				error: function(xhr, status, error) {
					$loading.hide();
					$error.show();
					console.error('AJAX Error:', status, error);
				}
			});
		},

		/**
		 * Update pagination for search results
		 */
	updateSearchPagination: function($container, currentPage, hasMore, pageSize) {
		var $pagination = $container.find('.atdw-pagination');
		var paginationType = $container.data('pagination-type') || 'number';

		// Clear existing pagination content
		$pagination.empty();

		// Always show pagination for search results if there's more than one page
		if (currentPage === 1 && !hasMore) {
			// Only one page of results
			$pagination.hide();
			return;
		}

		if (paginationType === 'prevnext') {
			// Render prev/next style pagination
			var prevDisabled = currentPage <= 1 ? ' disabled' : '';
			var nextDisabled = !hasMore ? ' disabled' : '';

			var html = '<button class="atdw-prev-page"' + prevDisabled + '>' + 'Previous' + '</button>';
			html += '<span class="atdw-page-info">Page <span class="atdw-current-page">' + currentPage + '</span></span>';
			html += '<button class="atdw-next-page"' + nextDisabled + '>' + 'Next' + '</button>';

			$pagination.html(html).show();

			// Attach click handlers for prev/next buttons
			$pagination.find('.atdw-prev-page').on('click', function() {
				if (currentPage > 1) {
					ATDWLive.loadSearchPage($container, currentPage - 1);
				}
			});

			$pagination.find('.atdw-next-page').on('click', function() {
				if (hasMore) {
					ATDWLive.loadSearchPage($container, currentPage + 1);
				}
			});
		} else {
			// Render number style pagination (inline numbers without prev/next)
			var html = '<div class="atdw-page-numbers">';

			// Calculate total number of pages based on results
			var totalPages = 0;
			var totalResults = $container.data('total-results') || 0;
			if (totalResults > 0 && pageSize > 0) {
				totalPages = Math.ceil(totalResults / pageSize);
			} else if (hasMore) {
				// If we don't know total results but hasMore is true, estimate
				totalPages = currentPage + 1; // At least one more page
			} else {
				// Current page is the last page
				totalPages = currentPage;
			}

			// Calculate page range to display (show 5 pages at a time)
			var maxPagesToShow = 5;
			var startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
			var endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

			// Adjust startPage if we're near the end
			if (endPage - startPage + 1 < maxPagesToShow) {
				startPage = Math.max(1, endPage - maxPagesToShow + 1);
			}

			// Show previous pages if available
			if (currentPage > 1) {
				for (var i = startPage; i < currentPage; i++) {
					html += '<button class="atdw-page-number" data-page="' + i + '">' + i + '</button>';
				}
			}

			// Current page (highlighted)
			html += '<button class="atdw-page-number atdw-current" data-page="' + currentPage + '">' + currentPage + '</button>';

			// Show next pages if available
			for (var i = currentPage + 1; i <= endPage; i++) {
				html += '<button class="atdw-page-number" data-page="' + i + '">' + i + '</button>';
			}

			html += '</div>';
			$pagination.html(html).show();

			// Attach click handlers to page number buttons
			$pagination.find('.atdw-page-number').on('click', function() {
				var page = parseInt($(this).data('page'));
				if (page !== currentPage) {
					ATDWLive.loadSearchPage($container, page);
				}
			});
		}
	},

		/**
		 * Map sort parameter to ATDW API order value
		 */
		mapSortOrder: function(sortValue) {
			if (!sortValue) return '';

			// Map user-friendly sort values to ATDW API order values
			var sortMap = {
				'date': 'next_occurrence asc',
				'price_asc': 'rate_from asc',
				'price_desc': 'rate_from desc'
			};

			// Return mapped value if exists, otherwise return original value
			return sortMap[sortValue] || sortValue;
		},

		/**
		 * Escape HTML to prevent XSS
		 */
		escapeHtml: function(text) {
			if (!text) return '';
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		},

		/**
		 * Convert newlines to <br> tags
		 * Use this after escapeHtml() to preserve line breaks in text
		 */
		nl2br: function(text) {
			if (!text) return '';
			return String(text).replace(/\n/g, '<br>');
		},

	/**
	 * Initialize or reinitialize GLightbox for dynamically loaded content
	 */
	initGLightbox: function() {
		if (typeof GLightbox !== 'undefined') {
			// Destroy existing instance if it exists
			if (window.atdwLightbox) {
				window.atdwLightbox.destroy();
			}

			// Create new instance
			window.atdwLightbox = GLightbox({
				touchNavigation: true,
				loop: true,
				autoplayVideos: true
			});
		}
	},

	/**
	 * Extract formatted address from addresses array
	 * Combines addressLine1, cityName, stateName, and addressPostalCode
	 */
	extractFormattedAddress: function(product) {
		if (!product.addresses || !Array.isArray(product.addresses) || product.addresses.length === 0) {
			return null;
		}

		var address = product.addresses[0];
		var parts = [];

		// Add addressLine1 with comma if present
		if (address.addressLine1) {
			parts.push(address.addressLine1 + ',');
		}
		if (address.cityName) parts.push(address.cityName);
		if (address.stateName) parts.push(address.stateName);
		if (address.addressPostalCode) parts.push(address.addressPostalCode);

		return parts.length > 0 ? parts.join(' ') : null;
	},

	/**
	 * Extract map coordinates from addresses array
	 * Returns object with latitude and longitude
	 */
	extractMapCoordinates: function(product) {
		if (!product.addresses || !Array.isArray(product.addresses) || product.addresses.length === 0) {
			return null;
		}

		var address = product.addresses[0];

		if (address.geocodeGdaLatitude && address.geocodeGdaLongitude) {
			return {
				latitude: address.geocodeGdaLatitude,
				longitude: address.geocodeGdaLongitude
			};
		}

		return null;
	},

	/**
	 * Extract gallery images from multimedia array
	 * Gets first occurrence of each unique multimediaId, extracts serverPath and altText, and removes query string
	 * Only includes items where attributeIdMultimediaContent is 'IMAGE'
	 */
	extractGalleryFromMultimedia: function(product) {
		if (!product.multimedia || !Array.isArray(product.multimedia) || product.multimedia.length === 0) {
			return null;
		}

		var seenIds = {};
		var gallery = [];

		for (var i = 0; i < product.multimedia.length; i++) {
			var item = product.multimedia[i];

			// Check if this multimedia item has multimediaId, serverPath, and is an IMAGE
			if (item.multimediaId && item.serverPath && item.attributeIdMultimediaContent === 'IMAGE') {
				// Only add if we haven't seen this multimediaId before (first occurrence only)
				if (!seenIds[item.multimediaId]) {
					seenIds[item.multimediaId] = true;

					// Extract serverPath and remove query string (everything after ?)
					var cleanPath = item.serverPath.split('?')[0];

					// Store both URL and altText
					gallery.push({
						url: cleanPath,
						altText: item.altText || ''
					});
				}
			}
		}

		return gallery.length > 0 ? gallery : null;
	},

	/**
	 * Extract phone number from communication array
	 * Looks for attributeIdCommunication === 'CAPHENQUIR'
	 */
	extractPhoneFromCommunication: function(product) {
		if (!product.communication || !Array.isArray(product.communication)) {
			return null;
		}

		var phoneEntry = product.communication.find(function(item) {
			return item.attributeIdCommunication === 'CAPHENQUIR';
		});

		return phoneEntry ? phoneEntry.communicationDetail : null;
	},

	/**
	 * Extract email from communication array
	 * Looks for attributeIdCommunication === 'CAEMENQUIR'
	 */
	extractEmailFromCommunication: function(product) {
		if (!product.communication || !Array.isArray(product.communication)) {
			return null;
		}

		var emailEntry = product.communication.find(function(item) {
			return item.attributeIdCommunication === 'CAEMENQUIR';
		});

		return emailEntry ? emailEntry.communicationDetail : null;
	},

	/**
	 * Extract website URL from communication array
	 * Looks for attributeIdCommunication === 'CAURENQUIR'
	 */
	extractWebsiteFromCommunication: function(product) {
		if (!product.communication || !Array.isArray(product.communication)) {
			return null;
		}

		var websiteEntry = product.communication.find(function(item) {
			return item.attributeIdCommunication === 'CAURENQUIR';
		});

		return websiteEntry ? websiteEntry.communicationDetail : null;
	},

	/**
	 * Extract attributes by type ID from attributes array
	 * Filters by attributeTypeId and returns array of attributeIdDescription values
	 */
	extractAttributesByType: function(product, attributeTypeId) {
		if (!product.attributes || !Array.isArray(product.attributes) || product.attributes.length === 0) {
			return null;
		}

		if (!attributeTypeId) {
			return null;
		}

		var descriptions = [];

		for (var i = 0; i < product.attributes.length; i++) {
			var attr = product.attributes[i];

			// Match attributeTypeId and extract attributeIdDescription
			if (attr.attributeTypeId === attributeTypeId && attr.attributeIdDescription) {
				descriptions.push(attr.attributeIdDescription);
			}
		}

		return descriptions.length > 0 ? descriptions : null;
	},

	/**
	 * Ensure URL has http:// or https:// protocol
	 * If URL doesn't start with a protocol, adds https://
	 */
	ensureUrlProtocol: function(url) {
		if (!url) return url;

		// Trim whitespace
		url = String(url).trim();

		// Check if URL already has a protocol
		if (/^https?:\/\//i.test(url)) {
			return url;
		}

		// Add https:// if no protocol present
		return 'https://' + url;
	},

	/**
	 * Initialize Google Maps JavaScript API
	 * Creates interactive map with controls and map/satellite toggle
	 */
	initGoogleMap: function(mapId, latitude, longitude, zoom, productName, apiKey) {
		// Queue this map for initialization
		if (!window.atdwPendingMaps) {
			window.atdwPendingMaps = [];
		}
		window.atdwPendingMaps.push({
			id: mapId,
			lat: latitude,
			lng: longitude,
			zoom: zoom,
			name: productName
		});

		// Load Google Maps API if not already loaded
		if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
			// Check if script is already being loaded
			if (!window.atdwGoogleMapsLoading) {
				window.atdwGoogleMapsLoading = true;

				// Define callback function globally
				window.initAtdwMaps = function() {
					window.atdwGoogleMapsLoaded = true;
					window.atdwGoogleMapsLoading = false;
					// Initialize all pending maps
					if (window.atdwPendingMaps) {
						window.atdwPendingMaps.forEach(function(mapData) {
							ATDWLive.createMap(mapData.id, mapData.lat, mapData.lng, mapData.zoom, mapData.name);
						});
						window.atdwPendingMaps = [];
					}
				};

				var script = document.createElement('script');
				script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&loading=async&callback=initAtdwMaps&libraries=marker';
				script.async = true;
				script.defer = true;
				document.head.appendChild(script);
			}
			// If already loading, the callback will handle all pending maps
		} else {
			// Google Maps API is already loaded, initialize immediately
			ATDWLive.createMap(mapId, latitude, longitude, zoom, productName);
		}
	},

	/**
	 * Create Google Map instance with controls
	 * Uses AdvancedMarkerElement for better performance and features
	 */
	createMap: function(mapId, latitude, longitude, zoom, productName) {
		// Wait for DOM element to be available
		setTimeout(function() {
			var mapElement = document.getElementById(mapId);
			if (!mapElement) {
				console.error('ATDW: Map element not found:', mapId);
				return;
			}

			// Ensure the map container has explicit height
			if (!mapElement.style.height || mapElement.style.height === '0px' || mapElement.style.height === '') {
				mapElement.style.height = '400px';
			}
			mapElement.style.display = 'block';

			var lat = parseFloat(latitude);
			var lng = parseFloat(longitude);
			var zoomLevel = parseInt(zoom) || 15;

			// Create map with controls and mapId for advanced markers
			var map = new google.maps.Map(mapElement, {
				center: { lat: lat, lng: lng },
				zoom: zoomLevel,
				mapId: 'ATDW_MAP_' + mapId, // Required for AdvancedMarkerElement
				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
					position: google.maps.ControlPosition.TOP_RIGHT,
					mapTypeIds: ['roadmap', 'satellite', 'hybrid', 'terrain']
				},
				zoomControl: true,
				zoomControlOptions: {
					position: google.maps.ControlPosition.RIGHT_CENTER
				},
				scaleControl: true,
				streetViewControl: true,
				streetViewControlOptions: {
					position: google.maps.ControlPosition.RIGHT_CENTER
				},
				fullscreenControl: true,
				fullscreenControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				}
			});

			// Create marker using AdvancedMarkerElement if available, fallback to standard Marker
			if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
				// Use new AdvancedMarkerElement
				new google.maps.marker.AdvancedMarkerElement({
					position: { lat: lat, lng: lng },
					map: map,
					title: productName
				});
			} else {
				// Fallback to standard Marker (for older API versions)
				new google.maps.Marker({
					position: { lat: lat, lng: lng },
					map: map,
					title: productName
				});
			}
		}, 100);
	}
	};

	// Initialize on document ready
	$(document).ready(function() {
		ATDWLive.init();

		// Initialize GLightbox for all galleries
		if (typeof GLightbox !== 'undefined') {
			ATDWLive.initGLightbox(); // Initialize GLightbox using our wrapper
		}
	});

})(jQuery);
