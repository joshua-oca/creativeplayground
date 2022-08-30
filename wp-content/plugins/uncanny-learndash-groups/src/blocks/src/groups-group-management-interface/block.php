<?php

/**
 * Register Course Expiry
 * render it with a callback function
 */

register_block_type( 'uncanny-learndash-groups/uo-groups', [
	'attributes'      => [
		'groupNameSelector'    => [
			'type'    => 'string',
			'default' => 'show',
		],
		'groupCoursesSection'  => [
			'type'    => 'string',
			'default' => 'show',
		],
		'addCoursesButton'     => [
			'type'    => 'string',
			'default' => 'show',
		],
		'seatsQuantity'        => [
			'type'    => 'string',
			'default' => 'show',
		],
		'addSeatsButton'       => [
			'type'    => 'string',
			'default' => 'show',
		],
		'addUserButton'        => [
			'type'    => 'string',
			'default' => 'show',
		],
		'removeUserButton'     => [
			'type'    => 'string',
			'default' => 'show',
		],
		'uploadUsersButton'    => [
			'type'    => 'string',
			'default' => 'show',
		],
		'downloadKeysButton'   => [
			'type'    => 'string',
			'default' => 'show',
		],
		'progressReportButton' => [
			'type'    => 'string',
			'default' => 'show',
		],
		'quizReportButton'     => [
			'type'    => 'string',
			'default' => 'show',
		],
		'keyColumn'            => [
			'type'    => 'string',
			'default' => 'show',
		],
		'groupLeaderSection'   => [
			'type'    => 'string',
			'default' => 'show',
		],
		'addGroupLeaderButton' => [
			'type'    => 'string',
			'default' => 'show',
		],
		'csvExportButton' => [
			'type'    => 'string',
			'default' => 'show',
		],
		'excelExportButton' => [
			'type'    => 'string',
			'default' => 'hide',
		],
		'keyOptions'           => [
			'type'    => 'string',
			'default' => 'show',
		],
		'groupEmailEutton'     => [
			'type'    => 'string',
			'default' => 'hide',
		],

		'enrolledUsersPageLength' => [
			'type'    => 'string',
			'default' => '50',
		],
		'enrolledUsersLengthMenu' => [
			'type'    => 'string',
			'default' => "25\n50\n100\n-1 : " . __( 'All', 'uncanny-learndash-groups' ),
		],
		'groupLeadersPageLength'  => [
			'type'    => 'string',
			'default' => '50',
		],
		'groupLeadersLengthMenu'  => [
			'type'    => 'string',
			'default' => "25\n50\n100\n-1 : " . __( 'All', 'uncanny-learndash-groups' ),
		],
	],
	'render_callback' => 'render_uo_group_mgr',
] );

function render_uo_group_mgr( $attributes ) {

	// Get course ID
	$group_name_selector        = $attributes['groupNameSelector'];
	$add_courses_button         = $attributes['addCoursesButton'];
	$group_courses_section      = $attributes['groupCoursesSection'];
	$seats_quantity             = $attributes['seatsQuantity'];
	$add_seats_button           = $attributes['addSeatsButton'];
	$add_user_button            = $attributes['addUserButton'];
	$remove_user_button         = $attributes['removeUserButton'];
	$upload_users_button        = $attributes['uploadUsersButton'];
	$download_keys_button       = $attributes['downloadKeysButton'];
	$progress_report_button     = $attributes['progressReportButton'];
	$quiz_report_button         = $attributes['quizReportButton'];
	$key_column                 = $attributes['keyColumn'];
	$group_leader_section       = $attributes['groupLeaderSection'];
	$add_group_leader_button    = $attributes['addGroupLeaderButton'];
	$key_options                = $attributes['keyOptions'];
	$group_email_button         = $attributes['groupEmailEutton'];
	$manage_progress_button     = $attributes['progressReportButton'];
	$csv_export_button          = $attributes['csvExportButton'];
	$excel_export_button          = $attributes['excelExportButton'];
	$enrolled_users_page_length = $attributes['enrolledUsersPageLength'];
	$enrolled_users_length_menu = $attributes['enrolledUsersLengthMenu'];
	$group_leaders_page_length  = $attributes['groupLeadersPageLength'];
	$group_leaders_length_menu  = $attributes['groupLeadersLengthMenu'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_learndash_groups\GroupManagementInterface' ) ) {

		$class = \uncanny_learndash_groups\Utilities::get_class_instance( 'GroupManagementInterface' );
		// Check if the course ID is empty
		echo $class->uo_group_mgr( [
			'group_name_selector'               => $group_name_selector,
			'add_courses_button'                => $add_courses_button,
			'group_courses_section'             => $group_courses_section,
			'seats_quantity'                    => $seats_quantity,
			'add_seats_button'                  => $add_seats_button,
			'add_user_button'                   => $add_user_button,
			'remove_user_button'                => $remove_user_button,
			'upload_users_button'               => $upload_users_button,
			'download_keys_button'              => $download_keys_button,
			'progress_report_button'            => $progress_report_button,
			'quiz_report_button'                => $quiz_report_button,
			'key_column'                        => $key_column,
			'group_leader_section'              => $group_leader_section,
			'add_group_leader_button'           => $add_group_leader_button,
			'key_options'                       => $key_options,
			'group_email_button'                => $group_email_button,
			'progress_management_report_button' => $manage_progress_button,
			'csv_export_button'                 => $csv_export_button,
			'excel_export_button'               => $excel_export_button,
			'enrolled_users_page_length'        => $enrolled_users_page_length,
			'enrolled_users_length_menu'        => $enrolled_users_length_menu,
			'group_leaders_page_length'         => $group_leaders_page_length,
			'group_leaders_length_menu'         => $group_leaders_length_menu,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}

register_block_type( 'uncanny-learndash-groups/uo-groups-url', [
	'attributes'      => [
		'text' => [
			'type'    => 'string',
			'default' => __( 'Group Management', 'uncanny-learndash-groups' ),
		],
	],
	'render_callback' => 'render_uo_groups_url',
] );

function render_uo_groups_url( $attributes ) {

	// Get course ID
	$text = $attributes['text'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_learndash_groups\GroupManagementInterface' ) ) {

		$class = \uncanny_learndash_groups\Utilities::get_class_instance( 'GroupManagementInterface' );
		// Check if the course ID is empty
		echo $class->uo_groups_url( [
			'text' => $text,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}
