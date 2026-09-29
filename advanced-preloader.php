<?php
/*
Plugin Name: Advanced Preloader
Plugin URI: https://sanjayshankar.me
Description: A customizable preloader with built-in CSS loaders, image and text options, and display rules.
Version: 1.4.0
Requires PHP: 7.0
Author: Sanjay Shankar
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts and styles
function advanced_preloader_enqueue_scripts()
{
    if (advanced_preloader_should_display()) {
        wp_enqueue_style('advanced-preloader-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'), 'all');
        wp_enqueue_script('advanced-preloader-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'assets/script.js'), true);
    }
}
add_action('wp_enqueue_scripts', 'advanced_preloader_enqueue_scripts');

// Enqueue media uploader script for admin
function advanced_preloader_admin_scripts($hook)
{
    if ($hook === 'toplevel_page_advanced-preloader') {
        wp_enqueue_media();
        wp_enqueue_script('advanced-preloader-admin-script', plugin_dir_url(__FILE__) . 'assets/admin/admin.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'assets/admin/admin.js'), true);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('advanced-preloader-admin-style', plugin_dir_url(__FILE__) . 'assets/admin/admin.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/admin/admin.css'), 'all');
        wp_enqueue_script('advanced-preloader-preview', plugin_dir_url(__FILE__) . 'assets/admin/preview.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'assets/admin/preview.js'), true);
        // The front-end stylesheet drives the loader picker and the live preview, so they match the site exactly.
        wp_enqueue_style('advanced-preloader-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'), 'all');
        wp_localize_script('advanced-preloader-preview', 'advancedPreloaderAdmin', array(
            'loaders' => array_map('advanced_preloader_loader_markup', array_combine(array_keys(advanced_preloader_loader_styles()), array_keys(advanced_preloader_loader_styles()))),
        ));
    }
}
add_action('admin_enqueue_scripts', 'advanced_preloader_admin_scripts');

// Sanitize settings
function advanced_preloader_sanitize_settings($input)
{
    $input = wp_unslash($input);
    $sanitized_input = [];

    if (isset($input['enabled'])) {
        $sanitized_input['enabled'] = sanitize_text_field($input['enabled']);
    }

    if (isset($input['type'])) {
        $sanitized_input['type'] = sanitize_key($input['type']);
    }

    if (isset($input['image'])) {
        $sanitized_input['image'] = sanitize_text_field($input['image']);
    }

    if (isset($input['text'])) {
        $sanitized_input['text'] = wp_kses_post($input['text']);
    }

    if (isset($input['layout_order'])) {
        $sanitized_input['layout_order'] = sanitize_key($input['layout_order']);
    }

    if (isset($input['bg_color'])) {
        $sanitized_input['bg_color'] = sanitize_hex_color($input['bg_color']);
    }

    if (isset($input['text_color'])) {
        $sanitized_input['text_color'] = sanitize_hex_color($input['text_color']);
    }

    if (isset($input['animation_speed'])) {
        $sanitized_input['animation_speed'] = sanitize_text_field($input['animation_speed']);
    }

    if (isset($input['delay_time'])) {
        $sanitized_input['delay_time'] = sanitize_text_field($input['delay_time']);
    }

    if (isset($input['custom_css'])) {
        $sanitized_input['custom_css'] = wp_strip_all_tags($input['custom_css']);
    }

    if (isset($input['text_display_mode'])) {
        $sanitized_input['text_display_mode'] = sanitize_key($input['text_display_mode']);
    }

    if (isset($input['loader_style'])) {
        $style = sanitize_key($input['loader_style']);
        $sanitized_input['loader_style'] = array_key_exists($style, advanced_preloader_loader_styles()) ? $style : 'spinner';
    }

    if (isset($input['loader_color'])) {
        $sanitized_input['loader_color'] = (string) sanitize_hex_color($input['loader_color']);
    }

    if (isset($input['loader_size'])) {
        $sanitized_input['loader_size'] = in_array($input['loader_size'], array('small', 'medium', 'large'), true) ? $input['loader_size'] : 'medium';
    }

    if (isset($input['show_on'])) {
        $sanitized_input['show_on'] = in_array($input['show_on'], array('all', 'home', 'only', 'except'), true) ? $input['show_on'] : 'all';
    }

    if (isset($input['pages'])) {
        $sanitized_input['pages'] = array_values(array_filter(array_map('absint', (array) $input['pages'])));
    }

    if (isset($input['frequency'])) {
        $sanitized_input['frequency'] = in_array($input['frequency'], array('always', 'session', 'once'), true) ? $input['frequency'] : 'always';
    }

    if (isset($input['hide_logged_in'])) {
        $sanitized_input['hide_logged_in'] = '1';
    }

    return $sanitized_input;
}

// Add plugin settings page to main menu
function advanced_preloader_settings_menu()
{
    add_menu_page(
        'Advanced Preloader Settings',
        'Preloader',
        'manage_options',
        'advanced-preloader',
        'advanced_preloader_settings_page',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'advanced_preloader_settings_menu');

// Display settings page with tabs
function advanced_preloader_settings_page()
{
    $tabs = advanced_preloader_tabs();
    $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';

    // Verify nonce for tab navigation if tab parameter exists
    if (isset($_GET['tab'])) {
        // Check if the nonce is set and validate it
        if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));

            // Verify the nonce
            if (!wp_verify_nonce($nonce, 'advanced-preloader-tab-nonce')) {
                wp_die('Security check failed');
            }
        } else {
            wp_die('Nonce is missing or invalid');
        }
    }

    ?>
    <div class="wrap advanced-preloader-settings">
        <h1>Advanced Preloader Settings</h1>

        <!-- Preview container -->
        <div class="preloader-preview-container">
            <div class="laptop-frame">
                <div class="laptop-screen">
                    <div id="preloader-preview"></div>
                </div>
            </div>
        </div>

        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab => $tab_label): ?>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('tab', $tab), 'advanced-preloader-tab-nonce')); ?>"
                    class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <div class="tab-content-wrapper">
            <?php foreach (array_keys($tabs) as $tab): ?>
                <div class="tab-content <?php echo $active_tab === $tab ? 'active' : 'hidden'; ?>">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('advanced_preloader_settings_group_' . $tab);
                        do_settings_sections('advanced-preloader-' . $tab);
                        submit_button();
                        ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// Register settings
function advanced_preloader_register_settings()
{
    foreach (array_keys(advanced_preloader_tabs()) as $tab) {
        register_setting(
            'advanced_preloader_settings_group_' . $tab,
            'advanced_preloader_' . $tab,
            'advanced_preloader_sanitize_settings'
        );
    }

    // General Settings
    add_settings_section('advanced_preloader_main_section', 'General Settings', null, 'advanced-preloader-general');
    add_settings_field('enabled', 'Enable Preloader', 'advanced_preloader_enabled_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('type', 'Preloader Type', 'advanced_preloader_type_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('loader_style', 'Loader Style', 'advanced_preloader_loader_style_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('image', 'Preloader Image', 'advanced_preloader_image_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('layout_order', 'Layout Order', 'advanced_preloader_layout_order_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('text', 'Preloader Text', 'advanced_preloader_text_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('text_display_mode', 'Text Display Mode', 'advanced_preloader_text_display_mode_field', 'advanced-preloader-general', 'advanced_preloader_main_section');

    // Design Settings
    add_settings_section('advanced_preloader_design_section', 'Design Settings', null, 'advanced-preloader-design');
    add_settings_field('bg_color', 'Background Color', 'advanced_preloader_bg_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');
    add_settings_field('text_color', 'Text Color', 'advanced_preloader_text_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');
    add_settings_field('loader_color', 'Loader Color', 'advanced_preloader_loader_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');
    add_settings_field('loader_size', 'Loader Size', 'advanced_preloader_loader_size_field', 'advanced-preloader-design', 'advanced_preloader_design_section');

    // Animation Settings
    add_settings_section('advanced_preloader_animation_section', 'Animation Settings', null, 'advanced-preloader-animation');
    add_settings_field('animation_speed', 'Animation Speed', 'advanced_preloader_animation_speed_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');
    add_settings_field('delay_time', 'Delay Time', 'advanced_preloader_delay_time_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');

    // Display Rules
    add_settings_section('advanced_preloader_display_section', 'Display Rules', 'advanced_preloader_display_section_intro', 'advanced-preloader-display');
    add_settings_field('show_on', 'Show On', 'advanced_preloader_show_on_field', 'advanced-preloader-display', 'advanced_preloader_display_section');
    add_settings_field('pages', 'Pages', 'advanced_preloader_pages_field', 'advanced-preloader-display', 'advanced_preloader_display_section');
    add_settings_field('frequency', 'How Often', 'advanced_preloader_frequency_field', 'advanced-preloader-display', 'advanced_preloader_display_section');
    add_settings_field('hide_logged_in', 'Logged-in Users', 'advanced_preloader_hide_logged_in_field', 'advanced-preloader-display', 'advanced_preloader_display_section');

    }
add_action('admin_init', 'advanced_preloader_register_settings');

// Callback functions for settings fields
function advanced_preloader_enabled_field()
{
    $options = get_option('advanced_preloader_general', []);
    $enabled = isset($options['enabled']) ? $options['enabled'] : '1';
    echo '<input type="checkbox" name="advanced_preloader_general[enabled]" value="1" ' . checked(1, $enabled, false) . ' /> Enable Preloader';
}

function advanced_preloader_type_field()
{
    $options = get_option('advanced_preloader_general');
    $type = isset($options['type']) ? $options['type'] : 'image';
    echo '<select name="advanced_preloader_general[type]" id="preloader_type">
            <option value="image" ' . selected($type, 'image', false) . '>Image</option>
            <option value="text" ' . selected($type, 'text', false) . '>Text</option>
            <option value="both" ' . selected($type, 'both', false) . '>Image + Text</option>
            <option value="loader" ' . selected($type, 'loader', false) . '>Loader Animation</option>
            <option value="loader_text" ' . selected($type, 'loader_text', false) . '>Loader Animation + Text</option>
          </select>';
    echo '<p class="description">Loader animations are built in and need no image upload.</p>';
}

function advanced_preloader_image_field()
{
    $options = get_option('advanced_preloader_general', []);
    $image_id = isset($options['image']) ? $options['image'] : '';
    $image_url = !empty($image_id) ? wp_get_attachment_url($image_id) : '';

    echo '<input type="text" name="advanced_preloader_general[image]" id="advanced_preloader_image" value="' . esc_attr($image_id) . '" style="display: none;" />
';
    echo '<input type="hidden" name="advanced_preloader_general[image_url]" id="advanced_preloader_image_url" value="' . esc_url($image_url) . '" style="display: none;" />
';
    echo '<button type="button" class="button upload_image_button">Upload Image</button>
          <button type="button" class="button remove_image_button" style="' . (empty($image_url) ? 'display:none;' : '') . '">Remove Image</button>
          <div id="preloader_image_preview" style="margin-top: 10px;">';
    if (!empty($image_url)) {
        echo '<img src="' . esc_url($image_url) . '" style="max-width: 200px; height: auto;" />';
    }
    echo '</div>';
}

function advanced_preloader_text_field()
{
    $options = get_option('advanced_preloader_general');
    $text = isset($options['text']) ? $options['text'] : "Loading...";
    echo '<textarea name="advanced_preloader_general[text]" id="advanced_preloader_text" rows="10" cols="80">' . wp_kses_post($text) . '</textarea>';
    echo '<p class="description">HTML tags are allowed. Add multiple lines for random display.</p>';
}

function advanced_preloader_text_display_mode_field()
{
    $options = get_option('advanced_preloader_general');
    $mode = isset($options['text_display_mode']) ? $options['text_display_mode'] : 'full';
    echo '<select name="advanced_preloader_general[text_display_mode]" id="text_display_mode">
            <option value="full" ' . selected($mode, 'full', false) . '>Show Full Content</option>
            <option value="random" ' . selected($mode, 'random', false) . '>Show Random Line</option>
          </select>';
}

function advanced_preloader_layout_order_field()
{
    $options = get_option('advanced_preloader_general', []);
    $layout_order = isset($options['layout_order']) ? $options['layout_order'] : 'image-over-text';
    echo '<div id="layout_order_wrapper">';
    echo '<select name="advanced_preloader_general[layout_order]" id="layout_order">
            <option value="image-over-text" ' . selected($layout_order, 'image-over-text', false) . '>Graphic above text</option>
            <option value="image-left-text" ' . selected($layout_order, 'image-left-text', false) . '>Graphic left of text</option>
            <option value="image-right-text" ' . selected($layout_order, 'image-right-text', false) . '>Graphic right of text</option>
            <option value="image-below-text" ' . selected($layout_order, 'image-below-text', false) . '>Graphic below text</option>
          </select>';
    echo '</div>';
}

function advanced_preloader_bg_color_field()
{
    $options = get_option('advanced_preloader_design');
    $color = isset($options['bg_color']) ? $options['bg_color'] : '#ffffff';
    echo '<input type="text" name="advanced_preloader_design[bg_color]" value="' . esc_attr($color) . '" class="color-picker" />';
}

function advanced_preloader_text_color_field()
{
    $options = get_option('advanced_preloader_design');
    $color = isset($options['text_color']) ? $options['text_color'] : '#000000';
    echo '<input type="text" name="advanced_preloader_design[text_color]" value="' . esc_attr($color) . '" class="color-picker" />';
}

function advanced_preloader_animation_speed_field()
{
    $options = get_option('advanced_preloader_animation');
    $speed = isset($options['animation_speed']) ? $options['animation_speed'] : '1s';
    echo '<input type="text" name="advanced_preloader_animation[animation_speed]" value="' . esc_attr($speed) . '" />';
}

function advanced_preloader_delay_time_field()
{
    $options = get_option('advanced_preloader_animation');
    $delay = isset($options['delay_time']) ? $options['delay_time'] : '0s';
    echo '<input type="text" name="advanced_preloader_animation[delay_time]" value="' . esc_attr($delay) . '" />';
}

/** Settings tabs, in display order. */
function advanced_preloader_tabs()
{
    return [
        'general'   => 'General',
        'design'    => 'Design',
        'animation' => 'Animation',
        'display'   => 'Display Rules',
    ];
}

/** Built-in CSS loaders. */
function advanced_preloader_loader_styles()
{
    return [
        'spinner'  => 'Spinner',
        'ring'     => 'Dual Ring',
        'dots'     => 'Bouncing Dots',
        'bars'     => 'Bars',
        'pulse'    => 'Pulse',
        'progress' => 'Progress Bar',
    ];
}

/** Markup for a loader; shared by the front end, the style picker and the live preview. */
function advanced_preloader_loader_markup($style)
{
    $children = ['dots' => 3, 'bars' => 4, 'progress' => 1];
    $inner    = str_repeat('<i></i>', $children[$style] ?? 0);
    return '<span class="ap-loader ap-loader-' . esc_attr($style) . '" aria-hidden="true">' . $inner . '</span>';
}

function advanced_preloader_loader_style_field()
{
    $options = get_option('advanced_preloader_general', []);
    $current = $options['loader_style'] ?? 'spinner';
    echo '<fieldset class="ap-loader-picker" id="ap_loader_style"><legend class="screen-reader-text">Loader Style</legend>';
    foreach (advanced_preloader_loader_styles() as $key => $label) {
        echo '<label class="ap-loader-option">';
        echo '<input type="radio" name="advanced_preloader_general[loader_style]" value="' . esc_attr($key) . '" ' . checked($current, $key, false) . ' />';
        echo '<span class="ap-loader-swatch">' . wp_kses_post(advanced_preloader_loader_markup($key)) . '</span>';
        echo '<span class="ap-loader-name">' . esc_html($label) . '</span>';
        echo '</label>';
    }
    echo '</fieldset>';
}

function advanced_preloader_loader_color_field()
{
    $options = get_option('advanced_preloader_design', []);
    $color = $options['loader_color'] ?? '';
    echo '<input type="text" name="advanced_preloader_design[loader_color]" value="' . esc_attr($color) . '" class="color-picker" />';
    echo '<p class="description">Leave empty to use the text color.</p>';
}

function advanced_preloader_loader_size_field()
{
    $options = get_option('advanced_preloader_design', []);
    $size = $options['loader_size'] ?? 'medium';
    echo '<select name="advanced_preloader_design[loader_size]" id="ap_loader_size">';
    foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($size, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function advanced_preloader_display_section_intro()
{
    echo '<p>Choose where the preloader appears and how often a visitor sees it. These rules work with page caching plugins.</p>';
}

function advanced_preloader_show_on_field()
{
    $options = get_option('advanced_preloader_display', []);
    $show_on = $options['show_on'] ?? 'all';
    $choices = [
        'all'     => 'Every page',
        'home'    => 'Homepage only',
        'only'    => 'Only on the pages selected below',
        'except'  => 'Every page except those selected below',
    ];
    echo '<fieldset id="ap_show_on"><legend class="screen-reader-text">Show On</legend>';
    foreach ($choices as $key => $label) {
        echo '<label style="display:block;margin-bottom:6px;"><input type="radio" name="advanced_preloader_display[show_on]" value="' . esc_attr($key) . '" ' . checked($show_on, $key, false) . ' /> ' . esc_html($label) . '</label>';
    }
    echo '</fieldset>';
}

function advanced_preloader_pages_field()
{
    $options  = get_option('advanced_preloader_display', []);
    $selected = array_map('absint', (array) ($options['pages'] ?? []));
    $pages    = get_pages(['sort_column' => 'menu_order,post_title', 'post_status' => 'publish']);

    echo '<div class="ap-page-picker" id="ap_pages">';
    if (!$pages) {
        echo '<p class="description">You have no published pages yet.</p></div>';
        return;
    }
    echo '<input type="search" class="regular-text ap-page-filter" placeholder="Filter pages…" aria-label="Filter pages" />';
    echo '<ul class="ap-page-list">';
    foreach ($pages as $page) {
        $depth = count(get_post_ancestors($page));
        $title = '' !== $page->post_title ? $page->post_title : '(no title)';
        echo '<li style="padding-left:' . (int) ($depth * 16) . 'px"><label><input type="checkbox" name="advanced_preloader_display[pages][]" value="' . (int) $page->ID . '" ' . checked(in_array((int) $page->ID, $selected, true), true, false) . ' /> ' . esc_html($title) . '</label></li>';
    }
    echo '</ul>';
    echo '<p class="description"><span class="ap-page-count">' . count($selected) . '</span> selected</p>';
    echo '</div>';
}

function advanced_preloader_frequency_field()
{
    $options = get_option('advanced_preloader_display', []);
    $freq = $options['frequency'] ?? 'always';
    $choices = [
        'always'  => ['Every page load', ''],
        'session' => ['Once per visit', 'Shown again after the visitor closes the browser.'],
        'once'    => ['First visit only', 'Returning visitors on the same browser never see it again.'],
    ];
    echo '<fieldset><legend class="screen-reader-text">How Often</legend>';
    foreach ($choices as $key => $choice) {
        echo '<label style="display:block;margin-bottom:6px;"><input type="radio" name="advanced_preloader_display[frequency]" value="' . esc_attr($key) . '" ' . checked($freq, $key, false) . ' /> ' . esc_html($choice[0]);
        if ($choice[1]) {
            echo ' <span class="description">— ' . esc_html($choice[1]) . '</span>';
        }
        echo '</label>';
    }
    echo '</fieldset>';
}

function advanced_preloader_hide_logged_in_field()
{
    $options = get_option('advanced_preloader_display', []);
    $hide = !empty($options['hide_logged_in']);
    echo '<label><input type="checkbox" name="advanced_preloader_display[hide_logged_in]" value="1" ' . checked($hide, true, false) . ' /> Don\'t show the preloader to logged-in users</label>';
    echo '<p class="description">Handy while you are editing the site.</p>';
}

/**
 * Should the preloader appear on the current request? Page rules are evaluated
 * on the server; the "how often" rule runs in the browser so it stays correct
 * behind page caches.
 */
function advanced_preloader_should_display()
{
    if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
        return false;
    }
    $general = get_option('advanced_preloader_general', []);
    if (!isset($general['enabled']) || '1' !== (string) $general['enabled']) {
        return false;
    }
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        return false;
    }

    $display = get_option('advanced_preloader_display', []);
    if (!empty($display['hide_logged_in']) && is_user_logged_in()) {
        return false;
    }

    $show_on = $display['show_on'] ?? 'all';
    $pages   = array_map('absint', (array) ($display['pages'] ?? []));
    switch ($show_on) {
        case 'home':
            $show = is_front_page();
            break;
        case 'only':
            $show = $pages && is_page($pages);
            break;
        case 'except':
            $show = !($pages && is_page($pages));
            break;
        default:
            $show = true;
    }

    /**
     * Filter whether the preloader shows on the current request.
     *
     * @param bool $show
     */
    return (bool) apply_filters('advanced_preloader_should_display', $show);
}

/**
 * Convert a duration setting such as "1s", "0.5s", "500ms" or "2" (seconds) to milliseconds.
 */
function advanced_preloader_to_ms($value, $fallback)
{
    $value = strtolower(trim((string) $value));
    if (!preg_match('/^(\d+(?:\.\d+)?)\s*(ms|s)?$/', $value, $m)) {
        return $fallback;
    }
    $ms = (isset($m[2]) && 'ms' === $m[2]) ? (float) $m[1] : (float) $m[1] * 1000;
    return (int) min($ms, 60000);
}

function advanced_preloader_display()
{
    static $printed = false;
    if ($printed || !advanced_preloader_should_display()) {
        return;
    }
    $printed = true;

    $general   = get_option('advanced_preloader_general', []);
    $design    = get_option('advanced_preloader_design', []);
    $animation = get_option('advanced_preloader_animation', []);
    $display   = get_option('advanced_preloader_display', []);

    $preloader_type = $general['type'] ?? 'image';
    if (!in_array($preloader_type, ['image', 'text', 'both', 'loader', 'loader_text'], true)) {
        $preloader_type = 'image';
    }
    $layout_order = $general['layout_order'] ?? 'image-over-text';
    if (!in_array($layout_order, [
        'image-over-text',
        'image-left-text',
        'image-right-text',
        'image-below-text'
    ], true)) {
        $layout_order = 'image-over-text';
    }
    $loader_style = $general['loader_style'] ?? 'spinner';
    if (!array_key_exists($loader_style, advanced_preloader_loader_styles())) {
        $loader_style = 'spinner';
    }
    $loader_size = in_array($design['loader_size'] ?? 'medium', ['small', 'medium', 'large'], true) ? $design['loader_size'] ?? 'medium' : 'medium';
    $text_color  = sanitize_hex_color($design['text_color'] ?? '') ?: '#000000';
    $bg_color    = sanitize_hex_color($design['bg_color'] ?? '') ?: '#ffffff';
    $loader_color = sanitize_hex_color($design['loader_color'] ?? '') ?: $text_color;
    $frequency   = in_array($display['frequency'] ?? 'always', ['always', 'session', 'once'], true) ? $display['frequency'] ?? 'always' : 'always';

    // "How often" is decided in the browser, before the overlay paints, so it works behind page caches.
    if ('always' !== $frequency) {
        $storage = 'once' === $frequency ? 'localStorage' : 'sessionStorage';
        $check   = "try{if(window." . $storage . ".getItem('advancedPreloaderSeen')){document.documentElement.classList.add('ap-skip');}}catch(e){}";
        if (function_exists('wp_print_inline_script_tag')) {
            wp_print_inline_script_tag($check);
        }
    }

    $output = '<div id="advanced-preloader" role="status" aria-live="polite" aria-label="' . esc_attr__('Loading', 'advanced-preloader') . '"
        class="' . esc_attr($layout_order . ' ap-size-' . $loader_size) . '"
        data-delay="' . esc_attr(advanced_preloader_to_ms($animation['delay_time'] ?? '0s', 0)) . '"
        data-animation-speed="' . esc_attr(advanced_preloader_to_ms($animation['animation_speed'] ?? '1s', 1000)) . '"
        data-frequency="' . esc_attr($frequency) . '"
        style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . '; --ap-loader-color: ' . esc_attr($loader_color) . ';">';

    // Graphic: an uploaded image or a built-in loader
    if (in_array($preloader_type, ['loader', 'loader_text'], true)) {
        $output .= advanced_preloader_loader_markup($loader_style);
    } elseif (($preloader_type === 'image' || $preloader_type === 'both') && !empty($general['image'])) {
        $image_url = wp_get_attachment_url((int) $general['image']);
        if ($image_url) {
            $output .= '<img src="' . esc_url($image_url) . '" alt="" class="preloader-image" />';
        }
    }

    // Text output
    if (in_array($preloader_type, ['text', 'both', 'loader_text'], true) && !empty($general['text'])) {
        $text_content = wp_kses_post($general['text']);
        $display_mode = in_array($general['text_display_mode'] ?? 'full', ['full', 'random'], true) ? $general['text_display_mode'] ?? 'full' : 'full';

        if ($display_mode === 'random') {
            $lines = array_filter(array_map('trim', explode("\n", $text_content)));
            $text_content = !empty($lines) ? $lines[array_rand($lines)] : __('Loading...', 'advanced-preloader');
        }

        $output .= '<div class="preloader-text">' . $text_content . '</div>';
    }

    $output .= '</div>';

    echo wp_kses_post($output);
}
// Print right after <body> opens so the overlay covers the page before it renders;
// fall back to the footer for themes that don't call wp_body_open().
add_action('wp_body_open', 'advanced_preloader_display', 1);
add_action('wp_footer', 'advanced_preloader_display');
