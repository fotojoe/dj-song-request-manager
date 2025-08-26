<?php
/**
 * General helper functions for the DJ Song Request Manager plugin.
 *
 * Deze file bevat algemene functies die door meerdere modules gebruikt kunnen worden,
 * zoals logging en IP-blocking. Door ze centraal te definiëren kunnen we eenvoudig
 * veiligere handelingen uitvoeren zonder code duplicatie.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logt een actie in de dj_srm_logs tabel.
 *
 * @param string $action  Een korte omschrijving van de actie (bijv. 'request_submitted').
 * @param string $user    De naam of ID van de gebruiker (optioneel).
 * @param string $ip      Het IP-adres van de client (optioneel). Indien leeg wordt automatisch het huidige IP gebruikt.
 */
function dj_srm_log_action( $action, $user = '', $ip = '' ) {
    global $wpdb;
    $table = $wpdb->prefix . 'dj_srm_logs';
    if ( ! $ip ) {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
    }
    $wpdb->insert( $table, [
        'action' => sanitize_text_field( $action ),
        'user'   => sanitize_text_field( $user ),
        'ip'     => sanitize_text_field( $ip ),
    ] );
}

/**
 * Controleert of een IP-adres op de blocklist staat.
 *
 * @param string $ip Het IP-adres van de client. Indien leeg wordt huidige IP gebruikt.
 * @return bool True wanneer geblokkeerd, anders false.
 */
function dj_srm_is_ip_blocked( $ip = '' ) {
    global $wpdb;
    if ( ! $ip ) {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
    }
    $table = $wpdb->prefix . 'dj_srm_ip_blocklist';
    $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE ip=%s", $ip ) );
    return $count > 0;
}

/**
 * Eenvoudige helper om een HTML-sjabloon in te laden voor e-mail.
 * Geeft de inhoud van het bestand terug en vervangt tokens door waarden.
 *
 * @param string $template_path Relatief pad binnen de plugin, bijvoorbeeld 'mail/request-received.php'.
 * @param array  $data          Associatieve array met sleutels/waarden voor tokens in de template.
 * @return string
 */
function dj_srm_get_email_template( $template_path, array $data = [] ) {
    $full_path = DJ_SRM_PLUGIN_DIR . trim( $template_path, '/' );
    if ( ! file_exists( $full_path ) ) {
        return '';
    }
    ob_start();
    include $full_path;
    $contents = ob_get_clean();
    // Vervang tokens {{key}} met waarden uit $data
    foreach ( $data as $key => $value ) {
        $contents = str_replace( '{{' . $key . '}}', $value, $contents );
    }
    return $contents;
}
