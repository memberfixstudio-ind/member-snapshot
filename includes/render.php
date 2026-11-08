<?php
/**
 * Card markup.
 *
 * @package MemberSnapshot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the member card.
 *
 * @param array $args showMemberships, showCourses, title, demo, wrapper.
 * @return string
 */
function member_snapshot_render( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'showMemberships' => true,
			'showCourses'     => true,
			'title'           => '',
			'demo'            => false,
			'wrapper'         => 'class="ms-card-wrap"',
		)
	);

	wp_enqueue_style( 'member-snapshot' );

	if ( ! is_user_logged_in() ) {
		return member_snapshot_logged_out( $args['wrapper'] );
	}

	$user        = wp_get_current_user();
	$memberships = $args['showMemberships'] ? member_snapshot_memberships( $user->ID ) : array();
	$courses     = $args['showCourses'] ? member_snapshot_courses( $user->ID ) : array();

	// Sample data is only ever shown to people who can edit the site.
	$is_demo = $args['demo'] && current_user_can( 'edit_posts' ) && empty( $memberships ) && empty( $courses );
	if ( $is_demo ) {
		$demo        = member_snapshot_demo_data();
		$memberships = $args['showMemberships'] ? $demo['memberships'] : array();
		$courses     = $args['showCourses'] ? $demo['courses'] : array();
	}

	$name     = $user->first_name ? $user->first_name : $user->display_name;
	$title    = $args['title'] ? $args['title'] : sprintf( /* translators: %s: member's first name. */ __( 'Welcome back, %s', 'member-snapshot' ), $name );
	$since    = $user->user_registered ? date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) : '';
	$account  = member_snapshot_account_url();
	$date_fmt = get_option( 'date_format' );

	ob_start();
	?>
	<div <?php echo $args['wrapper']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by get_block_wrapper_attributes or a fixed string. ?>>
		<section class="ms-card" aria-label="<?php esc_attr_e( 'Your membership', 'member-snapshot' ); ?>">
			<?php if ( $is_demo ) : ?>
				<p class="ms-demo-note"><?php esc_html_e( 'Preview with sample data. Members will see their own memberships and courses.', 'member-snapshot' ); ?></p>
			<?php endif; ?>

			<header class="ms-head">
				<?php echo get_avatar( $user->ID, 112, '', '', array( 'class' => 'ms-avatar' ) ); ?>
				<div class="ms-head-text">
					<h2 class="ms-title"><?php echo esc_html( $title ); ?></h2>
					<?php if ( $since ) : ?>
						<p class="ms-sub">
							<?php
							/* translators: %s: date the member joined. */
							printf( esc_html__( 'Member since %s', 'member-snapshot' ), esc_html( $since ) );
							?>
						</p>
					<?php endif; ?>
				</div>
				<?php if ( $account ) : ?>
					<a class="ms-button ms-button-ghost" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Manage account', 'member-snapshot' ); ?></a>
				<?php endif; ?>
			</header>

			<?php if ( $args['showMemberships'] ) : ?>
				<div class="ms-section">
					<h3 class="ms-section-title"><?php esc_html_e( 'Memberships', 'member-snapshot' ); ?></h3>
					<?php if ( empty( $memberships ) ) : ?>
						<p class="ms-empty"><?php esc_html_e( 'You don\'t have an active membership right now.', 'member-snapshot' ); ?></p>
					<?php else : ?>
						<ul class="ms-list">
							<?php foreach ( $memberships as $row ) : ?>
								<li class="ms-row ms-status-<?php echo esc_attr( $row['status'] ); ?>">
									<div class="ms-row-main">
										<span class="ms-row-title"><?php echo esc_html( $row['title'] ); ?></span>
										<span class="ms-row-meta">
											<?php
											if ( 'lifetime' === $row['status'] ) {
												esc_html_e( 'Lifetime access, never expires', 'member-snapshot' );
											} elseif ( $row['renews'] ) {
												/* translators: %s: renewal date. */
												printf( esc_html__( 'Renews on %s', 'member-snapshot' ), esc_html( date_i18n( $date_fmt, $row['expires'] ) ) );
											} else {
												/* translators: %s: expiry date. */
												printf( esc_html__( 'Ends on %s', 'member-snapshot' ), esc_html( date_i18n( $date_fmt, $row['expires'] ) ) );
											}
											?>
										</span>
									</div>
									<span class="ms-pill">
										<?php
										if ( 'lifetime' === $row['status'] ) {
											esc_html_e( 'Lifetime', 'member-snapshot' );
										} elseif ( 'ending' === $row['status'] ) {
											/* translators: %d: days left. */
											printf( esc_html( _n( '%d day left', '%d days left', $row['days'], 'member-snapshot' ) ), (int) $row['days'] );
										} else {
											esc_html_e( 'Active', 'member-snapshot' );
										}
										?>
									</span>
									<?php if ( 'lifetime' !== $row['status'] ) : ?>
										<span class="ms-meter" role="presentation"><span style="width: <?php echo (int) $row['progress']; ?>%"></span></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $args['showCourses'] && ( ! empty( $courses ) || function_exists( 'learndash_user_get_enrolled_courses' ) || $is_demo ) ) : ?>
				<div class="ms-section">
					<h3 class="ms-section-title"><?php esc_html_e( 'Your courses', 'member-snapshot' ); ?></h3>
					<?php if ( empty( $courses ) ) : ?>
						<p class="ms-empty"><?php esc_html_e( 'You haven\'t started a course yet.', 'member-snapshot' ); ?></p>
					<?php else : ?>
						<ul class="ms-list">
							<?php foreach ( $courses as $course ) : ?>
								<?php
								$done  = $course['percent'] >= 100;
								$label = $done ? __( 'Review', 'member-snapshot' ) : ( $course['percent'] > 0 ? __( 'Continue', 'member-snapshot' ) : __( 'Start', 'member-snapshot' ) );
								?>
								<li class="ms-row ms-course<?php echo $done ? ' is-done' : ''; ?>">
									<div class="ms-row-main">
										<span class="ms-row-title"><?php echo esc_html( $course['title'] ); ?></span>
										<span class="ms-row-meta">
											<?php
											if ( $done ) {
												esc_html_e( 'Completed', 'member-snapshot' );
											} elseif ( $course['total'] ) {
												/* translators: 1: steps completed, 2: total steps. */
												printf( esc_html__( '%1$d of %2$d steps done', 'member-snapshot' ), (int) $course['completed'], (int) $course['total'] );
											}
											?>
										</span>
									</div>
									<a class="ms-button<?php echo $done ? ' ms-button-ghost' : ''; ?>" href="<?php echo esc_url( $course['url'] ); ?>">
										<?php echo esc_html( $label ); ?>
										<span class="screen-reader-text"><?php echo esc_html( $course['title'] ); ?></span>
									</a>
									<span class="ms-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $course['percent']; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: course title. */ __( 'Progress in %s', 'member-snapshot' ), $course['title'] ) ); ?>">
										<span style="width: <?php echo (int) $course['percent']; ?>%"></span>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Card shown to visitors who are not logged in.
 *
 * @param string $wrapper Wrapper attributes.
 * @return string
 */
function member_snapshot_logged_out( $wrapper ) {
	$redirect = is_singular() ? get_permalink() : home_url( '/' );
	ob_start();
	?>
	<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<section class="ms-card ms-card-guest">
			<h2 class="ms-title"><?php esc_html_e( 'Your membership lives here', 'member-snapshot' ); ?></h2>
			<p class="ms-sub"><?php esc_html_e( 'Log in to see your plan, your renewal date and your course progress.', 'member-snapshot' ); ?></p>
			<a class="ms-button" href="<?php echo esc_url( wp_login_url( $redirect ) ); ?>"><?php esc_html_e( 'Log in', 'member-snapshot' ); ?></a>
		</section>
	</div>
	<?php
	return (string) ob_get_clean();
}
