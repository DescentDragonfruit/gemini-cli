<?php
/**
 * Uninstall: remove options. Generated WebP/AVIF files, /originals/ backups and the _ccpc_*
 * attachment meta (including _ccpc_focal / _ccpc_crops and the ccpc-crops/ export cache) are left in place on purpose (deleting them would silently change live images).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ccpc_settings' );
delete_option( 'ccpc_redirects' );
delete_option( 'ccpc_optimise_job' );
delete_option( 'ccpc_auto_crops_log' );
wp_unschedule_hook( 'ccpc_auto_crops' );
