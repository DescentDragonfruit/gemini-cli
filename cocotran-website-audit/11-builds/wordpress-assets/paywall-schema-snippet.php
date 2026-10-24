<?php
/**
 * Plugin Name: Coco Tran — Paywall schema + dateModified
 * Description: Adds isAccessibleForFree:false + hasPart{cssSelector:".members-only"} to Rank Math's Article/Review JSON-LD on gated pieces only (singular Brief, any post carrying a pms/content-restriction-start block, or a ct_gated meta), and maps ACF `updated_on` → dateModified so the visible date and the schema date can't drift.
 * Version:     1.0.0
 * Author:      Coco Tran
 * License:     GPL-2.0-or-later
 *
 * Install: copy to wp-content/mu-plugins/paywall-schema-snippet.php. Nothing to activate.
 * Reference: https://developers.google.com/search/docs/appearance/structured-data/paywalled-content
 *            03-fixes/paywall-structured-data.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post types that carry an Answerable opening and may carry `updated_on`. */
function ct_schema_post_types() {
	return apply_filters( 'ct_schema_post_types', array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'post' ) );
}

/**
 * Is this post gated (members-only after a free lead)?
 *
 * Order of precedence:
 *  1. `ct_gated` post meta, when set, wins both ways — "1/true/yes" forces gated, "0/false/no" forces free.
 *     Use ct_gated=0 on the one free sample Route (conversion-strategy.md) so it does not get paywall markup.
 *  2. Any singular Brief (Route / Dossier) is gated — the gate lives in the Kadence Element template, not in
 *     post_content, so has_block() cannot see it; this rule covers it.
 *  3. Any post whose own content contains a Paid Member Subscriptions restriction block.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function ct_post_is_gated( $post ) {
	$meta = get_post_meta( $post->ID, 'ct_gated', true );
	if ( '' !== $meta && null !== $meta ) {
		$gated = in_array( strtolower( trim( (string) $meta ) ), array( '1', 'true', 'yes', 'on' ), true );
		return (bool) apply_filters( 'ct_post_is_gated', $gated, $post );
	}
	$gated = ( 'brief' === $post->post_type ) || has_block( 'pms/content-restriction-start', $post );
	return (bool) apply_filters( 'ct_post_is_gated', $gated, $post );
}

/**
 * ACF `updated_on` (stored as Ymd) → ISO 8601 in the site timezone, or '' when unset / implausible.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ct_updated_on_iso( $post_id ) {
	$raw = get_post_meta( $post_id, 'updated_on', true ); // ACF date_picker stores Ymd regardless of return_format.
	if ( ! is_string( $raw ) || ! preg_match( '/^\d{8}$/', $raw ) ) {
		return '';
	}
	$tz = wp_timezone();
	$dt = DateTimeImmutable::createFromFormat( 'Ymd H:i:s', $raw . ' 09:00:00', $tz );
	if ( ! $dt ) {
		return '';
	}
	$published = get_post_datetime( $post_id, 'date' );
	$now       = new DateTimeImmutable( 'now', $tz );
	if ( $dt > $now || ( $published && $dt < $published ) ) {
		return ''; // a future date or one before publication would be rejected by Google anyway
	}
	return $dt->format( DATE_ATOM );
}

/**
 * Rank Math JSON-LD filter.
 *
 * @param array $data   Entities keyed by Rank Math (e.g. 'WebPage', 'richSnippet', 'ProfilePage', ...).
 * @param mixed $jsonld Rank Math JsonLD instance (unused).
 * @return array
 */
function ct_filter_rank_math_json_ld( $data, $jsonld = null ) {
	if ( ! is_singular() || ! is_array( $data ) ) {
		return $data;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, ct_schema_post_types(), true ) ) {
		return $data;
	}

	$gated    = ct_post_is_gated( $post );
	$modified = ct_updated_on_iso( $post->ID );
	if ( ! $gated && '' === $modified ) {
		return $data;
	}

	$article_types = array( 'Article', 'NewsArticle', 'BlogPosting', 'TechArticle', 'Review', 'CreativeWork' );
	$touched       = false;

	foreach ( $data as $key => $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
			continue;
		}
		$types = (array) $entity['@type'];
		if ( ! array_intersect( $types, $article_types ) ) {
			continue;
		}
		if ( '' !== $modified ) {
			$data[ $key ]['dateModified'] = $modified;
		}
		if ( $gated ) {
			$data[ $key ]['isAccessibleForFree'] = false;
			$data[ $key ]['hasPart']             = array(
				'@type'               => 'WebPageElement',
				'isAccessibleForFree' => false,
				'cssSelector'         => '.members-only',
			);
		}
		$touched = true;
	}

	// No Article/Review entity (schema type set to "None" on this post type): put the paywall markup on the WebPage node.
	if ( $gated && ! $touched ) {
		foreach ( $data as $key => $entity ) {
			if ( is_array( $entity ) && isset( $entity['@type'] ) && in_array( 'WebPage', (array) $entity['@type'], true ) ) {
				$data[ $key ]['isAccessibleForFree'] = false;
				$data[ $key ]['hasPart']             = array(
					'@type'               => 'WebPageElement',
					'isAccessibleForFree' => false,
					'cssSelector'         => '.members-only',
				);
				if ( '' !== $modified ) {
					$data[ $key ]['dateModified'] = $modified;
				}
				break;
			}
		}
	}

	return $data;
}
add_filter( 'rank_math/json_ld', 'ct_filter_rank_math_json_ld', 99, 2 );

/**
 * Keep the visible "modified" date (theme byline, og:updated_time, sitemap lastmod) in step with updated_on.
 * Rank Math reads get_the_modified_date() for article:modified_time; this nudges it when updated_on is newer.
 */
add_filter(
	'get_the_modified_date',
	function ( $the_date, $format, $post ) {
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, ct_schema_post_types(), true ) ) {
			return $the_date;
		}
		$iso = ct_updated_on_iso( $post->ID );
		if ( '' === $iso ) {
			return $the_date;
		}
		$dt = new DateTimeImmutable( $iso );
		if ( $dt <= get_post_datetime( $post, 'modified' ) ) {
			return $the_date; // WordPress's own modified time is newer; leave it
		}
		return wp_date( $format ? $format : get_option( 'date_format' ), $dt->getTimestamp(), wp_timezone() );
	},
	10,
	3
);
