<?php
/**
 * Class: DJ_SRM_Offers
 * Doel: Offertes maken, beheren, exporteren en mailen
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DJ_SRM_Offers {

    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Shortcodes
        add_shortcode('dj_offers',        [$this, 'render_offers']);
        add_shortcode('dj_offer',         [$this, 'render_offer_single']);
        add_shortcode('dj_offer_login',   [$this, 'render_offer_login']);

        // AJAX handlers
        add_action('wp_ajax_dj_srm_add_offer',          [$this, 'handle_new_offer']);
        add_action('wp_ajax_dj_srm_update_offer',       [$this, 'update_offer']);
        add_action('wp_ajax_dj_srm_update_offer_status',[$this, 'update_offer_status']);
        add_action('wp_ajax_nopriv_dj_srm_update_offer_status',[$this, 'update_offer_status']);
        add_action('wp_ajax_dj_srm_delete_offer',       [$this, 'delete_offer']);

        // Nieuw: PDF en Mail
        add_action('wp_ajax_dj_srm_offer_pdf',          [$this, 'generate_offer_pdf']);
        add_action('wp_ajax_dj_srm_offer_email',        [$this, 'send_offer_email']);
    }

    /** --------------------------
     * Admin Menu
     -------------------------- */
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

    /** --------------------------
     * Admin Pagina
     -------------------------- */
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

        // Filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search        = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $where = "WHERE 1=1";
        if($status_filter){
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
        if($search){
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (client_name LIKE %s OR client_email LIKE %s)", $like, $like);
        }

        // Paginering
        $paged  = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit  = 10;
        $offset = ($paged - 1) * $limit;

        $total_offers = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
        $offers = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

        // HTML
        echo "<div class='wrap dj-srm-dashboard'>";
        echo "<h1>Offertes</h1>";

        // Filterformulier
        echo "<form method='get' class='dj-filter-form'>";
        echo "<input type='hidden' name='page' value='dj-srm-offers'>";
        echo "<input type='text' name='s' placeholder='Zoek klant of email' value='".esc_attr($search)."'>";
        echo "<select name='status'>
                <option value=''>-- Status --</option>
                <option value='concept' ".selected($status_filter,'concept',false).">Concept</option>
                <option value='verzonden' ".selected($status_filter,'verzonden',false).">Verzonden</option>
                <option value='geaccepteerd' ".selected($status_filter,'geaccepteerd',false).">Geaccepteerd</option>
                <option value='geweigerd' ".selected($status_filter,'geweigerd',false).">Geweigerd</option>
                <option value='betaald' ".selected($status_filter,'betaald',false).">Betaald</option>
              </select>";
        echo "<button class='button'>Filter</button>";
        echo "</form>";

        // Tabel
        echo "<table class='widefat'><thead><tr>
                <th>ID</th><th>Klant</th><th>Email</th><th>Totaal</th><th>Status</th><th>Acties</th>
              </tr></thead><tbody>";

        if ($offers) {
            foreach ($offers as $offer) {
                $total = number_format((float)$offer->total, 2, ",", ".");
                echo "<tr>
                        <td>{$offer->id}</td>
                        <td>" . esc_html($offer->client_name) . "</td>
                        <td>" . esc_html($offer->client_email) . "</td>
                        <td>€ {$total}</td>
                        <td>" . esc_html($offer->status) . "</td>
                        <td>
                          <a href='" . esc_url(admin_url("admin.php?page=dj-srm-offers&view={$offer->id}")) . "' class='button'>Bekijken</a>
                          <a href='" . esc_url(admin_url("admin.php?page=dj-srm-offers&edit={$offer->id}")) . "' class='button'>Bewerken</a>
                          <a href='" . esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_pdf&offer_id={$offer->id}&_wpnonce=".wp_create_nonce('dj_srm_nonce'))) . "' class='button'>⬇ PDF</a>
                          <a href='" . esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_email&offer_id={$offer->id}&_wpnonce=".wp_create_nonce('dj_srm_nonce'))) . "' class='button'>📧 Mail</a>
                          <button class='button delete-offer' data-id='{$offer->id}' data-nonce='" . wp_create_nonce('dj_srm_nonce') . "'>🗑 Verwijderen</button>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Geen offertes gevonden.</td></tr>";
        }

        echo "</tbody></table>";

        // Paginering
        $total_pages = ceil($total_offers / $limit);
        if($total_pages > 1){
            echo "<div class='tablenav'><div class='tablenav-pages'>";
            for($i=1;$i<=$total_pages;$i++){
                $class = ($i==$paged) ? "class='current-page'" : "";
                $url = add_query_arg(['paged'=>$i]);
                echo "<a href='".esc_url($url)."' $class>$i</a> ";
            }
            echo "</div></div>";
        }

        echo "</div>";
    }

    /** --------------------------
     * Shortcodes
     -------------------------- */
    public function render_offers() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/offers.php';
        return ob_get_clean();
    }

    public function render_offer_single($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $offer_id = intval($atts['id']);
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/offer-single.php';
        return ob_get_clean();
    }

    public function render_offer_login() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/offer-login.php';
        return ob_get_clean();
    }

    /** --------------------------
     * PDF genereren
     -------------------------- */
    public function generate_offer_pdf() {
        check_ajax_referer('dj_srm_nonce');
        if ( ! current_user_can('manage_options') ) {
            wp_die(__('Geen rechten.', 'dj-srm'));
        }

        global $wpdb;
        $id = intval($_GET['offer_id']);
        $table = $wpdb->prefix . 'dj_srm_offers';
        $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
        if(!$offer) wp_die('Offerte niet gevonden.');

        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/pdf/offer-pdf.php';
        $html = ob_get_clean();

        require_once DJ_SRM_PLUGIN_DIR . 'lib/dompdf/autoload.inc.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('offerte-'.$offer->offer_number.'.pdf');
        exit;
    }

    /** --------------------------
     * Offerte mailen
     -------------------------- */
    public function send_offer_email() {
        check_ajax_referer('dj_srm_nonce');
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Geen rechten.']);
        }

        global $wpdb;
        $id = intval($_GET['offer_id']);
        $table = $wpdb->prefix . 'dj_srm_offers';
        $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
        if(!$offer) wp_send_json_error(['message'=>'Offerte niet gevonden.']);

        $to      = $offer->client_email;
        $subject = "🎶 Jouw offerte van DJ’s Oostboys (#{$offer->offer_number})";

        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/mail/offer-sent.php';
        $message = ob_get_clean();

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if(wp_mail($to, $subject, $message, $headers)){
            wp_redirect(admin_url("admin.php?page=dj-srm-offers&view=$id&mail=success"));
        } else {
            wp_redirect(admin_url("admin.php?page=dj-srm-offers&view=$id&mail=fail"));
        }
        exit;
    }

    /** --------------------------
     * Resterende functies: add, update, status, delete
     -------------------------- */
    // ... laat zoals je nu hebt (handle_new_offer, update_offer, update_offer_status, delete_offer)

    /** --------------------------
     * Hulpfunctie: Totals berekenen
     -------------------------- */
    private function calc_totals($items) {
        $subtotal=0; $vat=0;
        foreach ($items as $item) {
            $qty = intval($item['qty'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $vatRate = floatval($item['vat'] ?? 0);
            $lineSubtotal = $qty * $price;
            $lineVat = $lineSubtotal * ($vatRate/100);
            $subtotal += $lineSubtotal;
            $vat      += $lineVat;
        }
        return [$subtotal,$vat];
    }
}

new DJ_SRM_Offers();
