<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Sample
 * @package uncanny_pro_toolkit
 */
class LearnDashTranscript extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/**
	 * Initialize frontend actions and filters
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {

			// Enqueue Scripts for questionnaire
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'transcript_scripts' ) );

			/* ADD FILTERS ACTIONS FUNCTION */
			add_shortcode( 'uo_transcript', array( __CLASS__, 'display_course_transcript' ) );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;


	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'learner-transcript';

		$class_title = esc_html__( 'Learner Transcript', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/learner-transcript/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Add printable transcripts to the front end for your learners. This is a great way for learners to have a record of all course progress and overall standing.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-table "></i><span class="uo_pro_text">PRO</span>';
		$category   = 'learndash';
		$type       = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return array
	 */
	public static function get_class_settings( $class_title ) {

		// Get pages to populate drop down
		$args = array(
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
			'post_type'   => 'page',
			'post_status' => 'publish',
		);

		$pages     = get_pages( $args );
		$drop_down = array();
		array_push( $drop_down, array(
			'value' => '',
			'text'  => esc_attr__( 'Select a Page', 'uncanny-pro-toolkit' )
		) );

		foreach ( $pages as $page ) {
			if ( empty( $page->post_title ) ) {
				$page->post_title = esc_attr__( '(no title)', 'uncanny-pro-toolkit' );
			}

			array_push( $drop_down, array( 'value' => $page->ID, 'text' => $page->post_title ) );
		}

		// Create options
		$options = array();

		$options[] = array(
			'type'       => 'html',
			'inner_html' => '<h2>' . esc_attr__( 'General', 'uncanny-pro-toolkit' ) . '</h2>',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Display completed courses only', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-display-completed-courses-transcript',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Display courses that the user is no longer enrolled in', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-display-notenrolled-with-progress-courses-transcript',
		);

		$options[] = array(
			'type'        => 'color',
			'label'       => esc_html__( 'Accent UI Color', 'uncanny-pro-toolkit' ),
			'option_name' => 'accent_ui_color',
			'default'     => '#0790e8',
		);

		$options[] = array(
			'type'       => 'html',
			'inner_html' => '<h2>' . esc_attr__( 'Header', 'uncanny-pro-toolkit' ) . '</h2>',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'Logo Url', 'uncanny-pro-toolkit' ),
			'option_name' => 'logo_url',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'Heading', 'uncanny-pro-toolkit' ),
			'option_name' => 'transcript_heading',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'Organization', 'uncanny-pro-toolkit' ),
			'option_name' => 'center_name',
		);

		$options[] = array(
			'type'       => 'html',
			'inner_html' => '<h2>' . esc_attr__( 'Table', 'uncanny-pro-toolkit' ) . '</h2>',
		);

		$options[] = array(
			'type'        => 'select',
			'label'       => 'Table Course Sorting',
			'select_name' => 'transcript_sort_order',
			'options'     => array(
				array(
					'value' => 'alpha-desc',
					'text'  => 'Alphabetically Descending',
				),
				array(
					'value' => 'alpha-asc',
					'text'  => 'Alphabetically Ascending',
				),
				array(
					'value' => 'date-desc',
					'text'  => 'Date Descending',
				),
				array(
					'value' => 'date-asc',
					'text'  => 'Date Ascending',
				),
				array(
					'value' => 'menu-desc',
					'text'  => 'Menu Order Descending',
				),
				array(
					'value' => 'menu-asc',
					'text'  => 'Menu Order Ascending',
				),
			),
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Disable Status Column', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-disable-transcript-status-col',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Disable Steps Column', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-disable-transcript-stepscompleted-col',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Disable Avg Quiz Score Column', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-disable-transcript-avgquizscore-col',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Disable Final Quiz Score Column', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-disable-transcript-finalquizscore-col',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Disable Certificate Column', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-disable-certificate-col',
		);

		if ( defined( 'CEU_PLUGIN_NAME' ) ) {
			$ceus      = get_option( 'credit_designation_label_plural', esc_attr__( 'CEUs', 'uncanny-ceu' ) );
			$options[] = array(
				'type'        => 'checkbox',
				'label'       => sprintf(
				// Translators: CEUs plural designation
					esc_html__( 'Disable %s Column', 'uncanny-pro-toolkit' ),
					$ceus
				),
				'option_name' => 'uncanny-disable-ceus-col',
			);
			$options[] = array(
				'type'        => 'checkbox',
				'label'       => sprintf(
				// Translators: CEUs plural designation
					esc_html__( 'Enable Custom %s Rows', 'uncanny-pro-toolkit' ),
					$ceus
				),
				'option_name' => 'uncanny-enable-ceus-rows',
			);
		}


		$options[] = array(
			'type'       => 'html',
			'inner_html' => '<h2>' . esc_attr__( 'Footer', 'uncanny-pro-toolkit' ) . '</h2>',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'Logo Url', 'uncanny-pro-toolkit' ),
			'option_name' => 'footer_logo_url',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'Disclaimer', 'uncanny-pro-toolkit' ),
			'option_name' => 'footer_note',
		);

		// Build html
		$html = self::settings_output( array(
			'class'   => __CLASS__,
			'title'   => $class_title,
			'options' => $options,
		) );

		return $html;
	}

	/*
	 * Display the shortcode
	 * @param array $attributes
	 *
	 * @return string $html header and table
	 */
	public static function display_course_transcript( $attributes ) {

		if ( ! is_user_logged_in() ) {
			$html = esc_attr__( 'Log in to view information', 'uncanny-pro-toolkit' );
		} else {

			$data = [];

			$data['logo_url'] = '';
			$logo_url         = self::get_settings_value( 'logo_url', __CLASS__ );
			if ( '' !== $logo_url ) {
				$data['logo_url'] = $logo_url;
			}

			$data['transcript_heading'] = esc_attr__( 'Student Transcript', 'uncanny-pro-toolkit' );
			$feature_transcript_heading = self::get_settings_value( 'transcript_heading', __CLASS__ );
			if ( '' !== $feature_transcript_heading ) {
				$data['transcript_heading'] = $feature_transcript_heading;
			}

			$data['center_name'] = '';
			$feature_center_name = self::get_settings_value( 'center_name', __CLASS__ );
			if ( '' !== $feature_center_name ) {
				$data['center_name'] = $feature_center_name;
			}

			$data['footer_note'] = '';
			$feature_footer_note = self::get_settings_value( 'footer_note', __CLASS__ );
			if ( '' !== $feature_footer_note ) {
				$data['footer_note'] = $feature_footer_note;
			}

			$data['footer_logo_url'] = '';
			$feature_footer_logo_url = self::get_settings_value( 'footer_logo_url', __CLASS__ );
			if ( '' !== $feature_footer_logo_url ) {
				$data['footer_logo_url'] = $feature_footer_logo_url;
			}

			$request = shortcode_atts( array(
				'logo-url'    => '',
				'date-format' => 'F j, Y'
			), $attributes );

			if ( '' !== $request['logo-url'] ) {
				$data['logo_url'] = $request['logo-url'];
			}

			$data['date_format'] = $request['date-format'];

			$html = self::generate_transcript( $data );
		}

		return $html;
	}

	/**
	 * Generate transcript HTML Output
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	private static function generate_transcript( $data ) {

		$current_user = wp_get_current_user();

		$current_user = apply_filters( 'uo_transcript_current_user', $current_user );

		if ( isset( $current_user->user_firstname )
			 && isset( $current_user->user_lastname )
			 && ! empty( $current_user->user_firstname )
			 && ! empty( $current_user->user_lastname ) ) {

			$learner_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;

		} else {
			$learner_name = $current_user->display_name;
		}

		// Default amount of courses completed
		$courses_completed = 0;

		// Default amount of courses enrolled
		$courses_enrolled = 0;

		// Set up calculation for average quiz score
		$avg_quizzes_completed                       = 0;
		$sum_course_average_percentage_quizzes_score = 0;

		// Set up calculation for average quiz score
		$final_quizzes_completed                   = 0;
		$sum_course_final_percentage_quizzes_score = 0;

		// Get registered Courses
		$show_courses_ids = learndash_user_get_enrolled_courses( $current_user->ID );

		// Get courses progress
		$user_course_progress = get_user_meta( $current_user->ID, '_sfwd-course_progress', true );
		$user_course_progress = ! empty( $user_course_progress ) ? $user_course_progress : array();

		$not_enrolled_progress = [];
		if ( 'on' === self::get_settings_value( 'uncanny-display-notenrolled-with-progress-courses-transcript', __CLASS__ ) ) {
			if ( ! empty( $user_course_progress ) ) {
				foreach ( $user_course_progress as $course_id => $_data ) {
					if ( ! in_array( $course_id, $show_courses_ids, true ) ) {
						$show_courses_ids[]                  = $course_id;
						$not_enrolled_progress[ $course_id ] = $_data;
					}
				}
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-display-completed-courses-transcript', __CLASS__ ) ) {
			$completed_courses = [];
			if ( ! empty( $show_courses_ids ) ) {
				foreach ( $show_courses_ids as $user_course_id ) {

					if ( key_exists( $user_course_id, $not_enrolled_progress ) ) {
						// the user is not enrolled in the course but has progress
						/*
						 * The user is not enrolled in the course but has progress.
						 * Check if it is completed
						 */
						$course_data = $not_enrolled_progress[ $user_course_id ];
						if ( isset( $course_data['total'] ) && isset( $course_data['completed'] ) ) {
							if ( $course_data['total'] === $course_data['completed'] ) {
								$completed_courses[] = $user_course_id;
							}
						}
					} elseif ( learndash_course_status( $user_course_id, $current_user->ID ) === esc_attr__( 'Completed', 'learndash' ) ) {
						$completed_courses[] = $user_course_id;
					}
				}
			}
			$show_courses_ids = $completed_courses;
		}

		global $wpdb;

		$q               = "
			SELECT a.course_id, a.post_id, m.activity_meta_value as activity_percentage
			FROM {$wpdb->prefix}learndash_user_activity a
			LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
			WHERE a.user_id = {$current_user->ID}
			AND a.activity_type = 'quiz'
			AND a.activity_status = 1
			AND m.activity_meta_key = 'percentage'
		";
		$user_activities = $wpdb->get_results( $q );

		$rows = [];

		if ( $show_courses_ids ) {

			foreach ( $show_courses_ids as $course_id ) {

				$rows[ $course_id ] = (object) [];

				$courses_enrolled ++;

				$course = get_post( $course_id );

				$rows[ $course_id ]->course_title = $course->post_title;
				$rows[ $course_id ]->course_date  = absint( strtotime( $course->post_date ) );
				$rows[ $course_id ]->course_order = absint( $course->menu_order );

				// Column Completion Date
				$completion_date = self::get_completion_date( $current_user->ID, $course_id, $data['date_format'] );

				if ( key_exists( $course_id, $not_enrolled_progress ) ) {

					$_data = $not_enrolled_progress[ $course_id ];

					if ( isset( $_data['total'] ) && isset( $_data['completed'] ) && absint( $_data['total'] ) <= absint( $_data['completed'] ) ) {
						$ld_course_status = esc_html__( 'Completed', 'learndash' );
					} elseif ( isset( $_data['completed'] ) && absint( $_data['completed'] ) ) {
						$ld_course_status = esc_html__( 'In Progress', 'learndash' );
					} else {
						$ld_course_status = esc_html__( 'Not Started', 'learndash' );
					}


				} else {
					$ld_course_status = learndash_course_status( $course_id, $current_user->ID );
				}


				if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-status-col', __CLASS__ ) ) {

					// If status is complete the the status value as the date commpleted
					if ( esc_attr__( 'Completed', 'learndash' ) === $ld_course_status ) {
						/* Translators: 1. Formatted completion date */
						$course_status = sprintf( esc_attr__( 'Completed on %1$s', 'uncanny-pro-toolkit' ), $completion_date );
					} else {
						$course_status = $ld_course_status;
					}

					$rows[ $course_id ]->course_status = $course_status;
				}

				if ( $completion_date ) {
					$courses_completed ++;
				}

				if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-stepscompleted-col', __CLASS__ ) ) {
					$course_steps_count     = learndash_get_course_steps_count( $course_id );
					$course_steps_completed = learndash_course_get_completed_steps( $current_user->ID, $course_id );
					/* Translators: 1. number of lessons completed 2. number of total lessons */
					$lessons = sprintf( esc_attr__( '%1$s / %2$s', 'uncanny-pro-toolkit' ), $course_steps_completed, $course_steps_count );

					$rows[ $course_id ]->lessons = $lessons;
				}

				if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-avgquizscore-col', __CLASS__ ) ) {

					// Column Quiz Average
					$course_quiz_average = self::get_avergae_quiz_result( $course_id, $user_activities, $current_user );

					$avg_score = esc_attr__( '0%', 'uncanny-pro-toolkit' );

					if ( $course_quiz_average ) {
						/* Translators: 1. number percentage */
						$avg_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $course_quiz_average );
						$avg_quizzes_completed ++;
						$sum_course_average_percentage_quizzes_score += $course_quiz_average;
					}
					$rows[ $course_id ]->avg_score = $avg_score;
				}


				if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-finalquizscore-col', __CLASS__ ) ) {
					$course_lesson_list = learndash_get_lesson_list( $course_id );

					//Column Final quiz
					$final_quiz_results = self::get_final_quiz_result( $user_activities, $course_id, $course_lesson_list, $ld_course_status, $current_user );

					$final_score = esc_attr__( '0%', 'uncanny-pro-toolkit' );

					if ( $final_quiz_results ) {
						/* Translators: 1. number percentage */
						$final_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $final_quiz_results );
						$final_quizzes_completed ++;
						$sum_course_final_percentage_quizzes_score += $final_quiz_results;
					}

					$rows[ $course_id ]->final_score = $final_score;
				}
			}
		}

		$transcript = (object) array(
			'creation_date'    => learndash_adjust_date_time_display( current_time( 'timestamp' ), $data['date_format'] ),
			'logo'             => (object) array(
				'header' => $data['logo_url'],
				'footer' => $data['footer_logo_url'],
			),
			'heading'          => $data['transcript_heading'],
			'summary'          => (object) array(
				'learner_name'     => $learner_name,
				'centre_name'      => $data['center_name'],
				/* Translators: 1. number of courses completed 2. number of courses enrolled */
				'status'           => sprintf( esc_attr__( '%1$s / %2$s courses completed', 'uncanny-pro-toolkit' ), $courses_completed, $courses_enrolled ),
				'status_completed' => $courses_completed,
				'status_enrolled'  => $courses_enrolled,
			),
			'avg_quiz_score'   => '',
			'final_quiz_score' => '',
			'footnote'         => $data['footer_note'],
			'table'            => (object) [
				'heading' => (object) [
					'course_title' => \LearnDash_Custom_Label::get_label( 'course' ),
				],
				'rows'    => $rows,
			],
		);


		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-avgquizscore-col', __CLASS__ ) ) {
			$avg_quiz_score = '';
			if ( $avg_quizzes_completed ) {
				$avg_quiz_score = absint( $sum_course_average_percentage_quizzes_score / $avg_quizzes_completed );
			}

			/* Translators: 1. number percentage */
			$transcript->avg_quiz_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $avg_quiz_score );

		}


		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-finalquizscore-col', __CLASS__ ) ) {
			$avg_final_score = '';
			if ( $final_quizzes_completed ) {
				$avg_final_score = absint( $sum_course_final_percentage_quizzes_score / $final_quizzes_completed );
			}

			/* Translators: 1. number percentage */
			$transcript->final_quiz_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $avg_final_score );
		}


		// Maybe add headings to transcript
		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-status-col', __CLASS__ ) ) {
			$transcript->table->heading->course_status = esc_attr__( 'Status', 'uncanny-pro-toolkit' );
		}
		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-stepscompleted-col', __CLASS__ ) ) {
			$transcript->table->heading->lessons = esc_attr__( 'Steps', 'uncanny-pro-toolkit' );
		}
		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-avgquizscore-col', __CLASS__ ) ) {
			$transcript->table->heading->avg_score = esc_attr__( 'Avg. Score', 'uncanny-pro-toolkit' );
		}
		if ( 'on' !== self::get_settings_value( 'uncanny-disable-transcript-finalquizscore-col', __CLASS__ ) ) {
			$transcript->table->heading->final_score = esc_attr__( 'Final Score', 'uncanny-pro-toolkit' );
		}

		$transcript = apply_filters( 'uo_pro_transcript', $transcript, $current_user, __CLASS__ );

		if ( 'on' !== self::get_settings_value( 'uncanny-disable-certificate-col', __CLASS__ ) ) {

			foreach ( $transcript->table->rows as $course_id => $row ) {
				$certificate = learndash_get_course_certificate_link( $course_id, $current_user->ID );

				$certificate_link = '';
				$icon             = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/certificate-icon-small.png';
				if ( ! empty( $certificate ) ) {
					$certificate_link = sprintf( '<a target="_blank" href="%s"><img src="%s"/></a>', $certificate, $icon );
				}

				$row->certificate_link = $certificate_link;
			}

			$transcript->table->heading->certificate_link = esc_attr__( 'Certificate', 'uncanny-pro-toolkit' );
		}

		$sort_order = self::get_settings_value( 'transcript_sort_order', __CLASS__, '' );

		if ( '' === $sort_order ) {
			$sort_order = 'alpha-desc';
		}

		if ( 'alpha-desc' === $sort_order ) {
			usort( $transcript->table->rows, function ( $a, $b ) {
				return strcasecmp( $b->course_title, $a->course_title );
			} );
		}

		if ( 'alpha-asc' === $sort_order ) {
			usort( $transcript->table->rows, function ( $a, $b ) {
				return strcasecmp( $a->course_title, $b->course_title );
			} );
		}

		if ( 'menu-desc' === $sort_order ) {

			usort( $transcript->table->rows, function ( $a, $b ) {
				return $a->course_order < $b->course_order;
			} );
		}

		if ( 'menu-asc' === $sort_order ) {

			usort( $transcript->table->rows, function ( $a, $b ) {
				return $a->course_order > $b->course_order;
			} );
		}

		if ( 'date-desc' === $sort_order ) {
			usort( $transcript->table->rows, function ( $a, $b ) {
				return $a->course_date < $b->course_date;
			} );
		}

		if ( 'date-asc' === $sort_order ) {
			usort( $transcript->table->rows, function ( $a, $b ) {
				return $b->course_date < $a->course_date;
			} );
		}

		$accent_color         = '#0790e8';
		$feature_accent_color = self::get_settings_value( 'accent_ui_color', __CLASS__ );
		if ( '' !== $feature_accent_color ) {
			$accent_color = $feature_accent_color;
		}

		$class = __CLASS__;

		ob_start();

		$template = self::get_template( 'transcript.php', dirname( dirname( __FILE__ ) ) . '/src' );
		$template = apply_filters( 'uo_pro_transcript_template', $template );
		include $template;

		return ob_get_clean();
	}

	/*
	 * Get course completed on date with formatting
	 * @param int $user_id
	 * @param int $course_id
	 * @param string
	 *
	 * @return string
	 */
	private static function get_completion_date( $user_id, $course_id, $format ) {

		$timestamp = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( '' === $timestamp ) {
			return false;
		}

		$date = learndash_adjust_date_time_display( $timestamp, $format );

		return $date;

	}

	/*
	 *
	 */
	private static function get_avergae_quiz_result( $course_id, $user_activities, $current_user ) {

		$quiz_scores = [];

		foreach ( $user_activities as $activity ) {

			if ( $course_id == $activity->course_id ) {

				if ( ! isset( $quiz_scores[ $activity->post_id ] ) ) {

					$quiz_scores[ $activity->post_id ] = $activity->activity_percentage;
				} elseif ( $quiz_scores[ $activity->post_id ] < $activity->activity_percentage ) {

					$quiz_scores[ $activity->post_id ] = $activity->activity_percentage;
				}
			}
		}

		if ( 0 !== count( $quiz_scores ) ) {
			$average = absint( array_sum( $quiz_scores ) / count( $quiz_scores ) );
		} else {
			$average = false;
		}

		return $average;
	}

	/*
	 *
	 */
	private static function get_final_quiz_result( $user_activities, $course_id, $course_lesson_list, $course_status, $current_user ) {

		// Final score should only calculated if the course is completed
		if ( esc_attr__( 'Completed', 'learndash' ) !== $course_status ) {
			// Course not completed
			return false;
		}

		$course_quiz_list = learndash_get_course_quiz_list( $course_id );

		if ( ! empty( $course_quiz_list ) ) {
			// Last quiz at the course level
			$last_quiz = end( $course_quiz_list );
		} elseif ( ! empty( $course_lesson_list ) ) {
			$last_lesson_in_course = end( $course_lesson_list );
			$lesson_quizzes        = learndash_get_lesson_quiz_list( $last_lesson_in_course->ID, $current_user->ID, $course_id );
			if ( ! empty( $lesson_quizzes ) ) {
				// Last quiz on the last lesson
				$last_quiz = end( $lesson_quizzes );
			} else {
				// No final quiz found
				return false;
			}
		} else {
			// No final quiz found
			return false;
		}


		$last_quiz_id = $last_quiz['post']->ID;

		$percentage = 0;

		foreach ( $user_activities as $activity ) {

			if (
				$course_id == $activity->course_id && // Match the course ID
				(string) $last_quiz_id === $activity->post_id // Match the quiz ID
			) {

				if ( ! $percentage ) {
					$percentage = $activity->activity_percentage;
				} elseif ( $percentage < $activity->activity_percentage ) {
					$percentage = $activity->activity_percentage;
				}
			}
		}

		return $percentage;
	}

	public static function transcript_scripts() {
		global $post;
		$block_is_on_page = false;

		global $post;

		if ( empty( $post->ID ) ) {
			return;
		}

		if ( ! has_shortcode( $post->post_content, 'uo_transcript' ) && ! has_block( 'uncanny-toolkit-pro/learn-dash-transcript', $post ) ) {
			return;
		}

		self::enqueue_scripts();
	}

	public static function enqueue_scripts(){
		$transcript_css = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/css/transcript.css';
		wp_enqueue_style( 'uo-ultp-transcript-css', $transcript_css, array(), UNCANNY_TOOLKIT_PRO_VERSION );

		$transcript_js = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/js/transcript.js';
		wp_enqueue_script( 'responsive-datatables-script', $transcript_js, array( 'jquery' ), UNCANNY_TOOLKIT_PRO_VERSION, true );
	}
}
