<?php
/*
Plugin Name: Advanced Preloader
Plugin URI: https://sanjayshankar.me
Description: A customizable preloader plugin with image and text options.
Version: 1.3.1
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
    $options = get_option('advanced_preloader_general', []);
    $enabled = isset($options['enabled']) ? $options['enabled'] : '0';

    if ($enabled == '1') {
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
    $tabs = ['general', 'design', 'animation'];
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
    <div class="wrap">
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
            <?php foreach ($tabs as $tab): ?>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('tab', $tab), 'advanced-preloader-tab-nonce')); ?>"
                    class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html(ucfirst($tab)); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <div class="tab-content-wrapper">
            <?php foreach ($tabs as $tab): ?>
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
    $tabs = ['general', 'design', 'animation'];
    foreach ($tabs as $tab) {
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
    add_settings_field('image', 'Preloader Image', 'advanced_preloader_image_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('layout_order', 'Layout Order', 'advanced_preloader_layout_order_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('text', 'Preloader Text', 'advanced_preloader_text_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('text_display_mode', 'Text Display Mode', 'advanced_preloader_text_display_mode_field', 'advanced-preloader-general', 'advanced_preloader_main_section');

    // Design Settings
    add_settings_section('advanced_preloader_design_section', 'Design Settings', null, 'advanced-preloader-design');
    add_settings_field('bg_color', 'Background Color', 'advanced_preloader_bg_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');
    add_settings_field('text_color', 'Text Color', 'advanced_preloader_text_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');

    // Animation Settings
    add_settings_section('advanced_preloader_animation_section', 'Animation Settings', null, 'advanced-preloader-animation');
    add_settings_field('animation_speed', 'Animation Speed', 'advanced_preloader_animation_speed_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');
    add_settings_field('delay_time', 'Delay Time', 'advanced_preloader_delay_time_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');

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
            <option value="both" ' . selected($type, 'both', false) . '>Both</option>
          </select>';
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
            <option value="image-over-text" ' . selected($layout_order, 'image-over-text', false) . '>Image Over Text</option>
            <option value="image-left-text" ' . selected($layout_order, 'image-left-text', false) . '>Image Left to Text</option>
            <option value="image-right-text" ' . selected($layout_order, 'image-right-text', false) . '>Image Right to Text</option>
            <option value="image-below-text" ' . selected($layout_order, 'image-below-text', false) . '>Image Below Text</option>
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

function advanced_preloader_display()
{
    // Get all options in single calls
    $general = get_option('advanced_preloader_general', []);
    $design = get_option('advanced_preloader_design', []);
    $animation = get_option('advanced_preloader_animation', []);

    // Check if preloader is enabled and we're not on AMP
    if (!isset($general['enabled']) || $general['enabled'] !== '1' || function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        return;
    }

    // Validate and sanitize values
    $preloader_type = in_array($general['type'] ?? 'image', ['image', 'text', 'both']) ? $general['type'] : 'image';
    $layout_order = in_array($general['layout_order'] ?? 'image-over-text', [
        'image-over-text',
        'image-left-text',
        'image-right-text',
        'image-below-text'
    ]) ? $general['layout_order'] : 'image-over-text';

    // Prepare preloader content
    $output = '<div id="advanced-preloader" 
        class="' . esc_attr($layout_order) . '"
        data-delay="' . esc_attr($animation['delay_time'] ?? '0') . 's" 
        style="background-color: ' . esc_attr($design['bg_color'] ?? '#ffffff') . '; color: ' . esc_attr($design['text_color'] ?? '#000000') . ';">';

    // Image output
    if (($preloader_type === 'image' || $preloader_type === 'both') && !empty($general['image'])) {
        $image_id = $general['image'];
        $image_url = !empty($image_id) ? wp_get_attachment_url($image_id) : '';
        $output .= '<img src="' . $image_url . '" alt="' . esc_attr__('Loading...', 'advanced-preloader') . '" class="preloader-image" />';
    }

    // Text output
    if (($preloader_type === 'text' || $preloader_type === 'both') && !empty($general['text'])) {
        $text_content = wp_kses_post($general['text']);
        $display_mode = in_array($general['text_display_mode'] ?? 'full', ['full', 'random']) ? $general['text_display_mode'] : 'full';

        if ($display_mode === 'random') {
            $lines = array_filter(array_map('trim', explode("\n", $text_content)));
            $text_content = !empty($lines) ? $lines[array_rand($lines)] : __('Loading...', 'advanced-preloader');
        }

        $output .= '<div class="preloader-text">' . $text_content . '</div>';
    }

    $output .= '</div>';

    echo wp_kses_post($output);
}
add_action('wp_footer', 'advanced_preloader_display');