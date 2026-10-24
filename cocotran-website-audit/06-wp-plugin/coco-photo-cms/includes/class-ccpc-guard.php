<?php
/**
 * In-use guard: refuse to permanently delete an attachment that posts/fields still reference,
 * unless the user explicitly confirms ("Delete anyway") or a REST caller sends force.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Guard {

	const TRANSIENT_PREFIX = 'ccpc_force_delete_';
	const TTL              = 10 * MINUTE_IN_SECONDS;

	public static function init() {
		if ( empty( ccpc_setting( 'delete_guard' ) ) ) {
			return;
		}
		add_filter( 'pre_delete_attachment', array( __CLASS__, 'pre_delete' ), 10, 3 );
		add_action( 'load-upload.php', array( __CLASS__, 'intercept_list_delete' ) );
		add_action( 'load-post.php', array( __CLASS__, 'intercept_list_delete' ) );
		add_action( 'admin_post_ccpc_force_delete', array( __CLASS__, 'handle_force' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'rest_guard' ), 10, 3 );
	}

	/**
	 * Usages of an attachment that should block a delete, or an empty array.
	 */
	public static function blocking_usages( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return array();
		}
		return CCPC_Usages::find( $attachment_id );
	}

	/**
	 * Is a one-shot force ticket present for this attachment and user?
	 */
	public static function has_ticket( $attachment_id ) {
		$t = get_transient( self::TRANSIENT_PREFIX . (int) $attachment_id );
		return $t && (int) $t === get_current_user_id();
	}

	public static function grant_ticket( $attachment_id ) {
		set_transient( self::TRANSIENT_PREFIX . (int) $attachment_id, get_current_user_id(), self::TTL );
	}

	public static function consume_ticket( $attachment_id ) {
		delete_transient( self::TRANSIENT_PREFIX . (int) $attachment_id );
	}

	/**
	 * Last line of defence (wp_delete_attachment from anywhere). Returning false aborts the delete.
	 */
	public static function pre_delete( $delete, $post, $force ) {
		if ( null !== $delete || ! $post instanceof WP_Post ) {
			return $delete;
		}
		if ( self::has_ticket( $post->ID ) ) {
			self::consume_ticket( $post->ID );
			return $delete;
		}
		$usages = self::blocking_usages( $post->ID );
		if ( ! $usages ) {
			return $delete;
		}
		if ( is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			// A path we did not intercept (another plugin, grid-view AJAX…): queue the notice for the next screen.
			self::remember_block( $post->ID );
		}
		return false;
	}

	/**
	 * Media list ("Delete permanently" row link and bulk delete) and the attachment edit screen's
	 * delete link all arrive on upload.php / post.php with action=delete. Check before core runs.
	 */
	public static function intercept_list_delete() {
		$action = '';
		foreach ( array( 'action', 'action2' ) as $k ) {
			if ( isset( $_REQUEST[ $k ] ) && 'delete' === $_REQUEST[ $k ] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$action = 'delete';
			}
		}
		if ( 'delete' !== $action ) {
			return;
		}
		$ids = array();
		if ( isset( $_REQUEST['media'] ) && is_array( $_REQUEST['media'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$ids = array_map( 'intval', (array) $_REQUEST['media'] ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( isset( $_REQUEST['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$ids = array( (int) $_REQUEST['post'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$blocked = array();
		foreach ( $ids as $id ) {
			if ( 'attachment' !== get_post_type( $id ) || self::has_ticket( $id ) ) {
				continue;
			}
			if ( self::blocking_usages( $id ) ) {
				$blocked[] = $id;
			}
		}
		if ( ! $blocked ) {
			return;
		}
		// Let the non-blocked ones through, but only in the bulk flow (single delete is all-or-nothing).
		$allowed = array_values( array_diff( $ids, $blocked ) );
		foreach ( $blocked as $id ) {
			self::remember_block( $id );
		}
		if ( $allowed && isset( $_REQUEST['media'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_REQUEST['media'] = $allowed;
			$_POST['media']    = $allowed;
			$_GET['media']     = $allowed;
			return;
		}
		wp_safe_redirect( self::back_url( $blocked[0] ) );
		exit;
	}

	protected static function remember_block( $attachment_id ) {
		$list = get_transient( 'ccpc_blocked_' . get_current_user_id() );
		$list = is_array( $list ) ? $list : array();
		$list[ (int) $attachment_id ] = time();
		set_transient( 'ccpc_blocked_' . get_current_user_id(), $list, 5 * MINUTE_IN_SECONDS );
	}

	protected static function back_url( $attachment_id ) {
		$ref = wp_get_referer();
		if ( $ref && false !== strpos( $ref, '/wp-admin/' ) && false === strpos( $ref, 'action=delete' ) ) {
			return remove_query_arg( array( 'deleted', 'trashed' ), $ref );
		}
		return admin_url( 'upload.php?mode=list' );
	}

	/**
	 * "Delete anyway": grant a one-shot ticket and send the user into core's normal delete flow.
	 */
	public static function handle_force() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( ! $id || ! current_user_can( 'delete_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'coco-photo-cms' ) );
		}
		check_admin_referer( 'ccpc_force_delete_' . $id );
		self::grant_ticket( $id );
		$list = get_transient( 'ccpc_blocked_' . get_current_user_id() );
		if ( is_array( $list ) ) {
			unset( $list[ $id ] );
			set_transient( 'ccpc_blocked_' . get_current_user_id(), $list, 5 * MINUTE_IN_SECONDS );
		}
		wp_safe_redirect( wp_nonce_url( admin_url( 'post.php?action=delete&post=' . $id ), 'delete-post_' . $id ) );
		exit;
	}

	public static function force_link( $attachment_id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=ccpc_force_delete&id=' . (int) $attachment_id ), 'ccpc_force_delete_' . (int) $attachment_id );
	}

	public static function notices() {
		$list = get_transient( 'ccpc_blocked_' . get_current_user_id() );
		if ( ! is_array( $list ) || ! $list ) {
			return;
		}
		delete_transient( 'ccpc_blocked_' . get_current_user_id() );
		foreach ( array_keys( $list ) as $id ) {
			$usages = self::blocking_usages( $id );
			if ( ! $usages ) {
				continue;
			}
			$title = get_the_title( $id );
			$title = $title ? $title : basename( (string) get_post_meta( $id, '_wp_attached_file', true ) );
			echo '<div class="notice notice-error ccpc-guard-notice"><p><strong>'
				. esc_html( sprintf( __( 'Not deleted: "%1$s" (#%2$d) is still used in %3$s.', 'coco-photo-cms' ), $title, (int) $id, sprintf( _n( '%d place', '%d places', count( $usages ), 'coco-photo-cms' ), count( $usages ) ) ) )
				. '</strong></p><ul class="ul-disc">';
			foreach ( array_slice( $usages, 0, 5 ) as $u ) {
				echo '<li><a href="' . esc_url( $u['edit_link'] ) . '">' . esc_html( $u['title'] ) . '</a> <code>' . esc_html( $u['location'] ) . '</code></li>';
			}
			if ( count( $usages ) > 5 ) {
				echo '<li>' . esc_html( sprintf( __( '… and %d more', 'coco-photo-cms' ), count( $usages ) - 5 ) ) . '</li>';
			}
			echo '</ul><p><a class="button" href="' . esc_url( get_edit_post_link( $id ) . '#ccpc-usages' ) . '">' . esc_html__( 'Replace it first', 'coco-photo-cms' ) . '</a> '
				. '<a class="button button-link-delete" href="' . esc_url( self::force_link( $id ) ) . '" onclick="return window.confirm(' . esc_attr( wp_json_encode( __( 'Those posts will show a broken image. Delete anyway?', 'coco-photo-cms' ) ) ) . ')">' . esc_html__( 'Delete anyway', 'coco-photo-cms' ) . '</a></p></div>';
		}
	}

	/**
	 * REST: DELETE /wp/v2/media/{id} (and our own /media/{id}) → 409 with the usages unless force.
	 */
	public static function rest_guard( $response, $handler, $request ) {
		if ( is_wp_error( $response ) || 'DELETE' !== $request->get_method() ) {
			return $response;
		}
		$route = $request->get_route();
		if ( ! preg_match( '#^/wp/v2/media/(\d+)$#', $route, $m ) ) {
			return $response;
		}
		$id = (int) $m[1];
		if ( $request->get_param( 'ccpc_force' ) || self::has_ticket( $id ) ) {
			self::grant_ticket( $id ); // pre_delete() consumes it.
			return $response;
		}
		$usages = self::blocking_usages( $id );
		if ( ! $usages ) {
			return $response;
		}
		return self::conflict( $id, $usages );
	}

	public static function conflict( $id, $usages ) {
		return new WP_Error(
			'ccpc_in_use',
			sprintf( 'Attachment %d is still used in %d place(s). Replace it or send ccpc_force=1.', (int) $id, count( $usages ) ),
			array( 'status' => 409, 'usages' => $usages )
		);
	}
}
