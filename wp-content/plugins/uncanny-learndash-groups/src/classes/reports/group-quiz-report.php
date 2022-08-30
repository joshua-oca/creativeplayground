<?php

namespace uncanny_learndash_groups;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Sample
 * @package uncanny_learndash_groups
 */
class GroupQuizReport {

	/**
	 * Rest API root path
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $root_path = 'ulgm_quiz_report/v1';

	/**
	 * @var array
	 */
	static $ulgm_reporting_shortcode = array();

	/**
	 * Rest API root path
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	static $group_drop_downs = false;

	/**
	 * Course order by
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $course_orderby = '';

	/**
	 * Course order
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $course_order = '';

	/**
	 * Course order by
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $quiz_orderby = '';

	/**
	 * Course order
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $quiz_order = '';

	/**
	 * Columns that will be visible in the course report
	 *
	 * @since    3.8
	 * @access   static
	 * @var      array
	 */

	static $table_columns = array();
	/**
	 * @var string
	 */
	private static $score_type = 'percent';

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'run_frontend_hooks' ) );

		//register api class
		add_action( 'rest_api_init', array( $this, 'uo_api' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 *
	 */
	public function run_frontend_hooks() {

		//add_action( 'wp_enqueue_scripts', array( $this, 'report_scripts' ) );
		add_shortcode( 'uo_groups_quiz_report', array( $this, 'display_quiz_report' ) );
	}

	/*
	 * Display the shortcode
	 * @param array $attributes
	 *
	 * @return string $html header and table
	 */
	/**
	 * @param $request
	 *
	 * @return false|string|void
	 */
	public function display_quiz_report( $request ) {

		$request = shortcode_atts(
			array(
				'course-orderby' => 'title',
				'course-order'   => 'ASC',
				'quiz-orderby'   => 'title',
				'quiz-order'     => 'ASC',
				'columns'        => 'user_name,first_name,last_name,user_email,quiz_score,quiz_modal,quiz_date',
				'score-type'     => 'percent', //percent|points
			),
			$request
		);

		$course_orderby   = $request['course-orderby'];
		self::$score_type = $request['score-type'];

		if ( ! in_array( $course_orderby, array( 'ID', 'title', 'date', 'menu_order' ) ) ) {
			self::$course_orderby = 'title';
		} else {
			self::$course_orderby = $request['course-orderby'];
		}

		$course_order = $request['course-order'];

		if ( ! in_array( $course_order, array( 'ASC', 'DESC' ) ) ) {
			self::$course_order = 'ASC';
		} else {
			self::$course_order = $request['course-order'];
		}

		$quiz_orderby = $request['quiz-orderby'];

		if ( ! in_array( $quiz_orderby, array( 'ID', 'title', 'date', 'menu_order' ) ) ) {
			self::$quiz_orderby = 'title';
		} else {
			self::$quiz_orderby = $request['quiz-orderby'];
		}

		$quiz_order = $request['quiz-order'];

		if ( ! in_array( $quiz_order, array( 'ASC', 'DESC' ) ) ) {
			self::$quiz_order = 'ASC';
		} else {
			self::$quiz_order = $request['quiz-order'];
		}

		// Columns that are available to be set in the table
		$allowed_columns = array(
			'user_name',
			'first_name',
			'last_name',
			'user_email',
			'quiz_score',
			'quiz_modal',
			'quiz_date',
		);

		// Set column visibility
		if ( isset( $request['columns'] ) && ! empty( $request['columns'] ) ) {

			// Columns that the shortcode requested to show
			$columns = explode( ',', $request['columns'] );
			$columns = array_filter( array_map( 'trim', $columns ) );

			if ( ! empty( $columns ) ) {
				foreach ( $columns as $column ) {
					if ( in_array( $column, $allowed_columns ) ) {
						self::$table_columns[] = $column;
					}
				}
			}
		}

		if ( empty( self::$table_columns ) ) {
			self::$table_columns = $allowed_columns;
		}

		$html = self::generate_quiz_report_html();

		self::report_scripts();

		return $html;
	}

	/*
	 * Generate transcript HTML Output
	 *
	 * @return string
	 */
	/**
	 * @return false|string|void
	 */
	public static function generate_quiz_report_html() {

		//  Build Table Data
		$table             = array();
		$table['headings'] = array(
			\LearnDash_Custom_Label::get_label( 'quiz' ),
			sprintf( __( '%s Score', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quiz' ) ),
			__( 'Answers', 'uncanny-learndash-groups' ),
		);

		self::$ulgm_reporting_shortcode['text']['group_management_link'] = SharedFunctions::get_group_management_page_id( true );

		self::$ulgm_reporting_shortcode['text']['group_management'] = __( 'Back to Group Management', 'uncanny-learndash-groups' );

		return self::create_quiz_table( $table['headings'] );
	}

	/**
	 * @param $headings
	 *
	 * @return false|string|void
	 */
	public static function create_quiz_table( $headings ) {
		$user_id = get_current_user_id();

		// Is the user logged in
		if ( ! $user_id ) {
			return __( 'Please log in to view the report.', 'uncanny-learndash-groups' );
		}

		$allowed_roles = apply_filters(
			'ulgm_gm_allowed_roles',
			array(
				'administrator',
				'group_leader',
				'ulgm_group_management',
			)
		);
		// Is the user a group leader
		if ( array_intersect( wp_get_current_user()->roles, $allowed_roles ) ) {

			// Load Selection options for group and quiz list
			$group_drop_downs = self::get_groups_drop_downs( $user_id );

		} else {
			return __( 'You must be a admin or group leader to access this page.', 'uncanny-learndash-groups' );
		}

		// Create HTML table headings
		$headings_html = '';
		foreach ( $headings as $heading ) {
			$headings_html .= '<th>' . $heading . '</th>';
		}

		ob_start();

		if ( defined( 'LEARNDASH_LMS_PLUGIN_URL' ) && function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
			$icon = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/statistics-icon-small.png';
			?>
			<style>
				.statistic_icon {
					background: url(<?php echo $icon; ?>) no-repeat scroll 0 0 transparent;
					width: 23px;
					height: 23px;
					margin: auto;
					background-size: 23px;
				}
			</style>
			<?php
		}

		// Load Learndash Quiz modal window assets and localized data
		self::get_ld_modal();

		\LD_QuizPro::showModalWindow();

		?>

		<div class="uo-groups uo-quiz-report">
			<?php if ( ! empty( SharedFunctions::get_group_management_page_id() ) && ! empty( SharedFunctions::get_group_quiz_report_page_id() ) ) : ?>
				<div class="uo-row uo-groups-section uo-groups-report-go-back">
					<div class="uo-groups-actions">
						<div class="group-management-buttons">
							<button class="ulgm-link uo-btn uo-left uo-btn-arrow-left"
									onclick="location.href='<?php echo self::$ulgm_reporting_shortcode['text']['group_management_link']; ?>'"
									type="button">
								<?php echo self::$ulgm_reporting_shortcode['text']['group_management']; ?>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div class="uo-row uo-groups-section uo-groups-selection">

				<div class="group-management-form">

					<div class="uo-groups-select-filters">
						<?php if ( isset( $group_drop_downs['groups'] ) ) { ?>
							<div class="uo-row uo-groups-select-filter">
								<div class="uo-select">
									<label><?php _e( 'Group', 'uncanny-learndash-groups' ); ?></label>
									<select class="change-group-management-form"
											id="uo-group-report-group"><?php echo $group_drop_downs['groups']; ?></select>
								</div>
							</div>
						<?php } ?>
						<?php if ( isset( $group_drop_downs['courses'] ) ) { ?>
							<div class="uo-row uo-groups-select-filter">
								<div class="uo-select">
									<label><?php echo sprintf( _x( '%s', 'Course', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ); ?></label>
									<select class="change-group-management-form" style="display:none;"
											id="uo-group-report-courses"><?php echo $group_drop_downs['courses']; ?></select>
									<div id="uo-group-report-nocourses" class="group-management-rest-message"
										 style="display: none;"><?php echo sprintf( __( 'No %s found.', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ); ?></div>
								</div>
							</div>
						<?php } ?>
						<?php if ( isset( $group_drop_downs['quizzes'] ) ) { ?>
							<div class="uo-row uo-groups-select-filter">
								<div class="uo-select">
									<label><?php _e( \LearnDash_Custom_Label::get_label( 'quiz' ), 'uncanny-learndash-groups' ); ?></label>
									<select class="change-group-management-form" style=""
											id="uo-group-report-quizzes"><?php echo $group_drop_downs['quizzes']; ?></select>
									<div id="uo-group-report-noquizzes" class="group-management-rest-message"
										 style="display: none;"><?php echo sprintf( _x( 'No %s found.', 'No quizzes found.', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quizzes' ) ); ?></div>
								</div>
							</div>
						<?php } ?>
					</div>

				</div>
			</div>

			<div class="uo-row uo-groups-table">
				<table id="uo-quiz-report-table-hidden" class="display responsive no-wrap uo-table-datatable"
					   cellspacing="0" width="100%"></table>

				<table id="uo-quiz-report-table" class="display responsive no-wrap uo-table-datatable" cellspacing="0"
						<?php echo 'data-table_columns="' . implode( ',', self::$table_columns ) . '"'; ?>
					   width="100%"></table>
			</div>
		</div>

		<style>
			.uo-groups .uo-select select.h3-select {
				background: none !important;
				border: none;
				-webkit-box-shadow: none;
				box-shadow: none;
				font-size: 18px;
				font-weight: bold;
				padding-left: 0;
				padding-top: 0;
				/*for firefox*/
				-moz-appearance: none;
				/*for chrome*/
				-webkit-appearance: none;
			}

			/*for IE10*/
			.uo-groups .uo-select select.h3-select::-ms-expand {
				display: none;
			}

			.showme {
				display: block !important;
			}
		</style>

		<script>
			jQuery(document).ready(function () {

				let groupSelect = jQuery('#uo-group-report-group');
				let groupSelectOptions = jQuery('#uo-group-report-group option');

				if (2 === groupSelectOptions.length) {
					groupSelect.addClass('h3-select');
					let groupId = jQuery(groupSelect.find('option')[1]).val();
					window.ulgmGroupManagement.reporting.groupId = groupId;
					window.ulgmGroupManagement.reporting.scoreType = '<?php echo self::$score_type; ?>';
					groupSelect.val(groupId).trigger('change');

					groupSelect.prop('disabled', 'disabled');

					let courseGroupQuizzes = groupQuizReportSetup.courseGroupQuizzes[groupId];
					let coursesSelect = jQuery('#uo-group-report-courses');
					let coursesSelectOptions = jQuery('#uo-group-report-courses option');

					if (2 === coursesSelectOptions.length) {

						coursesSelect.show();
						coursesSelect.addClass('h3-select');
						// below line commented BY_AC
						//let courseId = jQuery( coursesSelect.find('option')[1] ).val();
						let courseId = jQuery(coursesSelect.find('option')[1]).val();
						window.ulgmGroupManagement.reporting.courseId = courseId;
						coursesSelect.val(courseId).trigger('change');

						coursesSelect.prop('disabled', 'disabled');

						jQuery('#uo-group-report-quizzes').show();
						jQuery('#uo-group-report-quizzes option').hide();
						jQuery('#uo-group-report-quizzes option').removeAttr('selected');
						// below line commented BY_AC
						//jQuery('#uo-group-report-quizzes option[value=0]').attr("selected", "selected");
						jQuery('#uo-group-report-quizzes option[value=0]').show();

						var groupsQuizzes = groupQuizReportSetup.relationships[window.ulgmGroupManagement.reporting.groupId][courseId];

						if (typeof groupsQuizzes !== 'undefined' && groupsQuizzes.length > 0) {
							jQuery.each(groupsQuizzes, function (key, quizId) {
								jQuery('#uo-group-report-quizzes option[value=' + quizId + ']').addClass('showme');
								jQuery('#uo-group-report-quizzes option[value=' + quizId + ']').css('display', 'block');
								jQuery('#uo-group-report-quizzes option[value=' + quizId + ']').show();
							});
							// need a delay and let jquery finish actions in each loop
							setTimeout(function () {
								jQuery('#uo-group-report-quizzes').trigger('change');
							}, 200);
						} else {
							jQuery('#uo-group-report-quizzes option').hide();
							jQuery('#uo-group-report-quizzes option[value=' + 0 + ']').show();
							//jQuery('#uo-group-report-noquizzes').show();
						}
					} else {
						coursesSelect.show();
						jQuery('#uo-group-report-courses option').hide();
						jQuery('#uo-group-report-courses option').removeAttr('selected');
						jQuery('#uo-group-report-courses option[0]').attr("selected", "selected");
						jQuery('#uo-group-report-courses option[0]').show();
						jQuery('#uo-group-report-courses option[value=' + 0 + ']').show();
						jQuery.each(courseGroupQuizzes, function (key, courseId) {
							jQuery('#uo-group-report-courses option[value=' + courseId + ']').show();
						});
						setTimeout(function () {
							jQuery('#uo-group-report-courses').trigger('change');
						}, 400);
					}
					window.ulgmGroupManagement.reporting.groupId = groupId;
					window.ulgmGroupManagement.reporting.scoreType = '<?php echo self::$score_type; ?>';
				} else {
					// need a delay and let jquery finish actions in each loop
					setTimeout(function () {
						let groupId = jQuery(groupSelect.find('option')[0]).val();
						window.ulgmGroupManagement.reporting.groupId = groupId;
						window.ulgmGroupManagement.reporting.scoreType = '<?php echo self::$score_type; ?>';

						groupSelect.val(groupId).trigger('change');

					}, 200);
				}
			})
		</script>

		<?php

		return ob_get_clean();
	}

	/**
	 * Loads Javascript files and variables used for the Quiz statistics modal
	 * We are leveraging it for our reports
	 */
	public static function get_ld_modal() {

		global $learndash_assets_loaded;

		if ( isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) {

			wp_enqueue_script( 'ulgm-frontend', Utilities::get_asset( 'frontend', 'bundle.min.js' ), array( 'jquery' ), Utilities::get_version() );

			$data            = array();
			$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
			$data            = array( 'json' => json_encode( $data ) );

			wp_localize_script( 'ulgm-frontend', 'sfwd_data', $data );
		}
	}

	/*
	 * Get all Groups the group leader is an administrator of
	 * @since
	 *
	 * @param int $group_leader_id
	 *
	 * @return string html
	 */
	/**
	 * @param int $user_id
	 *
	 * @return bool|string|void
	 */
	public static function get_groups_drop_downs( $user_id = 0 ) {

		if ( false !== self::$group_drop_downs ) {
			return self::$group_drop_downs;
		}

		$user_id = get_current_user_id();

		if ( ! user_can( $user_id, 'group_leader' ) && ! user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		// User is a group leader, get users groups
		$is_hierarchy_setting_enabled = false;
		if ( function_exists( 'learndash_is_groups_hierarchical_enabled' ) && learndash_is_groups_hierarchical_enabled() && 'yes' === get_option( 'ld_hierarchy_settings_child_groups', 'no' ) ) {
			$is_hierarchy_setting_enabled = true;
			if ( ! class_exists( 'Walker_GroupDropdown' ) ) {
				include_once Utilities::get_include( 'class-walker-group-dropdown.php' );
			}
			$dropdown_args = array(
				'selected'     => 0,
				'sort_column'  => 'post_title',
				'hierarchical' => true,
			);
			$walker        = new \Walker_GroupDropdown();
			// User is a group leader, get users groups ... We already verified that the user is already a group leader
			$user_groups = learndash_get_administrators_group_ids( $user_id );

		} else {
			// User is a group leader, get users groups ... We already verified that the user is already a group leader
			$user_groups = learndash_get_administrators_group_ids( $user_id, true );
		}

		if ( empty( $user_groups ) ) {
			return __( 'You are not a leader of any groups.', 'uncanny-learndash-groups' );
		}

		// LD returns a array of IDs as strings, refactor to Int
		$posts_in = array_map( 'intval', $user_groups );

		$args = array(
			'post_type'      => 'groups',
			'post__in'       => $posts_in,
			'posts_per_page' => 9999,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$args = apply_filters( 'quiz_group_dropdown', $args, $user_id, $posts_in );

		$group_post_objects = new \WP_Query( $args );

		$drop_down['groups'] = '<option value="0">' . __( 'Select Group', 'uncanny-learndash-groups' ) . '</option>';
		//$drop_down['groups'] = '';
		$drop_down['quizzes_objects'] = array();

		// Collect all the quiz IDs so we can query then altogether
		$course_ids = array();
		$quiz_ids   = array();

		if ( $group_post_objects->have_posts() ) {
			while ( $group_post_objects->have_posts() ) {
				$group_post_objects->the_post();
				$drop_down['groups'] .= '<option value="' . get_the_ID() . '">' . get_the_title() . '</option>';

				$group_quizzes                                = self::group_quizzes( get_the_ID() );
				$drop_down['quizzes_objects'][ get_the_ID() ] = $group_quizzes['group_quiz_ids'];

				$drop_down['course_quizzes_objects'][ get_the_ID() ] = $group_quizzes['group_course_quizzes'];

				$course_ids = array_merge( $course_ids, $group_quizzes['group_course_quizzes'] );
				$quiz_ids   = array_merge( $quiz_ids, $group_quizzes['group_quiz_ids'] );

				$drop_down['relationships'][ get_the_ID() ] = $group_quizzes['relationships'];

			}
			/* Restore original Post Data */
			wp_reset_postdata();
			// Re-arrange groups in hierarchy view
			if ( $is_hierarchy_setting_enabled ) {
				$drop_down['groups'] = '<option value="0">' . __( 'Select Group', 'uncanny-learndash-groups' ) . '</option>';
				if ( $walker instanceof \Walker_GroupDropdown ) {
					$drop_down['groups'] .= $walker->walk( $group_post_objects->posts, 0, $dropdown_args );
				}
			}
		} else {
			// no posts found
			$drop_down['groups'] = '<option value="0">' . __( 'No Groups', 'uncanny-learndash-groups' ) . '</option>';
		}

		// Get Courses
		$course_ids = array_unique( $course_ids );

		$courses = self::get_objects( $course_ids, 'sfwd-courses', self::$course_orderby, self::$course_order );

		if ( ! empty( $courses ) ) {
			// below line commented BY_AC
			$drop_down['courses'] = '<option value="0">' . sprintf( __( 'Select %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'course' ) ) . '</option>';
			//$drop_down['courses'] = '';
			foreach ( $courses as $course ) {
				$drop_down['courses'] .= '<option value="' . $course->ID . '"  style="display:none">' . $course->post_title . '</option>';

			}
		} else {
			$drop_down['courses'] = '<option value="0">' . sprintf( __( 'No %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ) . '</option>';
		}

		// Get Quizzes
		$quiz_ids = array_unique( $quiz_ids );

		$quizzes = self::get_objects( $quiz_ids, 'sfwd-quiz', self::$quiz_orderby, self::$quiz_order );

		if ( ! empty( $quizzes ) ) {
			// below line commented BY_AC
			$drop_down['quizzes'] = '<option value="0">' . sprintf( __( 'Select %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quiz' ) ) . '</option>';
			//$drop_down['quizzes'] = '';
			foreach ( $quizzes as $quiz ) {
				$drop_down['quizzes'] .= '<option value="' . $quiz->ID . '" style="display:none">' . $quiz->post_title . '</option>';

			}
		} else {
			$drop_down['quizzes'] = '<option value="0">' . sprintf( _x( 'No %s', 'No quizzes', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quizzes' ) ) . '</option>';
		}

		$drop_down = apply_filters( 'ulgm_quiz_report_get_groups_drop_downs', $drop_down, $user_id );

		// Cache results so we don't re-query
		self::$group_drop_downs = $drop_down;

		return $drop_down;
	}

	/**
	 * Get groups course quizzes
	 *
	 * @param int $group_id
	 *
	 * @return mixed
	 */
	public static function group_quizzes( $group_id = 0 ) {
		$group_quiz_ids = array();

		$relationships = array();
		if ( ! empty( $group_id ) ) {

			$group_course_ids = LearndashFunctionOverrides::learndash_group_enrolled_courses( intval( $group_id ) );

			if ( ! empty( $group_course_ids ) ) {
				foreach ( $group_course_ids as $course_id ) {
					$group_quiz_query_args = apply_filters(
						'ulgm_group_quiz_query_args',
						array(
							'post_type'  => 'sfwd-quiz',
							'nopaging'   => true,
							'orderby'    => 'title',
							'order'      => 'ASC',
							'fields'     => 'ids',
							'meta_query' => array(
								array(
									'relation' => 'OR',
									array(
										'key'     => 'course_id',
										'value'   => $course_id,
										'compare' => '=',
									),
									array(
										'key'     => 'ld_course_' . $course_id,
										'value'   => $course_id,
										'compare' => '=',
									),
								),
							),
						)
					);

					$group_quiz_query = new \WP_Query( $group_quiz_query_args );

					if ( ! empty( $group_quiz_query->posts ) ) {
						$group_quiz_ids = array_merge( $group_quiz_ids, $group_quiz_query->posts );
						$group_quiz_ids = array_unique( $group_quiz_ids );
						if ( isset( $relationships[ $course_id ] ) ) {
							$relationships[ $course_id ] = array_unique( array_merge( $relationships[ $course_id ], $group_quiz_query->posts ) );

						} else {
							$relationships[ $course_id ] = $group_quiz_query->posts;

						}
					}
				}
			}
		}

		return array(
			'group_quiz_ids'       => $group_quiz_ids,
			'group_course_quizzes' => $group_course_ids,
			'relationships'        => $relationships,
		);
	}

	/**
	 * Get all quiz post objects
	 *
	 * @param array $quiz_ids
	 *
	 * @return array $_quizzes
	 */
	public static function get_objects( $ids, $post_type, $order_by = 'title', $order = 'ASC' ) {

		if ( empty( $order_by ) ) {
			$order_by = 'title';
		}

		if ( empty( $order ) ) {
			$order = 'ASC';
		}

		if ( empty( $ids ) ) {
			return array();
		}

		$args = array(
			'post_type'      => $post_type,
			'post__in'       => $ids,
			'posts_per_page' => 9999,
			'orderby'        => $order_by,
			'order'          => $order,
		);

		$quizzes = get_posts( $args );

		// Set the Key as the post ID so we don't have to run a nested loop
		$_quizzes = array();
		foreach ( $quizzes as $quiz ) {
			$_quizzes[ $quiz->ID ] = $quiz;
		}

		return $_quizzes;

	}


	/*
	 * Register rest api endpoints
	 *
	 */
	/**
	 *
	 */
	public function uo_api() {

		register_rest_route(
			self::$root_path,
			'/get_quiz_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_quiz_data' ),
				'permission_callback' => function () {
					return RestApiEndPoints::permission_callback_check();
				},
			)
		);

	}

	/**
	 * @return array
	 */
	public function get_quiz_data() {

		$data = $_POST;

		// validate inputs
		$quiz_ID    = absint( $data['quizId'] );
		$score_type = isset( $data['scoreType'] ) ? sanitize_text_field( $data['scoreType'] ) : 'percent';

		// if any of the values are 0 then they didn't validate
		if ( 0 === $quiz_ID ) {
			$return_object['message'] = sprintf( __( 'invalid %s id supplied', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			$return_object['groupId'] = $data['courseId'];

			return $return_object;
		}

		// validate inputs
		$group_ID = absint( $data['groupId'] );

		// if any of the values are 0 then they didn't validate
		if ( 0 === $group_ID ) {
			$return_object['message'] = __( 'invalid group id supplied', 'uncanny-learndash-groups' );
			$return_object['groupId'] = $data['groupId'];

			return $return_object;
		}

		$quiz_table = self::quiz_table( $quiz_ID, $group_ID, $score_type );

		$quiz_table = apply_filters( 'ulgm_rest_api_get_quiz_data', $quiz_table, $_POST );

		return $quiz_table;

	}

	/**
	 * Return html for the quizzes table
	 *
	 * @param $quiz_ID
	 * @param $group_ID
	 *
	 * @return array
	 *
	 */
	public static function quiz_table( $quiz_ID, $group_ID, $score_type = 'percent' ) {

		global $learndash_shortcode_used;

		$group_users = LearndashFunctionOverrides::learndash_get_groups_user_ids( $group_ID, true );

		$user_data = Utilities::get_users_with_meta(
			array(
				'_sfwd-quizzes',
				'first_name',
				'last_name',
			),
			array(),
			$group_users
		);

		$data             = array();
		$html_vars        = array();
		$matched_user_ids = array();

		// $user_data returned all users data. Let remove all non-members of group
		// ToDo get_users_with_meta() can be modified to only get group users for a performance tweak
		foreach ( $user_data['results'] as $user ) {
			if ( in_array( (int) $user['ID'], $group_users ) ) {
				$data[ $user['ID'] ] = $user;
			}
		}

		$learndash_shortcode_used = true;

		foreach ( $data as $user_id => $user ) {

			$quiz_attempts_meta = empty( $user['_sfwd-quizzes'] ) ? false : $user['_sfwd-quizzes'];

			if ( ! empty( $quiz_attempts_meta ) ) {

				$quiz_attempts_meta = maybe_unserialize( $quiz_attempts_meta );

				//Utilities::log( $quiz_attempts_meta, '$quiz_attempts_meta', true, 'quiz-report' );

				foreach ( $quiz_attempts_meta as $quiz_attempt ) {

					if ( (int) $quiz_attempt['quiz'] !== $quiz_ID ) {
						continue;
					}

					$modal_link = '';

					$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

					if ( ( isset( $quiz_attempt['has_graded'] ) ) && ( true === $quiz_attempt['has_graded'] ) && ( true === \LD_QuizPro::quiz_attempt_has_ungraded_question( $quiz_attempt ) ) ) {
						$score = _x( 'Pending', 'Pending Certificate Status Label', 'learndash' );
					} else {
						if ( 'percent' === $score_type ) {
							$score = round( $quiz_attempt['percentage'], 2 ) . __( '%', 'uncanny-learndash-groups' );
						} elseif ( 'points' === $score_type ) {
							$score = sprintf( '%d/%d', $quiz_attempt['points'], $quiz_attempt['total_points'] );
						}
					}
					$score = apply_filters( 'ulgm_quiz_report_user_score', $score, $user, $quiz_attempt, $score_type );

					if ( intval( $quiz_attempt['statistic_ref_id'] ) ) {
						$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . wp_create_nonce( 'statistic_nonce_' . $quiz_attempt['statistic_ref_id'] . '_' . get_current_user_id() . '_' . $user['ID'] ) . '"
									     data-user_id="' . $user['ID'] . '"
									     data-quiz_id="' . $quiz_attempt['pro_quizid'] . '"
									     data-ref_id="' . intval( $quiz_attempt['statistic_ref_id'] ) . '"
									     data-nonce="' . wp_create_nonce( 'wpProQuiz_nonce' ) . '"
									     href="#">';
						$modal_link .= '<div class="statistic_icon"></div>';
						$modal_link .= '</a>';
					} else {
						$modal_link = __( 'No stats recorded', 'uncanny-learndash-groups' );
					}

					$date               = learndash_adjust_date_time_display( $quiz_attempt['time'] );
					$date               = ! empty( $date ) ? '<span class="ulg-hidden-data" style="display: none;">' . $quiz_attempt['time'] . '</span>' . $date : '';
					$matched_user_ids[] = $user['ID'];
					$html_vars[]        = (object) array(
						'ID'         => $user['ID'],
						'user_name'  => $user['user_login'],
						'user_email' => $user['user_email'],
						'first_name' => $user['first_name'],
						'last_name'  => $user['last_name'],
						'quiz_score' => $score,
						'quiz_modal' => $modal_link,
						'quiz_date'  => $date,
					);
				}
			}
		}

		if ( false === apply_filters( 'ulgm_quiz_report_hide_unattempted_users', false, $quiz_ID, $group_ID ) ) {
			$array_unique = array_diff( array_merge( $matched_user_ids, $group_users ), array_intersect( $matched_user_ids, $group_users ) );
			if ( isset( $array_unique ) && ! empty( $array_unique ) ) {
				foreach ( $array_unique as $user_id ) {
					$user_info   = $data[ $user_id ];
					$score       = _x( 'Pending', 'Pending Certificate Status Label', 'learndash' );
					$modal_link  = __( 'No stats recorded', 'uncanny-learndash-groups' );
					$html_vars[] = (object) array(
						'ID'         => $user_id,
						'user_name'  => $user_info['user_login'],
						'user_email' => $user_info['user_email'],
						'first_name' => $user_info['first_name'],
						'last_name'  => $user_info['last_name'],
						'quiz_score' => $score,
						'quiz_modal' => $modal_link,
						'quiz_date'  => '__',
					);
				}
			}
		}

		return apply_filters( 'ulgm_quiz_report_user_data', $html_vars, $quiz_ID, $group_ID, $group_users );
	}

	/**
	 *
	 */
	public static function report_scripts() {
		global $post;

		if ( Utilities::has_shortcode( $post, 'uo_groups_quiz_report' ) || Utilities::has_block( $post, 'uncanny-learndash-groups/uo-groups-quiz-report' ) ) {
			self::enqueue_frontend_assets();
		}
	}

	/**
	 * @since    3.7.5
	 * @author   Agus B.
	 * @internal Saad S.
	 */
	public static function enqueue_frontend_assets() {
		global $post;

		if ( ! empty( $post ) ) {
			// DataTables
			wp_enqueue_script(
				'ulgm-datatables',
				Utilities::get_vendor( 'datatables/datatables.min.js' ),
				array( 'jquery' ),
				Utilities::get_version(),
				true
			);

			wp_enqueue_style(
				'ulgm-datatables',
				Utilities::get_vendor( 'datatables/datatables.min.css' ),
				array(),
				Utilities::get_version()
			);

			// Front End Questionnaire JS
			wp_register_script(
				'ulgm-frontend',
				Utilities::get_asset( 'frontend', 'bundle.min.js' ),
				array(
					'jquery',
					'ulgm-datatables',
				),
				Utilities::get_version(),
				true
			);

			$group_drop_downs = self::get_groups_drop_downs();

			$learndash_version_compare = version_compare( LEARNDASH_VERSION, '3.2', '>=' );

			// Attach API data to ulgm-frontend
			$api_setup = array(
				'root'               => esc_url_raw( rest_url() . self::$root_path . '/' ),
				'nonce'              => \wp_create_nonce( 'wp_rest' ),
				'currentUser'        => get_current_user_id(),
				'localized'          => self::get_frontend_localized_strings(),
				'groupQuizzes'       => isset( $group_drop_downs['quizzes_objects'] ) ? $group_drop_downs['quizzes_objects'] : array(),
				'courseGroupQuizzes' => isset( $group_drop_downs['course_quizzes_objects'] ) ? $group_drop_downs['course_quizzes_objects'] : array(),
				'relationships'      => isset( $group_drop_downs['relationships'] ) ? $group_drop_downs['relationships'] : array(),
				'statistic_action'   => ( $learndash_version_compare ? 'wp_pro_quiz_admin_ajax_statistic_load_user' : 'wp_pro_quiz_admin_ajax' ),
				'i18n'               => array(
					'CSV'         => __( 'CSV', 'uncanny-learndash-groups' ),
					'exportCSV'   => __( 'CSV export', 'uncanny-learndash-groups' ),
					'excel'       => __( 'Excel', 'uncanny-learndash-groups' ),
					'exportExcel' => __( 'Excel export', 'uncanny-learndash-groups' ),
				),
			);

			wp_localize_script( 'ulgm-frontend', 'groupQuizReportSetup', $api_setup );

			wp_enqueue_script( 'ulgm-frontend' );

			wp_enqueue_script( 'ulgm-select2', Utilities::get_vendor( 'select2/js/select2.min.js' ), array( 'jquery' ), Utilities::get_version(), true );
			wp_enqueue_style( 'ulgm-select2', Utilities::get_vendor( 'select2/css/select2.min.css' ), array(), Utilities::get_version() );

			// Load styles
			wp_register_style( 'ulgm-frontend', Utilities::get_asset( 'frontend', 'bundle.min.css' ), array( 'ulgm-datatables' ), Utilities::get_version() );
			$user_colors = Utilities::user_colors();
			wp_add_inline_style( 'ulgm-frontend', $user_colors );
			wp_enqueue_style( 'ulgm-frontend', $user_colors );
		}
	}


	/**
	 * @return mixed|void
	 */
	private static function get_frontend_localized_strings() {

		$localized_strings = array();

		$localized_strings['username']             = __( 'Username', 'uncanny-learndash-groups' );
		$localized_strings['userEmail']            = __( 'Email', 'uncanny-learndash-groups' );
		$localized_strings['firstName']            = __( 'First name', 'uncanny-learndash-groups' );
		$localized_strings['lastName']             = __( 'Last name', 'uncanny-learndash-groups' );
		$localized_strings['quizScore']            = sprintf( _x( '%s score', 'Quiz score', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quiz' ) );
		$localized_strings['detailedReport']       = __( 'Detailed report', 'uncanny-learndash-groups' );
		$localized_strings['date']                 = __( 'Date', 'uncanny-learndash-groups' );
		$localized_strings['csvExport']            = __( 'CSV export', 'uncanny-learndash-groups' );
		$localized_strings['selectCourse']         = sprintf( __( 'Select %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'course' ) );
		$localized_strings['noCourse']             = sprintf( __( 'No %s available', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) );
		$localized_strings['selectUser']           = __( 'Select user', 'uncanny-learndash-groups' );
		$localized_strings['noUsers']              = __( 'No users available', 'uncanny-learndash-groups' );
		$localized_strings['all']                  = __( 'All', 'uncanny-learndash-groups' );
		$localized_strings['selectQuiz']           = sprintf( __( 'Select %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'quiz' ) );
		$localized_strings['customizeColumns']     = __( 'Customize columns', 'uncanny-learndash-groups' );
		$localized_strings['hideCustomizeColumns'] = __( 'Hide customize columns', 'uncanny-learndash-groups' );

		/* DataTable */
		$localized_strings                      = array_merge( $localized_strings, Utilities::i18n_datatable_strings() );
		$localized_strings['searchPlaceholder'] = __( 'Search by username, name, email, date or score', 'uncanny-learndash-groups' );

		$localized_strings = apply_filters( 'quiz-report-table-strings', $localized_strings );

		return $localized_strings;
	}
}
