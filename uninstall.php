<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes all snippets and their settings.
 *
 * @package Easy_WP_Snippets_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ewpsm_snippets = get_posts( array(
	'post_type'      => 'ewpsm_snippet',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
) );

foreach ( $ewpsm_snippets as $ewpsm_id ) {
	wp_delete_post( $ewpsm_id, true ); // Also deletes post meta.
}
