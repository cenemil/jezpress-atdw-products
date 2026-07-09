/**
 * Admin JavaScript
 *
 * @package ATDW_Live_Products
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		// Test API Connection
		$('#atdw-live-test-connection').on('click', function() {
			var $button = $(this);
			var originalText = $button.text();

			$button.prop('disabled', true).text(atdwLiveAdmin.strings.testingConnection);

			$.post(atdwLiveAdmin.ajaxUrl, {
				action: 'atdw_live_test_connection',
				nonce: atdwLiveAdmin.nonce
			})
			.done(function(response) {
				if (response.success) {
					alert(atdwLiveAdmin.strings.connectionSuccess);
				} else {
					alert(atdwLiveAdmin.strings.connectionFailed + '\n\n' + response.data.message);
				}
			})
			.fail(function() {
				alert(atdwLiveAdmin.strings.connectionFailed);
			})
			.always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		});

		// Purge ATDW Cache
		$('#atdw-live-purge-cache').on('click', function() {
			if (confirm('Purge all cached ATDW data? Next page load will fetch fresh data from the API.')) {
				var $btn = $(this);
				$btn.prop('disabled', true).text('Purging...');
				$.post(atdwLiveAdmin.ajaxUrl, {
					action: 'atdw_live_purge_cache',
					nonce: atdwLiveAdmin.nonce
				})
				.done(function(response) {
					if (response.success) {
						alert(response.data.message);
					} else {
						alert('Error: ' + response.data.message);
					}
				})
				.fail(function() {
					alert('Failed to purge cache.');
				})
				.always(function() {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="vertical-align:middle;margin-top:-2px;"></span> Purge ATDW Cache');
				});
			}
		});

		// Clear Errors
		$('#atdw-live-clear-errors').on('click', function() {
			if (confirm('Are you sure you want to clear all error logs?')) {
				$.post(atdwLiveAdmin.ajaxUrl, {
					action: 'atdw_live_clear_errors',
					nonce: atdwLiveAdmin.nonce
				}, function() {
					location.reload();
				});
			}
		});

	});

})(jQuery);
