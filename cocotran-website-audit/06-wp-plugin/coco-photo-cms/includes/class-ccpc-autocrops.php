<?php
/**
 * 1.2: social crops generated automatically when a post is published (featured image + the
 * images referenced in its meta), and the post-level crop endpoints the Studio Desk / Canva
 * hand-off reads.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_AutoCrops {

	const HOOK        = 'ccpc_auto_crops';
	const LOG_OPTION  = 'ccpc_auto_crops_log';
	const LOG_MAX     = 50;
	const META_POST   = '_ccpc_auto_crops';   // on the post: { time, source, ids, generated, reused }.
	const META_SOURCE = '_ccpc_crops_source'; // on the attachment: { auto, post_id, time, formats }.
	const DELAY       = 10;

	/**
	 * Post types watched (filter `ccpc_auto_crops_post_types`).
	 */
	const POST_TYPES = array( 'post', 'page', 'property', 'guide', 'brief', 'dispatch', 'shortlist' );

	/** @var int[] Post IDs already queued in this request (publish fires transition + save_post). */
	protected static $queued = array();

	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'on_save' ), 20, 3 );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
	}

	public static function post_types() {
		return (array) apply_filters( 'ccpc_auto_crops_post_types', self::POST_TYPES );
	}

	/* ---------- Triggers ---------- */

	public static function on_transition( $new, $old, $post ) {
		if ( 'publish' !== $new || 'publish' === $old || ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! ccpc_setting( 'auto_crops_on_publish' ) || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		self::schedule( $post->ID, 'publish' );
	}

	public static function on_save( $post_id, $post, $update ) {
		if ( ! $update || ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( ! ccpc_setting( 'auto_crops_on_publish' ) || ! ccpc_setting( 'auto_crops_refresh' ) || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		self::schedule( (int) $post_id, 'save' );
	}

	/**
	 * Queue one run for the post: a single WP-Cron event 10 s out (so ACF / REST meta writes that
	 * follow wp_insert_post() are on disk), or, when WP-Cron is disabled, at the end of this request.
	 *
	 * @return string 'cron' | 'shutdown' | 'queued' (already pending).
	 */
	public static function schedule( $post_id, $reason = 'publish' ) {
		$post_id = (int) $post_id;
		if ( isset( self::$queued[ $post_id ] ) ) {
			return 'queued';
		}
		self::$queued[ $post_id ] = $reason;
		$sync = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$sync = (bool) apply_filters( 'ccpc_auto_crops_sync', $sync, $post_id );
		if ( $sync ) {
			add_action( 'shutdown', function () use ( $post_id ) {
				self::run( $post_id, 'sync' );
			}, 5 );
			return 'shutdown';
		}
		if ( ! wp_next_scheduled( self::HOOK, array( $post_id ) ) ) {
			wp_schedule_single_event( time() + self::DELAY, self::HOOK, array( $post_id ) );
		}
		return 'cron';
	}

	/* ---------- Collecting images ---------- */

	/**
	 * Attachment IDs a post uses, featured image first, then every ID referenced in its meta
	 * (ACF image / gallery fields, Kadence gallery JSON, serialized arrays), then content images.
	 * Only image attachments; unique; order preserved.
	 *
	 * @return int[]
	 */
	public static function collect_ids( $post_id ) {
		$post_id = (int) $post_id;
		$ids     = array();
		$thumb   = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$ids[] = $thumb;
		}
		$meta = get_post_meta( $post_id );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				if ( '' === $key || '_' === $key[0] ) {
					continue; // ACF field refs (_name), WP internals, our own meta.
				}
				$img_key = self::key_looks_like_image( $key );
				foreach ( (array) $values as $raw ) {
					$v = maybe_unserialize( $raw );
					if ( is_numeric( $v ) ) {
						if ( $img_key && (int) $v > 0 ) {
							$ids[] = (int) $v;
						}
						continue;
					}
					if ( is_array( $v ) ) {
						foreach ( $v as $item ) {
							if ( is_numeric( $item ) && (int) $item > 0 && $img_key ) {
								$ids[] = (int) $item;
							} elseif ( is_array( $item ) && ! empty( $item['ID'] ) ) {
								$ids[] = (int) $item['ID'];
							} elseif ( is_array( $item ) && ! empty( $item['id'] ) && is_numeric( $item['id'] ) ) {
								$ids[] = (int) $item['id'];
							}
						}
						continue;
					}
					if ( is_string( $v ) && false !== strpos( $v, '"id"' ) && preg_match_all( '/"id"\s*:\s*(\d+)/', $v, $m ) ) {
						foreach ( $m[1] as $n ) {
							$ids[] = (int) $n;
						}
					}
				}
			}
		}
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( '' !== $content ) {
			if ( preg_match_all( '/wp-image-(\d+)/', $content, $m ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
			}
			if ( preg_match_all( '/"id":(\d+)[,}]/', $content, $m ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
			}
		}
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		$ids = array_values( array_filter( $ids, function ( $id ) {
			return wp_attachment_is_image( $id ) && CCPC_Processor::is_processable( (string) get_post_mime_type( $id ) );
		} ) );
		return (array) apply_filters( 'ccpc_auto_crops_ids', $ids, $post_id );
	}

	public static function key_looks_like_image( $key ) {
		return (bool) preg_match( '/(image|img|photo|gallery|hero|iamge|thumbnail|cover)/i', (string) $key );
	}

	/**
	 * The set a run works on: [ featured_id|0, [ other ids, at most auto_crops_max ] ].
	 */
	public static function targets( $post_id ) {
		$ids   = self::collect_ids( $post_id );
		$thumb = (int) get_post_thumbnail_id( $post_id );
		$thumb = ( $thumb && in_array( $thumb, $ids, true ) ) ? $thumb : 0;
		$rest  = array_values( array_diff( $ids, array( $thumb ) ) );
		$max   = max( 0, (int) ccpc_setting( 'auto_crops_max' ) );
		return array( $thumb, array_slice( $rest, 0, $max ) );
	}

	/* ---------- Running ---------- */

	/**
	 * Formats (non-watermark) not yet cached for the current key.
	 *
	 * @return string[]
	 */
	public static function missing_formats( $attachment_id ) {
		$cached = CCPC_Crops::cached( $attachment_id );
		$missing = array();
		foreach ( array_keys( CCPC_Crops::FORMATS ) as $fmt ) {
			if ( ! isset( $cached[ $fmt ] ) ) {
				$missing[] = $fmt;
			}
		}
		return $missing;
	}

	/**
	 * Generate the four crops for the post's featured image + first N images. Existing crops for
	 * the current key are never regenerated; the focal point is used when set, centre otherwise.
	 *
	 * @param int    $post_id Post.
	 * @param string $source  'cron' | 'sync' | 'rest' | 'cli' (for the log).
	 * @return array|WP_Error { post_id, ids, generated, reused, errors, seconds }.
	 */
	public static function run( $post_id, $source = 'cron' ) {
		$t0      = microtime( true );
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) || ( 'rest' !== $source && 'publish' !== $post->post_status ) ) {
			self::log( sprintf( 'post %d skipped (%s): not published', $post_id, $source ) );
			return new WP_Error( 'ccpc_not_published', 'Post is not published.', array( 'status' => 404 ) );
		}
		if ( 'rest' !== $source && ! ccpc_setting( 'auto_crops_on_publish' ) ) {
			self::log( sprintf( 'post %d skipped (%s): auto crops are off', $post_id, $source ) );
			return new WP_Error( 'ccpc_auto_off', 'Auto crops are off.', array( 'status' => 409 ) );
		}
		list( $thumb, $others ) = self::targets( $post_id );
		$ids = array_values( array_filter( array_merge( array( $thumb ), $others ) ) );

		$generated = 0;
		$reused    = 0;
		$errors    = array();
		foreach ( $ids as $id ) {
			$missing = self::missing_formats( $id );
			$reused += count( CCPC_Crops::FORMATS ) - count( $missing );
			if ( ! $missing ) {
				continue;
			}
			$r = CCPC_Crops::generate( $id, $missing, null, false, false );
			if ( is_wp_error( $r ) ) {
				$errors[] = $id . ': ' . $r->get_error_message();
				continue;
			}
			$generated += count( $r );
			update_post_meta( $id, self::META_SOURCE, array(
				'auto'    => 'rest' !== $source,
				'source'  => $source,
				'post_id' => $post_id,
				'time'    => time(),
				'formats' => array_keys( $r ),
			) );
		}
		$seconds = round( microtime( true ) - $t0, 2 );
		$result  = array(
			'post_id'   => $post_id,
			'ids'       => $ids,
			'generated' => $generated,
			'reused'    => $reused,
			'errors'    => $errors,
			'seconds'   => $seconds,
		);
		update_post_meta( $post_id, self::META_POST, array(
			'time'      => time(),
			'source'    => $source,
			'ids'       => $ids,
			'generated' => $generated,
			'reused'    => $reused,
		) );
		self::log( sprintf(
			'post %d (%s) via %s: %d image%s, %d crop%s generated, %d reused%s (%ss)',
			$post_id,
			$post->post_type,
			$source,
			count( $ids ),
			1 === count( $ids ) ? '' : 's',
			$generated,
			1 === $generated ? '' : 's',
			$reused,
			$errors ? ', ' . count( $errors ) . ' error(s): ' . implode( '; ', $errors ) : '',
			$seconds
		) );
		do_action( 'ccpc_auto_crops_done', $post_id, $result );
		return $result;
	}

	/**
	 * One line per run, newest first, last 50.
	 */
	public static function log( $line ) {
		$log = get_option( self::LOG_OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		array_unshift( $log, gmdate( 'Y-m-d H:i:s' ) . ' ' . $line );
		$log = array_slice( $log, 0, self::LOG_MAX );
		update_option( self::LOG_OPTION, $log, false );
	}

	public static function get_log() {
		$log = get_option( self::LOG_OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/* ---------- Post-level description (REST) ---------- */

	/**
	 * One image, with the public crop URLs (uploads/ccpc-crops/…) or `missing: true` per format.
	 */
	public static function describe_image( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$cached        = CCPC_Crops::cached( $attachment_id );
		$crops         = array();
		foreach ( CCPC_Crops::FORMATS as $fmt => $f ) {
			if ( isset( $cached[ $fmt ] ) ) {
				$crops[ $fmt ] = array(
					'url'    => $cached[ $fmt ]['jpg'],
					'webp'   => $cached[ $fmt ]['webp'],
					'width'  => (int) $f[0],
					'height' => (int) $f[1],
					'bytes'  => (int) $cached[ $fmt ]['bytes_jpg'],
				);
			} else {
				$crops[ $fmt ] = array(
					'url'     => null,
					'webp'    => null,
					'width'   => (int) $f[0],
					'height'  => (int) $f[1],
					'missing' => true,
				);
			}
		}
		$src    = wp_get_attachment_image_src( $attachment_id, 'full' );
		$source = get_post_meta( $attachment_id, self::META_SOURCE, true );
		return array(
			'attachment_id' => $attachment_id,
			'url'           => $src ? strtok( (string) $src[0], '?' ) : wp_get_attachment_url( $attachment_id ),
			'width'         => $src ? (int) $src[1] : 0,
			'height'        => $src ? (int) $src[2] : 0,
			'alt'           => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'credit'        => (string) get_post_meta( $attachment_id, '_ccpc_credit', true ),
			'focal'         => CCPC_Crops::get_focal( $attachment_id ),
			'auto'          => is_array( $source ) && ! empty( $source['auto'] ),
			'crops'         => $crops,
		);
	}

	/**
	 * The `/post-crops/{id}` payload.
	 *
	 * @param int  $post_id  Post.
	 * @param bool $generate Generate missing crops inline first.
	 */
	public static function describe_post( $post_id, $generate = false ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) || in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
			return new WP_Error( 'ccpc_post', 'No post with that ID.', array( 'status' => 404 ) );
		}
		$run = null;
		if ( $generate ) {
			$run = self::run( $post_id, 'rest' );
		}
		list( $thumb, $others ) = self::targets( $post_id );
		$last = get_post_meta( $post_id, self::META_POST, true );
		return array(
			'post_id'      => $post_id,
			'post_type'    => $post->post_type,
			'post_status'  => $post->post_status,
			'title'        => get_the_title( $post ),
			'permalink'    => get_permalink( $post ),
			'featured'     => $thumb ? self::describe_image( $thumb ) : null,
			'images'       => array_map( array( __CLASS__, 'describe_image' ), $others ),
			'formats'      => CCPC_Rest::formats(),
			'generated_at' => ( is_array( $last ) && ! empty( $last['time'] ) ) ? gmdate( 'c', (int) $last['time'] ) : null,
			'generated'    => is_wp_error( $run ) ? null : ( $run ? array( 'generated' => $run['generated'], 'reused' => $run['reused'], 'errors' => $run['errors'] ) : null ),
		);
	}
}
