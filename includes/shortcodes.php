<?php
/**
 * Add shortcodes
 *
 * @package     EDDMembers\Shortcodes
 * @since       1.0.0
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add shortcode for user expire date.
 *
 * @since  1.0.0
 * @return void
 */
function edd_members_expire_date_shortcode( $atts ) {
	
	$atts = shortcode_atts( array(
		'unknown_text' => null,
	), $atts );
	
	if ( false != edd_members_get_expire_date() && is_user_logged_in() ) {
		
		// Get expire date
		$expire_date = edd_members_get_unix_expire_date( $user_id = get_current_user_id() );
		
		// Set custom "Unknown" text for shortcode. Otherwise return expire date.
		if ( empty( $expire_date ) && ! is_null( $atts['unknown_text'] ) ) {
			return esc_html( $atts['unknown_text'] );
		} else {
			return edd_members_get_expire_date();
		}
		
	}
	
	return '';

}
add_shortcode( 'edd_members_expire_date', 'edd_members_expire_date_shortcode' );

/**
 * Add edd_members_only shortcode for members only content.
 *
 * @since  1.0.0
 * @param  array $atts The attributes to pass to the shortcode
 * @param  string $content The content of the shortcode
 * @return string $content The data to return for the shortcode
 */
function edd_members_only_shortcode( $atts, $content = null ) {
	
	$atts = shortcode_atts( array(
		'message'    => null,
		'login-form' => false,
	), $atts );
	
	// Is membership valid
	$edd_members_is_membership_valid = edd_members_is_membership_valid();
	
	if( $edd_members_is_membership_valid || current_user_can( 'edd_members_show_all_content' ) ) {
		$content = do_shortcode( $content );
	} elseif( ! is_null( $atts['message'] ) ) {
		$content = '<div class="edd-members-private-message edd-members-private-shortcode">' . wpautop( ( $atts['message'] ) ) . '</div>';
	} else {
		ob_start();
			$content = edd_get_template_part( 'content', 'private' );
		return ob_get_clean();
	}
	
	return $content;
}
add_shortcode( 'edd_members_only', 'edd_members_only_shortcode' );

/**
 * Add edd_members_drip shortcode for delayed content.
 *
 * @since  1.0.0
 * @param  array $atts The attributes to pass to the shortcode
 * @param  string $content The content of the shortcode
 * @return string $content The data to return for the shortcode
 */
function edd_members_drip_shortcode( $atts, $content = null ) {
	
	$atts = shortcode_atts( array(
		'delay'   => null,
		'message' => null
	), $atts );
	
	// Current date
	$current_date = current_time( 'timestamp' );
	
	// Get expire date
	$expire_date = edd_members_get_unix_expire_date();
	
	// Set delay
	$delay = '+' . absint( $atts['delay'] ) . ' ' . 'days';
	
	// Delay time
	$delay_time = strtotime( $delay, $expire_date );
	
	// Calculate when to show content
	$when_to_show_time = $current_date - $delay_time;
	
	if( $when_to_show_time >= 0 ) {
		$content = do_shortcode( $content );
	} elseif( ! is_null( $atts['message'] ) ) {
		$content = wpautop( $atts['message'] );
	} else {
		$content = '';
	}
	
	return $content;
}
add_shortcode( 'edd_members_drip', 'edd_members_drip_shortcode' );

/**
 * Add edd_members_name shortcode for displaying name or any other author meta.
 *
 * @since  1.1.2
 * @param  array $atts The attributes to pass to the shortcode
 * @param  string $content The content of the shortcode
 * @return string $content The data to return for the shortcode
 */
function edd_members_name_shortcode( $atts, $content = null ) {
	
	$atts = shortcode_atts( array(
		'field'       => 'display_name',
	), $atts );
	
	// Bail if user is not logged in
	if ( ! is_user_logged_in() ) {
		return;
	}
	
	// Return current user name or any other author meta when using field attribute
	return get_the_author_meta( esc_attr( $atts['field'] ), get_current_user_id() );
	
}
add_shortcode( 'edd_members_name', 'edd_members_name_shortcode' );