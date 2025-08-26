<?php
/**
 * Class: DJ_SRM_Events
 *
 * Beheer van Events (admin).
 * - Overzicht: zoeken, filteren, nette layout
 * - Nieuw/bewerken: formulier met subtabs
 * - Verwijderen: met bevestiging + admin_post actie
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DJ_SRM_Events {

    private $templates = [];

    public function __construct() {
        // Templates
        $this->templates = [
            'bruiloft' => [
                'label' => 'Bruiloft',
                'defaults' => [
                    'name' => 'Bruiloft',
                    'location' => 'Trouwzaal',
                    'type' => 'bruiloft',
                    'status' => 'concept'
                ]
            ],
            'festival' => [
                'label' => 'Festival',
                'defaults' => [
                    'name' => 'Festival',
                    'location' => 'Main Stage',
                    'type' => 'festival',
                    'status' => 'concept'
                ]
            ]
        ];

        // Hooks
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_post_dj_srm_save_event', [ $this, 'save_event' ] );
        add_action( 'admin_post_dj_srm_delete_event', [ $this, 'delete_event' ] );
    }

    /**
     * Submenu
     */
    public function register_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'Events', 'dj-srm' ),
            __( 'Events', 'dj-srm' ),
            'manage_options',
            'dj-srm-events',
            [ $this, 'render_events_page' ]
        );
    }

    /**
     * Overzichtspagina
     */
    public function render_events_page() {
        // Als actie new/edit → alleen formulier
        if ( isset($_GET['action']) && in_array($_GET['action'], ['new','edit']) ) {
            $this->render_event_form();
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_events';

        // Zoek/filter
        $search = $_GET['s'] ?? '';
        $status = $_GET['status'] ?? '';
        $paged  = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit  = 10;
        $offset = ($paged - 1) * $limit;

        $where = "WHERE 1=1";
        if ($search) $where .= $wpdb->prepare(" AND (name LIKE %s OR location LIKE %s)", "%$search%", "%$search%");
        if ($status) $where .= $wpdb->prepare(" AND status=%s", $status);

        $events = $wpdb->get_results("SELECT * FROM $table $where ORDER BY start_time DESC LIMIT $limit OFFSET $offset");
        $total  = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

        ?>
        <div class="wrap dj-srm-dashboard">
          <h1><span class="dashicons dashicons-calendar"></span> Events</h1>

          <!-- Zoek/filter -->
          <form method="get" class="dj-srm-filters">
            <input type="hidden" name="page" value="dj-srm-events">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Zoek...">
            <select name="status">
              <option value="">Alle statussen</option>
              <option value="concept" <?php selected($status,'concept'); ?>>Concept</option>
              <option value="bevestigd" <?php selected($status,'bevestigd'); ?>>Bevestigd</option>
              <option value="afgerond" <?php selected($status,'afgerond'); ?>>Afgerond</option>
              <option value="geannuleerd" <?php selected($status,'geannuleerd'); ?>>Geannuleerd</option>
            </select>
            <button class="button">Filter</button>
            <a href="?page=dj-srm-events&action=new" class="button button-primary">+ Nieuw Event</a>
          </form>

          <!-- Tabel -->
          <table class="widefat striped dj-srm-events-table">
            <thead><tr><th>Naam</th><th>Start</th><th>Locatie</th><th>Status</th><th>Acties</th></tr></thead>
            <tbody>
            <?php if ($events): foreach($events as $event): ?>
              <tr>
                <td><a href="?page=dj-srm-events&action=edit&id=<?php echo $event->id; ?>"><strong><?php echo esc_html($event->name); ?></strong></a></td>
                <td><?php echo esc_html($event->start_time); ?></td>
                <td><?php echo esc_html($event->location); ?></td>
                <td><span class="status-<?php echo esc_attr($event->status); ?>"><?php echo esc_html($event->status); ?></span></td>
                <td>
                  <a href="?page=dj-srm-events&action=edit&id=<?php echo $event->id; ?>" class="button">Bewerken</a>
                  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('Weet je zeker dat je dit event wilt verwijderen?');">
                    <input type="hidden" name="action" value="dj_srm_delete_event">
                    <input type="hidden" name="id" value="<?php echo $event->id; ?>">
                    <button type="submit" class="button button-danger">Verwijderen</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5">Geen events gevonden.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php
    }

    /**
     * Formulier (nieuw/bewerk) + tabs
     */
    private function render_event_form() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_events';
        $id = $_GET['id'] ?? 0;
        $event = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id)) : null;
        $tab = $_GET['tab'] ?? 'details';

        ?>
        <div class="wrap dj-srm-dashboard">
          <h2><?php echo $id ? 'Event: '.esc_html($event->name) : 'Nieuw Event'; ?></h2>

          <!-- Tabs -->
          <div class="dj-srm-tabs">
            <button data-tab="details" class="active">Details</button>
            <?php if ($id): ?>
              <button data-tab="requests">Requests</button>
              <button data-tab="polls">Polls</button>
              <button data-tab="awards">Awards</button>
              <button data-tab="offers">Offertes</button>
              <button data-tab="afterparty">Afterparty</button>
            <?php endif; ?>
          </div>

          <div id="details" class="dj-srm-tab-content active">
            <?php $this->render_event_details($event); ?>
          </div>
          <div id="requests" class="dj-srm-tab-content"><?php if($id) $this->render_event_requests($id); ?></div>
          <div id="polls" class="dj-srm-tab-content"><?php if($id) $this->render_event_polls($id); ?></div>
          <div id="awards" class="dj-srm-tab-content"><?php if($id) $this->render_event_awards($id); ?></div>
          <div id="offers" class="dj-srm-tab-content"><?php if($id) $this->render_event_offers($id); ?></div>
          <div id="afterparty" class="dj-srm-tab-content"><?php if($id) $this->render_event_afterparty($id); ?></div>
        </div>
        <?php
    }

    /**
     * Details formulier
     */
    private function render_event_details($event) {
        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
          <input type="hidden" name="action" value="dj_srm_save_event">
          <input type="hidden" name="id" value="<?php echo esc_attr($event->id ?? 0); ?>">

          <table class="form-table">
            <tr><th>Naam</th><td><input type="text" name="name" value="<?php echo esc_attr($event->name ?? ''); ?>" class="regular-text"></td></tr>
            <tr><th>Beschrijving</th><td><textarea name="description" rows="4" class="large-text"><?php echo esc_textarea($event->description ?? ''); ?></textarea></td></tr>
            <tr><th>Starttijd</th><td><input type="datetime-local" name="start_time" value="<?php echo esc_attr($event->start_time ?? ''); ?>"></td></tr>
            <tr><th>Eindtijd</th><td><input type="datetime-local" name="end_time" value="<?php echo esc_attr($event->end_time ?? ''); ?>"></td></tr>
            <tr><th>Locatie</th><td><input type="text" name="location" value="<?php echo esc_attr($event->location ?? ''); ?>"></td></tr>
            <tr><th>Contactpersoon</th><td><input type="text" name="contact_name" value="<?php echo esc_attr($event->contact_name ?? ''); ?>"></td></tr>
            <tr><th>Contact E-mail</th><td><input type="email" name="contact_email" value="<?php echo esc_attr($event->contact_email ?? ''); ?>"></td></tr>
            <tr><th>Contact Telefoon</th><td><input type="text" name="contact_phone" value="<?php echo esc_attr($event->contact_phone ?? ''); ?>"></td></tr>
            <tr>
              <th>Type (Template)</th>
              <td>
                <select name="type">
                  <option value="">-- Kies --</option>
                  <?php foreach($this->templates as $key=>$tpl): ?>
                    <option value="<?php echo $key; ?>" <?php selected($event->type ?? '', $key); ?>>
                      <?php echo $tpl['label']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <tr>
              <th>Status</th>
              <td>
                <select name="status">
                  <option value="concept" <?php selected($event->status ?? '','concept'); ?>>Concept</option>
                  <option value="bevestigd" <?php selected($event->status ?? '','bevestigd'); ?>>Bevestigd</option>
                  <option value="afgerond" <?php selected($event->status ?? '','afgerond'); ?>>Afgerond</option>
                  <option value="geannuleerd" <?php selected($event->status ?? '','geannuleerd'); ?>>Geannuleerd</option>
                </select>
              </td>
            </tr>
          </table>
          <p><button class="button button-primary">Opslaan</button></p>
        </form>
        <?php
    }

    /**
     * Opslaan
     */
    public function save_event() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_events';
        $id = intval($_POST['id'] ?? 0);

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'end_time' => sanitize_text_field($_POST['end_time']),
            'location' => sanitize_text_field($_POST['location']),
            'contact_name' => sanitize_text_field($_POST['contact_name']),
            'contact_email' => sanitize_email($_POST['contact_email']),
            'contact_phone' => sanitize_text_field($_POST['contact_phone']),
            'type' => sanitize_text_field($_POST['type']),
            'status' => sanitize_text_field($_POST['status']),
            'updated_at' => current_time('mysql')
        ];

        if ($id) {
            $wpdb->update($table,$data,['id'=>$id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table,$data);
        }

        wp_redirect(admin_url('admin.php?page=dj-srm-events'));
        exit;
    }

    /**
     * Verwijderen
     */
    public function delete_event() {
        global $wpdb;
        $table = $wpdb->prefix.'dj_srm_events';
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $wpdb->delete($table, ['id'=>$id]);
        }
        wp_redirect(admin_url('admin.php?page=dj-srm-events'));
        exit;
    }

    // === Placeholder tabs ===
    private function render_event_requests($id){ echo "<p>Requests gekoppeld aan event $id</p>"; }
    private function render_event_polls($id){ echo "<p>Polls gekoppeld aan event $id</p>"; }
    private function render_event_awards($id){ echo "<p>Awards gekoppeld aan event $id</p>"; }
    private function render_event_offers($id){ echo "<p>Offertes gekoppeld aan event $id</p>"; }
    private function render_event_afterparty($id){ echo "<p>Afterparty mail instellen voor event $id</p>"; }
}

new DJ_SRM_Events();
