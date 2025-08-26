<?php
/**
 * Uninstall script voor DJ Song Request Manager
 *
 * Wordt uitgevoerd wanneer de plugin definitief wordt verwijderd in WP Admin.
 * 
 * Functie:
 * - Checkt of de gebruiker in de instellingen heeft aangegeven dat data verwijderd mag worden.
 * - Zo ja: verwijder alle database-tabellen, opties en pagina’s.
 * - Zo nee: behoud data zodat de plugin later opnieuw geactiveerd kan worden.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Ophalen of gebruiker “Verwijder data bij uninstall” heeft aangevinkt
$delete_data = get_option( 'dj_srm_delete_on_uninstall', '0' );

if ( $delete_data !== '1' ) {
    // Gebruiker wil data behouden → stop hier
    return;
}

global $wpdb;

// ===========================================================
// Stap 1: Database tabellen verwijderen
// ===========================================================
$tables = [
    "{$wpdb->prefix}dj_srm_events",
    "{$wpdb->prefix}dj_srm_requests",
    "{$wpdb->prefix}dj_srm_polls",
    "{$wpdb->prefix}dj_srm_awards",
    "{$wpdb->prefix}dj_srm_offers",
    "{$wpdb->prefix}dj_srm_guestbook",
    "{$wpdb->prefix}dj_srm_afterparty",
    "{$wpdb->prefix}dj_srm_obs",
    "{$wpdb->prefix}dj_srm_security",
    "{$wpdb->prefix}dj_srm_logs",
    "{$wpdb->prefix}dj_srm_ip_blocklist"
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS $table" );
}

// ===========================================================
// Stap 2: Opties verwijderen
// ===========================================================
$options = [
    'dj_srm_settings',
    'dj_srm_version',
    'dj_srm_delete_on_uninstall',
    'dj_srm_email_sender',
    'dj_srm_spotify_api',
    'dj_srm_openai_api',
    'dj_srm_enable_logging'
];
foreach ( $options as $option ) {
    delete_option( $option );
}

// ===========================================================
// Stap 3: Aangemaakte pagina’s verwijderen
// ===========================================================
$slugs = [
    'dj-events',
    'dj-requests',
    'dj-playlist',
    'dj-polls',
    'dj-awards',
    'dj-guestbook',
    'dj-afterparty',
    'dj-portaal',
    'dj-srm-dashboard'
];

foreach ( $slugs as $slug ) {
    $page = get_page_by_path( $slug );
    if ( $page ) {
        wp_delete_post( $page->ID, true ); // true = force delete (geen prullenbak)
    }
}

// ===========================================================
// Einde uninstall
// ===========================================================
