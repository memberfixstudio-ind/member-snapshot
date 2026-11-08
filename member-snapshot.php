<?php
/**
 * Plugin Name:       Member Snapshot
 * Plugin URI:        https://github.com/memberfixstudio-ind/member-snapshot
 * Description:       One clean card that shows members what they have: their MemberPress memberships, when they renew or expire, and their LearnDash course progress. Add it with a block or [member_snapshot].
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Rohan Salave
 * Author URI:        https://www.linkedin.com/in/rohan-salave-362475189/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       member-snapshot
 * Domain Path:       /languages
 *
 * @package MemberSnapshot
 */

defined( 'ABSPATH' ) || exit;

define( 'MEMBER_SNAPSHOT_VERSION', '1.0.0' );
define( 'MEMBER_SNAPSHOT_DIR', __DIR__ );
define( 'MEMBER_SNAPSHOT_URL', plugin_dir_url( __FILE__ ) );

require_once MEMBER_SNAPSHOT_DIR . '/includes/sources.php';
require_once MEMBER_SNAPSHOT_DIR . '/includes/render.php';

/**
 * Load translations bundled with the plugin (the plugin is not served from WordPress.org).
 */
function member_snapshot_load_textdomain() {
	load_plugin_textdomain( 'member-snapshot', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'member_snapshot_load_textdomain', 0 );

/**
 * Register the stylesheet, the block and the shortcode.
 */
function member_snapshot_init() {
	wp_register_style( 'member-snapshot', MEMBER_SNAPSHOT_URL . 'assets/member-snapshot.css', array(), MEMBER_SNAPSHOT_VERSION );

	wp_register_script(
		'member-snapshot-editor',
		MEMBER_SNAPSHOT_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		MEMBER_SNAPSHOT_VERSION,
		true
	);

	// Lets the block editor UI strings in assets/editor.js use translations from /languages.
	wp_set_script_translations( 'member-snapshot-editor', 'member-snapshot', MEMBER_SNAPSHOT_DIR . '/languages' );

	register_block_type(
		MEMBER_SNAPSHOT_DIR . '/block.json',
		array(
			'render_callback' => 'member_snapshot_render_block',
		)
	);

	add_shortcode( 'member_snapshot', 'member_snapshot_shortcode' );
}
add_action( 'init', 'member_snapshot_init' );

/**
 * Shortcode: [member_snapshot show="memberships,courses" title="Your account" demo="0"]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function member_snapshot_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'show'  => 'memberships,courses',
			'title' => '',
			'demo'  => '0',
		),
		$atts,
		'member_snapshot'
	);

	$show = array_filter( array_map( 'trim', explode( ',', strtolower( $atts['show'] ) ) ) );

	return member_snapshot_render(
		array(
			'showMemberships' => in_array( 'memberships', $show, true ),
			'showCourses'     => in_array( 'courses', $show, true ),
			'title'           => $atts['title'],
			'demo'            => in_array( strtolower( (string) $atts['demo'] ), array( '1', 'true', 'yes' ), true ),
		)
	);
}

/**
 * Block render callback.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function member_snapshot_render_block( $attributes ) {
	$is_editor_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

	return member_snapshot_render(
		array(
			'showMemberships' => ! empty( $attributes['showMemberships'] ),
			'showCourses'     => ! empty( $attributes['showCourses'] ),
			'title'           => isset( $attributes['title'] ) ? $attributes['title'] : '',
			// In the editor, show sample data when the admin has nothing to show.
			'demo'            => $is_editor_preview,
			'wrapper'         => get_block_wrapper_attributes( array( 'class' => 'ms-card-wrap' ) ),
		)
	);
}
