<?php
/**
 * Class: DJ_SRM_Offers
 * Doel: Offertes maken, beheren en koppelen aan events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DJ_SRM_Offers {

    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Shortcodes
        add_shortcode('dj_offers', [$this, 'render_offers']);
        add_shortcode('dj_offer', [$this, 'render_offer_single']);
        add_shortcode('dj_offer_login', [$this, 'render_offer_login']); // klant login via mail + pincode

        // AJAX handlers
        add_action('wp_ajax_dj_srm_add_offer', [$this, 'handle_new_offer']);
        add_action('wp_ajax_dj_srm_update_offer', [$this, 'update_offer']);
        add_action('wp_ajax_dj_srm_update_offer_status', [$this, 'update_offer_status']);
        add_action('wp_ajax_nopriv_dj_srm_update_offer_status', [$this, 'update_offer_status']);
        add_action('wp_ajax_dj_srm_delete_offer', [$this, 'delete_offer']); // ✅ nu in constructor
    }

    /**
     * Admin Menu
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __('Offertes', 'dj-srm'),
            __('Offertes', 'dj-srm'),
            'manage_options',
            'dj-srm-offers',
            [$this, 'admin_page']
        );
    }

    /**
     * Admin Pagina
     */
    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';

        // Offerte bekijken
        if ( isset($_GET['view']) ) {
            $offer_id = intval($_GET['view']);
            $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));
            include DJ_SRM_PLUGIN_DIR . 'templates/offer-single.php';
            return;
        }

        // Offerte bewerken
        if ( isset($_GET['edit']) ) {
            $offer_id = intval($_GET['edit']);
            $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));
            include DJ_SRM_PLUGIN_DIR . 'templates/offer-edit.php';
            return;
        }

        // Lijst
        $offers = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        echo "<div class='wrap dj-srm-dashboard'>";
        echo "<h1>Offertes</h1>";

        echo "<div class='dj-srm-tabs'>
                <button data-tab='list' class='active'>Overzicht</button>
                <button data-tab='new'>Nieuwe Offerte</button>
              </div>";

        echo "<select class='dj-srm-tabs-mobile'>
                <option value='list'>Overzicht</option>
                <option value='new'>Nieuwe Offerte</option>
              </select>";

        // Overzicht
        echo "<div id='list' class='dj-srm-tab-content active'>";
        echo "<table class='widefat'><thead><tr>
                <th>ID</th><th>Klant</th><th>Email</th><th>Totaal</th><th>Status</th><th>Acties</th>
              </tr></thead><tbody>";

        if ($offers) {
            foreach ($offers as $offer) {
                echo "<tr>
                        <td>{$offer->id}</td>
                        <td>{$offer->client_name}</td>
                        <td>{$offer->client_email}</td>
                        <td>€ " . number_format($offer->total,2,",",".") . "</td>
                        <td>{$offer->status}</td>
                        <td>
                          <a href='" . admin_url("admin.php?page=dj-srm-offers&view={$offer->id}") . "' class='button'>Bekijken</a>
                          <a href='" . admin_url("admin.php?page=dj-srm-offers&edit={$offer->id}") . "' class='button'>Bewerken</a>
                          <button class='button delete-offer' data-id='{$offer->id}' data-nonce='" . wp_create_nonce('dj_srm_nonce') . "'>🗑 Verwijderen</button>
                          <a href='" . site_url("/offers/?id={$offer->id}") . "' target='_blank' class='button'>Publieke link</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Geen offertes gevonden.</td></tr>";
        }

        echo "</tbody></table></div>";

        // Wizard formulier
        echo "<div id='new' class='dj-srm-tab-content'>";
        include DJ_SRM_PLUGIN_DIR . 'templates/offer-form.php';
        echo "</div>";

        echo "</div>";
    }

    /**
     * Shortcodes
     */
    public function render_offers() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/offers.php';
        return ob_get_clean();
    }

    public function render_offer_single($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        ob_start();
        $offer_id = intval($atts['id']);
        include DJ_SRM_PLUGIN_DIR . 'templates/offer-single.php';
        return ob_get_clean();
    }

    public function render_offer_login() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/offer-login.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Nieuwe offerte opslaan
     */
    public function handle_new_offer() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';

        $items = isset($_POST['items']) ? (array) $_POST['items'] : [];
        $subtotal = 0; $vat = 0;

        foreach ($items as $item) {
            $qty = intval($item['qty'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $vatRate = floatval($item['vat'] ?? 0);

            $lineSubtotal = $qty * $price;
            $lineVat = $lineSubtotal * ($vatRate/100);

            $subtotal += $lineSubtotal;
            $vat += $lineVat;
        }

        $discount = floatval($_POST['discount'] ?? 0);
        $subtotal = max(0, $subtotal - $discount);
        $total = $subtotal + $vat;

        $wpdb->insert($table, [
            'offer_number' => uniqid("DJ-"),
            'client_email' => sanitize_email($_POST['client_email']),
            'client_name'  => sanitize_text_field($_POST['client_name']),
            'client_phone' => sanitize_text_field($_POST['client_phone']),
            'event_type'   => sanitize_text_field($_POST['event_type']),
            'event_date'   => sanitize_text_field($_POST['event_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'end_time'     => sanitize_text_field($_POST['end_time']),
            'venue_city'   => sanitize_text_field($_POST['venue_city']),
            'items'        => wp_json_encode($items),
            'discount'     => $discount,
            'subtotal'     => $subtotal,
            'vat'          => $vat,
            'total'        => $total,
            'notes'        => sanitize_textarea_field($_POST['notes']),
            'terms'        => sanitize_textarea_field($_POST['terms']),
            'status'       => 'verzonden',
            'valid_until'  => sanitize_text_field($_POST['valid_until']),
            'sent_at'      => current_time('mysql')
        ]);

        $offer_id = $wpdb->insert_id;

        wp_send_json_success([
            'message'    => 'Offerte succesvol aangemaakt.',
            'offer_id'   => $offer_id,
            'admin_link' => admin_url("admin.php?page=dj-srm-offers&view=$offer_id"),
            'offer_link' => site_url("/offers/?id=$offer_id")
        ]);
    }

    /**
     * AJAX: Offerte bijwerken
     */
    public function update_offer() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';
        $id = intval($_POST['offer_id']);

        $items = isset($_POST['items']) ? (array) $_POST['items'] : [];
        $subtotal = 0; $vat = 0;

        foreach ($items as $item) {
            $qty = intval($item['qty'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $vatRate = floatval($item['vat'] ?? 0);
            $lineSubtotal = $qty * $price;
            $lineVat = $lineSubtotal * ($vatRate/100);
            $subtotal += $lineSubtotal;
            $vat += $lineVat;
        }

        $total = $subtotal + $vat;

        $wpdb->update($table, [
            'client_name'  => sanitize_text_field($_POST['client_name']),
            'client_email' => sanitize_email($_POST['client_email']),
            'client_phone' => sanitize_text_field($_POST['client_phone']),
            'event_type'   => sanitize_text_field($_POST['event_type']),
            'event_date'   => sanitize_text_field($_POST['event_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'end_time'     => sanitize_text_field($_POST['end_time']),
            'venue_city'   => sanitize_text_field($_POST['venue_city']),
            'items'        => wp_json_encode($items),
            'subtotal'     => $subtotal,
            'vat'          => $vat,
            'total'        => $total,
            'notes'        => sanitize_textarea_field($_POST['notes']),
            'terms'        => sanitize_textarea_field($_POST['terms']),
            'valid_until'  => sanitize_text_field($_POST['valid_until']),
            'updated_at'   => current_time('mysql')
        ], ['id' => $id]);

        wp_send_json_success([
            'message' => 'Offerte bijgewerkt.',
            'admin_link' => admin_url("admin.php?page=dj-srm-offers&view=$id")
        ]);
    }

    /**
     * AJAX: Status bijwerken
     */
    public function update_offer_status() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';

        $id     = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);

        $wpdb->update($table, ['status' => $status], ['id' => $id]);

        wp_send_json_success(['message' => 'Status bijgewerkt naar: ' . $status]);
    }

    /**
     * AJAX: Offerte verwijderen
     */
    public function delete_offer() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Geen rechten om offertes te verwijderen.']);
        }

        check_ajax_referer('dj_srm_nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';
        $id = intval($_POST['id']);

        $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($deleted !== false) {
            wp_send_json_success([
                'message' => 'Offerte verwijderd.',
                'redirect' => admin_url("admin.php?page=dj-srm-offers")
            ]);
        } else {
            wp_send_json_error(['message' => 'Verwijderen mislukt.']);
        }
    }
}

new DJ_SRM_Offers();
