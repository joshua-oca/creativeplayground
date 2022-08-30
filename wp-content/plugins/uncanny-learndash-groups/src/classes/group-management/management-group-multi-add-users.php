<?php

namespace uncanny_learndash_groups;

/**
 * Class ManagementGroupMultiAddUsers
 * @package uncanny_learndash_groups
 */
class ManagementGroupMultiAddUsers {
	/**
	 * ProcessManualGroupAdminPage constructor.
	 */
	function __construct() {
		add_action( 'init', array( $this, 'bulk_add_users' ), 30 );
	}

	public function bulk_add_users() {
		if ( ulgm_filter_has_var( 'action' ) && 'bulk-add-users' === ulgm_filter_input( 'action' ) && ulgm_filter_has_var( 'group-id' ) ) {
			$group_id       = absint( ulgm_filter_input( 'group-id' ) );
			$first_names    = $_REQUEST['first_name'];
			$last_names     = $_REQUEST['last_name'];
			$emails         = $_REQUEST['email'];
			$passwords      = $_REQUEST['uo_password'];
			$error_results  = array();
			$insert_results = array();
			if ( $emails ) {
				$role = apply_filters( 'uo-groups-user-role', get_option( 'default_role', 'subscriber' ) );
				foreach ( $emails as $k => $email ) {
					if ( ! empty( $email ) ) {
						$email = stripcslashes( $email );
						if ( is_email( $email ) ) {
							$first       = $first_names[ $k ];
							$last        = $last_names[ $k ];
							$email       = sanitize_email( $email );
							$is_existing = email_exists( $email );
							$pass        = $passwords[ $k ];

							if ( is_numeric( $is_existing ) ) {
								$user_id     = $is_existing;
								$user_groups = learndash_get_users_group_ids( $user_id, true );
								if ( in_array( $group_id, $user_groups ) ) {
									$error_results[] = sprintf( __( 'Line #%d: %s is existing user of group.', 'uncanny-learndash-groups' ), $k + 1, $email );
									continue;
								}
								$user_data = array(
									'user_email' => $email,
									'user_id'    => $user_id,
									'first_name' => $first,
									'last_name'  => $last,
									'role'       => $role,
								);

								if ( isset( $user_data['first_name'] ) && ! $is_existing ) {
									update_user_meta( $user_id, 'first_name', $user_data['first_name'] );
								}

								if ( isset( $user_data['last_name'] ) && ! $is_existing ) {
									update_user_meta( $user_id, 'last_name', $user_data['last_name'] );
								}

								Group_Management_Helpers::add_existing_user( $user_data, false, $group_id, 0, SharedFunctions::$not_redeemed_status, false );

							} else {
								$user_data = array(
									'user_login' => $email,
									'user_email' => $email,
									'first_name' => $first,
									'last_name'  => $last,
									'role'       => $role,
									'group_id'   => $group_id,
								);

								if ( ! empty( $pass ) && strlen( $pass ) < 6 ) {
									$error_results[] = sprintf( __( 'Line #%d: Password must include at least 6 characters.', 'uncanny-learndash-groups' ), $k + 1 );
									continue;
								}

								if ( ! empty( $pass ) && strlen( $pass ) >= 6 ) {
									$user_data['user_pass'] = $pass;
								}

								$user_invited = Group_Management_Helpers::add_invite_user( $user_data, false, false, false );
								if ( is_wp_error( $user_invited ) ) {
									$error_results[] = sprintf( __('%s could not be added. Error: %s.', 'uncanny-learndash-groups' ), $email, $user_invited->get_error_message() );
									continue;
								}
							}
							$insert_results[] = sprintf( __( '%s added & invited successfully.', 'uncanny-learndash-groups' ), $email );
						} else {
							$error_results[] = sprintf( __( 'Line #%d: Email (%s) not correct.', 'uncanny-learndash-groups' ), $k + 1, $email );
						}
					} else {
						if ( ! empty( $first_names[ $k ] ) || ! empty( $last_names[ $k ] ) ) {
							$error_results[] = sprintf( __( 'Line #%d: Email field is empty.', 'uncanny-learndash-groups' ), $k + 1 );
						}
					}
				}
			}
			$url = SharedFunctions::get_group_management_page_id( true );
			$url .= '?group-id=' . $group_id;
			$url .= '&bulk=1';
			if ( ! empty( $error_results ) ) {
				$url .= '&bulk-errors=' . urlencode( join( '<br /> ', $error_results ) );
			}
			if ( ! empty( $insert_results ) ) {
				$url .= '&success-invited=' . urlencode( join( '<br /> ', $insert_results ) );
			}
			wp_safe_redirect( $url );
			exit;
		}
	}
}
