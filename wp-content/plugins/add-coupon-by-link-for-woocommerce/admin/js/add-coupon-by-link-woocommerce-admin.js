(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	jQuery(function ($) {
		var url = jQuery("#pi-qr-code").data('qr-code-url');
		if (url != "") {
			new QRCode(document.getElementById("pi-qr-code"), url);
			jQuery("#pi-qr-code-download").fadeIn()
		}

		jQuery("#pi-qr-code-download").on('click', function () {
			var data = jQuery("#pi-qr-code img").attr('src');
			downloadURI(data, 'Coupon Qr Code');
		});

		function downloadURI(uri, name) {
			var link = document.createElement("a");
			link.download = name;
			link.href = uri;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			link = null;
		}
	});
})(jQuery);
