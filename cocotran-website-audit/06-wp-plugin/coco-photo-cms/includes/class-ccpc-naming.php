<?php
/**
 * Metadata fields on the attachment (property, place, caption, alt, credit, visited, license),
 * required alt, and descriptive filenames built from a template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Naming {

	const FIELDS = array( 'property', 'place', 'subject', 'credit', 'visited', 'license_note' );

	public static function init() {
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'prefilter_name' ) );
		add_action( 'add_attachment', array( __CLASS__, 'on_add' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * Build a filename stem from the template and field values.
	 *
	 * @param array $vals  property, place, subject, n.
	 * @return string Slug (no extension) or '' if nothing usable.
	 */
	public static function build_stem( $vals ) {
		$tpl  = (string) ccpc_setting( 'filename_template' );
		$vals = wp_parse_args( $vals, array( 'property' => '', 'place' => '', 'subject' => '', 'n' => '' ) );
		if ( '' === trim( $vals['property'] . $vals['place'] . $vals['subject'] ) ) {
			return '';
		}
		$out = $tpl;
		foreach ( $vals as $k => $v ) {
			$out = str_replace( '{' . $k . '}', sanitize_title( (string) $v ), $out );
		}
		$out = preg_replace( '/-{2,}/', '-', $out );
		$out = trim( $out, '-' );
		return sanitize_title( $out );
	}

	/**
	 * Rename at upload time when fields arrive with the request (REST intake sets these).
	 */
	public static function prefilter_name( $file ) {
		if ( empty( $_POST['ccpc_property'] ) && empty( $_POST['ccpc_place'] ) && empty( $_POST['ccpc_subject'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $file;
		}
		$vals = array();
		foreach ( array( 'property', 'place', 'subject' ) as $k ) {
			$vals[ $k ] = isset( $_POST[ 'ccpc_' . $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'ccpc_' . $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		}
		$vals['n'] = isset( $_POST['ccpc_n'] ) ? (int) $_POST['ccpc_n'] : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$stem      = self::build_stem( $vals );
		if ( $stem ) {
			$ext          = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$file['name'] = $stem . ( $ext ? '.' . strtolower( $ext ) : '' );
		}
		return $file;
	}

	/**
	 * Default credit on new attachments.
	 */
	public static function on_add( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}
		if ( '' === (string) get_post_meta( $attachment_id, '_ccpc_credit', true ) ) {
			update_post_meta( $attachment_id, '_ccpc_credit', (string) ccpc_setting( 'default_credit' ) );
		}
		foreach ( self::FIELDS as $k ) {
			$key = 'ccpc_' . $k;
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- REST/sideload passes these.
				update_post_meta( $attachment_id, '_ccpc_' . $k, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}

	/**
	 * Fields in the media modal / attachment edit screen.
	 */
	public static function fields( $form_fields, $post ) {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $form_fields;
		}
		$labels = array(
			'property'     => __( 'Property / subject', 'coco-photo-cms' ),
			'place'        => __( 'Place', 'coco-photo-cms' ),
			'subject'      => __( 'Detail (room, dish, view…)', 'coco-photo-cms' ),
			'credit'       => __( 'Credit', 'coco-photo-cms' ),
			'visited'      => __( 'Visited (month year)', 'coco-photo-cms' ),
			'license_note' => __( 'License / usage note', 'coco-photo-cms' ),
		);
		foreach ( $labels as $k => $label ) {
			$form_fields[ 'ccpc_' . $k ] = array(
				'label' => $label,
				'input' => 'text',
				'value' => (string) get_post_meta( $post->ID, '_ccpc_' . $k, true ),
				'helps' => 'property' === $k ? sprintf( __( 'Used to build the filename: %s', 'coco-photo-cms' ), esc_html( ccpc_setting( 'filename_template' ) ) ) : '',
			);
		}
		$alt = (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
		if ( '' === trim( $alt ) ) {
			$form_fields['ccpc_alt_warning'] = array(
				'label' => '',
				'input' => 'html',
				'html'  => '<p class="ccpc-alt-warning"><strong>' . esc_html__( 'Alt text is empty.', 'coco-photo-cms' ) . '</strong> ' . esc_html__( 'Describe what is actually in the photo. Saving without it is blocked.', 'coco-photo-cms' ) . '</p>',
			);
		}
		$form_fields['ccpc_rename'] = array(
			'label' => __( 'Filename', 'coco-photo-cms' ),
			'input' => 'html',
			'html'  => '<code>' . esc_html( basename( (string) get_post_meta( $post->ID, '_wp_attached_file', true ) ) ) . '</code> '
				. '<button type="button" class="button button-small ccpc-rename-btn" data-id="' . (int) $post->ID . '">' . esc_html__( 'Rename…', 'coco-photo-cms' ) . '</button>'
				. '<span class="ccpc-rename-help">' . esc_html__( 'Renaming updates every post that uses it and adds a 301 from the old URL.', 'coco-photo-cms' ) . '</span>',
		);
		return $form_fields;
	}

	/**
	 * Save fields; enforce alt; optionally rename from the template.
	 */
	public static function save( $post, $attachment ) {
		$id = (int) $post['ID'];
		if ( ! wp_attachment_is_image( $id ) ) {
			return $post;
		}
		foreach ( self::FIELDS as $k ) {
			if ( isset( $attachment[ 'ccpc_' . $k ] ) ) {
				update_post_meta( $id, '_ccpc_' . $k, sanitize_text_field( wp_unslash( $attachment[ 'ccpc_' . $k ] ) ) );
			}
		}

		// Alt: the modal posts it as changes[alt] (async) or attachments[id][image_alt] (edit screen).
		$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		if ( isset( $attachment['image_alt'] ) ) {
			$alt = sanitize_text_field( wp_unslash( $attachment['image_alt'] ) );
		}
		if ( '' === trim( $alt ) && ! empty( ccpc_setting( 'auto_alt' ) ) ) {
			$alt = self::auto_alt( $id );
			if ( $alt ) {
				update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			}
		}
		if ( '' === trim( $alt ) ) {
			set_transient( 'ccpc_notice_' . get_current_user_id(), __( 'Alt text is required: that image was saved but is flagged. Add alt text before using it.', 'coco-photo-cms' ), 60 );
			update_post_meta( $id, '_ccpc_alt_missing', 1 );
		} else {
			delete_post_meta( $id, '_ccpc_alt_missing' );
		}

		if ( ! empty( ccpc_setting( 'auto_rename' ) ) ) {
			$stem = self::build_stem( array(
				'property' => get_post_meta( $id, '_ccpc_property', true ),
				'place'    => get_post_meta( $id, '_ccpc_place', true ),
				'subject'  => get_post_meta( $id, '_ccpc_subject', true ),
				'n'        => '',
			) );
			$current = pathinfo( (string) get_post_meta( $id, '_wp_attached_file', true ), PATHINFO_FILENAME );
			// Only rename when the current name is not already derived from this stem (avoid -2, -3 churn).
			if ( $stem && 0 !== strpos( $current, $stem ) ) {
				CCPC_Rename::rename( $id, $stem );
			}
		}
		return $post;
	}

	/**
	 * Alt from fields, only when the setting allows.
	 */
	public static function auto_alt( $id ) {
		$parts = array_filter( array(
			get_post_meta( $id, '_ccpc_subject', true ),
			get_post_meta( $id, '_ccpc_property', true ),
			get_post_meta( $id, '_ccpc_place', true ),
		) );
		if ( ! $parts ) {
			return '';
		}
		return sanitize_text_field( implode( ', ', $parts ) );
	}

	public static function notices() {
		$msg = get_transient( 'ccpc_notice_' . get_current_user_id() );
		if ( $msg ) {
			delete_transient( 'ccpc_notice_' . get_current_user_id() );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}
}
