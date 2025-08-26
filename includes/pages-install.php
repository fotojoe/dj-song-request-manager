<?php
/**
 * Pagina-installatie voor DJ Song Request Manager
 * Maakt of overschrijft benodigde pagina's.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hulpfunctie om een pagina te maken of bij te werken.
 */
function dj_srm_create_or_update_page( $title, $slug, $shortcode ) {
    $page = get_page_by_path( $slug );

    $page_data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $shortcode,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ];

    if ( $page ) {
        // Bijwerken
        $page_data['ID'] = $page->ID;
        wp_update_post( $page_data );
    } else {
        // Nieuw aanmaken
        wp_insert_post( $page_data );
    }
}

/**
 * Hoofdfunctie: maakt alle benodigde pagina's aan.
 */
function dj_srm_create_pages() {
    // Events pagina
    dj_srm_create_or_update_page( 'DJ Events', 'dj-events', '[dj_events]' );

    // Requestformulier
    dj_srm_create_or_update_page( 'Verzoeknummer aanvragen', 'dj-requests', '[dj_request_form]' );

    // Playlist pagina
    dj_srm_create_or_update_page( 'Playlist', 'dj-playlist', '[dj_playlist]' );

    // Polls pagina
    dj_srm_create_or_update_page( 'DJ Polls', 'dj-polls', '[dj_polls]' );

    // Awards pagina
    dj_srm_create_or_update_page( 'DJ Awards', 'dj-awards', '[dj_awards]' );

    // Guestbook pagina
    dj_srm_create_or_update_page( 'Gastenboek', 'dj-guestbook', '[dj_guestbook]' );

    // Afterparty pagina
    dj_srm_create_or_update_page( 'Afterparty', 'dj-afterparty', '[dj_afterparty]' );

    // ✅ DJ Portaal
    dj_srm_create_or_update_page( 'DJ Portaal', 'dj-portaal', '[dj_portal]' );

    // ✅ Admin Dashboard (frontend versie – admin zelf heeft eigen submenu)
    dj_srm_create_or_update_page( 'DJ SRM Dashboard', 'dj-srm-dashboard', '[dj_srm_dashboard]' );
}
