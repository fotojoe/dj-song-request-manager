<?php
/**
 * Class: DJ_SRM_Polls
 * Doel: Publieksvragen stellen en stemmen verwerken
 * Waarom: Zorgt voor interactie en energie tijdens events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DJ_SRM_Polls {

    // === Constructor ===
    public function __construct() {
        // Admin menu toevoegen
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Shortcodes registreren
        add_shortcode('dj_polls', [$this, 'render_polls']);              // actieve poll tonen
        add_shortcode('dj_poll_results', [$this, 'render_poll_results']); // resultaten tonen

        // AJAX stemmen
        add_action('wp_ajax_dj_srm_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_dj_srm_vote', [$this, 'handle_vote']);
    }

    /**
     * Admin Menu
     * Voegt submenu "Polls" toe in DJ Manager (dashboard)
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __('Polls', 'dj-srm'),
            __('Polls', 'dj-srm'),
            'manage_options',
            'dj-srm-polls',
            [$this, 'admin_page']
        );
    }

    /**
     * Admin Pagina
     * Toont lijst van polls in dashboard stijl
     */
    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_polls';
        $polls = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        echo "<div class='wrap'><h1>" . __('Polls beheren', 'dj-srm') . "</h1>";
        echo "<table class='widefat'><thead><tr>
                <th>ID</th><th>Event ID</th><th>Vraag</th><th>Status</th><th>Aangemaakt</th>
              </tr></thead><tbody>";

        if ($polls) {
            foreach($polls as $poll) {
                echo "<tr>
                        <td>{$poll->id}</td>
                        <td>{$poll->event_id}</td>
                        <td>{$poll->question}</td>
                        <td>{$poll->status}</td>
                        <td>{$poll->created_at}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>Geen polls gevonden.</td></tr>";
        }
        echo "</tbody></table></div>";
    }

    /**
     * Shortcode [dj_polls]
     * Toont actieve poll met opties
     */
    public function render_polls() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/polls.php';
        return ob_get_clean();
    }

    /**
     * Shortcode [dj_poll_results]
     * Toont resultaten van actieve poll
     */
    public function render_poll_results() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/poll-results.php';
        return ob_get_clean();
    }

    /**
     * AJAX: handle_vote
     * Verwerkt een stem van een gast
     */
    public function handle_vote() {
        global $wpdb;
        $poll_id = intval($_POST['poll_id']);
        $choice  = sanitize_text_field($_POST['choice']);
        $voter   = sanitize_text_field($_POST['voter']);
        $ip      = $_SERVER['REMOTE_ADDR'];

        $table = $wpdb->prefix . 'dj_srm_poll_votes';

        // Dubbele stemmen voorkomen
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE poll_id=%d AND ip=%s", $poll_id, $ip
        ));
        if ($existing > 0) {
            wp_send_json_error(['message' => 'Je hebt al gestemd!']);
        }

        // Opslaan stem
        $wpdb->insert($table, [
            'poll_id' => $poll_id,
            'choice'  => $choice,
            'voter'   => $voter,
            'ip'      => $ip
        ]);

        wp_send_json_success(['message' => 'Stem ontvangen!']);
    }
}

// Class initialiseren
new DJ_SRM_Polls();

/**
 * === Einde bestand class-polls.php ===
 * Uitbreiden:
 * - CRUD toevoegen in admin_page()
 * - Templates aanpassen (polls.php, poll-results.php)
 * - OBS integratie voor live weergave
 */
