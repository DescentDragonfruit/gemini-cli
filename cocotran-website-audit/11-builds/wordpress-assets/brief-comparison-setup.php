<?php
/**
 * Coco Tran — Brief "The Comparison" one-shot setup (idempotent, no deletions).
 *
 * Run ONE of:
 *   wp eval-file brief-comparison-setup.php                 (from the WordPress root, or add --path=/path/to/wp)
 *   Code Snippets → Add New → paste → Run once (PHP snippet, "Only run once")
 *   php brief-comparison-setup.php                          (file placed beside wp-load.php)
 *
 * What it does, each step skipped when already in place:
 *   1. brief_format term "The Comparison" (slug the-comparison) on the Brief CPT.
 *   2. ACF field group group_ctt_comparison_brief — imported from acf-comparison-brief.json when the file sits
 *      beside this script (or in wp-content/uploads/), otherwise from the copy embedded at the bottom of this file.
 *   3. Destination terms: "Bora Bora" under French Polynesia (under Oceania) and "Maldives" under Asia,
 *      in the hierarchical destination taxonomy (slug `destination`, REST base /wp/v2/destinations; falls back to `destinations`).
 *   4. Prints every ID it found or created. Nothing is deleted or overwritten.
 *
 * Afterwards: the Kadence Element (kadence-element-brief-comparison.html) is pasted by hand — see brief-comparison-SETUP.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Direct `php brief-comparison-setup.php` run: find wp-load.php beside or above this file.
	$ct_dir = __DIR__;
	for ( $i = 0; $i < 6; $i++ ) {
		if ( file_exists( $ct_dir . '/wp-load.php' ) ) {
			require_once $ct_dir . '/wp-load.php';
			break;
		}
		$ct_dir = dirname( $ct_dir );
	}
	if ( ! defined( 'ABSPATH' ) ) {
		fwrite( STDERR, "wp-load.php not found. Run with wp eval-file, or place this file beside wp-load.php.\n" );
		exit( 1 );
	}
}

/** Print a line to WP-CLI, the terminal or the browser. */
function ctt_cmp_say( $msg ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $msg );
	} elseif ( php_sapi_name() === 'cli' ) {
		echo $msg, "\n";
	} else {
		echo esc_html( $msg ), "<br>\n";
	}
}

/** Find a term by name in a taxonomy, optionally under a parent; returns WP_Term|null. Never creates. */
function ctt_cmp_find_term( $name, $tax, $parent = null ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'name'       => $name,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		// Fall back to slug match (handles "Bora Bora" vs "bora-bora").
		$by_slug = get_term_by( 'slug', sanitize_title( $name ), $tax );
		return $by_slug instanceof WP_Term ? $by_slug : null;
	}
	if ( null !== $parent ) {
		foreach ( $terms as $t ) {
			if ( (int) $t->parent === (int) $parent ) {
				return $t;
			}
		}
	}
	return $terms[0];
}

/** Get-or-create a term; returns array( WP_Term|null, 'found'|'created'|'error', message ). */
function ctt_cmp_ensure_term( $name, $tax, $parent = 0, $slug = '' ) {
	$existing = ctt_cmp_find_term( $name, $tax, $parent ? $parent : null );
	if ( $existing ) {
		return array( $existing, 'found', '' );
	}
	$args = array( 'parent' => (int) $parent );
	if ( $slug ) {
		$args['slug'] = $slug;
	}
	$r = wp_insert_term( $name, $tax, $args );
	if ( is_wp_error( $r ) ) {
		// term_exists race / same slug elsewhere: report, do not force.
		return array( null, 'error', $r->get_error_message() );
	}
	return array( get_term( $r['term_id'], $tax ), 'created', '' );
}

$ctt_report = array();

// ---------------------------------------------------------------------------------------------------------------------
// 1. brief_format term "The Comparison"
// ---------------------------------------------------------------------------------------------------------------------
ctt_cmp_say( '== 1. brief_format term ==' );
if ( ! post_type_exists( 'brief' ) ) {
	ctt_cmp_say( 'WARNING: post type `brief` is not registered on this site (is ACF / the CPT plugin active?). Continuing.' );
}
if ( ! taxonomy_exists( 'brief_format' ) ) {
	ctt_cmp_say( 'WARNING: taxonomy `brief_format` is not registered — the term cannot be created from here. Add it in WP admin → Briefs → Formats once the taxonomy loads (name "The Comparison", slug "the-comparison").' );
	$ctt_report['brief_format_term'] = 'SKIPPED (taxonomy missing)';
} else {
	list( $t, $how, $err ) = ctt_cmp_ensure_term( 'The Comparison', 'brief_format', 0, 'the-comparison' );
	if ( $t ) {
		ctt_cmp_say( sprintf( '%s brief_format "The Comparison" → term_id %d (slug %s)', 'created' === $how ? 'Created' : 'Found', $t->term_id, $t->slug ) );
		$ctt_report['brief_format_term'] = $t->term_id;
	} else {
		ctt_cmp_say( 'ERROR creating brief_format term: ' . $err );
		$ctt_report['brief_format_term'] = 'ERROR';
	}
}

// ---------------------------------------------------------------------------------------------------------------------
// 2. ACF field group
// ---------------------------------------------------------------------------------------------------------------------
ctt_cmp_say( '== 2. ACF field group group_ctt_comparison_brief ==' );
$ctt_group_key = 'group_ctt_comparison_brief';
if ( ! function_exists( 'acf_get_field_group' ) ) {
	ctt_cmp_say( 'WARNING: ACF is not active — field group not imported. Import acf-comparison-brief.json via ACF → Tools → Import once ACF is on.' );
	$ctt_report['acf_group'] = 'SKIPPED (ACF missing)';
} else {
	$existing_group = acf_get_field_group( $ctt_group_key );
	if ( $existing_group ) {
		$where = ! empty( $existing_group['ID'] ) ? 'database post ' . $existing_group['ID'] : 'local/PHP registration';
		ctt_cmp_say( 'Found field group already registered (' . $where . ') — not re-imported.' );
		$ctt_report['acf_group'] = 'found';
	} else {
		// Locate the JSON: beside this file, then uploads, then the embedded copy.
		$json_text  = '';
		$candidates = array(
			__DIR__ . '/acf-comparison-brief.json',
			trailingslashit( wp_upload_dir()['basedir'] ) . 'acf-comparison-brief.json',
			ABSPATH . 'acf-comparison-brief.json',
		);
		$json_from = 'embedded copy';
		foreach ( $candidates as $c ) {
			if ( is_readable( $c ) ) {
				$json_text = (string) file_get_contents( $c );
				$json_from = $c;
				break;
			}
		}
		if ( '' === $json_text ) {
			$json_text = ctt_cmp_embedded_group_json();
		}
		$decoded = json_decode( $json_text, true );
		if ( ! is_array( $decoded ) ) {
			ctt_cmp_say( 'ERROR: could not decode the field-group JSON (' . $json_from . ').' );
			$ctt_report['acf_group'] = 'ERROR';
		} else {
			// The export is a list of groups; take the one with our key (or the first).
			$group = null;
			if ( isset( $decoded['key'] ) ) {
				$group = $decoded;
			} else {
				foreach ( $decoded as $g ) {
					if ( isset( $g['key'] ) && $g['key'] === $ctt_group_key ) {
						$group = $g;
						break;
					}
				}
				if ( ! $group && isset( $decoded[0]['key'] ) ) {
					$group = $decoded[0];
				}
			}
			if ( ! $group ) {
				ctt_cmp_say( 'ERROR: no field group found in JSON (' . $json_from . ').' );
				$ctt_report['acf_group'] = 'ERROR';
			} elseif ( function_exists( 'acf_import_field_group' ) ) {
				// Same path as ACF → Tools → Import: attach an existing post ID if one exists so it updates rather than duplicates.
				if ( function_exists( 'acf_get_field_group_post' ) ) {
					$post = acf_get_field_group_post( $group['key'] );
					if ( $post ) {
						$group['ID'] = $post->ID;
					}
				}
				$imported = acf_import_field_group( $group );
				if ( is_array( $imported ) && ! empty( $imported['ID'] ) ) {
					ctt_cmp_say( sprintf( 'Imported field group "%s" from %s → post ID %d, %d fields.', $imported['title'], $json_from, $imported['ID'], count( $group['fields'] ) ) );
					$ctt_report['acf_group'] = $imported['ID'];
				} else {
					ctt_cmp_say( 'ERROR: acf_import_field_group() returned no ID.' );
					$ctt_report['acf_group'] = 'ERROR';
				}
			} elseif ( function_exists( 'acf_add_local_field_group' ) ) {
				acf_add_local_field_group( $group );
				ctt_cmp_say( 'WARNING: acf_import_field_group() is not available on this ACF version; registered the group LOCALLY for this request only. That does NOT persist — import acf-comparison-brief.json via ACF → Tools → Import, or upgrade ACF (≥ 5.7.10).' );
				$ctt_report['acf_group'] = 'LOCAL ONLY (not persisted)';
			} else {
				ctt_cmp_say( 'ERROR: neither acf_import_field_group() nor acf_add_local_field_group() exists.' );
				$ctt_report['acf_group'] = 'ERROR';
			}
		}
	}
}

// ---------------------------------------------------------------------------------------------------------------------
// 3. Destination terms
// ---------------------------------------------------------------------------------------------------------------------
ctt_cmp_say( '== 3. Destination terms ==' );
$ctt_tax = '';
foreach ( array( 'destination', 'destinations' ) as $candidate ) {
	if ( taxonomy_exists( $candidate ) ) {
		$ctt_tax = $candidate;
		break;
	}
}
if ( '' === $ctt_tax ) {
	ctt_cmp_say( 'WARNING: neither `destination` nor `destinations` taxonomy is registered — destination terms skipped. (The REST base /wp/v2/destinations maps to slug `destination`; check Taxonomies in ACF.)' );
	$ctt_report['destinations'] = 'SKIPPED (taxonomy missing)';
} else {
	$tax_obj = get_taxonomy( $ctt_tax );
	ctt_cmp_say( sprintf( 'Using taxonomy `%s` (REST base %s, hierarchical: %s).', $ctt_tax, isset( $tax_obj->rest_base ) && $tax_obj->rest_base ? $tax_obj->rest_base : $ctt_tax, ! empty( $tax_obj->hierarchical ) ? 'yes' : 'NO — check this' ) );

	// Oceania › French Polynesia › Bora Bora
	$oceania = ctt_cmp_find_term( 'Oceania', $ctt_tax );
	if ( ! $oceania ) {
		list( $oceania, $how, $err ) = ctt_cmp_ensure_term( 'Oceania', $ctt_tax, 0 );
		ctt_cmp_say( $oceania ? 'Created continent "Oceania" → ' . $oceania->term_id . ' (expected an existing term ~632; none found by name)' : 'ERROR creating Oceania: ' . $err );
	} else {
		ctt_cmp_say( 'Found "Oceania" → ' . $oceania->term_id . ( 632 !== (int) $oceania->term_id ? ' (note: the audit expected 632)' : '' ) );
	}
	$fp = null;
	if ( $oceania ) {
		list( $fp, $how, $err ) = ctt_cmp_ensure_term( 'French Polynesia', $ctt_tax, $oceania->term_id );
		if ( $fp ) {
			ctt_cmp_say( sprintf( '%s "French Polynesia" → %d (parent %d)%s', 'created' === $how ? 'Created' : 'Found', $fp->term_id, $fp->parent, 654 !== (int) $fp->term_id ? ' (note: the audit expected 654)' : '' ) );
			if ( (int) $fp->parent !== (int) $oceania->term_id ) {
				ctt_cmp_say( '  note: existing French Polynesia term is not under Oceania; left as is.' );
			}
		} else {
			ctt_cmp_say( 'ERROR French Polynesia: ' . $err );
		}
	}
	if ( $fp ) {
		list( $bb, $how, $err ) = ctt_cmp_ensure_term( 'Bora Bora', $ctt_tax, $fp->term_id );
		ctt_cmp_say( $bb ? sprintf( '%s "Bora Bora" → %d (parent %d)', 'created' === $how ? 'Created' : 'Found', $bb->term_id, $bb->parent ) : 'ERROR Bora Bora: ' . $err );
		$ctt_report['bora_bora'] = $bb ? $bb->term_id : 'ERROR';
	}

	// Asia › Maldives
	$asia = ctt_cmp_find_term( 'Asia', $ctt_tax );
	if ( ! $asia ) {
		list( $asia, $how, $err ) = ctt_cmp_ensure_term( 'Asia', $ctt_tax, 0 );
		ctt_cmp_say( $asia ? 'Created continent "Asia" → ' . $asia->term_id . ' (expected an existing term ~628; none found by name)' : 'ERROR creating Asia: ' . $err );
	} else {
		ctt_cmp_say( 'Found "Asia" → ' . $asia->term_id . ( 628 !== (int) $asia->term_id ? ' (note: the audit expected 628)' : '' ) );
	}
	if ( $asia ) {
		list( $mv, $how, $err ) = ctt_cmp_ensure_term( 'Maldives', $ctt_tax, $asia->term_id );
		ctt_cmp_say( $mv ? sprintf( '%s "Maldives" → %d (parent %d)', 'created' === $how ? 'Created' : 'Found', $mv->term_id, $mv->parent ) : 'ERROR Maldives: ' . $err );
		$ctt_report['maldives'] = $mv ? $mv->term_id : 'ERROR';
	}
	$ctt_report['oceania']          = $oceania ? $oceania->term_id : 'ERROR';
	$ctt_report['french_polynesia'] = $fp ? $fp->term_id : 'ERROR';
	$ctt_report['asia']             = $asia ? $asia->term_id : 'ERROR';
	$ctt_report['taxonomy']         = $ctt_tax;
}

// ---------------------------------------------------------------------------------------------------------------------
// 4. Summary
// ---------------------------------------------------------------------------------------------------------------------
ctt_cmp_say( '== Summary (paste into the job\'s worker_notes) ==' );
ctt_cmp_say( wp_json_encode( $ctt_report ) );
ctt_cmp_say( 'Next: paste kadence-element-brief-comparison.html into a new Kadence Element (Single Content: Before, priority 10, Show on Singular: Briefs + brief_format = The Comparison), then Delete all Cache and purge Cloudflare. See brief-comparison-SETUP.md.' );

/**
 * Embedded copy of acf-comparison-brief.json (used only when the file is not found beside this script).
 * Keep in sync with 11-builds/wordpress-assets/acf-comparison-brief.json.
 */
function ctt_cmp_embedded_group_json() {
	return <<<'ACFJSON'
[{"key":"group_ctt_comparison_brief","title":"Comparison (Brief · destination vs destination)","fields":[{"key":"field_ctt_cmp_short","label":"Short answer","name":"comparison_short_answer","type":"textarea","instructions":"2–3 sentences that stand alone. The reader could stop here.","required":0,"show_in_rest":1,"rows":3,"new_lines":""},{"key":"field_ctt_cmp_aname","label":"Side A","name":"side_a_name","type":"text","instructions":"","required":0,"show_in_rest":1},{"key":"field_ctt_cmp_bname","label":"Side B","name":"side_b_name","type":"text","instructions":"","required":0,"show_in_rest":1},{"key":"field_ctt_cmp_bfa","label":"Choose A if","name":"best_for_a","type":"text","instructions":"","required":0,"show_in_rest":1},{"key":"field_ctt_cmp_bfb","label":"Choose B if","name":"best_for_b","type":"text","instructions":"","required":0,"show_in_rest":1},{"key":"field_ctt_cmp_setup","label":"The set-up","name":"comparison_the_set_up","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_loc","label":"The location","name":"comparison_the_location","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_rooms","label":"The stays","name":"comparison_the_stays","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_food","label":"The food","name":"comparison_the_food","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_days","label":"The days","name":"comparison_the_days","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_price","label":"The price difference","name":"comparison_the_price_difference","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_who","label":"Who books which","name":"comparison_who_books_which","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_verdict","label":"The verdict","name":"comparison_the_verdict","type":"wysiwyg","instructions":"","required":0,"show_in_rest":1,"tabs":"all","toolbar":"basic","media_upload":0},{"key":"field_ctt_cmp_book","label":"Booking note","name":"comparison_booking_note","type":"text","instructions":"One line for the booking box: perks that apply, the room to ask for.","required":0,"show_in_rest":1},{"key":"field_ctt_cmp_img1","label":"Image 1 (hero)","name":"comparison_image_1","type":"image","instructions":"","required":0,"show_in_rest":1,"return_format":"id","preview_size":"medium","library":"all"},{"key":"field_ctt_cmp_img2","label":"Image 2","name":"comparison_image_2","type":"image","instructions":"","required":0,"show_in_rest":1,"return_format":"id","preview_size":"medium","library":"all"},{"key":"field_ctt_cmp_img3","label":"Image 3","name":"comparison_image_3","type":"image","instructions":"","required":0,"show_in_rest":1,"return_format":"id","preview_size":"medium","library":"all"},{"key":"field_ctt_cmp_img4","label":"Image 4","name":"comparison_image_4","type":"image","instructions":"","required":0,"show_in_rest":1,"return_format":"id","preview_size":"medium","library":"all"},{"key":"field_ctt_cmp_img5","label":"Image 5","name":"comparison_image_5","type":"image","instructions":"","required":0,"show_in_rest":1,"return_format":"id","preview_size":"medium","library":"all"}],"location":[[{"param":"post_type","operator":"==","value":"brief"},{"param":"post_taxonomy","operator":"==","value":"brief_format:the-comparison"}]],"menu_order":5,"position":"normal","style":"default","label_placement":"top","instruction_placement":"label","active":true,"show_in_rest":1,"description":"Destination-vs-destination decisions (Bora Bora vs Maldives). Hotel-vs-hotel stays on the Property CPT as The Trade-Off."}]
ACFJSON;
}
