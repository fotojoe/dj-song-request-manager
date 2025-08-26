<?php 
/**
 * File: db-install.php
 * Doel: maakt alle database-tabellen aan voor de DJ Song Request Manager.
 * Wordt aangeroepen tijdens activatie van de plugin via dj_srm_create_tables().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Creëert de benodigde database-tabellen via dbDelta.
 * Deze functie is veilig om meerdere keren uit te voeren: dbDelta werkt idempotent.
 */
function dj_srm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $prefix = $wpdb->prefix;

    // === EVENTS ===
    $events_table = $prefix . 'dj_srm_events';
    $sql_events = "CREATE TABLE $events_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_time DATETIME,
        end_time DATETIME,
        location VARCHAR(255),
        contact_name VARCHAR(255),
        contact_email VARCHAR(255),
        contact_phone VARCHAR(50),
        type VARCHAR(50),
        status ENUM('concept','bevestigd','afgerond','geannuleerd') DEFAULT 'concept',
        template VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // === REQUESTS ===
    $requests_table = $prefix . 'dj_srm_requests';
    $sql_requests = "CREATE TABLE $requests_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED,
        song VARCHAR(255) NOT NULL,
        artist VARCHAR(255) NOT NULL,
        requester VARCHAR(255),
        message TEXT,
        status ENUM('nieuw','goedgekeurd','geweigerd','gedraaid') DEFAULT 'nieuw',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === POLLS ===
    $polls_table = $prefix . 'dj_srm_polls';
    $sql_polls = "CREATE TABLE $polls_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED,
        question VARCHAR(255) NOT NULL,
        options LONGTEXT,
        status ENUM('open','gesloten') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === POLL VOTES ===
    $votes_table = $prefix . 'dj_srm_poll_votes';
    $sql_votes = "CREATE TABLE $votes_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        poll_id BIGINT(20) UNSIGNED,
        choice VARCHAR(255),
        voter VARCHAR(255),
        ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY poll_id (poll_id)
    ) $charset_collate;";

    // === AWARDS ===
    $awards_table = $prefix . 'dj_srm_awards';
    $sql_awards = "CREATE TABLE $awards_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED,
        name VARCHAR(255) NOT NULL,
        receiver VARCHAR(255) NOT NULL,
        type VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === OFFERS ===
    $offers_table = $prefix . 'dj_srm_offers';
    $sql_offers = "CREATE TABLE $offers_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

        -- Basis info
        offer_number VARCHAR(50) NOT NULL,
        event_id BIGINT(20) UNSIGNED,
        status ENUM('concept','verzonden','geaccepteerd','geweigerd','betaald') DEFAULT 'concept',
        valid_until DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        sent_at DATETIME,

        -- Klantgegevens
        salutation ENUM('Dhr','Mevr') DEFAULT 'Dhr',
        client_name VARCHAR(255) NOT NULL,
        client_email VARCHAR(255),
        client_phone VARCHAR(50),

        -- Login
        pincode VARCHAR(10),

        -- Locatie
        venue_type ENUM('commercieel','prive') DEFAULT 'prive',
        venue_name VARCHAR(255),
        venue_street VARCHAR(255),
        venue_number VARCHAR(20),
        venue_postcode VARCHAR(20),
        venue_city VARCHAR(100),
        venue_country VARCHAR(100) DEFAULT 'Nederland',

        -- Eventdetails
        event_type VARCHAR(50),
        event_date DATE,
        start_time TIME,
        end_time TIME,
        guest_count VARCHAR(50),

        -- Financieel
        currency VARCHAR(10) DEFAULT 'EUR',
        items LONGTEXT,
        discount DECIMAL(10,2) DEFAULT 0.00,
        subtotal DECIMAL(10,2) DEFAULT 0.00,
        vat DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) DEFAULT 0.00,
        deposit DECIMAL(10,2) DEFAULT 0.00,

        -- Extra info
        notes TEXT,
        terms TEXT,
        rider LONGTEXT,

        PRIMARY KEY  (id),
        UNIQUE KEY offer_number (offer_number),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === GUESTBOOK ===
    $guestbook_table = $prefix . 'dj_srm_guestbook';
    $sql_guestbook = "CREATE TABLE $guestbook_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED,
        name VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        approved TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === AFTERPARTY ===
    $afterparty_table = $prefix . 'dj_srm_afterparty';
    $sql_afterparty = "CREATE TABLE $afterparty_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED,
        playlist LONGTEXT,
        highlights TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // === SETTINGS ===
    $settings_table = $prefix . 'dj_srm_settings';
    $sql_settings = "CREATE TABLE $settings_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        option_name VARCHAR(191) NOT NULL,
        value LONGTEXT NULL,
        autoload TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY option_name (option_name),
        KEY autoload (autoload)
    ) $charset_collate;";

    // === LOGS ===
    $logs_table = $prefix . 'dj_srm_logs';
    $sql_logs = "CREATE TABLE $logs_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        action VARCHAR(191) NOT NULL,
        user_id BIGINT(20) UNSIGNED NULL,
        ip VARCHAR(45) NULL,
        meta LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY action (action),
        KEY user_id (user_id),
        KEY ip (ip),
        KEY created_at (created_at)
    ) $charset_collate;";

    // === IP BLOCKLIST ===
    $block_table = $prefix . 'dj_srm_ip_blocklist';
    $sql_block = "CREATE TABLE $block_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip VARCHAR(45) NOT NULL,
        reason TEXT NULL,
        expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY ip (ip),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    // === OBS SETTINGS ===
    $obs_table = $prefix . 'dj_srm_obs_settings';
    $sql_obs = "CREATE TABLE $obs_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        option_name VARCHAR(191) NOT NULL,
        value TEXT,
        PRIMARY KEY  (id),
        UNIQUE KEY option_name (option_name)
    ) $charset_collate;";

    // Uitvoeren
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_events );
    dbDelta( $sql_requests );
    dbDelta( $sql_polls );
    dbDelta( $sql_votes );
    dbDelta( $sql_awards );
    dbDelta( $sql_offers );
    dbDelta( $sql_guestbook );
    dbDelta( $sql_afterparty );
    dbDelta( $sql_settings );
    dbDelta( $sql_logs );
    dbDelta( $sql_block );
    dbDelta( $sql_obs );
}

