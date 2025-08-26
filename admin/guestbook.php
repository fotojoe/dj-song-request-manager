<?php
/**
 * Admin pagina voor het beheren van het gastenboek.
 * Hier kan de beheerder berichten goedkeuren of verwijderen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_guestbook';

// Acties voor goedkeuren of verwijderen via URL-parameters (simpel voorbeeld)
if ( isset( $_GET['approve'] ) ) {
    $id = intval( $_GET['approve'] );
    $wpdb->update( $table, [ 'approved' => 1 ], [ 'id' => $id ] );
    echo '<div class="updated notice"><p>' . esc_html__( 'Bericht goedgekeurd.', 'dj-srm' ) . '</p></div>';
}
if ( isset( $_GET['delete'] ) ) {
    $id = intval( $_GET['delete'] );
    $wpdb->delete( $table, [ 'id' => $id ] );
    echo '<div class="updated notice"><p>' . esc_html__( 'Bericht verwijderd.', 'dj-srm' ) . '</p></div>';
}

$entries = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );

echo '<div class="wrap">';
echo '<h1>' . esc_html__( 'Gastenboek beheren', 'dj-srm' ) . '</h1>';
echo '<table class="widefat"><thead><tr>';
echo '<th>' . esc_html__( 'Naam', 'dj-srm' ) . '</th>';
echo '<th>' . esc_html__( 'Bericht', 'dj-srm' ) . '</th>';
echo '<th>' . esc_html__( 'Status', 'dj-srm' ) . '</th>';
echo '<th>' . esc_html__( 'Acties', 'dj-srm' ) . '</th>';
echo '</tr></thead><tbody>';
if ( $entries ) {
    foreach ( $entries as $entry ) {
        $status = $entry->approved ? __( 'Goedgekeurd', 'dj-srm' ) : __( 'In afwachting', 'dj-srm' );
        echo '<tr>';
        echo '<td>' . esc_html( $entry->name ) . '</td>';
        echo '<td>' . esc_html( $entry->message ) . '</td>';
        echo '<td>' . esc_html( $status ) . '</td>';
        echo '<td>';
        if ( ! $entry->approved ) {
            echo '<a href="' . esc_url( add_query_arg( [ 'approve' => $entry->id ] ) ) . '">' . esc_html__( 'Goedkeuren', 'dj-srm' ) . '</a> | ';
        }
        echo '<a href="' . esc_url( add_query_arg( [ 'delete' => $entry->id ] ) ) . '">' . esc_html__( 'Verwijderen', 'dj-srm' ) . '</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4">' . esc_html__( 'Geen berichten gevonden.', 'dj-srm' ) . '</td></tr>';
}
echo '</tbody></table></div>';

// Einde admin/guestbook.php