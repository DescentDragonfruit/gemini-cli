<?php
/**
 * Coco Tran — internal auto-linking from the library link map.
 *
 * Reads the link map the worker POSTs to /coco-tran/v1/link-map (option `ct_link_map`, an array of
 * {keyword, aliases[], url, title, post_id, pillar, destination, priority}) and, on singular pages,
 * links the FIRST plain-text occurrence of each keyword or alias inside paragraphs. Longest keyword
 * first, case-insensitive, word-boundary; never inside headings, existing links, buttons, captions,
 * blockquotes, the booking box or the answerable-opening block; never a post to itself; at most
 * `ct_autolink_max` (default 6) links per post and one per target URL; the first 150 visible
 * characters are left alone so the short answer stays clean. Works on a DOMDocument of the
 * rendered content, not regex over HTML. Post meta `ct_autolink_off` = 1 switches it off per post.
 *
 * Also the REST surface for the worker and the Studio Desk:
 *   GET  /coco-tran/v1/link-map                 → the stored map + version
 *   POST /coco-tran/v1/link-map                 → validate, dedupe, store, bump version, clear Pair caches
 *   GET  /coco-tran/v1/autolink-report?post=ID  → dry run: the links autolink would add to that post
 * (all three: capability `edit_others_posts`, Application Password basic auth).
 *
 * Filters: `ct_autolink_skip_selectors` (array of simple selectors: tag, .class, tag.class, #id),
 *          `ct_autolink_max` (int), `ct_autolink_post_types` (array), `ct_autolink_entries` (array).
 *
 * Ships inside the Coco Tran Site Snippets plugin. Settings: Settings → Reading → "Coco Tran snippets".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CT_AUTOLINK_SKIP_CHARS', 150 );
define( 'CT_AUTOLINK_MIN_KEYWORD', 4 );

/* -------------------------------------------------------------------------
 * Shared helpers (used by pair.php and authority.php too)
 * ---------------------------------------------------------------------- */

/** The five pillar CPTs + posts. */
function ct_site_post_types() {
	return apply_filters( 'ct_autolink_post_types', array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'post' ) );
}

/** The destination taxonomy slug on this install (`destination`, falling back to `destinations`), or ''. */
function ct_destination_taxonomy() {
	foreach ( apply_filters( 'ct_destination_taxonomies', array( 'destination', 'destinations' ) ) as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			return $tax;
		}
	}
	return '';
}

/**
 * Is this `the_content` run the main piece on a singular front-end page (not feed / REST / admin / embed)?
 * Block themes render core/post-content outside the classic loop, so in_the_loop() is not required;
 * the content must belong to the queried post.
 */
function ct_is_main_singular_content() {
	if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_the_ID();
	return $id && (int) $id === (int) get_queried_object_id();
}

/** Compare two site URLs as paths (scheme / host case / trailing slash / query / fragment ignored). */
function ct_url_key( $url ) {
	$url  = (string) $url;
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path = '/' . trim( $path, '/' );
	return strtolower( $path );
}

/**
 * Insert a block of HTML before the booking box when the box sits inside the content
 * (the `[ctt_book]` shortcode renders `<aside class="ctt-book"`), otherwise append it.
 */
function ct_insert_before_booking_box( $content, $html ) {
	if ( '' === $html ) {
		return $content;
	}
	$pos = strpos( $content, '<aside class="ctt-book"' );
	if ( false === $pos ) {
		$pos = strpos( $content, 'class="ctt-book' ); // a block wrapper carrying the class
		if ( false !== $pos ) {
			$lt  = strrpos( substr( $content, 0, $pos ), '<' );
			$pos = ( false === $lt ) ? false : $lt;
		}
	}
	if ( false === $pos ) {
		return $content . "\n" . $html;
	}
	return substr( $content, 0, $pos ) . $html . "\n" . substr( $content, $pos );
}

/* -------------------------------------------------------------------------
 * The link map
 * ---------------------------------------------------------------------- */

/** Stored map (already validated by the REST handler), filtered. */
function ct_link_map() {
	$map = get_option( 'ct_link_map', array() );
	if ( ! is_array( $map ) ) {
		$map = array();
	}
	return (array) apply_filters( 'ct_autolink_entries', array_values( $map ) );
}

/** Map version (timestamp set on every POST; 0 when no map yet). */
function ct_link_map_version() {
	return (int) get_option( 'ct_link_map_version', 0 );
}

/** Max links per post. */
function ct_autolink_max() {
	$max = (int) get_option( 'ct_autolink_max', 6 );
	if ( $max < 0 ) {
		$max = 0;
	}
	return (int) apply_filters( 'ct_autolink_max', $max );
}

/**
 * Validate one raw entry. Returns the clean entry, or a WP_Error with the reason.
 *
 * @param array  $raw  Entry from the request.
 * @param string $host Site host (lower-case, no leading www.).
 */
function ct_autolink_validate_entry( $raw, $host ) {
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'not_object', 'entry is not an object' );
	}
	$keyword = isset( $raw['keyword'] ) ? trim( wp_strip_all_tags( (string) $raw['keyword'] ) ) : '';
	$keyword = preg_replace( '/\s+/u', ' ', $keyword );
	if ( mb_strlen( $keyword ) < CT_AUTOLINK_MIN_KEYWORD ) {
		return new WP_Error( 'keyword_short', 'keyword must be at least ' . CT_AUTOLINK_MIN_KEYWORD . ' characters' );
	}
	if ( mb_strlen( $keyword ) > 80 ) {
		return new WP_Error( 'keyword_long', 'keyword longer than 80 characters' );
	}

	$url = isset( $raw['url'] ) ? esc_url_raw( trim( (string) $raw['url'] ) ) : '';
	if ( '' === $url ) {
		return new WP_Error( 'url_missing', 'url missing or invalid' );
	}
	$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$url_host = preg_replace( '/^www\./', '', $url_host );
	if ( '' === $url_host || $url_host !== $host ) {
		return new WP_Error( 'url_offsite', 'url is not on ' . $host );
	}

	$aliases = array();
	if ( ! empty( $raw['aliases'] ) && is_array( $raw['aliases'] ) ) {
		foreach ( $raw['aliases'] as $alias ) {
			$alias = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( (string) $alias ) ) );
			if ( mb_strlen( $alias ) >= CT_AUTOLINK_MIN_KEYWORD && mb_strlen( $alias ) <= 80 && 0 !== strcasecmp( $alias, $keyword ) ) {
				$aliases[ mb_strtolower( $alias ) ] = $alias;
			}
		}
	}

	$priority = isset( $raw['priority'] ) ? (int) $raw['priority'] : 1;
	$priority = max( 1, min( 3, $priority ) );

	$post_id = isset( $raw['post_id'] ) ? (int) $raw['post_id'] : 0;
	if ( ! $post_id ) {
		$post_id = (int) url_to_postid( $url );
	}

	return array(
		'keyword'     => $keyword,
		'aliases'     => array_values( $aliases ),
		'url'         => $url,
		'title'       => isset( $raw['title'] ) ? sanitize_text_field( (string) $raw['title'] ) : '',
		'post_id'     => $post_id,
		'pillar'      => isset( $raw['pillar'] ) ? sanitize_text_field( (string) $raw['pillar'] ) : '',
		'destination' => isset( $raw['destination'] ) ? sanitize_text_field( (string) $raw['destination'] ) : '',
		'priority'    => $priority,
	);
}

/**
 * Validate + dedupe a whole map. Returns ['entries' => [...], 'rejected' => [ [index, reason], … ]].
 * Dedupe: one entry per keyword (case-insensitive; higher priority wins), aliases that collide with
 * another entry's keyword or alias are dropped, entries with the same URL are merged (aliases union).
 */
function ct_autolink_validate_map( $raw_map ) {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$host = preg_replace( '/^www\./', '', $host );

	$rejected = array();
	$by_url   = array();
	if ( ! is_array( $raw_map ) ) {
		return array( 'entries' => array(), 'rejected' => array( array( 'index' => 0, 'reason' => 'map must be an array' ) ) );
	}
	if ( count( $raw_map ) > 3000 ) {
		return array( 'entries' => array(), 'rejected' => array( array( 'index' => 0, 'reason' => 'map larger than 3000 entries' ) ) );
	}
	foreach ( array_values( $raw_map ) as $i => $raw ) {
		$entry = ct_autolink_validate_entry( $raw, $host );
		if ( is_wp_error( $entry ) ) {
			$rejected[] = array( 'index' => $i, 'reason' => $entry->get_error_message() );
			continue;
		}
		$ukey = ct_url_key( $entry['url'] );
		if ( isset( $by_url[ $ukey ] ) ) {
			// Same URL twice: merge. Keep the higher-priority keyword as the keyword, the other becomes an alias.
			$have = $by_url[ $ukey ];
			if ( $entry['priority'] > $have['priority'] ) {
				$have['aliases'][] = $have['keyword'];
				$have['keyword']   = $entry['keyword'];
				$have['priority']  = $entry['priority'];
			} elseif ( 0 !== strcasecmp( $have['keyword'], $entry['keyword'] ) ) {
				$have['aliases'][] = $entry['keyword'];
			}
			$have['aliases'] = array_merge( $have['aliases'], $entry['aliases'] );
			foreach ( array( 'title', 'pillar', 'destination' ) as $f ) {
				if ( '' === $have[ $f ] && '' !== $entry[ $f ] ) {
					$have[ $f ] = $entry[ $f ];
				}
			}
			if ( ! $have['post_id'] ) {
				$have['post_id'] = $entry['post_id'];
			}
			$by_url[ $ukey ] = $have;
			$rejected[]      = array( 'index' => $i, 'reason' => 'duplicate url merged into "' . $have['keyword'] . '"' );
			continue;
		}
		$by_url[ $ukey ] = $entry;
	}

	// One owner per phrase across keywords and aliases (higher priority wins; then longer keyword).
	$entries = array_values( $by_url );
	usort(
		$entries,
		function ( $a, $b ) {
			if ( $a['priority'] !== $b['priority'] ) {
				return $b['priority'] <=> $a['priority'];
			}
			return mb_strlen( $b['keyword'] ) <=> mb_strlen( $a['keyword'] );
		}
	);
	$taken = array();
	$clean = array();
	foreach ( $entries as $entry ) {
		$k = mb_strtolower( $entry['keyword'] );
		if ( isset( $taken[ $k ] ) ) {
			$rejected[] = array( 'index' => -1, 'reason' => 'keyword "' . $entry['keyword'] . '" already owned by ' . $taken[ $k ] );
			continue;
		}
		$taken[ $k ] = $entry['url'];
		$aliases     = array();
		foreach ( $entry['aliases'] as $alias ) {
			$ak = mb_strtolower( $alias );
			if ( isset( $taken[ $ak ] ) ) {
				continue;
			}
			$taken[ $ak ] = $entry['url'];
			$aliases[]    = $alias;
		}
		$entry['aliases'] = array_values( array_unique( $aliases ) );
		$clean[]          = $entry;
	}
	return array( 'entries' => $clean, 'rejected' => $rejected );
}

/** Store a validated map, bump the version, clear the Pair caches. */
function ct_link_map_store( $entries ) {
	$version = time();
	if ( $version <= ct_link_map_version() ) {
		$version = ct_link_map_version() + 1;
	}
	delete_option( 'ct_link_map' );
	add_option( 'ct_link_map', array_values( $entries ), '', false ); // autoload off: it is only read on singular pages
	update_option( 'ct_link_map_version', $version, false );
	if ( function_exists( 'ct_pair_flush_cache' ) ) {
		ct_pair_flush_cache();
	}
	do_action( 'ct_link_map_updated', $entries, $version );
	return $version;
}

/* -------------------------------------------------------------------------
 * The engine
 * ---------------------------------------------------------------------- */

/** Selectors whose subtree is never linked. Simple selectors only: tag, .class, tag.class, #id. */
function ct_autolink_skip_selectors() {
	$defaults = array(
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'a', 'button', 'figcaption', 'blockquote', 'code', 'pre', 'kbd', 'script', 'style', 'textarea', 'select', 'label',
		'.wp-block-button', '.wp-element-button', '.button', '.btn',
		'.wp-block-pullquote', '.wp-block-quote', '.wp-caption', '.wp-caption-text', '.wp-block-image',
		'.ctt-book', '.ct-ao', '.ct-pair', '.ct-author', '.members-only-notice', '.ct-autolink-off',
	);
	return (array) apply_filters( 'ct_autolink_skip_selectors', $defaults );
}

/**
 * Does an element match one simple selector?
 *
 * @param DOMElement $el       Element.
 * @param string     $selector tag | .class | tag.class | #id | tag#id
 */
function ct_autolink_el_matches( $el, $selector ) {
	$selector = trim( (string) $selector );
	if ( '' === $selector ) {
		return false;
	}
	if ( ! preg_match( '/^([a-z0-9-]*)((?:[.#][A-Za-z0-9_-]+)*)$/i', $selector, $m ) ) {
		return false;
	}
	if ( '' !== $m[1] && strtolower( $el->tagName ) !== strtolower( $m[1] ) ) {
		return false;
	}
	if ( '' === $m[2] ) {
		return '' !== $m[1];
	}
	$classes = preg_split( '/\s+/', trim( (string) $el->getAttribute( 'class' ) ) );
	preg_match_all( '/([.#])([A-Za-z0-9_-]+)/', $m[2], $parts, PREG_SET_ORDER );
	foreach ( $parts as $p ) {
		if ( '.' === $p[1] && ! in_array( $p[2], $classes, true ) ) {
			return false;
		}
		if ( '#' === $p[1] && $el->getAttribute( 'id' ) !== $p[2] ) {
			return false;
		}
	}
	return true;
}

/**
 * Is a text node linkable: inside a <p>, and no ancestor (up to the root) matches a skip selector
 * or already carries an autolink?
 */
function ct_autolink_text_node_ok( $text, $root, $selectors ) {
	$in_p = false;
	for ( $el = $text->parentNode; $el && $el !== $root; $el = $el->parentNode ) {
		if ( ! $el instanceof DOMElement ) {
			continue;
		}
		if ( 'p' === strtolower( $el->tagName ) ) {
			$in_p = true;
		}
		if ( $el->hasAttribute( 'data-ct-autolink' ) ) {
			return false;
		}
		foreach ( $selectors as $sel ) {
			if ( ct_autolink_el_matches( $el, $sel ) ) {
				return false;
			}
		}
	}
	return $in_p;
}

/** Load an HTML fragment into a DOMDocument. Returns [DOMDocument, DOMElement root] or null. */
function ct_autolink_load_fragment( $html ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		return null;
	}
	$dom  = new DOMDocument( '1.0', 'UTF-8' );
	$prev = libxml_use_internal_errors( true );
	$ok   = $dom->loadHTML(
		'<?xml encoding="utf-8" ?><html><body><div id="ct-autolink-root">' . $html . '</div></body></html>',
		LIBXML_HTML_NODEFDTD | LIBXML_NONET
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	if ( ! $ok ) {
		return null;
	}
	$root = $dom->getElementById( 'ct-autolink-root' );
	if ( ! $root ) {
		return null;
	}
	return array( $dom, $root );
}

/** Serialise the fragment back (inner HTML of the root), as UTF-8. */
function ct_autolink_save_fragment( $dom, $root ) {
	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}
	return $out;
}

/**
 * Plan the links for one piece of rendered HTML. Returns:
 *   ['links' => [ {keyword, matched, url, title, post_id, priority, pillar, destination, context, position} … ],
 *    'dom' => DOMDocument|null, 'root' => DOMElement|null, 'nodes' => [ nodeIndex => [ [offset,len,link] … ] ]]
 *
 * @param string $html    Rendered content HTML.
 * @param int    $post_id The post being rendered (never linked to itself).
 */
function ct_autolink_plan( $html, $post_id ) {
	$empty = array( 'links' => array(), 'dom' => null, 'root' => null, 'nodes' => array() );
	$max   = ct_autolink_max();
	if ( $max < 1 || '' === trim( (string) $html ) || false === stripos( $html, '<p' ) ) {
		return $empty;
	}
	$map = ct_link_map();
	if ( empty( $map ) ) {
		return $empty;
	}
	$loaded = ct_autolink_load_fragment( $html );
	if ( ! $loaded ) {
		return $empty;
	}
	list( $dom, $root ) = $loaded;

	// URLs already linked in the body (editorial links win; one link per target, full stop).
	$self_key  = $post_id ? ct_url_key( get_permalink( $post_id ) ) : '';
	$used_urls = array();
	foreach ( $root->getElementsByTagName( 'a' ) as $a ) {
		$href = (string) $a->getAttribute( 'href' );
		if ( '' !== $href && '#' !== $href[0] ) {
			$used_urls[ ct_url_key( $href ) ] = true;
		}
	}

	// Phrase list: every keyword and alias, longest first, pointing at its entry.
	$phrases = array();
	foreach ( $map as $i => $entry ) {
		if ( empty( $entry['keyword'] ) || empty( $entry['url'] ) ) {
			continue;
		}
		$ukey = ct_url_key( $entry['url'] );
		if ( ( $post_id && (int) ( $entry['post_id'] ?? 0 ) === (int) $post_id ) || ( '' !== $self_key && $ukey === $self_key ) ) {
			continue; // never link a post to itself
		}
		if ( isset( $used_urls[ $ukey ] ) ) {
			continue; // already linked by hand
		}
		$phrases[] = array( 'text' => $entry['keyword'], 'entry' => $i );
		foreach ( (array) ( $entry['aliases'] ?? array() ) as $alias ) {
			if ( '' !== $alias ) {
				$phrases[] = array( 'text' => $alias, 'entry' => $i );
			}
		}
	}
	if ( empty( $phrases ) ) {
		return $empty;
	}
	usort(
		$phrases,
		function ( $a, $b ) {
			return mb_strlen( $b['text'] ) <=> mb_strlen( $a['text'] );
		}
	);

	$selectors = ct_autolink_skip_selectors();
	$xpath     = new DOMXPath( $dom );
	$texts     = $xpath->query( './/text()', $root );
	$candidates = array();  // entry index => candidate
	$claimed    = array();  // node index => [ [start, end] … ]
	$offset     = 0;        // visible characters seen so far
	$node_list  = array();

	foreach ( $texts as $n => $text ) {
		$node_list[ $n ] = $text;
		$value           = $text->nodeValue;
		$len             = mb_strlen( $value );
		$node_start      = $offset;
		$offset         += $len;
		if ( '' === trim( $value ) || ! ct_autolink_text_node_ok( $text, $root, $selectors ) ) {
			continue;
		}
		$from = max( 0, CT_AUTOLINK_SKIP_CHARS - $node_start ); // character offset inside this node where linking may start
		if ( $from >= $len ) {
			continue;
		}
		foreach ( $phrases as $phrase ) {
			$ei = $phrase['entry'];
			if ( isset( $candidates[ $ei ] ) ) {
				continue; // this entry already found its first occurrence
			}
			$pattern = '/(?<![\p{L}\p{N}])' . preg_quote( $phrase['text'], '/' ) . '(?![\p{L}\p{N}])/iu';
			$search  = 0;
			while ( preg_match( $pattern, $value, $m, PREG_OFFSET_CAPTURE, $search ) ) {
				$byte_pos = $m[0][1];
				$char_pos = mb_strlen( substr( $value, 0, $byte_pos ) );
				$char_len = mb_strlen( $m[0][0] );
				$search   = $byte_pos + strlen( $m[0][0] );
				if ( $char_pos < $from ) {
					continue; // inside the first 150 characters
				}
				$overlap = false;
				foreach ( $claimed[ $n ] ?? array() as $range ) {
					if ( $char_pos < $range[1] && $char_pos + $char_len > $range[0] ) {
						$overlap = true;
						break;
					}
				}
				if ( $overlap ) {
					continue;
				}
				$claimed[ $n ][] = array( $char_pos, $char_pos + $char_len );
				$entry           = $map[ $ei ];
				$candidates[ $ei ] = array(
					'node'     => $n,
					'offset'   => $char_pos,
					'length'   => $char_len,
					'position' => $node_start + $char_pos,
					'link'     => array(
						'keyword'     => (string) $entry['keyword'],
						'matched'     => $m[0][0],
						'url'         => (string) $entry['url'],
						'title'       => (string) ( $entry['title'] ?? '' ),
						'post_id'     => (int) ( $entry['post_id'] ?? 0 ),
						'priority'    => (int) ( $entry['priority'] ?? 1 ),
						'pillar'      => (string) ( $entry['pillar'] ?? '' ),
						'destination' => (string) ( $entry['destination'] ?? '' ),
						'context'     => trim( mb_substr( $value, max( 0, $char_pos - 40 ), $char_len + 80 ) ),
						'position'    => $node_start + $char_pos,
					),
				);
				break;
			}
		}
	}
	if ( empty( $candidates ) ) {
		return $empty;
	}

	// Slots go to the commercial entries first, then by position in the text.
	$list = array_values( $candidates );
	usort(
		$list,
		function ( $a, $b ) {
			if ( $a['link']['priority'] !== $b['link']['priority'] ) {
				return $b['link']['priority'] <=> $a['link']['priority'];
			}
			return $a['position'] <=> $b['position'];
		}
	);
	$list = array_slice( $list, 0, $max );
	usort(
		$list,
		function ( $a, $b ) {
			return $a['position'] <=> $b['position'];
		}
	);

	$nodes = array();
	$links = array();
	foreach ( $list as $c ) {
		$nodes[ $c['node'] ][] = array( $c['offset'], $c['length'], $c['link'] );
		$links[]               = $c['link'];
	}
	foreach ( $nodes as $n => &$ranges ) {
		usort(
			$ranges,
			function ( $a, $b ) {
				return $a[0] <=> $b[0];
			}
		);
		$ranges = array( 'node' => $node_list[ $n ], 'ranges' => $ranges );
	}
	unset( $ranges );

	return array( 'links' => $links, 'dom' => $dom, 'root' => $root, 'nodes' => $nodes );
}

/** Apply a plan: split the text nodes and wrap the matches in <a data-ct-autolink="1">. Returns HTML. */
function ct_autolink_apply( $plan ) {
	$dom = $plan['dom'];
	foreach ( $plan['nodes'] as $item ) {
		$text   = $item['node'];
		$value  = $text->nodeValue;
		$parent = $text->parentNode;
		$cursor = 0;
		$frag   = $dom->createDocumentFragment();
		foreach ( $item['ranges'] as $range ) {
			list( $start, $len, $link ) = $range;
			if ( $start > $cursor ) {
				$frag->appendChild( $dom->createTextNode( mb_substr( $value, $cursor, $start - $cursor ) ) );
			}
			$a = $dom->createElement( 'a' );
			$a->setAttribute( 'href', $link['url'] );
			if ( '' !== $link['title'] ) {
				$a->setAttribute( 'title', $link['title'] );
			}
			$a->setAttribute( 'data-ct-autolink', '1' );
			$a->appendChild( $dom->createTextNode( mb_substr( $value, $start, $len ) ) );
			$frag->appendChild( $a );
			$cursor = $start + $len;
		}
		if ( $cursor < mb_strlen( $value ) ) {
			$frag->appendChild( $dom->createTextNode( mb_substr( $value, $cursor ) ) );
		}
		$parent->replaceChild( $frag, $text );
	}
	return ct_autolink_save_fragment( $dom, $plan['root'] );
}

/** Is autolinking off for this post (meta `ct_autolink_off` = 1/true/yes)? */
function ct_autolink_is_off( $post_id ) {
	$meta = get_post_meta( $post_id, 'ct_autolink_off', true );
	$off  = in_array( strtolower( trim( (string) $meta ) ), array( '1', 'true', 'yes', 'on' ), true );
	return (bool) apply_filters( 'ct_autolink_off', $off, $post_id );
}

/** the_content, priority 20. */
function ct_autolink_the_content( $content ) {
	if ( ! ct_is_main_singular_content() ) {
		return $content;
	}
	$post_id = get_the_ID();
	if ( ! in_array( get_post_type( $post_id ), ct_site_post_types(), true ) || ct_autolink_is_off( $post_id ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'data-ct-autolink=' ) ) {
		return $content; // already done (the filter ran twice)
	}
	$plan = ct_autolink_plan( $content, $post_id );
	if ( empty( $plan['links'] ) ) {
		return $content;
	}
	$out = ct_autolink_apply( $plan );
	return '' !== $out ? $out : $content;
}
add_filter( 'the_content', 'ct_autolink_the_content', 20 );

/**
 * Dry run for one post: render its content with every the_content filter except ours, plan the links.
 *
 * @return array|WP_Error
 */
function ct_autolink_report( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, ct_site_post_types(), true ) ) {
		return new WP_Error( 'ct_not_found', 'No such piece.', array( 'status' => 404 ) );
	}
	global $wp_query;
	$prev_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	setup_postdata( $post );

	$had = remove_filter( 'the_content', 'ct_autolink_the_content', 20 );
	$had_pair   = function_exists( 'ct_pair_the_content' ) ? remove_filter( 'the_content', 'ct_pair_the_content', 25 ) : false;
	$had_author = function_exists( 'ct_author_the_content' ) ? remove_filter( 'the_content', 'ct_author_the_content', 26 ) : false;
	$html = apply_filters( 'the_content', $post->post_content );
	if ( $had ) {
		add_filter( 'the_content', 'ct_autolink_the_content', 20 );
	}
	if ( $had_pair ) {
		add_filter( 'the_content', 'ct_pair_the_content', 25 );
	}
	if ( $had_author ) {
		add_filter( 'the_content', 'ct_author_the_content', 26 );
	}

	$plan = ct_autolink_plan( $html, $post->ID );
	wp_reset_postdata();
	if ( $prev_post ) {
		$GLOBALS['post'] = $prev_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}

	$links = array();
	foreach ( $plan['links'] as $l ) {
		unset( $l['position'] );
		$links[] = $l;
	}
	return array(
		'post'        => $post->ID,
		'post_type'   => $post->post_type,
		'permalink'   => get_permalink( $post ),
		'off'         => ct_autolink_is_off( $post->ID ),
		'max'         => ct_autolink_max(),
		'map_version' => ct_link_map_version(),
		'map_entries' => count( ct_link_map() ),
		'count'       => count( $links ),
		'links'       => $links,
		'note'        => ct_autolink_is_off( $post->ID ) ? 'ct_autolink_off is set on this post: nothing is linked on the page; the list shows what would be.' : '',
	);
}

/* -------------------------------------------------------------------------
 * REST
 * ---------------------------------------------------------------------- */

function ct_autolink_rest_permission() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return new WP_Error( 'ct_forbidden', 'You need the edit_others_posts capability (Editor or Administrator, Application Password).', array( 'status' => is_user_logged_in() ? 403 : 401 ) );
	}
	return true;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'coco-tran/v1',
			'/link-map',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => 'ct_autolink_rest_permission',
					'callback'            => function () {
						$map = ct_link_map();
						return rest_ensure_response(
							array(
								'version' => ct_link_map_version(),
								'count'   => count( $map ),
								'max'     => ct_autolink_max(),
								'entries' => $map,
							)
						);
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => 'ct_autolink_rest_permission',
					'callback'            => function ( WP_REST_Request $request ) {
						$body = $request->get_json_params();
						if ( null === $body ) {
							$body = json_decode( (string) $request->get_body(), true );
						}
						if ( is_array( $body ) && isset( $body['entries'] ) ) {
							$body = $body['entries'];
						} elseif ( is_array( $body ) && isset( $body['map'] ) ) {
							$body = $body['map'];
						}
						if ( ! is_array( $body ) ) {
							return new WP_Error( 'ct_bad_map', 'Send a JSON array of entries, or {"entries":[…]}.', array( 'status' => 400 ) );
						}
						$result = ct_autolink_validate_map( $body );
						if ( empty( $result['entries'] ) ) {
							return new WP_Error( 'ct_empty_map', 'No valid entries; nothing stored.', array( 'status' => 400, 'rejected' => $result['rejected'] ) );
						}
						$version = ct_link_map_store( $result['entries'] );
						return rest_ensure_response(
							array(
								'stored'   => count( $result['entries'] ),
								'rejected' => $result['rejected'],
								'version'  => $version,
							)
						);
					},
				),
			)
		);

		register_rest_route(
			'coco-tran/v1',
			'/autolink-report',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => 'ct_autolink_rest_permission',
				'args'                => array(
					'post' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return is_numeric( $v ) && (int) $v > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => function ( WP_REST_Request $request ) {
					$report = ct_autolink_report( (int) $request['post'] );
					return is_wp_error( $report ) ? $report : rest_ensure_response( $report );
				},
			)
		);
	}
);

/* -------------------------------------------------------------------------
 * Settings (Settings → Reading → "Coco Tran snippets"), also writable over /wp/v2/settings
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		$fields = array(
			'ct_autolink_max'     => array( 'integer', 'Max autolinks per piece (default 6).', 'absint', 6 ),
			'ct_author_bio'       => array( 'string', 'One-sentence author bio shown under every piece.', 'sanitize_text_field', '' ),
			'ct_social_instagram' => array( 'string', 'Instagram profile URL.', 'esc_url_raw', '' ),
			'ct_social_youtube'   => array( 'string', 'YouTube channel URL.', 'esc_url_raw', '' ),
		);
		foreach ( $fields as $name => $f ) {
			register_setting(
				'reading',
				$name,
				array(
					'type'              => $f[0],
					'description'       => $f[1],
					'sanitize_callback' => $f[2],
					'show_in_rest'      => true,
					'default'           => $f[3],
				)
			);
		}
	}
);

add_action(
	'admin_init',
	function () {
		add_settings_section( 'ct_snippets', 'Coco Tran snippets', function () {
			echo '<p>Autolink, the “Pair it with” block and the author line. The link map itself is posted by the worker to <code>/wp-json/coco-tran/v1/link-map</code>.</p>';
		}, 'reading' );
		$fields = array(
			'ct_autolink_max'     => array( 'Max autolinks per piece', 'number' ),
			'ct_author_bio'       => array( 'Author bio (one sentence)', 'text' ),
			'ct_social_instagram' => array( 'Instagram URL', 'url' ),
			'ct_social_youtube'   => array( 'YouTube URL', 'url' ),
		);
		foreach ( $fields as $name => $f ) {
			add_settings_field(
				$name,
				$f[0],
				function () use ( $name, $f ) {
					printf(
						'<input type="%s" name="%s" id="%s" value="%s" class="%s" />',
						esc_attr( $f[1] ),
						esc_attr( $name ),
						esc_attr( $name ),
						esc_attr( (string) get_option( $name, 'ct_autolink_max' === $name ? 6 : '' ) ),
						'number' === $f[1] ? 'small-text' : 'large-text'
					);
				},
				'reading',
				'ct_snippets'
			);
		}
	}
);
