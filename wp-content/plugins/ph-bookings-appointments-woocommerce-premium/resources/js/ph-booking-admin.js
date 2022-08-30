jQuery(document).ready(function ($) {
	jQuery('._tax_status_field').closest('.show_if_simple').addClass('show_if_phive_booking').show();

	//yith points and rewards settings on product general setting start
	jQuery('._ywpar_max_point_discount_field').addClass('show_if_phive_booking').show();
	jQuery('._ywpar_point_earned_field').addClass('show_if_phive_booking').show();
	jQuery('.ywpar_point_earned_dates_fields').addClass('show_if_phive_booking').show();
	jQuery('._ywpar_redemption_percentage_discount_field').addClass('show_if_phive_booking').show();
	//yith points and rewards settings on product general setting end

	/***********************************************************************
	**** ADMIN PRODUCT PAGE (html-ph-booking-product-admin-options.php) ****
	************************************************************************/
	toggle_interval_type();
	toggle_related_to_interval_period();
	toggle_related_to_cancel_interval_period();
	toggle_related_to_additional_notes();
	toggle_related_to_admin_order_submit();
	toggle_booking_start_day();
	toggle_related_to_enable_buffer();
	toggle_related_to_custom_date_range();

	// #105168 - allow customers to set maximum participant in a booking even if consider each participant as separate booking is active.
	// toggle_maxparticipant_box();
	ph_max_participant_warning();

	toggle_across_the_day_booking();
	toggle_end_time_display();

	toggle_enable_participant_box();
	$('#_phive_booking_person_enable').click(function () {
		toggle_enable_participant_box();
	});

	$("#_phive_book_interval_type").change(function () {
		toggle_interval_type();

	});

	$('#_phive_booking_persons_as_booking').click(function () {
		// toggle_maxparticipant_box();
		// #105168
		ph_max_participant_warning();
	});

	function toggle_enable_participant_box() {
		if (!$('#_phive_booking_person_enable').prop('checked')) {
			$('#_phive_booking_minimum_number_of_required_participant').val(0);
		}
	}


	toggle_booking_from_type();
	$("#_phive_fixed_availability_from").change(function () {
		toggle_booking_from_type();

	});
	function toggle_booking_from_type() {
		if ($('#_phive_fixed_availability_from').val() == '') {

			$('#_phive_first_booking_availability_type').attr('disabled', false);
		}
		else {
			$('#_phive_first_booking_availability_type').val('today');
			$('#_phive_first_booking_availability_type').attr('disabled', true);
		}
	}

	function toggle_maxparticipant_box() {
		if ($('#_phive_booking_persons_as_booking').prop('checked')) {
			$('#_phive_booking_maximum_number_of_allowed_participant').val('');
			$('._phive_booking_maximum_number_of_allowed_participant_field').hide();
			// $('#_phive_booking_minimum_number_of_required_participant').val(0);
			// $('._phive_booking_minimum_number_of_required_participant_field').hide();
		}
		else {
			$('._phive_booking_maximum_number_of_allowed_participant_field').show();
			// $('._phive_booking_minimum_number_of_required_participant_field').show();
		}
	}
	$("#_phive_book_interval_period").change(function () {
		toggle_related_to_interval_period();
		$('#_phive_enable_buffer').prop('checked', false);
		$('#_phive_buffer_before').val('0');
		$('#_phive_buffer_after').val('0');
		$('#ph_buffer_before').hide();
		$('#ph_buffer_after').hide();
		toggle_related_to_custom_date_range();
		toggle_across_the_day_booking();
		toggle_end_time_display();
	});

	// Hide Number of Seats/Places left based on maximum booking limit per block
	hide_number_of_seats_left();
	$('#_phive_book_allowed_per_slot').change(function () {
		hide_number_of_seats_left();
	});

	function hide_number_of_seats_left() {
		if ($("#_phive_book_allowed_per_slot").val() == "1" || $("#_phive_book_allowed_per_slot").val() == "") {
			$('#_phive_display_bookings_capacity').prop('checked', false);
			$('._phive_display_bookings_capacity_field').hide();
			$('._phive_remainng_bokkings_text_field').hide();
		}
		else {
			$('._phive_display_bookings_capacity_field').show();
			remaining_block_text();
		}
	}
	$('#_phive_display_bookings_capacity').click(function () {
		hide_number_of_seats_left();
	});
	function remaining_block_text() {

		if ($('#_phive_display_bookings_capacity').is(":checked")) {
			$('._phive_remainng_bokkings_text_field').show();
		}
		else {
			$('._phive_remainng_bokkings_text_field').hide();
		}
	}
	hide_auto_select_min_block();
	$('#_phive_book_min_allowed_booking').change(function () {
		hide_auto_select_min_block();
	});

	function hide_auto_select_min_block() {
		if ($("#_phive_book_min_allowed_booking").val() == "1" || $("#_phive_book_min_allowed_booking").val() == "" || $("#_phive_book_interval_type").val() == 'fixed') {
			$('._phive_auto_select_min_booking_field').hide();
		}
		else {
			if ($('.calendar_design').val() == '3') {
				$('._phive_auto_select_min_booking_field').hide();
				$('#_phive_auto_select_min_booking').prop('checked', false);
			}
			else if ($("#_phive_book_interval_type").val() != 'fixed') {
				$('._phive_auto_select_min_booking_field').show();
			}
		}
	}

	$("#_phive_booking_persons_as_booking").change(function () {
		var persons_rule_min = $('.ph_booking_persons_rule_min').val();
		var persons_rule_max = $('.ph_booking_persons_rule_max').val();
		regex = $('#_phive_book_allowed_per_slot').val();
		error = 'exceed_booking';

		if (persons_rule_min > regex) {
			$('.ph_booking_persons_rule_min').val(regex);
		}
		if (persons_rule_max > regex) {
			$('.ph_booking_persons_rule_max').val(regex);
		}

	});
	$("#ph_booking_order_new").change(function () {
		toggle_related_to_admin_order_submit();
	});
	$("#ph_booking_order_existing").change(function () {
		toggle_related_to_admin_order_submit();
	});
	$("#ph_booking_order_id").focusout(function () {
		toggle_related_to_admin_order_submit();
	});

	//#107352
	$('#ph_booking_order_id').keyup(function(){
		toggle_related_to_admin_order_submit();
	});

	$("#_phive_additional_notes_label").attr('maxlength', '50');


	function toggle_across_the_day_booking() {

		if ($("#_phive_book_interval_period").val() == 'minute' || $("#_phive_book_interval_period").val() == 'hour') {
			if ($("#_phive_book_interval_type").val() == 'customer_choosen') {
				$("._phive_enable_across_the_day").show();
			}
			else {
				$("._phive_enable_across_the_day").hide();
			}
		} else {
			$("._phive_enable_across_the_day").hide();
		}

	}
	function toggle_end_time_display() {

		if ($("#_phive_book_interval_period").val() == 'minute' || $("#_phive_book_interval_period").val() == 'hour') {

			if ($('.calendar_design').val() == '3') {
				$('._phive_enable_end_time_display').hide();
				$('#_phive_enable_end_time_display').prop('checked', false);
			}
			else {
				$("._phive_enable_end_time_display").show();
			}
		} else {
			$("._phive_enable_end_time_display").hide();
		}

	}
	function toggle_related_to_admin_order_submit() {
		var isNewBookingChecked = $('#ph_booking_order_new').prop('checked');
		var isExistingBookingChecked = $('#ph_booking_order_existing').prop('checked');
		var ExistingOrderId = $('#ph_booking_order_id').val();
		if (isNewBookingChecked || isExistingBookingChecked) {
			if (isExistingBookingChecked && ((ExistingOrderId).length == 0)) {
				$("#ph_create_booking").attr('disabled', 'disabled');

			} else if (isExistingBookingChecked && (ExistingOrderId).length > 0) {
				$("#ph_create_booking").removeAttr("disabled");

			} else {
				$("#ph_create_booking").removeAttr("disabled");
			}

		}
		else {

			$("#ph_create_booking").attr('disabled', 'disabled');
		}
	}

	function toggle_related_to_interval_period() {

		if ($("#_phive_book_interval_period").val() == 'minute' || $("#_phive_book_interval_period").val() == 'hour') {
			$("._phive_book_working_hour_start_field").show();
			$("._phive_book_working_hour_end_field").show();
			$(".ph_bookings_admin_time_start_end_time_settings").show();
			if ($("#_phive_book_interval_period").val() == 'minute') {
				$('._phive_buffer_period').html('minute');
				$('#_phive_buffer_period').val('minute');
			}
			else {
				$('._phive_buffer_period').html('hour');
				$('#_phive_buffer_period').val('hour');
			}
		} else {
			$("._phive_book_working_hour_start_field").hide();
			$("._phive_book_working_hour_end_field").hide();
			$(".ph_bookings_admin_time_start_end_time_settings").hide();
		}

		if ($("#_phive_book_interval_period").val() == 'day') {
			if ($("#_phive_book_interval_type").val() == 'customer_choosen') {
				$("._phive_book_charge_per_night_field").show();
			}

			/*$("._phive_book_checkin_field").show();
			$("._phive_book_checkout_field").show();*/
			$('._phive_buffer_period').html('day');
			$('#_phive_buffer_period').val('day');
		} else {
			$("._phive_book_charge_per_night_field").hide();
			/*$("._phive_book_checkin_field").hide();
			$("._phive_book_checkout_field").hide();*/
		}

		if ($("#_phive_book_interval_period").val() == 'day') {
			$("#_phive_restrict_start_day").closest("div").show();
			$("#ph_start_day_related").show();
		} else {
			$("#_phive_restrict_start_day").closest("div").hide();
			$("#ph_start_day_related").hide();
		}

		toggle_related_to_enable_buffer();
	}
	function toggle_interval_type() {

		if ($("#_phive_book_interval_type").val() == 'customer_choosen') {
			$(".for-type-customer-choosen").closest(".form-field").show();
			$("#_phive_book_charge_per_night").closest(".form-field").show();
			if ($("#_phive_book_interval_period").val() == 'day' || $("#_phive_book_interval_period").val() == 'month') {
				$("._phive_enable_across_the_day").hide();
				$("._phive_enable_end_time_display").hide();
			}
			else {
				$("._phive_enable_across_the_day").show();
				$("._phive_enable_end_time_display").show();
			}
		} else {
			$("#_phive_book_charge_per_night").closest(".form-field").hide();
			//change the value to no when interval type is not customer choosen
			$('#_phive_book_charge_per_night').attr('checked', false);
			$(".for-type-customer-choosen").closest(".form-field").hide();
			if ($("#_phive_book_interval_period").val() == 'minute' || $("#_phive_book_interval_period").val() == 'hour') {
				$("._phive_enable_end_time_display").show();
			}
		}
	}

	/******* cancellation period **********/
	$("#_phive_book_allow_cancel").change(function () {
		toggle_related_to_cancel_interval_period();
	});

	function toggle_related_to_cancel_interval_period() {
		if ($('#_phive_book_allow_cancel').is(":checked")) {
			$('#_phive_cancellation_period').show();
		}
		else {
			$('#_phive_cancellation_period').hide();
		}
	}
	/******* Additional Notes ***********/
	$("#_phive_book_additional_notes").change(function () {
		toggle_related_to_additional_notes();
	});

	function toggle_related_to_additional_notes() {
		if ($('#_phive_book_additional_notes').is(":checked")) {
			$("._phive_additional_notes_label_field").show();
		}
		else {
			$("._phive_additional_notes_label_field").hide();
		}
	}

	/*****************  Buffer Enable    ****************/
	$("#_phive_enable_buffer").change(function () {
		toggle_related_to_enable_buffer();
	});
	function toggle_related_to_enable_buffer() {
		if ($("#_phive_book_interval_period").val() != 'month') {
			$("#_phive_enable_buffer_field").show();
			if ($('#_phive_enable_buffer').is(":checked")) {
				$("#ph_buffer_before").show();
				$("#ph_buffer_after").show();
			}
			else {
				$("#ph_buffer_before").hide();
				$("#ph_buffer_after").hide();
			}

		}
		else {
			$("#_phive_enable_buffer_field").hide();
			$("#ph_buffer_before").hide();
			$("#ph_buffer_after").hide();

		}

	}

	/****** html-ph-booking-product-admin-assets.php ********/
	toggle_auto_assign_related();
	display_assets_options();

	jQuery(document).on('change', '#_phive_booking_assets_enable', function () {
		display_assets_options();
	})

	jQuery(document).on('change', '#_phive_booking_assets_auto_assign', function () {
		toggle_auto_assign_related();
	})

	function toggle_auto_assign_related() {
		if ($("#_phive_booking_assets_auto_assign").val() == 'yes') {
			$("#_phive_booking_assets_label").closest(".form-field").hide();
		} else {
			$("#_phive_booking_assets_label").closest(".form-field").show();
		}
	}

	function display_assets_options() {
		if ($("#_phive_booking_assets_enable").is(":checked")) {
			$(".assets-wraper").show();
		} else {
			$(".assets-wraper").hide();
		}
	}
	function toggle_related_to_custom_date_range() {
		if ($("#_phive_book_interval_period").length && $("#_phive_book_interval_period").val() != 'minute' && $("#_phive_book_interval_period").val() != 'hour') {
			$('.availability_time_picker_field').hide();
		}
		else {
			$('.availability_time_picker_field').show();
		}
	}

	/****** html-ph-booking-product-admin-availability.php ********/

	jQuery(document).on('change', '.ph_booking_from_date,.ph_booking_to_date,.ph_booking_from_timepicker,.ph_booking_to_timepicker', function () {
		var row = $(this).closest('tr');
		$(row).css('border', '1px solid #f00');
		var from_datepicker = $(row).find('.ph_booking_from_date').val();
		var from_timepicker = $(row).find('.ph_booking_from_timepicker').val();
		var from_date = from_datepicker + ' ' + from_timepicker;
		var to_datepicker = $(row).find('.ph_booking_to_date').val();
		var to_timepicker = $(row).find('.ph_booking_to_timepicker').val();
		var to_date = to_datepicker + ' ' + to_timepicker;
		$(row).find('.ph_booking_from_datetime').val(from_date);
		$(row).find('.ph_booking_to_datetime').val(to_date);
	})
	jQuery(document).on('change', '.ph_booking_from_date_for_date_range_and_time,.ph_booking_to_date_for_date_range_and_time,.ph_booking_from_timepicker_date_range_and_time,.ph_booking_to_timepicker_date_range_and_time', function () {
		var row = $(this).closest('tr');
		$(row).css('border', '1px solid #f00');
		var from_datepicker = $(row).find('.ph_booking_from_date_for_date_range_and_time').val();
		var from_timepicker = $(row).find('.ph_booking_from_timepicker_date_range_and_time').val();
		var from_date = from_datepicker + ' ' + from_timepicker;
		var to_datepicker = $(row).find('.ph_booking_to_date_for_date_range_and_time').val();
		var to_timepicker = $(row).find('.ph_booking_to_timepicker_date_range_and_time').val();
		var to_date = to_datepicker + ' ' + to_timepicker;
		$(row).find('.ph_booking_from_datetime_for_date_range_and_time').val(from_date);
		$(row).find('.ph_booking_to_datetime_for_date_range_and_time').val(to_date);
	})
	jQuery(document).on('change', '#_phive_restrict_start_day', function () {
		toggle_booking_start_day();
	})

	function toggle_booking_start_day() {
		if ($('#_phive_restrict_start_day').is(":checked")) {
			$(".ph_start_day_related").show();
		} else {
			$(".ph_start_day_related").hide();
		}
	}

	jQuery(document).on('change', '.ph_booking_availability_type', function () {
		var value = $(this).val();
		var row = $(this).closest('tr');

		$(row).css('border', '1px solid #f00');

		$(row).find('.availability_by_week_days, .availability_by_month, .availability_by_date, .availability_by_time, .availability_by_date_range_and_time').hide();

		if (value == 'custom') {
			$(row).find('.availability_by_date').show();
		}
		if (value == 'date-range-and-time') {
			$(row).find('.availability_by_date_range_and_time').show();
		}
		if (value == 'months') {
			$(row).find('.availability_by_month').show();
		}
		if (value == 'days') {
			$(row).find('.availability_by_week_days').show();
		}
		if (value.match("^time")) {
			$(row).find('.availability_by_time').show();
		}
	});

	jQuery(".availability_date_picker").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});

	jQuery('.ph_availability_table tbody').sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: '.sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function (event, ui) {
			ui.item.css('baclbsround-color', '#f6f6f6');
		},
		stop: function (event, ui) {
			ui.item.removeAttr('style');
		}
	});


	jQuery(document).on('click', "table[class^='ph_'] td.remove", function () {
		$(this).closest('tr').remove();
	});


	/****** html-ph-booking-admin-reports-list-filters.php ********/

	$(document).on("click", ".btn_filter", function () {
		admin_url = window.location.pathname + '?page=bookings';
		booking_status = $(this).closest(".tablenav").find(".ph_booking_status").val();
		filter_product_ids = $(this).closest(".tablenav").find(".ph_filter_product_ids").val();
		filter_from = $(this).closest(".tablenav").find(".ph_filter_from").val();
		filter_end_from = $(this).closest(".tablenav").find(".ph_filter_end_from").val();
		filter_to = $(this).closest(".tablenav").find(".ph_filter_to").val();
		filter_end_to = $(this).closest(".tablenav").find(".ph_filter_end_to").val();
		window.location = admin_url + "&ph_booking_status=" + booking_status + "&ph_filter_product_ids=" + filter_product_ids + "&ph_filter_from=" + filter_from + "&ph_filter_end_from=" + filter_end_from + "&ph_filter_to=" + filter_to + "&ph_filter_end_to=" + filter_end_to;
	});


	$(".ph_from-top").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});
	$(".ph_end_from-top").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});

	$(".ph_from-bottom").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});
	$(".ph_end_from-bottom").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});

	$(".ph_to-top").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});
	$(".ph_end_to-top").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});
	$(".ph_to-bottom").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});
	$(".ph_end_to-bottom").datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
	});


	/***************** Custom error **********************/

	$(document).on('wc_add_error_tip', function (e, element, error_type, newvalue = '') {
		var offset = element.position();

		if (element.parent().find('.wc_error_tip').length === 0) {
			element.after('<div class="wc_error_tip ' + error_type + '">' + (phive_booking_locale.exceed_booking.replace(/%d/g, newvalue)) + '</div>');
			element.parent().find('.wc_error_tip')
				.css('left', offset.left + element.width() - (element.width() / 2) - ($('.wc_error_tip').width() / 2))
				.css('top', offset.top + element.height())
				.fadeIn('100');
		}
	})

	$(document).on('wc_remove_error_tip', function (e, element, error_type) {
		element.parent().find('.wc_error_tip.' + error_type).fadeOut('100', function () { $(this).remove(); });
	})

	$(document).on('click', function () {
		$('.wc_error_tip').fadeOut('100', function () { $(this).remove(); });
	})

	$(document).on('blur', '.ph_booking_persons_rule_min,ph_booking_persons_rule_max', function () {
		$('.wc_error_tip').fadeOut('100', function () { $(this).remove(); });
	});
	$(document).on('change', '.ph_booking_persons_rule_min,.ph_booking_persons_rule_max', function () {
		var regex;
		if ($('#_phive_booking_persons_as_booking').prop('checked')) {

			if ($(this).is('.ph_booking_persons_rule_min')) {
				regex = $('#_phive_book_allowed_per_slot').val();
				error = 'exceed_booking';
			}
			else if ($(this).is('.ph_booking_persons_rule_max')) {
				regex = $('#_phive_book_allowed_per_slot').val();
				error = 'exceed_booking';
			}


			var value = parseInt($(this).val());
			var newvalue = parseInt(regex);
			if (value > newvalue) {
				$(this).val(regex);
			}
		}

	})
	$(document).on('keyup', '.ph_booking_persons_rule_min ,.ph_booking_persons_rule_max', function () {
		var regex, error;
		if ($('#_phive_booking_persons_as_booking').prop('checked')) {

			if ($(this).is('.ph_booking_persons_rule_min') || $(this).is('.ph_booking_persons_rule_max')) {
				regex = $('#_phive_book_allowed_per_slot').val();
				error = 'exceed_booking';
			}
			var value = parseInt($(this).val());
			var newvalue = parseInt(regex);
			if (value > newvalue) {
				$(document).triggerHandler('wc_add_error_tip', [$(this), error, newvalue]);
			} else {
				$(document).triggerHandler('wc_remove_error_tip', [$(this), error]);
			}
		}
	})
	// console.log($('.modify_booking_from_meta_id'));
	var edit_order_item = $('#woocommerce-order-items'); 
	$(edit_order_item).on('click', '.edit-order-item', function () 
	{
		// console.log('clicked');
		$('.modify_booking_from_meta_id').each(function () 
		{
			date_value = $(this).val();
			meta_id = $(this).data('meta-id');
			item_id = $(this).data('item-id');
			meta = "meta_value[" + item_id + "][" + meta_id + "]";
			jQuery('#order_line_items').find('.edit').find('textarea[name="' + meta + '"]').html(date_value);
		});

		$('.modify_booking_to_meta_id').each(function () {
			date_value = $(this).val();
			meta_id = $(this).data('meta-id');
			item_id = $(this).data('item-id');
			meta = "meta_value[" + item_id + "][" + meta_id + "]";
			jQuery('#order_line_items').find('.edit').find('textarea[name="' + meta + '"]').html(date_value);
		});
	});
	
	// #105168
	$('#_phive_booking_maximum_number_of_allowed_participant').on('change', function (e) 
	{
		ph_max_participant_warning();
	});

	$('input[name=_phive_book_allowed_per_slot]').on('change', function () 
	{
		ph_max_participant_warning();
	});

	function ph_max_participant_warning()
	{
		max_booking = parseInt($('input[name=_phive_book_allowed_per_slot]').val());
		if ($('#_phive_booking_persons_as_booking').prop('checked')) 
		{
			if($('#_phive_booking_maximum_number_of_allowed_participant').val() > max_booking)
			{
				style = "display:block;margin:inherit;padding:10px 0px;color:red";
				elem = $('#_phive_booking_maximum_number_of_allowed_participant').parent('p._phive_booking_maximum_number_of_allowed_participant_field').find('span.ph-participant-more-than-max-bookings');
				elem.remove();
				$('#_phive_booking_maximum_number_of_allowed_participant').parent('p._phive_booking_maximum_number_of_allowed_participant_field').append('<span class="ph-participant-more-than-max-bookings" style="'+style+'">Value must be less than or equal to '+max_booking+'</span>');
			}
			else
			{
				elem = $('#_phive_booking_maximum_number_of_allowed_participant').parent('p._phive_booking_maximum_number_of_allowed_participant_field').find('span.ph-participant-more-than-max-bookings');
				elem.remove();
			}
		}
		else
		{
			elem = $('#_phive_booking_maximum_number_of_allowed_participant').parent('p._phive_booking_maximum_number_of_allowed_participant_field').find('span.ph-participant-more-than-max-bookings');
			elem.remove();
		}
	}
	
	// notice-dismiss not working in all bookings page
	jQuery('.ph-notice-success').on('click', 'button.notice-dismiss', function () 
	{
		$(this).parent('div.ph-notice-success').hide();
	});
	// Ticket-133055- Give warning message (Allow to enter numeric or monetory decimal point only ) 
	$("#ph_booking_display_cost, #ph_booking_base_cost , #ph_booking_cost_per_unit").on("keyup", function() {
		if ( $( this ).is( '#ph_booking_display_cost' ) || $( this ).is( '#ph_booking_base_cost' ) || $( this ).is( '#ph_booking_cost_per_unit' ) ) {
			checkDecimalNumbers = true;
			regex = new RegExp( '[^\-0-9\%\\' + woocommerce_admin.mon_decimal_point + ']+', 'gi' );
			decimalRegex = new RegExp( '[^\\' + woocommerce_admin.mon_decimal_point + ']', 'gi' );
			error = 'i18n_mon_decimal_error';
		}
		var value    = $( this ).val();
		var newvalue = value.replace( regex, '' );

		if ( checkDecimalNumbers && 1 < newvalue.replace( decimalRegex, '' ).length ) {
			newvalue = newvalue.replace( decimalRegex, '' );
		}
		if($( this ).is( '#ph_booking_display_cost' )){
			$( '#ph_booking_display_cost' ).val( newvalue ) ;
		}
		else if($( this ).is( '#ph_booking_base_cost' )){
			$( '#ph_booking_base_cost' ).val( newvalue ) ;
		}
		else if($( this ).is( '#ph_booking_cost_per_unit' )){
			$( '#ph_booking_cost_per_unit' ).val( newvalue );
		}
		if ( value !== newvalue ) {
			$( document.body ).triggerHandler( 'wc_add_error_tip', [ $( this ), error ] );
		} else {
			$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $( this ), error ] );
		}
		
	});
})