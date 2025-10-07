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

		/**
		 * Initialize
		 */
		init: function() {
			this.initProductContainers();
			this.initSingleContainers();
			this.initSearchContainers();
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
					fl: $container.data('fl') || ''
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

			// Get sort option (overrides any existing sort)
			var sortBy = $container.find('.atdw-filter-sort').val();
			if (sortBy) {
				// Use order parameter for ATDW API sorting
				params.order = sortBy;
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
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		ATDWLive.init();
	});

})(jQuery);
