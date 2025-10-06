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
