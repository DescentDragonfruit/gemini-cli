<?php
/**
 * Uninstall: remove options. Generated WebP/AVIF files, /originals/ backups and the _ccpc_*
 * attachment meta are left in place on purpose (deleting them would silently change live images).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ccpc_settings' );
delete_option( 'ccpc_redirects' );
