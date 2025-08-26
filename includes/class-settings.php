<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * DJ_SRM_Settings
 * - Settings CRUD (+ caching)
 * - Logs (actie + meta)
 * - IP-blocklist (permanent/tijdelijk)
 */
class DJ_SRM_Settings {
    private static $instance;
    private static $cache = [];
    const CACHE_GROUP = 'dj_srm_settings';

    /** @var wpdb */
    private $db;
    private $table_settings;
    private $table_logs;
    private $table_block;

    private function __construct(){
        global $wpdb;
        $this->db = $wpdb;
        $this->table_settings = $wpdb->prefix . 'dj_srm_settings';
        $this->table_logs     = $wpdb->prefix . 'dj_srm_logs';
        $this->table_block    = $wpdb->prefix . 'dj_srm_ip_blocklist';
    }

    public static function instance(){
        if (!self::$instance){ self::$instance = new self(); }
        return self::$instance;
    }

    /** Install/upgrade tabellen */
    public static function install(){
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $sql_settings = "CREATE TABLE {$p}dj_srm_settings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            option_name VARCHAR(191) NOT NULL,
            value LONGTEXT NULL,
            autoload TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY option_name (option_name),
            KEY autoload (autoload)
        ) $c;";

        $sql_logs = "CREATE TABLE {$p}dj_srm_logs (
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
        ) $c;";

        $sql_block = "CREATE TABLE {$p}dj_srm_ip_blocklist (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip VARCHAR(45) NOT NULL,
            reason TEXT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY ip (ip),
            KEY expires_at (expires_at)
        ) $c;";

        dbDelta($sql_settings);
        dbDelta($sql_logs);
        dbDelta($sql_block);
    }

    /* ================= SETTINGS ================= */

    public function get($key, $default=null){
        $key = $this->sanitize_key($key);
        if (array_key_exists($key, self::$cache)) return self::$cache[$key];
        $cached = wp_cache_get($key, self::CACHE_GROUP);
        if ($cached !== false){ self::$cache[$key] = $cached; return $cached; }

        $val = $this->db->get_var($this->db->prepare(
            "SELECT value FROM {$this->table_settings} WHERE option_name=%s LIMIT 1", $key
        ));
        if ($val === null) return $default;
        $val = $this->maybe_json_decode($val);
        self::$cache[$key] = $val;
        wp_cache_set($key, $val, self::CACHE_GROUP);
        return $val;
    }

    public function set($key, $value, $autoload=true){
        $key  = $this->sanitize_key($key);
        $val  = $this->maybe_json_encode($value);
        $auto = $autoload ? 1 : 0;

        $sql = "INSERT INTO {$this->table_settings} (option_name, value, autoload)
                VALUES (%s,%s,%d)
                ON DUPLICATE KEY UPDATE value=VALUES(value), autoload=VALUES(autoload), updated_at=CURRENT_TIMESTAMP";
        $ok = $this->db->query($this->db->prepare($sql, $key, $val, $auto));
        self::$cache[$key] = $value;
        wp_cache_set($key, $value, self::CACHE_GROUP);
        return (bool)$ok;
    }

    public function delete($key){
        $key = $this->sanitize_key($key);
        $ok = $this->db->delete($this->table_settings, ['option_name'=>$key], ['%s']);
        unset(self::$cache[$key]);
        wp_cache_delete($key, self::CACHE_GROUP);
        return (bool)$ok;
    }

    /* ================= LOGGING ================= */

    public function log($action, array $meta=[]){
        $action  = substr(sanitize_key(str_replace('.', '_', $action)), 0, 191);
        $user_id = get_current_user_id() ?: null;
        $ip      = $this->current_ip();
        $metaTxt = empty($meta) ? null : wp_json_encode($meta, JSON_UNESCAPED_UNICODE);

        return (bool)$this->db->insert($this->table_logs, [
            'action'=>$action,'user_id'=>$user_id,'ip'=>$ip,'meta'=>$metaTxt
        ], ['%s','%d','%s','%s']);
    }

    public function purge_logs($days=90){
        $days = max(1,(int)$days);
        return (bool)$this->db->query($this->db->prepare(
            "DELETE FROM {$this->table_logs} WHERE created_at < (NOW() - INTERVAL %d DAY)", $days
        ));
    }

    /* ================= BLOCKLIST ================= */

    public function is_blocked($ip=null){
        $ip = $ip ? $this->sanitize_ip($ip) : $this->current_ip();
        $id = $this->db->get_var($this->db->prepare(
            "SELECT id FROM {$this->table_block} WHERE ip=%s AND (expires_at IS NULL OR expires_at>NOW()) LIMIT 1", $ip
        ));
        return !empty($id);
    }

    public function block($ip, $reason=null, $ttlSeconds=null){
        $ip = $this->sanitize_ip($ip);
        $expires = $ttlSeconds ? gmdate('Y-m-d H:i:s', time() + (int)$ttlSeconds) : null;
        $sql = "INSERT INTO {$this->table_block} (ip, reason, expires_at)
                VALUES (%s,%s,%s)
                ON DUPLICATE KEY UPDATE reason=VALUES(reason), expires_at=VALUES(expires_at)";
        return (bool)$this->db->query($this->db->prepare($sql, $ip, $reason, $expires));
    }

    public function unblock($ip){
        $ip = $this->sanitize_ip($ip);
        return (bool)$this->db->delete($this->table_block, ['ip'=>$ip], ['%s']);
    }

    public function purge_expired_blocks(){
        return (bool)$this->db->query("DELETE FROM {$this->table_block} WHERE expires_at IS NOT NULL AND expires_at<=NOW()");
    }

    /* ================= Helpers ================= */

    private function sanitize_key($k){ return substr(trim((string)$k), 0, 191); }
    private function current_ip(){ return $this->sanitize_ip($_SERVER['REMOTE_ADDR'] ?? ''); }
    private function sanitize_ip($ip){ return substr(trim((string)$ip), 0, 45); }
    private function maybe_json_encode($v){ return (is_array($v)||is_object($v)) ? wp_json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v; }
    private function maybe_json_decode($v){
        if(!is_string($v)) return $v;
        $t=ltrim($v); if($t==='' ) return '';
        if($t[0]==='{'||$t[0]==='['){ $d=json_decode($v,true); if(json_last_error()===JSON_ERROR_NONE) return $d; }
        return $v;
    }
}
