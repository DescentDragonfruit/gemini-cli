<?php
/**
 * resolve-ids.php — fill the ID column of rank-math-title-import.csv from the URL column, and
 * optionally write the titles/descriptions/focus keywords straight into Rank Math's post meta.
 *
 * Run on the server with WP-CLI, from the WordPress root (or pass --path):
 *
 *   wp eval-file resolve-ids.php rank-math-title-import.csv
 *       → writes rank-math-title-import.resolved.csv  (same six columns, ID filled)
 *         and  rank-math-title-import.rankmath.csv    (Rank Math's own import columns)
 *         and prints any URL it could not resolve.
 *
 *   wp eval-file resolve-ids.php rank-math-title-import.csv --apply
 *       → additionally sets rank_math_title / rank_math_description / rank_math_focus_keyword on each
 *         resolved post. This is the most reliable route: it does not depend on the CSV importer's format.
 *         Add --dry-run with --apply to print what would change without writing.
 *
 * Without WP-CLI: upload this file next to wp-load.php and run
 *   php resolve-ids.php rank-math-title-import.csv [--apply]
 * (it bootstraps WordPress itself when wp-load.php is beside it).
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Plain PHP run: bootstrap WordPress from the same directory.
	if ( ! file_exists( __DIR__ . '/wp-load.php' ) ) {
		fwrite( STDERR, "Run with `wp eval-file resolve-ids.php <csv>` or place this file beside wp-load.php.\n" );
		exit( 1 );
	}
	require_once __DIR__ . '/wp-load.php';
	$args = array_slice( $argv, 1 );
} else {
	$args = isset( $args ) && is_array( $args ) ? $args : array();
}

$apply   = in_array( '--apply', $args, true );
$dry_run = in_array( '--dry-run', $args, true );
$files   = array_values( array_filter( $args, static function ( $a ) { return 0 !== strpos( $a, '--' ); } ) );
$in      = isset( $files[0] ) ? $files[0] : __DIR__ . '/rank-math-title-import.csv';
if ( ! is_readable( $in ) ) {
	fwrite( STDERR, "Cannot read $in\n" );
	exit( 1 );
}
$base         = preg_replace( '/\.csv$/i', '', $in );
$out_resolved = $base . '.resolved.csv';
$out_rankmath = $base . '.rankmath.csv';

/**
 * URL → post ID, trying harder than url_to_postid alone.
 */
function ct_resolve_url( $url, $post_type_hint ) {
	$url  = trim( $url );
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

	if ( '' === $path ) { // home
		$front = (int) get_option( 'page_on_front' );
		return $front > 0 ? $front : 0;
	}

	$id = (int) url_to_postid( $url );
	if ( $id ) {
		return $id;
	}

	// CPT singular: /property/slug/ etc.
	$parts = explode( '/', $path );
	$cpts  = array( 'property', 'guide', 'brief', 'dispatch', 'shortlist' );
	if ( count( $parts ) >= 2 && in_array( $parts[0], $cpts, true ) ) {
		$p = get_page_by_path( $parts[1], OBJECT, $parts[0] );
		if ( $p ) {
			return (int) $p->ID;
		}
	}

	// Pages (nested paths allowed) then posts by slug.
	foreach ( array_unique( array( $post_type_hint, 'page', 'post' ) ) as $pt ) {
		if ( ! $pt || ! post_type_exists( $pt ) ) {
			continue;
		}
		$p = get_page_by_path( $path, OBJECT, $pt );
		if ( $p ) {
			return (int) $p->ID;
		}
	}
	$slug = end( $parts );
	$q    = get_posts( array( 'name' => $slug, 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	return $q ? (int) $q[0] : 0;
}

$fh = fopen( $in, 'r' );
$header = fgetcsv( $fh );
if ( ! $header || ! in_array( 'url', $header, true ) ) {
	fwrite( STDERR, "Expected columns ID,post_type,title,description,focus_keyword,url\n" );
	exit( 1 );
}
$idx = array_flip( $header );

$resolved   = array();
$unresolved = array();
$rows_out   = array();

while ( ( $row = fgetcsv( $fh ) ) !== false ) {
	if ( count( $row ) < count( $header ) ) {
		$row = array_pad( $row, count( $header ), '' );
	}
	$url  = $row[ $idx['url'] ];
	$hint = isset( $idx['post_type'] ) ? $row[ $idx['post_type'] ] : '';
	$id   = ! empty( $row[ $idx['ID'] ] ) ? (int) $row[ $idx['ID'] ] : ct_resolve_url( $url, $hint );

	if ( ! $id ) {
		$unresolved[] = $url;
	} else {
		$resolved[] = $id;
		$row[ $idx['ID'] ] = $id;
		if ( isset( $idx['post_type'] ) ) {
			$row[ $idx['post_type'] ] = get_post_type( $id ); // trust WordPress over the guessed type
		}
	}
	$rows_out[] = $row;
}
fclose( $fh );

// 1. Same six columns, IDs filled.
$o = fopen( $out_resolved, 'w' );
fputcsv( $o, $header );
foreach ( $rows_out as $r ) {
	fputcsv( $o, $r );
}
fclose( $o );

// 2. Rank Math's CSV import format (Rank Math Pro → Status & Tools → Import & Export → CSV).
//    Columns match a Rank Math CSV export; unresolved rows are left out.
$o = fopen( $out_rankmath, 'w' );
fputcsv( $o, array( 'id', 'object_type', 'slug', 'seo_title', 'seo_description', 'focus_keyword' ) );
foreach ( $rows_out as $r ) {
	$id = (int) $r[ $idx['ID'] ];
	if ( ! $id ) {
		continue;
	}
	$post = get_post( $id );
	fputcsv(
		$o,
		array(
			$id,
			'post', // Rank Math object_type for any post type (posts, pages, CPTs); 'term' and 'user' are the others
			$post ? $post->post_name : '',
			$r[ $idx['title'] ],
			$r[ $idx['description'] ],
			$r[ $idx['focus_keyword'] ],
		)
	);
}
fclose( $o );

// 3. Optional direct write.
$written = 0;
if ( $apply ) {
	foreach ( $rows_out as $r ) {
		$id = (int) $r[ $idx['ID'] ];
		if ( ! $id ) {
			continue;
		}
		$changes = array(
			'rank_math_title'         => trim( $r[ $idx['title'] ] ),
			'rank_math_description'   => trim( $r[ $idx['description'] ] ),
			'rank_math_focus_keyword' => trim( $r[ $idx['focus_keyword'] ] ),
		);
		foreach ( $changes as $meta_key => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$current = (string) get_post_meta( $id, $meta_key, true );
			if ( $current === $value ) {
				continue;
			}
			if ( $dry_run ) {
				echo "[dry-run] #$id $meta_key: \"$current\" → \"$value\"\n";
			} else {
				update_post_meta( $id, $meta_key, $value );
			}
			$written++;
		}
	}
}

printf( "Resolved %d of %d rows.\n", count( $resolved ), count( $rows_out ) );
printf( "Wrote %s and %s\n", $out_resolved, $out_rankmath );
if ( $apply ) {
	printf( "%s %d meta values%s.\n", $dry_run ? 'Would update' : 'Updated', $written, $dry_run ? ' (dry run)' : '' );
}
if ( $unresolved ) {
	echo "Could not resolve (old slug already redirected, trashed, or not a post):\n  " . implode( "\n  ", $unresolved ) . "\n";
}
