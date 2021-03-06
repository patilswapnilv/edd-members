<?php
/**
 * Additional settings handling email notices
 *
 * @package     EDDMembers\Settings
 * @since       1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the renewal notices options
 *
 * @access      public
 * @since       1.0.0
 * @param 		$args array option arguments
 * @return      void
*/
function edd_members_renewal_notices_settings( $args ) {

	$notices = edd_members_get_renewal_notices();
	//echo '<pre>'; print_r( $notices ); echo '</pre>';
	ob_start(); ?>
	<table id="edd_members_renewal_notices" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th style="width:350%; padding-left: 10px;" scope="col"><?php esc_html_e( 'Subject', 'edd-members' ); ?></th>
				<th style="width:350%; padding-left: 10px;" scope="col"><?php esc_html_e( 'Send Period', 'edd-members' ); ?></th>
				<th scope="col" style="padding-left: 10px;"><?php esc_html_e( 'Actions', 'edd-members' ); ?></th>
			</tr>
		</thead>
		<?php if( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach( $notices as $key => $notice ) : $notice = edd_members_get_renewal_notice( $key ); ?>
			<tr <?php if( $i % 2 == 0 ) { echo 'class="alternate"'; } ?>>
				<td><?php echo esc_html( $notice['subject'] ); ?></td>
				<td><?php echo esc_html( edd_members_get_renewal_notice_period_label( $key ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-members-renewal-notice&edd_members_action=edit-renewal-notice&notice=' . $key ) ); ?>" class="edd-members-edit-renewal-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Edit', 'edd-members' ); ?></a>&nbsp;|
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=download&page=edd-members-renewal-notice&edd_action=members_delete_renewal_notice&notice-id=' . $key ) ) ); ?>" class="edd-delete"><?php esc_html_e( 'Delete', 'edd-members' ); ?></a>
				</td>
			</tr>
			<?php $i++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-members-renewal-notice&edd_members_action=add-renewal-notice' ) ); ?>" class="button-secondary" id="edd_members_add_renewal_notice"><?php esc_html_e( 'Add Renewal Notice', 'edd-members' ); ?></a>
	</p>
	<?php
	echo ob_get_clean();
}
add_action( 'edd_members_renewal_notices', 'edd_members_renewal_notices_settings' );

/**
 * Renders the add / edit renewal notice screen
 *
 * @since  1.0.0
 * @param  array $input The value inputted in the field
 * @return string $input Sanitizied value
 */
function edd_members_renewal_notice_edit() {

	$action = isset( $_GET['edd_members_action'] ) ? sanitize_text_field( $_GET['edd_members_action'] ) : 'add-renewal-notice';

	if( 'edit-renewal-notice' === $action ) {
		include EDD_MEMBERS_DIR . 'includes/edit-renewal-notice.php';
	} else {
		include EDD_MEMBERS_DIR . 'includes/add-renewal-notice.php';
	}

}

/**
 * Processes the creation of a new renewal notice
 *
 * @since  1.0.0
 * @param  array $data The post data
 * @return void
 */
function edd_members_process_add_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices', 'edd-members' ) );
	}

	if( ! wp_verify_nonce( $data['edd-members-renewal-notice-nonce'], 'edd_members_renewal_nonce' ) ) {
		wp_die( __( 'Nonce verification failed', 'edd-members' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : esc_html__( 'Your membership is about to expire', 'edd-members' );
	$period  = isset( $data['period'] )  ? sanitize_text_field( $data['period'] )  : '+1week';
	$message = isset( $data['message'] ) ? wp_kses( $data['message'], wp_kses_allowed_html( 'post' ) ) : false;

	if( empty( $message ) ) {
		$message = 'Hello {name},

Your membership is about to expire.

Your membership expires on: {edd_members_expiration}.

Renew now: {renewal_link}.';
	}


	$notices = edd_members_get_renewal_notices();
	$notices[] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period
	);

	update_option( 'edd_members_renewal_notices', $notices );

	wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-members-settings-section' ) ); exit;

}
add_action( 'edd_members_add_renewal_notice', 'edd_members_process_add_renewal_notice' );

/**
 * Processes the update of an existing renewal notice
 *
 * @since  1.0.0
 * @param  array $data The post data
 * @return void
 */
function edd_members_process_update_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to add renewal notices', 'edd-members' ) );
	}

	if( ! wp_verify_nonce( $data['edd-members-renewal-notice-nonce'], 'edd_members_renewal_nonce' ) ) {
		wp_die( esc_html__( 'Nonce verification failed', 'edd-members' ) );
	}

	if( ! isset( $data['notice-id'] ) ) {
		wp_die( esc_html__( 'No renewal notice ID was provided', 'edd-members' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : esc_html__( 'Your membership is about to expire', 'edd-members' );
	$period  = isset( $data['period'] )  ? sanitize_text_field( $data['period'] )  : '+1week';
	$message = isset( $data['message'] ) ? wp_kses( $data['message'], wp_kses_allowed_html( 'post' ) ) : false;

	if( empty( $message ) ) {
		$message = 'Hello {name},

Your membership is about to expire.

Your membership expires on: {edd_members_expiration}.

Renew now: {renewal_link}.';
	}


	$notices = edd_members_get_renewal_notices();
	$notices[ absint( $data['notice-id'] ) ] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period
	);

	update_option( 'edd_members_renewal_notices', $notices );

	wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-members-settings-section' ) ); exit;

}
add_action( 'edd_members_edit_renewal_notice', 'edd_members_process_update_renewal_notice' );

/**
 * Processes the deletion of an existing renewal notice
 *
 * @since  1.0.0
 * @param  array $data The post data
 * @return void
 */
function edd_members_process_delete_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to add renewal notices', 'edd-members' ) );
	}

	if( ! wp_verify_nonce( $data['_wpnonce'] ) ) {
		wp_die( esc_html__( 'Nonce verification failed', 'edd-members' ) );
	}

	if( empty( $data['notice-id'] ) ) {
		wp_die( esc_html__( 'No renewal notice ID was provided', 'edd-members' ) );
	}

	$notices = edd_members_get_renewal_notices();
	unset( $notices[ absint( $data['notice-id'] ) ] );

	update_option( 'edd_members_renewal_notices', $notices );

	wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-members-settings-section' ) ); exit;

}
add_action( 'edd_members_delete_renewal_notice', 'edd_members_process_delete_renewal_notice' );

/**
 * Add renewal admin submenu page
 * *
 * @access      private
 * @since       1.0.0
 * @return      void
*/
function edd_members_add_renewal_page() {

	$edd_members_renewal_page = add_submenu_page( 'edit.php?post_type=download', esc_html__( 'Membership Renewal Notice', 'edd-members' ), __( 'Membership Renewal Notice', 'edd-members' ), 'manage_shop_settings', 'edd-members-renewal-notice', 'edd_members_renewal_notice_edit' );

	add_action( 'admin_head', 'edd_members_hide_renewal_notice_page' );
}
add_action( 'admin_menu', 'edd_members_add_renewal_page', 10 );

/**
 * Removes the membership renewal notice menu link
 *
 * @access      private
 * @since       1.0.0
 * @return      void
*/
function edd_members_hide_renewal_notice_page() {
	remove_submenu_page( 'edit.php?post_type=download', 'edd-members-renewal-notice' );
}