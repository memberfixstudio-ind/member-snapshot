<?php
/**
 * Data sources. Each returns plain arrays so the card never touches plugin APIs directly.
 *
 * @package MemberSnapshot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a MemberPress date means "never expires".
 *
 * @param string $date Date string from a transaction.
 * @return bool
 */
function member_snapshot_is_lifetime( $date ) {
	return empty( $date ) || 0 === strpos( (string) $date, '0000-00-00' );
}

/**
 * Active MemberPress memberships for a user.
 *
 * Each row: title, status (active|ending|lifetime), expires (timestamp|null),
 * renews (bool), progress (0-100, share of the term still left), url.
 *
 * @param int $user_id User ID.
 * @return array[]
 */
function member_snapshot_memberships( $user_id ) {
	$rows = array();

	if ( class_exists( 'MeprUser' ) ) {
		try {
			$member = new MeprUser( $user_id );
			$found  = method_exists( $member, 'active_product_subscriptions' )
				? $member->active_product_subscriptions( 'transactions', true )
				: array();

			$by_product = array();
			foreach ( (array) $found as $txn ) {
				if ( is_object( $txn ) && ! empty( $txn->product_id ) ) {
					$product_id = (int) $txn->product_id;
					$expires_at = isset( $txn->expires_at ) ? $txn->expires_at : '';
					$created_at = isset( $txn->created_at ) ? $txn->created_at : '';
					$renews     = false;
					if ( method_exists( $txn, 'subscription' ) ) {
						$sub    = $txn->subscription();
						$renews = $sub && isset( $sub->status ) && 'active' === $sub->status;
					}
				} elseif ( is_numeric( $txn ) ) {
					$product_id = (int) $txn;
					$expires_at = '';
					$created_at = '';
					$renews     = false;
				} else {
					continue;
				}

				$expires = member_snapshot_is_lifetime( $expires_at ) ? null : strtotime( $expires_at . ' UTC' );

				// Keep the transaction that runs longest when a product appears twice.
				if ( isset( $by_product[ $product_id ] ) ) {
					$current = $by_product[ $product_id ]['expires'];
					if ( null === $current || ( null !== $expires && $expires <= $current ) ) {
						continue;
					}
				}

				$started = $created_at ? strtotime( $created_at . ' UTC' ) : 0;

				$by_product[ $product_id ] = array(
					'title'   => get_the_title( $product_id ),
					'expires' => $expires,
					'started' => $started,
					'renews'  => $renews,
					'url'     => get_permalink( $product_id ),
				);
			}

			foreach ( $by_product as $row ) {
				$rows[] = member_snapshot_shape_membership( $row );
			}
		} catch ( Throwable $e ) {
			$rows = array();
		}
	}

	/**
	 * Filter the memberships shown on the card. Use this to add rows from another membership plugin.
	 *
	 * @param array[] $rows    Membership rows.
	 * @param int     $user_id User ID.
	 */
	return apply_filters( 'member_snapshot_memberships', $rows, $user_id );
}

/**
 * Add status and progress to a raw membership row.
 *
 * @param array $row title, expires, started, renews, url.
 * @return array
 */
function member_snapshot_shape_membership( $row ) {
	$now     = time();
	$expires = $row['expires'];

	if ( null === $expires ) {
		$row['status']   = 'lifetime';
		$row['progress'] = 100;
		return $row;
	}

	$days_left       = (int) floor( ( $expires - $now ) / DAY_IN_SECONDS );
	$row['days']     = max( 0, $days_left );
	$row['status']   = ( ! $row['renews'] && $days_left <= 14 ) ? 'ending' : 'active';
	$span            = $row['started'] ? max( 1, $expires - $row['started'] ) : 0;
	$row['progress'] = $span ? (int) round( max( 0, min( 1, ( $expires - $now ) / $span ) ) * 100 ) : 100;

	return $row;
}

/**
 * Enrolled LearnDash courses for a user, unfinished courses first.
 *
 * Each row: title, url, percent, completed, total.
 *
 * @param int $user_id User ID.
 * @return array[]
 */
function member_snapshot_courses( $user_id ) {
	$rows = array();

	if ( function_exists( 'learndash_user_get_enrolled_courses' ) && function_exists( 'learndash_course_progress' ) ) {
		$course_ids = (array) learndash_user_get_enrolled_courses( $user_id, array(), true );
		foreach ( $course_ids as $course_id ) {
			$progress = learndash_course_progress(
				array(
					'user_id'   => $user_id,
					'course_id' => $course_id,
					'array'     => true,
				)
			);
			$rows[]   = array(
				'title'     => get_the_title( $course_id ),
				'url'       => get_permalink( $course_id ),
				'percent'   => isset( $progress['percentage'] ) ? (int) $progress['percentage'] : 0,
				'completed' => isset( $progress['completed'] ) ? (int) $progress['completed'] : 0,
				'total'     => isset( $progress['total'] ) ? (int) $progress['total'] : 0,
			);
		}

		usort(
			$rows,
			function ( $a, $b ) {
				$a_done = $a['percent'] >= 100;
				$b_done = $b['percent'] >= 100;
				if ( $a_done !== $b_done ) {
					return $a_done ? 1 : -1;
				}
				return $b['percent'] - $a['percent'];
			}
		);
	}

	/**
	 * Filter the courses shown on the card.
	 *
	 * @param array[] $rows    Course rows.
	 * @param int     $user_id User ID.
	 */
	return apply_filters( 'member_snapshot_courses', $rows, $user_id );
}

/**
 * Sample data for previews, so the card can be styled before anyone has joined.
 *
 * @return array
 */
function member_snapshot_demo_data() {
	$now = time();
	return array(
		'memberships' => array(
			member_snapshot_shape_membership(
				array(
					'title'   => __( 'Pro Membership', 'member-snapshot' ),
					'expires' => $now + 23 * DAY_IN_SECONDS,
					'started' => $now - 7 * DAY_IN_SECONDS,
					'renews'  => true,
					'url'     => '#',
				)
			),
			member_snapshot_shape_membership(
				array(
					'title'   => __( 'Workshop Pass', 'member-snapshot' ),
					'expires' => $now + 6 * DAY_IN_SECONDS,
					'started' => $now - 84 * DAY_IN_SECONDS,
					'renews'  => false,
					'url'     => '#',
				)
			),
			member_snapshot_shape_membership(
				array(
					'title'   => __( 'Founding Member', 'member-snapshot' ),
					'expires' => null,
					'started' => 0,
					'renews'  => false,
					'url'     => '#',
				)
			),
		),
		'courses'     => array(
			array(
				'title'     => __( 'Getting Started', 'member-snapshot' ),
				'url'       => '#',
				'percent'   => 60,
				'completed' => 6,
				'total'     => 10,
			),
			array(
				'title'     => __( 'Advanced Techniques', 'member-snapshot' ),
				'url'       => '#',
				'percent'   => 15,
				'completed' => 2,
				'total'     => 13,
			),
			array(
				'title'     => __( 'Welcome Course', 'member-snapshot' ),
				'url'       => '#',
				'percent'   => 100,
				'completed' => 4,
				'total'     => 4,
			),
		),
	);
}

/**
 * Where "Manage account" should point.
 *
 * @return string
 */
function member_snapshot_account_url() {
	$url = '';
	if ( class_exists( 'MeprOptions' ) ) {
		try {
			$options = MeprOptions::fetch();
			if ( method_exists( $options, 'account_page_url' ) ) {
				$url = $options->account_page_url();
			}
		} catch ( Throwable $e ) {
			$url = '';
		}
	}
	/**
	 * Filter the "Manage account" link. Return an empty string to hide the button.
	 *
	 * @param string $url Account page URL.
	 */
	return apply_filters( 'member_snapshot_account_url', $url ? $url : admin_url( 'profile.php' ) );
}
