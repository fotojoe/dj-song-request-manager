<?php
/**
 * Admin pagina: Instellingen DJ SRM
 */

if ( ! current_user_can('manage_options') ) {
    wp_die(__('Geen toegang tot instellingen.', 'dj-srm'));
}

$S = DJ_SRM_Settings::instance();

/**
 * Opslaan formulier
 */
if (isset($_POST['dj_srm_settings_nonce']) && wp_verify_nonce($_POST['dj_srm_settings_nonce'], 'save_dj_srm_settings')) {
    foreach ($_POST['settings'] as $key => $val) {
        $S->set($key, sanitize_text_field($val));
    }
    echo '<div class="updated"><p>Instellingen opgeslagen ✅</p></div>';
}

/**
 * Ophalen huidige waarden
 */
$get = function($k, $d = '') use ($S) {
    return esc_attr($S->get($k, $d));
};
?>

<div class="wrap dj-srm-settings">
    <h1>🎛 DJ Song Request Manager - Instellingen</h1>

    <form method="post">
        <?php wp_nonce_field('save_dj_srm_settings', 'dj_srm_settings_nonce'); ?>

        <h2 class="nav-tab-wrapper">
            <a href="#tab-general" class="nav-tab nav-tab-active">Algemeen</a>
            <a href="#tab-ui" class="nav-tab">Kleuren & Fonts</a>
            <a href="#tab-header" class="nav-tab">Header/Footer</a>
            <a href="#tab-social" class="nav-tab">Socials</a>
        </h2>

        <div id="tab-general" class="tab-content active">
            <table class="form-table">
                <tr><th>Telefoon</th><td><input type="text" name="settings[org.phone]" value="<?php echo $get('org.phone'); ?>"></td></tr>
                <tr><th>Email</th><td><input type="email" name="settings[org.email]" value="<?php echo $get('org.email'); ?>"></td></tr>
                <tr><th>Adres</th><td><input type="text" name="settings[org.address]" value="<?php echo $get('org.address'); ?>"></td></tr>
            </table>
        </div>

        <div id="tab-ui" class="tab-content" style="display:none;">
            <h3>Kleuren</h3>
            <table class="form-table">
                <tr><th>Primary</th><td><input type="color" name="settings[ui.color.primary]" value="<?php echo $get('ui.color.primary'); ?>"></td></tr>
                <tr><th>Secondary</th><td><input type="color" name="settings[ui.color.secondary]" value="<?php echo $get('ui.color.secondary'); ?>"></td></tr>
                <tr><th>Accent</th><td><input type="color" name="settings[ui.color.accent]" value="<?php echo $get('ui.color.accent'); ?>"></td></tr>
                <tr><th>Surface</th><td><input type="color" name="settings[ui.color.surface]" value="<?php echo $get('ui.color.surface'); ?>"></td></tr>
                <tr><th>Text</th><td><input type="color" name="settings[ui.color.text]" value="<?php echo $get('ui.color.text'); ?>"></td></tr>
                <tr><th>Link</th><td><input type="color" name="settings[ui.color.link]" value="<?php echo $get('ui.color.link'); ?>"></td></tr>
            </table>

            <h3>Fonts</h3>
            <table class="form-table">
                <tr><th>Body font</th><td><input type="text" name="settings[ui.font.body]" value="<?php echo $get('ui.font.body'); ?>"></td></tr>
                <tr><th>Headings font</th><td><input type="text" name="settings[ui.font.headings]" value="<?php echo $get('ui.font.headings'); ?>"></td></tr>
            </table>
        </div>

        <div id="tab-header" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr><th>Header HTML</th><td>
                    <textarea name="settings[ui.header.html]" rows="3" cols="60"><?php echo $S->get('ui.header.html'); ?></textarea>
                </td></tr>
                <tr><th>Footer HTML</th><td>
                    <textarea name="settings[ui.footer.html]" rows="3" cols="60"><?php echo $S->get('ui.footer.html'); ?></textarea>
                </td></tr>
            </table>
        </div>

        <div id="tab-social" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr><th>Facebook</th><td><input type="url" name="settings[social.facebook]" value="<?php echo $get('social.facebook'); ?>"></td></tr>
                <tr><th>Instagram</th><td><input type="url" name="settings[social.instagram]" value="<?php echo $get('social.instagram'); ?>"></td></tr>
                <tr><th>TikTok</th><td><input type="url" name="settings[social.tiktok]" value="<?php echo $get('social.tiktok'); ?>"></td></tr>
            </table>
        </div>

        <p class="submit"><button type="submit" class="button button-primary">💾 Opslaan</button></p>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const tabs = document.querySelectorAll(".nav-tab");
    const contents = document.querySelectorAll(".tab-content");
    tabs.forEach(tab => {
        tab.addEventListener("click", function(e){
            e.preventDefault();
            tabs.forEach(t => t.classList.remove("nav-tab-active"));
            contents.forEach(c => c.style.display = "none");
            this.classList.add("nav-tab-active");
            document.querySelector(this.getAttribute("href")).style.display = "block";
        });
    });
});
</script>
