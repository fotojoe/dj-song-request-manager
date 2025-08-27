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

        // Shortcodes (frontend klant)
        add_shortcode('dj_offers',        [$this, 'render_offers']);       // overzicht klant
        add_shortcode('dj_offer',         [$this, 'render_offer_single']); // detail klant
        add_shortcode('dj_offer_login',   [$this, 'render_offer_login']);  // login klant

        // AJAX handlers
        add_action('wp_ajax_dj_srm_add_offer',          [$this, 'handle_new_offer']);
        add_action('wp_ajax_dj_srm_update_offer',       [$this, 'update_offer']);
        add_action('wp_ajax_dj_srm_update_offer_status',[$this, 'update_offer_status']);
        add_action('wp_ajax_nopriv_dj_srm_update_offer_status',[$this, 'update_offer_status']);
        add_action('wp_ajax_dj_srm_delete_offer',       [$this, 'delete_offer']);

        // PDF & Mail
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

        // === Tabs ===
        $tab = $_GET['tab'] ?? 'list';
        echo "<div class='wrap dj-srm-dashboard'>";
        echo "<h1 class='wp-heading-inline'>Offertes</h1>";
        echo "<nav class='nav-tab-wrapper'>
                <a href='?page=dj-srm-offers&tab=list' class='nav-tab ".($tab=='list'?'nav-tab-active':'')."'>Overzicht</a>
                <a href='?page=dj-srm-offers&tab=new' class='nav-tab ".($tab=='new'?'nav-tab-active':'')."'>Nieuwe Offerte</a>
              </nav>";

        // === Tabs logica ===
        if ($tab == 'new') {
            include DJ_SRM_PLUGIN_DIR . 'templates/offer-form.php';
            echo "</div>";
            return;
        }
        if (isset($_GET['view'])) {
            $offer_id = intval($_GET['view']);
            $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));
            include DJ_SRM_PLUGIN_DIR . 'templates/offer-single.php';
            echo "</div>";
            return;
        }
        if (isset($_GET['edit'])) {
            $offer_id = intval($_GET['edit']);
            $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));
            include DJ_SRM_PLUGIN_DIR . 'templates/offer-edit.php';
            echo "</div>";
            return;
        }

        // === Filters & Zoeken ===
        $status_filter = $_GET['status'] ?? '';
        $search        = $_GET['s'] ?? '';
        $where = "WHERE 1=1";
        if($status_filter){
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
        if($search){
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (client_name LIKE %s OR client_email LIKE %s)", $like, $like);
        }

        // === Paginering ===
        $paged  = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit  = 10;
        $offset = ($paged - 1) * $limit;

        $total_offers = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
        $offers = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

        // === Filter formulier ===
        echo "<form method='get' style='margin:15px 0; display:flex; flex-wrap:wrap; gap:10px; align-items:center;'>";
        echo "<input type='hidden' name='page' value='dj-srm-offers'>";
        echo "<input type='text' name='s' placeholder='Zoek klant of email' value='".esc_attr($search)."'>";
        echo "<select name='status'>
                <option value=''>-- Status --</option>
                <option ".selected($status_filter,'concept',false)." value='concept'>Concept</option>
                <option ".selected($status_filter,'verzonden',false)." value='verzonden'>Verzonden</option>
                <option ".selected($status_filter,'geaccepteerd',false)." value='geaccepteerd'>Geaccepteerd</option>
                <option ".selected($status_filter,'geweigerd',false)." value='geweigerd'>Geweigerd</option>
                <option ".selected($status_filter,'betaald',false)." value='betaald'>Betaald</option>
              </select>";
        submit_button('Filter', '', '', false);
        echo "</form>";

        // === Tabel Offertes ===
        echo "<div style='overflow-x:auto'>";
        echo "<table class='widefat striped'>
                <thead>
                  <tr>
                    <th>ID</th><th>Klant</th><th>Email</th><th>Totaal</th><th>Status</th><th>Acties</th>
                  </tr>
                </thead>
                <tbody>";
        if ($offers) {
            foreach ($offers as $offer) {
                $total = number_format((float)$offer->total, 2, ",", ".");
                echo "<tr>
                        <td>{$offer->id}</td>
                        <td>".esc_html($offer->client_name)."</td>
                        <td>".esc_html($offer->client_email)."</td>
                        <td>€ {$total}</td>
                        <td>".esc_html($offer->status)."</td>
                        <td style='display:flex; gap:5px; flex-wrap:wrap;'>
                          <a href='".esc_url(admin_url("admin.php?page=dj-srm-offers&view={$offer->id}"))."' class='button button-small'>Bekijken</a>
                          <a href='".esc_url(admin_url("admin.php?page=dj-srm-offers&edit={$offer->id}"))."' class='button button-small'>Bewerken</a>
                          <a href='".esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_pdf&offer_id={$offer->id}&_wpnonce=".wp_create_nonce('dj_srm_nonce')))."' class='button button-small'>⬇ PDF</a>
                          <a href='".esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_email&offer_id={$offer->id}&_wpnonce=".wp_create_nonce('dj_srm_nonce')))."' class='button button-small'>📧 Mail</a>
                          <button type='button' class='button button-small delete-offer' 
                                  data-id='{$offer->id}' data-nonce='".wp_create_nonce('dj_srm_nonce')."'>🗑 Verwijderen</button>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Geen offertes gevonden.</td></tr>";
        }
        echo "</tbody></table></div>";

        // === Paginering links ===
        $total_pages = ceil($total_offers / $limit);
        if($total_pages > 1){
            echo "<div class='tablenav'><div class='tablenav-pages'>";
            for($i=1;$i<=$total_pages;$i++){
                $class = ($i==$paged) ? "class='page-numbers current'" : "class='page-numbers'";
                $url = add_query_arg(['paged'=>$i]);
                echo "<a href='".esc_url($url)."' $class>$i</a> ";
            }
            echo "</div></div>";
        }
        echo "</div>";

        // === Verwijderen Script ===
        ?>
        <script>
        jQuery(document).ready(function($){
            $(".delete-offer").on("click", function(e){
                e.preventDefault();
                if(!confirm("Weet je zeker dat je deze offerte wilt verwijderen?")) return;
                var id = $(this).data("id");
                var nonce = $(this).data("nonce");
                $.post(ajaxurl, {
                    action: "dj_srm_delete_offer",
                    id: id,
                    _wpnonce: nonce
                }, function(resp){
                    if(resp.success){
                        alert(resp.data.message);
                        location.reload();
                    } else {
                        alert("Fout: " + resp.data.message);
                    }
                });
            });
        });
        </script>
        <?php
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
     * Nieuwe offerte opslaan
     -------------------------- */
    public function handle_new_offer() {
        check_ajax_referer('dj_srm_nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_offers';

        $items = $_POST['items'] ?? [];
        list($subtotal,$vat) = $this->calc_totals($items);

        $discount = floatval($_POST['discount'] ?? 0);
        $subtotal = max(0, $subtotal - $discount);
        $total    = $subtotal + $vat;
        $pincode  = rand(100000, 999999);

        $wpdb->insert($table, [
            'offer_number'  => 'DJ-' . time(),
            'client_email'  => sanitize_email($_POST['client_email']),
            'client_name'   => sanitize_text_field($_POST['client_name']),
            'client_phone'  => sanitize_text_field($_POST['client_phone']),
            'event_type'    => sanitize_text_field($_POST['event_type']),
            'event_date'    => sanitize_text_field($_POST['event_date']),
            'venue_city'    => sanitize_text_field($_POST['venue_city']),
            'items'         => wp_json_encode($items),
            'discount'      => $discount,
            'subtotal'      => $subtotal,
            'vat'           => $vat,
            'total'         => $total,
            'status'        => 'verzonden',
            'valid_until'   => sanitize_text_field($_POST['valid_until']),
            'sent_at'       => current_time('mysql'),
            'pincode'       => $pincode
        ]);
        $offer_id = $wpdb->insert_id;
        $offer    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$offer_id));

        // Mail sturen
        $to      = $offer->client_email;
        $subject = "🎶 Jouw offerte van DJ’s Oostboys (#{$offer->offer_number})";
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/mail/offer-sent.php';
        $message = ob_get_clean();
        wp_mail($to, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);

        wp_send_json_success(['message'=>'Offerte aangemaakt en verzonden','redirect'=>admin_url("admin.php?page=dj-srm-offers")]);
    }

    /** --------------------------
     * PDF
     -------------------------- */
    public function generate_offer_pdf() {
        check_ajax_referer('dj_srm_nonce');
        if ( ! current_user_can('manage_options') ) wp_die(__('Geen rechten.', 'dj-srm'));
        global $wpdb;
        $id = intval($_GET['offer_id']);
        $table = $wpdb->prefix . 'dj_srm_offers';
        $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
        if(!$offer) wp_die('Offerte niet gevonden.');

        ob_start(); include DJ_SRM_PLUGIN_DIR . 'templates/pdf/offer-pdf.php'; $html = ob_get_clean();
        require_once DJ_SRM_PLUGIN_DIR . 'lib/dompdf/autoload.inc.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
        $dompdf->stream('offerte-'.$offer->offer_number.'.pdf');
        exit;
    }

    /** --------------------------
     * Mail
     -------------------------- */
    public function send_offer_email() {
        check_ajax_referer('dj_srm_nonce');
        if ( ! current_user_can('manage_options') ) wp_send_json_error(['message'=>'Geen rechten']);
        global $wpdb;
        $id = intval($_GET['offer_id']);
        $table = $wpdb->prefix . 'dj_srm_offers';
        $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
        if(!$offer) wp_send_json_error(['message'=>'Niet gevonden']);

        ob_start(); include DJ_SRM_PLUGIN_DIR . 'templates/mail/offer-sent.php'; $message = ob_get_clean();
        wp_mail($offer->client_email, "🎶 Jouw offerte (#{$offer->offer_number})", $message, ['Content-Type: text/html; charset=UTF-8']);
        wp_redirect(admin_url("admin.php?page=dj-srm-offers&view=$id&mail=success")); exit;
    }

    /** --------------------------
     * Delete
     -------------------------- */
    public function delete_offer() {
        check_ajax_referer('dj_srm_nonce');
        if ( ! current_user_can('manage_options') ) wp_send_json_error(['message'=>'Geen rechten']);
        global $wpdb;
        $id = intval($_POST['id']);
        $deleted = $wpdb->delete($wpdb->prefix.'dj_srm_offers',['id'=>$id],['%d']);
        if($deleted) wp_send_json_success(['message'=>'Offerte verwijderd']);
        else wp_send_json_error(['message'=>'Verwijderen mislukt']);
    }

    /** --------------------------
     * Totals berekenen
     -------------------------- */
    private function calc_totals($items) {
        $subtotal=0; $vat=0;
        foreach ((array)$items as $item) {
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
