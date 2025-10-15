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

			// Try to use the exact API field name first (case-sensitive)
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

			if (!value) {
				$content.html('<p class="atdw-no-value">No ' + ATDWLive.escapeHtml(metaKey) + ' available</p>');
				return;
			}

			var html = before;

			// Handle different field types
			if (metaKey.toLowerCase() === 'gallery' || metaKey.toLowerCase() === 'images') {
				// Gallery display
				html += ATDWLive.renderGallery(value, size, columns, link, product.productName || '');
			} else if (metaKey.toLowerCase() === 'image' || metaKey.toLowerCase() === 'featured_image') {
				// Single image
				html += ATDWLive.renderImage(value, size, link, product.productName || '');
			} else if (metaKey.toLowerCase() === 'phone') {
				// Phone with link
				if (format === 'html') {
					html += '<a href="tel:' + ATDWLive.escapeHtml(value) + '" class="atdw-meta-phone">' + ATDWLive.escapeHtml(value) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'email') {
				// Email with link
				if (format === 'html') {
					html += '<a href="mailto:' + ATDWLive.escapeHtml(value) + '" class="atdw-meta-email">' + ATDWLive.escapeHtml(value) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
				}
			} else if (metaKey.toLowerCase() === 'website' || fieldName === 'websiteUrl') {
				// Website with link
				if (format === 'html') {
					html += '<a href="' + ATDWLive.escapeHtml(value) + '" target="_blank" rel="noopener noreferrer" class="atdw-meta-website">' + ATDWLive.escapeHtml(value) + '</a>';
				} else {
					html += ATDWLive.escapeHtml(value);
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
		},

		/**
		 * Render gallery of images
		 */
		renderGallery: function(images, size, columns, link, altText) {
			if (!Array.isArray(images)) {
				images = [images];
			}

			if (images.length === 0) {
				return '<p class="atdw-no-value">No images available</p>';
			}

			var html = '<div class="atdw-meta-gallery atdw-gallery-columns-' + columns + '">';

			for (var i = 0; i < images.length; i++) {
				var imageUrl = images[i];
				if (imageUrl) {
					// Strip query string
					imageUrl = imageUrl.split('?')[0];

					// Apply size parameter for thumbnail (you can expand this)
					var displayUrl = imageUrl;
					if (size === 'thumbnail') {
						displayUrl = imageUrl.replace(/\.(jpg|jpeg|png|gif)$/i, '-thumb.$1');
					}

					html += '<div class="atdw-gallery-item">';
					if (link) {
						html += '<a href="' + ATDWLive.escapeHtml(imageUrl) + '" target="_blank" class="atdw-gallery-link">';
					}
					html += '<img src="' + ATDWLive.escapeHtml(displayUrl) + '" alt="' + ATDWLive.escapeHtml(altText + ' - Image ' + (i + 1)) + '" loading="lazy" />';
					if (link) {
						html += '</a>';
					}
					html += '</div>';
				}
			}

			html += '</div>';
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

			// Apply size parameter
			var displayUrl = imageUrl;
			if (size === 'thumbnail') {
				displayUrl = imageUrl.replace(/\.(jpg|jpeg|png|gif)$/i, '-thumb.$1');
			}

			var html = '<div class="atdw-meta-image">';
			if (link) {
				html += '<a href="' + ATDWLive.escapeHtml(imageUrl) + '" target="_blank" class="atdw-image-link">';
			}
			html += '<img src="' + ATDWLive.escapeHtml(displayUrl) + '" alt="' + ATDWLive.escapeHtml(altText) + '" />';
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

			$resultsCount.html('Found <strong>' + totalResults + '</strong> ' + resultsText).show();

			var html = '';

			$.each(products, function(index, product) {
				html += ATDWLive.renderProductCard(product, showImage, showExcerpt, viewLabel, viewSlug);
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
		renderProductCard: function(product, showImage, showExcerpt, viewLabel, viewSlug) {
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

					html += '<div class="atdw-product-image">';
					html += '<img src="' + this.escapeHtml(imageUrl) + '" alt="' + this.escapeHtml(product.productName || '') + '" loading="lazy" />';

					// Add price overlay if rate_from exists
					if (product.rate_from || product.rateFrom) {
						var price = product.rate_from || product.rateFrom;
						html += '<div class="atdw-product-price">$' + this.escapeHtml(price) + '</div>';
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
				var excerpt = product.productDescription.substring(0, 150);
				if (product.productDescription.length > 150) {
					excerpt += '...';
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
				html += '<div class="atdw-product-featured-image">';
				html += '<img src="' + product.images[0] + '" alt="' + this.escapeHtml(product.productName || '') + '" />';
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
				html += '<div class="atdw-meta-item"><strong>Website:</strong> <a href="' + product.websiteUrl + '" target="_blank">' + this.escapeHtml(product.websiteUrl) + '</a></div>';
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
						var products = response.data.products || response.data.product || [];

						// Handle single product as array
						if (!Array.isArray(products)) {
							products = [products];
						}

						// Display products with full response data (including numberOfResults)
						ATDWLive.displayProducts(response.data, $container);

						// Store total results for pagination info
						if (response.data.numberOfResults) {
							$container.data('total-results', response.data.numberOfResults);
						}

						// Update current page
						$container.data('current-page', page);

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
		updatePaginationDisplay: function($container, hasMore) {
			var $pagination = $container.find('.atdw-pagination');
			var $prevBtn = $pagination.find('.atdw-prev-page');
			var $nextBtn = $pagination.find('.atdw-next-page');
			var $currentPage = $pagination.find('.atdw-current-page');
			var $totalPages = $pagination.find('.atdw-total-pages');
			var $pageSeparator = $pagination.find('.atdw-page-separator');

			var currentPage = $container.data('current-page') || 1;
			var pageSize = $container.data('params').size || 12;
			var totalResults = $container.data('total-results') || 0;

			// Update current page display
			$currentPage.text(currentPage);

			// Since ATDW API doesn't provide total count, hide total pages
			$pageSeparator.hide();
			$totalPages.hide();

			// Enable/disable buttons
			$prevBtn.prop('disabled', currentPage <= 1);

			// Disable next if we got less than requested (indicates last page)
			$nextBtn.prop('disabled', !hasMore);

			// Hide pagination if total results <= page size OR if on page 1 with no more pages
			if (totalResults > 0 && totalResults <= pageSize) {
				// If we know total results and it's less than a page, hide pagination
				$pagination.hide();
			} else if (currentPage === 1 && !hasMore) {
				// If on first page and no more results, hide pagination
				$pagination.hide();
			} else {
				// Otherwise show pagination
				$pagination.show();
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

			$loading.show();
			$products.empty();
			$error.hide();

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
						ATDWLive.displayProducts(response.data, $container);

						var products = response.data.products || response.data.product || [];
						if (!Array.isArray(products)) {
							products = [products];
						}

						// Update current page
						$container.data('search-current-page', page);

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
			var $prevBtn = $pagination.find('.atdw-prev-page');
			var $nextBtn = $pagination.find('.atdw-next-page');
			var $currentPage = $pagination.find('.atdw-current-page');
			var $pageSeparator = $pagination.find('.atdw-page-separator');
			var $totalPages = $pagination.find('.atdw-total-pages');

			// Update current page display
			$currentPage.text(currentPage);

			// Hide "of X" text since we don't know total pages
			$pageSeparator.hide();
			$totalPages.hide();

			// Enable/disable buttons
			$prevBtn.prop('disabled', currentPage <= 1);
			$nextBtn.prop('disabled', !hasMore);

			// Always show pagination for search results if there's more than one page
			if (currentPage === 1 && !hasMore) {
				// Only one page of results
				$pagination.hide();
			} else {
				$pagination.show();
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
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		ATDWLive.init();
	});

})(jQuery);
