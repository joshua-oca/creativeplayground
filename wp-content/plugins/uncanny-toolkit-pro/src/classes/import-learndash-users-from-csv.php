<?php

namespace uncanny_pro_toolkit;

use WP_User;
use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * ImportUsers
 * @package uncanny_pro_toolkit
 */
class ImportLearndashUsersFromCsv extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * @var bool
	 */
	public static $log_error = false;
	/**
	 * @var string
	 */
	public static $module_name;
	/**
	 * @var string
	 */
	public static $module_key = 'learndash-toolkit-import-user';
	/**
	 * @var string
	 */
	public static $option_key = 'learndash-toolkit-import-user';
	/**
	 * @var string
	 */
	public static $capability = 'manage_options';
	/**
	 * @var string
	 */
	public static $version = '1.0';
	/**
	 * @var bool
	 */
	public static $is_module_menu;
	/**
	 * @var mixed|void
	 */
	public static $template;
	/**
	 * @var string
	 */
	public static $email_title = 'User Registration Completed';
	/**
	 * @var mixed|void
	 */
	public static $email_template;

	/**
	 * @var string
	 */
	public static $import_role = 'uo_csv_import';

	/**
	 * @var
	 */
	public static $csv_header;
	/**
	 * @var
	 */
	public static $uo_import_users_send_new_user_email;
	/**
	 * @var
	 */
	public static $uo_import_users_new_user_email_subject;
	/**
	 * @var
	 */
	public static $uo_import_users_new_user_email_body;
	/**
	 * @var
	 */
	public static $uo_import_users_send_updated_user_email;
	/**
	 * @var
	 */
	public static $uo_import_users_updated_user_email_subject;
	/**
	 * @var
	 */
	public static $uo_import_users_updated_user_email_body;

	// WP_Users Attr
	/**
	 * @var array|string[]
	 */
	public static $registered_columns = array(
		'user_pass'     => 'Password',
		'user_nicename' => 'Nice Name',
		'user_url'      => 'URL',
		'display_name'  => 'Display Name',
		'nickname'      => 'Nick Name',
		'first_name'    => 'First Name',
		'last_name'     => 'Last Name',
		'description'   => 'Description',
	);

	// Required
	/**
	 * @var string[]
	 */
	public static $required_columns = array(
		'user_login' => 'User Login',
		'user_email' => 'Email',
	);

	// Extra
	/**
	 * @var string[]
	 */
	public static $extra_columns = array(
		'learndash_group'   => 'LearnDash Group(s)',
		'learndash_courses' => 'LearnDash Course(s)',
		'wp_role'           => 'Role',
	);

	// Custom Mapping
	/**
	 * @var string[]
	 */
	public static $import_map
		= array(
			'user_login'        => 'user_login',
			'user_email'        => 'user_email',
			'first_name'        => 'first_name',
			'last_name'         => 'last_name',
			'user_pass'         => 'user_pass',
			'wp_role'           => 'wp_role',
			'learndash_courses' => 'learndash_courses',
			'learndash_groups'  => 'learndash_groups',
			'display_name'      => 'display_name',
			'group_leader'      => 'group_leader',
		);

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		self::$module_name        = esc_attr__( 'Import Users', 'uncanny-pro-toolkit' );
		self::$email_title        = esc_attr__( 'User Registration Completed', 'uncanny-pro-toolkit' );
		self::$is_module_menu     = ( ! empty( $_GET['page'] ) && $_GET['page'] == self::$module_key ) ? true : false;
		self::$template           = self::get_template( 'admin-import-user/admin-import-users.php', dirname( dirname( __FILE__ ) ) . '/src' );
		self::$template           = apply_filters( 'uo_admin_import_users_template', self::$template );
		self::$email_template     = self::get_template( 'import-user-email.php', dirname( dirname( __FILE__ ) ) . '/src' );
		self::$email_template     = apply_filters( 'uo_import_user_email_template', self::$email_template );
		self::$registered_columns = array(
			'user_pass'     => esc_attr__( 'Password', 'uncanny-pro-toolkit' ),
			'user_nicename' => esc_attr__( 'Nice Name', 'uncanny-pro-toolkit' ),
			'user_url'      => esc_attr__( 'URL', 'uncanny-pro-toolkit' ),
			'display_name'  => esc_attr__( 'Display Name', 'uncanny-pro-toolkit' ),
			'nickname'      => esc_attr__( 'Nick Name', 'uncanny-pro-toolkit' ),
			'first_name'    => esc_attr__( 'First Name', 'uncanny-pro-toolkit' ),
			'last_name'     => esc_attr__( 'Last Name', 'uncanny-pro-toolkit' ),
			'description'   => esc_attr__( 'Description', 'uncanny-pro-toolkit' ),
		);
		add_action( 'plugins_loaded', array( __CLASS__, 'run_backend_hooks' ) );

		// Ajax Requests
		add_action(
			'wp_ajax_Uncanny Toolkit Pro - Import Users : File Upload',
			array(
				__CLASS__,
				'ajax_file_upload',
			)
		);

		add_action(
			'wp_ajax_Uncanny Toolkit Pro - Import Users : Options Form',
			array(
				__CLASS__,
				'ajax_option_checked',
			)
		);

		add_action( 'wp_ajax_Uncanny Toolkit Pro - Import Users : Test Email', array( __CLASS__, 'ajax_test_email' ) );

		add_action( 'wp_ajax_Uncanny Toolkit Pro - Import Users : Save Email', array( __CLASS__, 'ajax_save_email' ) );

		add_action(
			'wp_ajax_Uncanny Toolkit Pro - Import Users : Perform Import',
			array(
				__CLASS__,
				'ajax_perform_import',
			)
		);
	}

	/**
	 * Initialize frontend actions and filters
	 */
	public static function run_backend_hooks() {

		self::$module_name = esc_attr__( 'Import Users', 'uncanny-pro-toolkit' );

		// Admin Page on Users
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		if ( self::$is_module_menu ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Admin Page on Users
	 *
	 * This module needs file uploading and sorting, so the setting modal is not good
	 *
	 * @since 1.0.0
	 */
	public static function admin_menu() {
		if ( current_user_can( 'list_users' ) ) {
			add_users_page(
				self::$module_name,
				self::$module_name,
				apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ),
				self::$module_key,
				array(
					__CLASS__,
					'cb_admin_menu',
				)
			);
		} else {
			add_dashboard_page(
				self::$module_name,
				self::$module_name,
				apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ),
				self::$module_key,
				array(
					__CLASS__,
					'cb_admin_menu',
				)
			);
		}
	}

	/**
	 * Get import user template file
	 */
	public static function cb_admin_menu() {
		include self::$template;
	}

	/**
	 * Enqueue all scripts and styles
	 */
	public static function enqueue_scripts() {

		$plugin_base_url = plugins_url( basename( dirname( UO_FILE ) ) );

		$script_url = $plugin_base_url . '/src/assets/legacy/backend/js/import-user.js';
		$style_url  = $plugin_base_url . '/src/assets/legacy/backend/css/import-user.css';

		wp_enqueue_script( self::$module_key, $script_url, array( 'jquery' ), self::$version );
		wp_enqueue_style( self::$module_key, $style_url, false, self::$version );

		// Main CSS file
		wp_enqueue_style( 'ult-admin', \uncanny_learndash_toolkit\Config::get_admin_css( 'style.css' ), array(), UNCANNY_TOOLKIT_VERSION );

		// import validation header
		$import_headers = apply_filters( 'uo_toolkit_csv_import_map', self::$import_map );

		$translation_array = array(
			'max_upload_size'            => wp_max_upload_size(),
			'err_upload_failed'          => esc_html__( 'Something went wrong!', 'uncanny-pro-toolkit' ),
			'err_required_file'          => esc_html__( 'Select the file!', 'uncanny-pro-toolkit' ),
			'err_max_upload'             => esc_html__( 'The file size is too big!', 'uncanny-pro-toolkit' ),
			'err_file_type'              => esc_html__( 'File type must be a csv!', 'uncanny-pro-toolkit' ),
			'err_required_fields'        => esc_html__( 'Username and Email are required!', 'uncanny-pro-toolkit' ),
			'err_test_email_user_empty'  => esc_html__( 'Test user ID is required!', 'uncanny-pro-toolkit' ),
			'uo_verify_required_headers' => isset( $import_headers['user_email'] ) ? $import_headers['user_email'] : 'user_email',
		);

		wp_localize_script( self::$module_key, 'objString', $translation_array );

		wp_localize_script( self::$module_key, 'ULTP_ImportUsers', array(
			'i18n' => array(
				'tooManyRows' => esc_html__( 'CSV file has too many rows. Please decrease rows to 1000.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.tooManyRows

				'requiresUserEmail' => esc_html__( 'Each row in the CSV must have values and requires the user_email column.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.requiresUserEmail

				'allEmailsUnique' => esc_html__( 'CSV requires all user_email cells to be unique', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.allEmailsUnique

				'chooseFile' => esc_html__( 'Please choose a file to upload.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.chooseFile

				'everythingGood' => esc_html__( 'Everything looks good!', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.everythingGood

				'makeSureSubjectFilled' => esc_html__( 'Please check that the email is correct and the subject is filled.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.makeSureSubjectFilled

				'fileMustBeCSV' => esc_html__( 'The file must be a CSV', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.fileMustBeCSV

				'csvFileError' => esc_html__( 'CSV File Error', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.csvFileError

				'csvRequiresHeader' => esc_html__( 'CSV requires %s header.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.csvRequiresHeader

				'csvRequiresOneHeader' => esc_html__( 'CSV requires only one %s header.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.csvRequiresOneHeader

				'fileAPInotSupported' => esc_html__( 'Error: seems File API is not supported. Update your browser.', 'uncanny-pro-toolkit' ),
				// ULTP_ImportUsers.i18n.fileAPInotSupported
			)
		) );

		// Load select2 for base toolkit
		wp_enqueue_style( 'ult-select2', self::get_vendor( 'select2/css/select2.min.css' ), array(), UNCANNY_TOOLKIT_VERSION );
		wp_enqueue_script( 'ult-select2', self::get_vendor( 'select2/js/select2.min.js' ), array( 'jquery' ), UNCANNY_TOOLKIT_VERSION, true );
	}

	/**
	 * Ajax Process #1 : Validate CSV
	 *
	 * @since 1.0.0
	 */
	public static function ajax_file_upload() {

		if ( ! current_user_can( apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ) ) ) {
			$data['error'] = 'You do not have permission to do this.';
			wp_send_json_error( $data );
		}

		// Get CSV from uploaded $_POST
		$csv_input = self::get_csv( filter_input( INPUT_POST, 'csv' ) );

		// Store CSV for later use
		update_option( self::$option_key, filter_input( INPUT_POST, 'csv' ) );

		// Remove all extra spaces
		$csv_input[0] = array_map( 'trim', $csv_input[0] );

		// Mapping custom header
		$import_headers             = apply_filters( 'uo_toolkit_csv_import_map', self::$import_map );
		$uo_verify_required_headers = isset( $import_headers['user_email'] ) ? $import_headers['user_email'] : 'user_email';

		if ( 1 !== count( array_intersect( array( $uo_verify_required_headers ), $csv_input[0] ) ) ) {

			$data['error'] = 'minimum_coulmns';
			wp_send_json_error( $data );
		} else {

			$data['validated_data'] = self::validate_data( $csv_input );
			wp_send_json_success( $data );
		}

		wp_die();
	}

	/**
	 * Process CSV from file
	 *
	 * @since 1.0.0
	 */
	private static function get_csv( $csv_input ) {
		@ini_set( 'auto_detect_line_endings', '1' );
		$csv_input = str_replace( "\r\n", "\n", $csv_input );
		$csv_input = str_replace( "\r", "\n", $csv_input );

		$csv_input = str_getcsv( $csv_input, "\n" );

		$csv_input_temp = array();

		foreach ( $csv_input as $key => $row ) {

			if ( ! empty( $row ) ) {
				$csv_input_temp[] = $row;
			}
		}

		$csv_input = $csv_input_temp;

		unset( $csv_input_temp );

		foreach ( $csv_input as &$row ) {
			$row = str_getcsv( stripcslashes( $row ), ',' );
		}

		return $csv_input;
	}

	/**
	 * Validate CSV fields
	 *
	 * @param $csv_input
	 *
	 * @return mixed
	 */
	private static function validate_data( $csv_input ) {

		// The amount user are going to be updated or added
		$validate_data['total_rows'] = count( $csv_input ) - 1;

		$validate_data['emails'] = self::validate_email_addresses( $csv_input );

		// The learndash_courses column exists
		if ( in_array( 'learndash_courses', $csv_input[0] ) ) {
			$validate_data['courses'] = self::validate_learndash_courses( $csv_input );
		} else {
			// add an empty column
			$new_csv_input = array();
			foreach ( $csv_input as $key => $row ) {
				if ( $key === 0 ) {
					$row[] = 'learndash_courses';
				} else {
					$row[] = '';
				}
				$new_csv_input[] = $row;
			}
			$validate_data['courses'] = self::validate_learndash_courses( $new_csv_input );
		}

		// The groups column exists
		if ( in_array( 'learndash_groups', $csv_input[0] ) ) {
			$validate_data['groups'] = self::validate_learndash_groups( $csv_input );
		} else {
			// add an empty column
			$new_csv_input = array();
			foreach ( $csv_input as $key => $row ) {
				if ( $key === 0 ) {
					$row[] = 'learndash_groups';
				} else {
					$row[] = '';
				}
				$new_csv_input[] = $row;
			}
			$validate_data['groups'] = self::validate_learndash_groups( $new_csv_input );
		}

		// The group_leader column exists
		if ( in_array( 'group_leader', $csv_input[0] ) ) {
			$validate_data['group_leader'] = self::validate_learndash_groups( $csv_input, 'group_leader' );
		} else {
			// add an empty column
			$new_csv_input = array();
			foreach ( $csv_input as $key => $row ) {
				if ( $key === 0 ) {
					$row[] = 'group_leader';
				} else {
					$row[] = '';
				}
				$new_csv_input[] = $row;
			}
			$validate_data['group_leader'] = self::validate_learndash_groups( $new_csv_input, 'group_leader' );
		}

		return $validate_data;

	}

	/**
	 * Validate email addresses
	 *
	 * @param $csv_input
	 *
	 * @return array
	 */
	private static function validate_email_addresses( $csv_input ) {

		$validation = array(
			'new_emails'                => array(),
			'existing_emails'           => array(),
			'malformed_emails'          => array(),
			'import_existing_user_data' => get_option( 'uo_import_existing_user_data', 'update' ),

		);

		// Get column number of user_email
		// Mapping custom header
		$import_headers             = apply_filters( 'uo_toolkit_csv_import_map', self::$import_map );
		$uo_verify_required_headers = isset( $import_headers['user_email'] ) ? $import_headers['user_email'] : 'user_email';
		$user_email_column_key      = array_search( $uo_verify_required_headers, $csv_input[0] );

		// Remove header from CSV and loop through all rows of data
		unset( $csv_input[0] );
		foreach ( $csv_input as $row_key => $row ) {

			$email = trim( $row[ $user_email_column_key ] );

			// check if its a valid email
			$is_email = is_email( trim( stripcslashes( $email ) ) );
			if ( ! $is_email ) {

				$validation['malformed_emails'][ $row_key ] = $email;
				continue;
			}

			// check if email exists, email_exists() return false or the match users ID
			$email_exists = email_exists( $email );
			if ( $email_exists ) {
				$validation['existing_emails'][ $row_key ] = array(
					'user_email' => $email,
					'user_id'    => $email_exists,
					'edit_link'  => get_edit_user_link( $email_exists ),
				);
				continue;
			}

			$validation['new_emails'][ $row_key ] = $email;

		}

		return $validation;
	}

	/**
	 * Validate courses
	 *
	 * @param $csv_input
	 *
	 * @return array
	 */
	private static function validate_learndash_courses( $csv_input ) {

		$uo_import_existing_user_data = get_option( 'uo_import_existing_user_data', 'update' );
		$uo_import_enrol_in_courses   = get_option( 'uo_import_enrol_in_courses' );
		$validation                   = array( 'invalid_learndash_courses' => array() );

		// Get all course IDs
		global $wpdb;
		$post_type = 'sfwd-courses';

		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				$post_type
			)
		);

		// Get column numer of user_email
		$learndash_courses_column_key = array_search( 'learndash_courses', $csv_input[0] );

		// Remove header from CSV and loop through all rows of data
		unset( $csv_input[0] );

		foreach ( $csv_input as $row_key => $row ) {

			$inputted_ids = $row[ $learndash_courses_column_key ];

			if ( ! empty( $row[ $learndash_courses_column_key ] ) ) {
				$csv_course_ids = array_map( 'intval', explode( ';', $row[ $learndash_courses_column_key ] ) );
			} else {
				$csv_course_ids                       = array_map( 'intval', explode( ',', $uo_import_enrol_in_courses ) );
				$row[ $learndash_courses_column_key ] = implode( ';', $csv_course_ids );
			}

			$invalid_ids = array_diff( $csv_course_ids, $course_ids );

			if ( count( $invalid_ids ) ) {
				if ( 'update' === $uo_import_existing_user_data && '' === $row[ $learndash_courses_column_key ] ) {
					continue;
				}
				if ( '' === $inputted_ids ) {
					continue;
				}
				$validation['invalid_learndash_courses'][ $row_key ] = array(
					'invalid_ids'   => $invalid_ids,
					'available_ids' => $course_ids,
					'inputted_ids'  => $row[ $learndash_courses_column_key ],
				);
			}
		}

		return $validation;
	}

	/**
	 * Validate groups
	 *
	 * @param $csv_input
	 *
	 * @return array
	 */
	private static function validate_learndash_groups( $csv_input, $column_key = 'learndash_groups' ) {

		$uo_import_existing_user_data = get_option( 'uo_import_existing_user_data', 'update' );
		$uo_import_add_to_group       = get_option( 'uo_import_add_to_group' );

		$validation = array(
			'invalid_' . $column_key => array(),
		);

		// Get all course IDs
		global $wpdb;
		$post_type = 'groups';
		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE  post_type = %s",
				$post_type
			)
		);

		// Get column numer of user_email
		$learndash_groups_column_key = array_search( $column_key, $csv_input[0] );

		// Remove header from CSV and loop through all rows of data
		unset( $csv_input[0] );

		foreach ( $csv_input as $row_key => $row ) {

			$inputted_ids = $row[ $learndash_groups_column_key ];

			if ( ! empty( $row[ $learndash_groups_column_key ] ) ) {
				$csv_group_ids = array_map( 'intval', explode( ';', $row[ $learndash_groups_column_key ] ) );
			} else {
				$csv_group_ids                       = array_map( 'intval', explode( ',', $uo_import_add_to_group ) );
				$row[ $learndash_groups_column_key ] = implode( ';', $csv_group_ids );
			}

			$invalid_ids = array_diff( $csv_group_ids, $group_ids );

			if ( count( $invalid_ids ) ) {
				if ( 'update' === $uo_import_existing_user_data && '' === $row[ $learndash_groups_column_key ] ) {
					continue;
				}
				if ( '' === $inputted_ids ) {
					continue;
				}
				$validation[ 'invalid_' . $column_key ][ $row_key ] = array(
					'invalid_ids'   => $invalid_ids,
					'available_ids' => $group_ids,
					'inputted_ids'  => $row[ $learndash_groups_column_key ],
				);
			}
		}

		return $validation;
	}

	/**
	 * AJAX save import options
	 */
	public static function ajax_option_checked() {

		if ( ! current_user_can( apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ) ) ) {
			$data['error'] = 'You do not have permission to do this.';
			wp_send_json_error( $data );
		}

		$options = array();

		if ( filter_has_var( INPUT_POST, 'uo_import_add_to_group' ) ) {
			$options['uo_import_add_to_group'] = implode( ',', $_POST['uo_import_add_to_group'] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$options['uo_import_add_to_group'] = '';
		}

		if ( filter_has_var( INPUT_POST, 'uo_import_enrol_in_courses' ) ) {
			$options['uo_import_enrol_in_courses'] = implode( ',', $_POST['uo_import_enrol_in_courses'] );//phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$options['uo_import_enrol_in_courses'] = '';
		}

		if ( filter_has_var( INPUT_POST, 'uo_import_existing_user_data' ) ) {
			$options['uo_import_existing_user_data'] = filter_input( INPUT_POST, 'uo_import_existing_user_data' );
		} else {
			$options['uo_import_existing_user_data'] = '';
		}

		if ( filter_has_var( INPUT_POST, 'uo_import_set_roles' ) ) {
			$options['uo_import_set_roles'] = filter_input( INPUT_POST, 'uo_import_set_roles' );
		} else {
			$options['uo_import_set_roles'] = '';
		}

		foreach ( $options as $meta_key => $meta_value ) {
			update_option( $meta_key, $meta_value );
		}

		$data['message'] = esc_html__( 'Options are successfully saved.', 'uncanny-pro-toolkit' );

		wp_send_json_success( $data );
	}

	/**
	 * @return void
	 */
	private static function setup_email_subject_body() {
		self::$uo_import_users_send_new_user_email    = ( get_option( 'uo_import_users_send_new_user_email', 'false' ) === 'true' ) ? true : false;
		self::$uo_import_users_new_user_email_subject = get_option( 'uo_import_users_new_user_email_subject', esc_html__( 'Your Account Has Been Created', 'uncanny-pro-toolkit' ) );
		if ( '' === self::$uo_import_users_new_user_email_subject ) {
			self::$uo_import_users_new_user_email_subject = esc_html__( 'Your Account Has Been Created', 'uncanny-pro-toolkit' );
		}

		self::$uo_import_users_new_user_email_body = get_option( 'uo_import_users_new_user_email_body', esc_html__( 'Your new user account has been created at %Site URL%.', 'uncanny-pro-toolkit' ) );
		if ( '' === self::$uo_import_users_new_user_email_body ) {
			self::$uo_import_users_new_user_email_body = esc_html__( 'Your new user account has been created at %Site URL%.', 'uncanny-pro-toolkit' );
		}

		self::$uo_import_users_send_updated_user_email = ( get_option( 'uo_import_users_send_updated_user_email', 'false' ) === 'true' ) ? true : false;

		self::$uo_import_users_updated_user_email_subject = get_option( 'uo_import_users_updated_user_email_subject', esc_html__( 'Your Account Has Been Updated', 'uncanny-pro-toolkit' ) );
		if ( '' === self::$uo_import_users_updated_user_email_subject ) {
			self::$uo_import_users_updated_user_email_subject = esc_html__( 'Your Account Has Been Updated', 'uncanny-pro-toolkit' );
		}

		self::$uo_import_users_updated_user_email_body = get_option( 'uo_import_users_updated_user_email_body', esc_html__( 'Your new user account has been updated at %Site URL%.', 'uncanny-pro-toolkit' ) );
		if ( '' === self::$uo_import_users_updated_user_email_body ) {
			self::$uo_import_users_updated_user_email_body = esc_html__( 'Your new user account has been updated at %Site URL%.', 'uncanny-pro-toolkit' );
		}
	}

	/**
	 * Ajax Process #2 : Option Checked & Create User
	 *
	 * @since 1.0.0
	 */
	public static function ajax_perform_import() {

		if ( ! current_user_can( apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ) ) ) {
			$data['error'] = esc_html__( 'You do not have permission to do this.', 'uncanny-pro-toolkit' );
			wp_send_json_error( $data );
		}

		$csv_input = get_option( self::$option_key );

		// Get CSV from uploaded $_POST
		$csv_array  = self::get_csv( $csv_input );
		$csv_header = array_shift( $csv_array );
		$csv_header = array_map( 'trim', $csv_header );
		$status     = get_option( 'user_import_status', 'starting' );

		// This is the first run ever OR the previous run has completed
		if ( 'starting' === $status || 'completed' === $status ) {
			// Reset the progress and start fresh
			update_option( 'user_import_total_rows', count( $csv_array ) );
			update_option( 'user_import_imported_rows', 0 );
			update_option( 'user_import_status', 'processing' );

		}
		self::setup_email_subject_body();
		$total_rows                = get_option( 'user_import_total_rows' );
		$imported_rows             = get_option( 'user_import_imported_rows' );
		$status                    = get_option( 'user_import_status' );
		$data['total_rows']        = $total_rows;
		$key_location              = self::get_key_location( $csv_header );
		$data['new_users']         = 0;
		$data['updated_users']     = 0;
		$data['emails_sent']       = 0;
		$data['rows_ignored']      = 0;
		$data['ignored_rows_data'] = array();
		$option_keys               = array(
			'uo_import_add_to_group',
			'uo_import_enrol_in_courses',
			array( 'uo_import_existing_user_data', 'update' ),
			'uo_import_set_roles',
		);
		$options                   = array();

		foreach ( $option_keys as $meta_key ) {

			if ( is_array( $meta_key ) ) {
				$option = get_option( $meta_key[0], $meta_key[1] );
			} else {
				$option = get_option( $meta_key );
			}

			// all meta value have comma separated values from an array implode except uo_import_existing_user_data
			if ( is_array( $meta_key ) && $meta_key[0] === 'uo_import_existing_user_data' ) {
				$options[ $meta_key[0] ] = $option;
			} else {
				$options[ $meta_key ] = explode( ',', $option );
			}
		}

		$row_queue = $imported_rows + 9;
		for ( $i = $imported_rows; $i <= $row_queue; $i ++ ) {

			if ( $i >= $total_rows ) {
				break;
			}

			$current_row = apply_filters( 'uo_toolkit_csv_import_current_row', $csv_array[ $i ], $csv_header, $key_location );
			$_email      = trim( $current_row[ $key_location['user_email'] ] );

			do_action( 'uo_toolkit_csv_import_before_row_import', $current_row, $csv_header, $key_location );
			// check if email is proper
			if ( ! is_email( stripcslashes( $_email ) ) ) {
				$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				$data['ignored_rows_data'][ $i ] = esc_html__( 'Malformed Email', 'uncanny-pro-toolkit' );
				continue;
			}

			// check if login is too long
			if ( mb_strlen( $current_row[ $key_location['user_login'] ] ) > 60 ) {
				$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				$data['ignored_rows_data'][ $i ] = esc_html__( 'Username is too long', 'uncanny-pro-toolkit' );
				continue;
			}

			// check if login has illegal characters
			if ( isset( $current_row[ $key_location['user_login'] ] ) ) {
				$sanitized_user_name = sanitize_user( $current_row[ $key_location['user_login'] ] );
				if ( $sanitized_user_name !== $current_row[ $key_location['user_login'] ] ) {
					$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
					$data['ignored_rows_data'][ $i ] = esc_html__( 'Username has illegal characters', 'uncanny-pro-toolkit' );
					continue;
				}

				if ( empty( $sanitized_user_name ) || "''" === $sanitized_user_name || '""' === $sanitized_user_name ) {
					$sanitized_user_name = sanitize_user( $_email );
				}
			} else {
				$sanitized_user_name = sanitize_user( $_email );
			}

			$email_exists = email_exists( $_email );

			if ( false === $email_exists ) {

				$password = ( $key_location['user_pass'] ) ? $current_row[ $key_location['user_pass'] ] : wp_generate_password( 12, false );

				// If the user_pass column is available but the cell is empty, generate a password
				if ( '' === $password ) {
					$password = wp_generate_password( 12, false );
				}

				if ( username_exists( $sanitized_user_name ) ) {
					$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
					$data['ignored_rows_data'][ $i ] = esc_html__( 'This username name already exists', 'uncanny-pro-toolkit' );
					continue;
				}

				$userdata = array(
					'user_email' => $_email,
					'user_login' => $sanitized_user_name,
					'user_pass'  => $password,
				);

				$display_name = ( isset( $key_location['display_name'] ) ) ? $current_row[ $key_location['display_name'] ] : '';
				if ( ! empty( $display_name ) ) {
					$userdata['display_name'] = $display_name;
				}

				$user_url             = ( isset( $key_location['user_url'] ) ) ? $current_row[ $key_location['user_url'] ] : '';
				$userdata['user_url'] = $user_url;

				// Remove new user notifications
				if ( ! function_exists( 'wp_new_user_notification' ) ) {
					function wp_new_user_notification() {
					}
				}

				$userdata = apply_filters_deprecated(
					'csv_wp_insert_user',
					array( $userdata, $current_row ),
					'3.7.12',
					'uo_toolkit_csv_import_wp_insert_user'
				);
				$userdata = apply_filters( 'uo_toolkit_csv_import_wp_insert_user', $userdata, $current_row );

				$user_id     = wp_insert_user( $userdata );
				$import_type = 'new_user';

				if ( self::$log_error ) {
					$encode_user_data = wp_json_encode( $userdata );
					$log              = "[ User id: {$user_id}] encode_user_data: {$encode_user_data} password: {$password}\n";
					self::log_error( $log, 'new_user' );
				}
				$user_object = get_user_by( 'ID', $user_id );
				do_action( 'uo_toolkit_csv_import_user_created', $user_object, $current_row, $csv_header, $key_location );
			} else {

				// Check if updating is allowed
				if ( 'update' !== $options['uo_import_existing_user_data'] ) {
					$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
					$data['ignored_rows_data'][ $i ] = esc_html__( 'User Exists, option to update users is off', 'uncanny-pro-toolkit' );
					continue;
				}

				// Emails exists, check if user updates are allow
				$password    = ( $key_location['user_pass'] ) ? $current_row[ $key_location['user_pass'] ] : '';
				$user_object = get_user_by( 'email', $_email );

				if ( ! $user_object ) {
					$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
					$data['ignored_rows_data'][ $i ] = esc_html__( 'User with this email not found.', 'uncanny-pro-toolkit' );
					continue;
				}

				if ( isset( $current_row[ $key_location['user_login'] ] ) && ! empty( $current_row[ $key_location['user_login'] ] ) && strtolower( $user_object->user_login ) !== strtolower( $current_row[ $key_location['user_login'] ] ) ) {

					$login = $current_row[ $key_location['user_login'] ];
					if ( self::$log_error ) {
						$log = "[ User by Email id: $user_object->ID email: $_email User Login id: $user_object->user_login login: $login ]\n";
						self::log_error( $log, 'login_email_match' );
					}

					$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
					$data['ignored_rows_data'][ $i ] = esc_html__( 'A user was found with a matching email address; however, the username in WordPress does not match the username in the spreadsheet.  No update was made to this user.', 'uncanny-pro-toolkit' );
					continue;
				}

				$userdata = array(
					'ID'        => (int) $user_object->ID,
					'user_pass' => $password,
				);

				$display_name = ( isset( $key_location['display_name'] ) ) ? $current_row[ $key_location['display_name'] ] : '';
				if ( ! empty( $display_name ) ) {
					$userdata['display_name'] = $display_name;
				}

				// Remove all updated user notifications
				add_filter( 'send_email_change_email', '__return_false' );
				add_filter( 'send_password_change_email', '__return_false' );

				$userdata = apply_filters_deprecated(
					'csv_wp_update_user',
					array(
						$userdata,
						$current_row,
					),
					'3.7.12',
					'uo_toolkit_csv_import_wp_update_user'
				);
				$userdata = apply_filters( 'uo_toolkit_csv_import_wp_update_user', $userdata, $current_row );

				$user_id = wp_update_user( $userdata );

				if ( self::$log_error ) {
					$encode_user_data = wp_json_encode( $userdata );
					$log              = "[ User id: {$user_id}] encode_user_data: {$encode_user_data} password: {$password}\n";
					self::log_error( $log, 'update_user' );
				}

				$import_type = 'updated_user';
				do_action( 'uo_toolkit_csv_import_user_updated', $user_object, $current_row, $csv_header, $key_location );
			}

			do_action( 'uo_toolkit_csv_import_row_user', $user_object, $current_row, $csv_header, $key_location );

			//On success
			if ( ! is_wp_error( $user_id ) ) {

				if ( 'new_user' === $import_type ) {

					$data['new_users'] += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning

				} elseif ( 'updated_user' === $import_type ) {

					$data['updated_users'] += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning

				}

				// Enroll in Courses
				if ( ! isset( $current_row[ $key_location['learndash_courses'] ] ) ) {
					$current_row[ $key_location['learndash_courses'] ] = '';
				}
				$current_row = self::perform_learndash_coursed_col_import( $user_id, $current_row, $key_location, $options );

				// Enrol in groups
				if ( ! isset( $current_row[ $key_location['learndash_groups'] ] ) ) {
					$current_row[ $key_location['learndash_groups'] ] = '';
				}
				$current_row = self::perform_learndash_groups_col_import( $user_id, $current_row, $key_location, $options );

				// Make groups leader
				if ( ! isset( $current_row[ $key_location['group_leader'] ] ) ) {
					$current_row[ $key_location['group_leader'] ] = '';
				}
				$current_row = self::perform_learndash_group_leader_col_import( $user_id, $current_row, $key_location, $options );

				// Assign Roles
				self::perform_wp_role_col_import( $user_id, $current_row, $key_location, $options );

				// Remove values that are not needed anymore so we can loop the remaining as meta
				unset( $current_row[ $key_location['user_email'] ] );
				if ( isset( $current_row[ $key_location['user_login'] ] ) ) {
					unset( $current_row[ $key_location['user_login'] ] );
				}
				if ( isset( $current_row[ $key_location['user_pass'] ] ) ) {
					unset( $current_row[ $key_location['user_pass'] ] );
				}

				// Any other field is considered a meta key
				self::perform_user_meta_cols_import( $user_id, $current_row, $csv_header );

				$email_text_args = array(
					'user_id'      => $user_id,
					'csv_array'    => $csv_array[ $i ],
					'csv_header'   => $csv_header,
					'key_location' => $key_location,
				);
				if ( 'new_user' === $import_type ) {

					if ( self::$uo_import_users_send_new_user_email ) {

						$email = self::send_email( $_email, self::$uo_import_users_new_user_email_subject, self::$uo_import_users_new_user_email_body, $password, $email_text_args );
						if ( $email ) {
							$data['emails_sent'] += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
						} else {
							$data['ignored_rows_data'][ $i ] = $email;
						}
					}
				}
				if ( 'updated_user' === $import_type ) {

					if ( self::$uo_import_users_send_updated_user_email ) {

						if ( '' === $password ) {
							$password = esc_html__( 'Password has not changed', 'uncanny-pro-toolkit' );
						}

						$email = self::send_email( $_email, self::$uo_import_users_updated_user_email_subject, self::$uo_import_users_updated_user_email_body, $password, $email_text_args );
						if ( $email ) {
							$data['emails_sent'] += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
						} else {
							$data['ignored_rows_data'][ $i ] = esc_html__( 'Email failed to send.', 'uncanny-pro-toolkit' );
						}
					}
				}
				do_action( 'uo_after_user_row_imported', $user_id, $csv_array[ $i ], $csv_header, $key_location, $import_type );
			} else {
				// define error message
				$data['rows_ignored']            += 1; //phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				$data['ignored_rows_data'][ $i ] = $user_id->get_error_message();
			}
		}

		$imported_rows = $i;

		update_option( 'user_import_imported_rows', $imported_rows );

		if ( $imported_rows >= $total_rows ) {
			// Reset the progress and set completed
			update_option( 'user_import_total_rows', count( $csv_array ) );
			update_option( 'user_import_imported_rows', 0 );
			update_option( 'user_import_status', 'completed' );
			update_option( 'user_import_results', array() );

			$status = 'completed';
		}

		$data['imported_rows'] = $imported_rows;
		$data['status']        = $status;
		wp_send_json_success( $data );
	}

	/**
	 * @param $user_id
	 * @param $current_row
	 * @param $key_location
	 * @param $options
	 *
	 * @return mixed
	 */
	private static function perform_learndash_coursed_col_import( $user_id, $current_row, $key_location, $options ) {
		if ( ! isset( $current_row[ $key_location['learndash_courses'] ] ) ) {
			unset( $current_row[ $key_location['learndash_courses'] ] );

			return $current_row;
		}

		if ( '' === $current_row[ $key_location['learndash_courses'] ] ) {
			$csv_course_ids = array_map( 'intval', $options['uo_import_enrol_in_courses'] );
		} else {
			$csv_course_ids = array_map( 'intval', explode( ';', $current_row[ $key_location['learndash_courses'] ] ) );
		}

		// Get all course IDs
		$post_type = 'sfwd-courses';
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				$post_type
			)
		);

		$course_ids = array_map( 'intval', $course_ids );

		if ( self::$log_error ) {
			$t1  = wp_json_encode( $csv_course_ids );
			$t2  = wp_json_encode( $course_ids );
			$log = "[ User id: {$user_id}] csv_course_ids: {$t1} course_ids: {$t2}\n";
			self::log_error( $log, 'enroll_course' );
		}

		foreach ( $csv_course_ids as $csv_course_id ) {
			if ( in_array( $csv_course_id, $course_ids ) ) {
				// add course access
				if ( self::is_learndash_active() ) {
					ld_update_course_access( $user_id, $csv_course_id );
				} else {
					add_user_meta( $user_id, 'import_user_csv_learndash_courses', $csv_course_id );
				}

				if ( self::$log_error ) {
					$log = "[ User id: {$user_id}] csv_course_id: {$csv_course_id}\n";
					self::log_error( $log, 'enroll_course' );
				}
			}
		}

		// Remove values that are needed anymore so we can loop the rest as meta
		unset( $current_row[ $key_location['learndash_courses'] ] );
		do_action( 'uo_toolkit_csv_import_row_course_col', $user_id, $course_ids, $current_row, $key_location );

		return $current_row;
	}

	/**
	 * @param $user_id
	 * @param $current_row
	 * @param $key_location
	 * @param $options
	 *
	 * @return mixed
	 */
	public static function perform_learndash_groups_col_import( $user_id, $current_row, $key_location, $options ) {
		if ( ! isset( $current_row[ $key_location['learndash_groups'] ] ) ) {
			unset( $current_row[ $key_location['learndash_groups'] ] );

			return $current_row;
		}
		global $wpdb;
		$csv_group_ids = array();
		if ( '' === $current_row[ $key_location['learndash_groups'] ] ) {
			$csv_group_ids = array_map( 'intval', $options['uo_import_add_to_group'] );
		} else {
			$csv_group_ids = array_map( 'intval', explode( ';', $current_row[ $key_location['learndash_groups'] ] ) );
		}

		// Get all group IDs
		$post_type = 'groups';

		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				$post_type
			)
		);

		$group_ids = array_map( 'intval', $group_ids );

		if ( self::$log_error ) {
			$t1  = wp_json_encode( $csv_group_ids );
			$t2  = wp_json_encode( $group_ids );
			$log = "[ User id: {$user_id}] csv_group_ids: {$t1} group_ids: {$t2}\n";
			self::log_error( $log, 'enroll_groups' );
		}

		foreach ( $csv_group_ids as $csv_group_id ) {
			if ( in_array( $csv_group_id, $group_ids ) ) {
				if ( self::is_learndash_active() ) {
					// add group access
					ld_update_group_access( $user_id, $csv_group_id );
				} else {
					add_user_meta( $user_id, 'import_user_csv_learndash_groups', $csv_group_id );
				}
				if ( self::is_learndash_active() && class_exists( '\uncanny_learndash_groups\SharedFunctions' ) ) {

					// check if group is converted
					$code_group_id = get_post_meta( $csv_group_id, '_ulgm_code_group_id', true );

					if ( ! empty( $code_group_id ) ) {
						$code = $wpdb->prepare(
							'SELECT code FROM ' . $wpdb->prefix . \uncanny_learndash_groups\SharedFunctions::$db_group_codes_tbl . ' WHERE student_id IS NULL AND user_email IS NULL AND group_id = %d ',
							$code_group_id
						);

						$code_available = $wpdb->get_var( $code );

						if ( ! empty( $code_available ) ) {
							$status = 'Not Started';
							if ( ! class_exists( '\uncanny_learndash_groups\Database' ) ) {
								include_once \uncanny_learndash_groups\Utilities::get_include( 'database.php' );
							}
							\uncanny_learndash_groups\SharedFunctions::set_user_to_code( $user_id, $code_available, $status );
							update_user_meta( $user_id, 'uo_code_status', $code );
						} else {
							// add new code and update it with new user.
							$new_codes = \uncanny_learndash_groups\SharedFunctions::generate_random_codes( 1 );
							if ( ! class_exists( '\uncanny_learndash_groups\Database' ) ) {
								include_once \uncanny_learndash_groups\Utilities::get_include( 'database.php' );
							}
							$attr = array(
								'qty'           => 1,
								'code_group_id' => $code_group_id,
							);
							\uncanny_learndash_groups\Database::add_additional_codes( $attr, $new_codes );
							$existing_seats      = get_post_meta( $csv_group_id, '_ulgm_total_seats', true );
							$new_number_of_seats = $existing_seats + 1;
							update_post_meta( $csv_group_id, '_ulgm_total_seats', $new_number_of_seats );

							$code           = $wpdb->prepare(
								'SELECT code FROM ' . $wpdb->prefix . \uncanny_learndash_groups\SharedFunctions::$db_group_codes_tbl . ' WHERE student_id IS NULL AND user_email IS NULL AND group_id = %d ',
								$code_group_id
							);
							$code_available = $wpdb->get_var( $code );
							$status         = 'Not Started';
							\uncanny_learndash_groups\SharedFunctions::set_user_to_code( $user_id, $code_available, $status );
						}
					}
				}

				if ( self::$log_error ) {
					$log = "[ User id: {$user_id}] csv_group_id: {$csv_group_id}\n";
					self::log_error( $log, 'enroll_course' );
				}
			}
		}

		// Remove values that are needed anymore so we can loop the rest as meta
		unset( $current_row[ $key_location['learndash_groups'] ] );
		do_action( 'uo_toolkit_csv_import_row_groups_col', $user_id, $group_ids, $current_row, $key_location );

		return $current_row;
	}

	/**
	 * @param $user_id
	 * @param $current_row
	 * @param $key_location
	 * @param $options
	 *
	 * @return mixed
	 */
	private static function perform_learndash_group_leader_col_import( $user_id, $current_row, $key_location, $options ) {
		$add_group_leader_role = false;
		if ( ! isset( $current_row[ $key_location['group_leader'] ] ) ) {
			unset( $current_row[ $key_location['group_leader'] ] );

			return $current_row;
		}
		$csv_group_ids = array();
		if ( '' !== $current_row[ $key_location['group_leader'] ] ) {
			$csv_group_ids = array_map( 'intval', explode( ';', $current_row[ $key_location['group_leader'] ] ) );
		}

		// Get all group IDs
		$post_type = 'groups';
		global $wpdb;
		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				$post_type
			)
		);

		$group_ids = array_map( 'intval', $group_ids );

		if ( self::$log_error ) {
			$t1  = wp_json_encode( $csv_group_ids );
			$t2  = wp_json_encode( $group_ids );
			$log = "[ User id: {$user_id}] csv_group_ids: {$t1} group_ids: {$t2}\n";
			self::log_error( $log, 'enroll_as_groupleader' );
		}

		foreach ( $csv_group_ids as $csv_group_id ) {
			if ( in_array( $csv_group_id, $group_ids ) ) {
				if ( self::is_learndash_active() ) {
					// add group leader
					ld_update_leader_group_access( $user_id, $csv_group_id );

					$add_group_leader_role = true;
					// Adding group leader role
					$wp_user = new \WP_User( $user_id );
					if ( $wp_user instanceof WP_User ) {
						$wp_user->add_role( 'group_leader' );
					}
					if ( class_exists( '\uncanny_learndash_groups\SharedFunctions' ) ) {
						if ( 'yes' !== get_option( 'do_not_add_group_leader_as_member', 'no' ) ) {
							// add group access
							ld_update_group_access( $user_id, $csv_group_id );
							// check if group is converted
							if ( 'no' === (string) get_option( 'group_leaders_dont_use_seats', 'no' ) ) {
								$code_group_id = get_post_meta( $csv_group_id, '_ulgm_code_group_id', true );

								if ( ! empty( $code_group_id ) ) {
									$code = $wpdb->prepare(
										'SELECT code FROM ' . $wpdb->prefix . \uncanny_learndash_groups\SharedFunctions::$db_group_codes_tbl . ' WHERE student_id IS NULL AND user_email IS NULL AND group_id = %d ',
										$code_group_id
									);

									$code_available = $wpdb->get_var( $code );

									if ( ! empty( $code_available ) ) {
										$status = 'Not Started';
										if ( ! class_exists( '\uncanny_learndash_groups\Database' ) ) {
											include_once \uncanny_learndash_groups\Utilities::get_include( 'database.php' );
										}
										\uncanny_learndash_groups\SharedFunctions::set_user_to_code( $user_id, $code_available, $status );
										update_user_meta( $user_id, 'uo_code_status', $code );
									} else {
										// add new code and update it with new user.
										$new_codes = \uncanny_learndash_groups\SharedFunctions::generate_random_codes( 1 );
										if ( ! class_exists( '\uncanny_learndash_groups\Database' ) ) {
											include_once \uncanny_learndash_groups\Utilities::get_include( 'database.php' );
										}
										$attr = array(
											'qty'           => 1,
											'code_group_id' => $code_group_id,
										);
										\uncanny_learndash_groups\Database::add_additional_codes( $attr, $new_codes );
										$existing_seats      = get_post_meta( $csv_group_id, '_ulgm_total_seats', true );
										$new_number_of_seats = $existing_seats + 1;
										update_post_meta( $csv_group_id, '_ulgm_total_seats', $new_number_of_seats );

										$code           = $wpdb->prepare(
											'SELECT code FROM ' . $wpdb->prefix . \uncanny_learndash_groups\SharedFunctions::$db_group_codes_tbl . ' WHERE student_id IS NULL AND user_email IS NULL AND group_id = %d ',
											$code_group_id
										);
										$code_available = $wpdb->get_var( $code );
										$status         = 'Not Started';
										\uncanny_learndash_groups\SharedFunctions::set_user_to_code( $user_id, $code_available, $status );
									}
								}
							}
						}
					}
				}
				if ( self::$log_error ) {
					$log = "[ User id: {$user_id}] csv_group_id: {$csv_group_id}\n";
					self::log_error( $log, 'enroll_course' );
				}
			}
		}

		// Remove values that are needed anymore so we can loop the rest as meta
		unset( $current_row[ $key_location['group_leader'] ] );
		do_action( 'uo_toolkit_csv_import_row_group_leaders_col', $user_id, $group_ids, $current_row, $key_location );

		return $current_row;
	}

	/**
	 * @param $user_id
	 * @param $current_row
	 * @param $key_location
	 * @param $options
	 *
	 * @return mixed
	 */
	private static function perform_wp_role_col_import( $user_id, $current_row, $key_location, $options ) {
		if ( isset( $current_row[ $key_location['wp_role'] ] ) ) {

			if ( '' === $current_row[ $key_location['wp_role'] ] ) {
				$csv_role = $options['uo_import_set_roles'][0];
			} else {
				$csv_role = $current_row[ $key_location['wp_role'] ];
			}
			if ( current_user_can( 'manage_options' ) && 'administrator' !== (string) $csv_role ) {
				unset( $current_row[ $key_location['wp_role'] ] );
				$wp_user                         = new \WP_User( $user_id );
				$uo_csv_overwrite_existing_roles = apply_filters( 'uo_csv_overwrite_existing_roles', true );
				if ( ! $uo_csv_overwrite_existing_roles ) {
					$wp_user->add_role( $csv_role );
				} else {
					$wp_user->set_role( $csv_role );
				}
			}
			// Remove values that are needed anymore so we can loop the rest as meta
			unset( $current_row[ $key_location['wp_role'] ] );
		}
		do_action( 'uo_toolkit_csv_import_row_wp_role_col', $user_id, $current_row, $key_location );

		return $current_row;
	}

	/**
	 * @param $user_id
	 * @param $current_row
	 * @param $csv_header
	 *
	 * @return void
	 */
	private static function perform_user_meta_cols_import( $user_id, $current_row, $csv_header ) {
		foreach ( $current_row as $key => $value ) {
			$t1         = wp_json_encode( $current_row );
			$t2         = wp_json_encode( $key );
			$meta_value = wp_json_encode( $value );
			$meta_key   = wp_json_encode( $csv_header[ $key ] );
			if ( '' == $csv_header[ $key ] ) {
				continue;
			}

			$update = update_user_meta( (int) $user_id, $csv_header[ $key ], $value );

			if ( self::$log_error ) {
				$update = wp_json_encode( $update );
				$log    = "[ User id: {$user_id}] current_row: {$t1} index: {$t2} key: {$meta_key} value: {$meta_value} update: {$update}  \n";
				self::log_error( $log, 'meta' );
			}
		}
	}

	/**
	 * @param $columns
	 *
	 * @return array
	 */
	private static function get_key_location( $columns ) {

		$key_location = array();
		$import_map   = apply_filters(
			'uo_toolkit_csv_import_map',
			array(
				'user_login'        => 'user_login',
				'user_email'        => 'user_email',
				'first_name'        => 'first_name',
				'last_name'         => 'last_name',
				'user_pass'         => 'user_pass',
				'wp_role'           => 'wp_role',
				'learndash_courses' => 'learndash_courses',
				'learndash_groups'  => 'learndash_groups',
				'display_name'      => 'display_name',
				'group_leader'      => 'group_leader',
			)
		);

		foreach ( $columns as $key => $v ) {
			$mapped_index                  = array_search( $v, $import_map );
			$key_location[ $mapped_index ] = $key;
		}

		return $key_location;
	}

	/**
	 * Send proccessed email
	 *
	 * @param        $user_email
	 * @param        $email_title
	 * @param        $email_body
	 * @param string $password
	 *
	 * @return bool
	 */
	public static function send_email( $user_email, $email_title, $email_body, $password = 'Password', $email_text_args = array() ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( self::$log_error ) {
			$en_headers = wp_json_encode( $headers );
			$log        = "[ C user_email: {$user_email}] email_title: {$email_title} email_body: {$email_body} headers: {$en_headers}  \n";
			self::log_error( $log, 'email' );
		}

		$email_title = self::convert_mail_text( $user_email, $email_title, $password, $email_text_args );
		$email_body  = self::convert_mail_text( $user_email, $email_body, $password, $email_text_args );
		if ( self::$log_error ) {
			$autop = wpautop( $email_body );
			$log   = "[ D user_email: {$user_email}] email_title: {$email_title} email_body: {$email_body}  wpautop( email_body ): {$autop} headers: {$en_headers}  \n";
			self::log_error( $log, 'email' );
		}

		return wp_mail( $user_email, $email_title, wpautop( $email_body ), $headers );
	}

	/**
	 * Replace variable in email text
	 *
	 * @since 1.0.0
	 */
	private static function convert_mail_text( $user_email, $text, $password = 'Password', $email_text_args = array() ) {

		$wp_user      = get_user_by( 'email', $user_email );
		$user_id      = $wp_user->ID;
		$user_email   = $wp_user->user_email;
		$user_name    = $wp_user->user_login;
		$first_name   = $wp_user->first_name;
		$last_name    = $wp_user->last_name;
		$display_name = $wp_user->data->display_name;

		$text = str_replace( '%Site URL%', get_home_url(), $text );
		$text = str_replace( '%Login URL%', wp_login_url(), $text );
		$text = str_replace( '%Email%', $user_email, $text );
		$text = str_replace( '%Username%', $user_name, $text );
		$text = str_replace( '%First Name%', $first_name, $text );
		$text = str_replace( '%Last Name%', $last_name, $text );
		$text = str_replace( '%Password%', $password, $text );
		$text = str_replace( '%Reset Password Link%', self::generate_reset_token( $user_id ), $text );
		$text = str_replace( '%Display Name%', $display_name, $text );
		if ( self::is_learndash_active() ) {
			// Courses
			$user_courses = array();
			foreach ( ld_get_mycourses( $user_id ) as $course_id ) {
				$course         = get_post( $course_id );
				$user_courses[] = $course->post_title;
			}
			$user_courses = implode( ', ', $user_courses );

			$text = str_replace( '%LD Courses%', $user_courses, $text );

			// Groups
			$user_groups = array();
			foreach ( learndash_get_users_group_ids( $user_id ) as $group_id ) {
				$group         = get_post( $group_id );
				$user_groups[] = $group->post_title;
			}
			$user_groups = implode( ', ', $user_groups );

			$text = str_replace( '%LD Groups%', $user_groups, $text );
		} else {
			$text = str_replace( '%LD Groups%', '', $text );
			$text = str_replace( '%LD Courses%', '', $text );
		}
		// Esc Chars
		$text = str_replace( '\"', '"', $text );
		$text = str_replace( "\'", "'", $text );

		return apply_filters( 'uo_import_users_convert_mail_text', $text, $user_email, $text, $email_text_args );
	}

	/**
	 * AJAX Send test email
	 */
	public static function ajax_test_email() {

		if ( ! current_user_can( apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ) ) ) {
			$data['error'] = esc_html__( 'You do not have permission to do this.', 'uncanny-pro-toolkit' );
			wp_send_json_error( $data );
		}

		if ( self::send_email( filter_input( INPUT_POST, 'user_email_address' ), filter_input( INPUT_POST, 'email_subject' ), filter_input( INPUT_POST, 'email_body' ) ) ) {
			$data['message'] = esc_attr__( 'Email was successfully sent.', 'uncanny-pro-toolkit' );
			$data[]          = filter_input( INPUT_POST, 'user_email_address' );
			$data[]          = filter_input( INPUT_POST, 'email_subject' );
			$data[]          = filter_input( INPUT_POST, 'email_body' );
			wp_send_json_success( $data );
		} else {
			$data['message'] = esc_attr__( 'Otherwise, check your WordPress and server settings.', 'uncanny-pro-toolkit' );
			$data[]          = filter_input( INPUT_POST, 'user_email_address' );
			$data[]          = filter_input( INPUT_POST, 'email_subject' );
			$data[]          = filter_input( INPUT_POST, 'email_body' );
			wp_send_json_error( $data );
		}
	}

	/**
	 * Save email settings
	 */
	public static function ajax_save_email() {

		if ( ! current_user_can( apply_filters( 'toolkit_learndash_user_import_capability', self::$capability ) ) ) {
			$data['error'] = esc_html__( 'You do not have permission to do this.', 'uncanny-pro-toolkit' );
			wp_send_json_error( $data );
		}

		$_POST['new_user_email_body'] = str_replace( '\"', '"', filter_input( INPUT_POST, 'new_user_email_body' ) );
		$_POST['new_user_email_body'] = str_replace( "\'", "'", filter_input( INPUT_POST, 'new_user_email_body' ) );

		$_POST['updated_user_email_body'] = str_replace( '\"', '"', filter_input( INPUT_POST, 'updated_user_email_body' ) );
		$_POST['updated_user_email_body'] = str_replace( "\'", "'", filter_input( INPUT_POST, 'updated_user_email_body' ) );

		update_option( 'uo_import_users_send_new_user_email', filter_input( INPUT_POST, 'send_new_user_email' ) );
		update_option( 'uo_import_users_new_user_email_subject', filter_input( INPUT_POST, 'new_user_email_subject' ) );
		update_option( 'uo_import_users_new_user_email_body', filter_input( INPUT_POST, 'new_user_email_body' ) );

		update_option( 'uo_import_users_send_updated_user_email', filter_input( INPUT_POST, 'send_updated_user_email' ) );
		update_option( 'uo_import_users_updated_user_email_subject', filter_input( INPUT_POST, 'updated_user_email_subject' ) );
		update_option( 'uo_import_users_updated_user_email_body', filter_input( INPUT_POST, 'updated_user_email_body' ) );

		//Testing
		$data['$_POST']  = $_POST;
		$data['message'] = esc_html__( 'Email template is successfully saved.', 'uncanny-pro-toolkit' );
		wp_send_json_success( $data );

	}

	/**
	 * @param $user_id
	 *
	 * @return bool|string
	 */
	public static function generate_reset_token( $user_id ) {

		$user = get_user_by( 'ID', $user_id );
		if ( $user ) {
			$adt_rp_key = get_password_reset_key( $user );
			$user_login = $user->user_login;
			$url        = network_site_url( "wp-login.php?action=rp&key=$adt_rp_key&login=" . rawurlencode( $user_login ), 'login' );
			$text       = __( 'Click here to reset your password.', 'uncanny-pro-toolkit' );
			$rp_link    = sprintf( '<a href="%s">%s</a>', $url, $text );
		} else {
			$rp_link = '';
		}

		return $rp_link;

	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'import-users';

		$class_title = self::$module_name;

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/import-learndash-users/ ';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Create or update users (optionally) and assign them to courses and LearnDash Groups from a CSV file.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-upload"></i><span class="uo_pro_text">PRO</span>';

		$category = 'WordPress';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => false,
			'icon'             => $class_icon,
		);

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {
		return true;
	}

	/**
	 * @return bool
	 */
	public static function is_learndash_active() {
		return defined( 'LEARNDASH_VERSION' );
	}

	/**
	 * @param $log
	 * @param $filename
	 *
	 * @return void
	 */
	private static function log_error( $log, $filename = 'new_user' ) {
		Boot::trace_logs( $log, '', "import-user-{$filename}" );
	}
}
