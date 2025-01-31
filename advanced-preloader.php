<?php
/*
Plugin Name: Advanced Preloader
Plugin URI: https://acodez.in
Description: A customizable preloader plugin with image and text options.
Version: 1.2
Author: Sanjay Shankar
Author URI: https://acodez.in
License: GPL2
*/

// Enqueue necessary scripts and styles
function advanced_preloader_enqueue_scripts()
{
    $options = get_option('advanced_preloader_general', []); // Get options with a default empty array
    $enabled = isset($options['enabled']) ? $options['enabled'] : '0'; // Default to '0' if 'enabled' key is not set

    if ($enabled == '1') {
        wp_enqueue_style('advanced-preloader-style', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('advanced-preloader-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'advanced_preloader_enqueue_scripts');

// Enqueue media uploader script for admin
function advanced_preloader_admin_scripts($hook)
{
    // Check if we are on the plugin's settings page
    if ($hook === 'toplevel_page_advanced-preloader') {
        wp_enqueue_media(); // Enqueue WordPress media uploader
        wp_enqueue_script('advanced-preloader-admin-script', plugin_dir_url(__FILE__) . 'assets/admin/admin.js', array('jquery'), null, true);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        // Add new admin CSS
        wp_enqueue_style('advanced-preloader-admin-style', plugin_dir_url(__FILE__) . 'assets/admin/admin.css');
        // Add preview update script
        wp_enqueue_script('advanced-preloader-preview', plugin_dir_url(__FILE__) . 'assets/admin/preview.js', array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'advanced_preloader_admin_scripts');

// Add plugin settings page to main menu
function advanced_preloader_settings_menu()
{
    add_menu_page('Advanced Preloader Settings', 'Advanced Preloader', 'manage_options', 'advanced-preloader', 'advanced_preloader_settings_page', 'dashicons-admin-generic');
}
add_action('admin_menu', 'advanced_preloader_settings_menu');

// Display settings page with tabs
function advanced_preloader_settings_page()
{
    $tabs = ['general', 'design', 'animation', 'advanced'];
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
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
                <a href="?page=advanced-preloader&tab=<?php echo $tab; ?>"
                    class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php echo ucfirst($tab); ?>
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
    $tabs = ['general', 'design', 'animation', 'advanced'];
    foreach ($tabs as $tab) {
        register_setting('advanced_preloader_settings_group_' . $tab, 'advanced_preloader_' . $tab);
    }

    // General Settings
    add_settings_section('advanced_preloader_main_section', 'General Settings', null, 'advanced-preloader-general');
    add_settings_field('enabled', 'Enable Preloader', 'advanced_preloader_enabled_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('type', 'Preloader Type', 'advanced_preloader_type_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('image', 'Preloader Image', 'advanced_preloader_image_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('layout_order', 'Layout Order', 'advanced_preloader_layout_order_field', 'advanced-preloader-general', 'advanced_preloader_main_section');
    add_settings_field('text', 'Preloader Text', 'advanced_preloader_text_field', 'advanced-preloader-general', 'advanced_preloader_main_section');

    // Design Settings
    add_settings_section('advanced_preloader_design_section', 'Design Settings', null, 'advanced-preloader-design');
    add_settings_field('bg_color', 'Background Color', 'advanced_preloader_bg_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');
    add_settings_field('text_color', 'Text Color', 'advanced_preloader_text_color_field', 'advanced-preloader-design', 'advanced_preloader_design_section');

    // Animation Settings
    add_settings_section('advanced_preloader_animation_section', 'Animation Settings', null, 'advanced-preloader-animation');
    add_settings_field('animation_speed', 'Animation Speed', 'advanced_preloader_animation_speed_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');
    add_settings_field('delay_time', 'Delay Time', 'advanced_preloader_delay_time_field', 'advanced-preloader-animation', 'advanced_preloader_animation_section');

    // Advanced Settings
    add_settings_section('advanced_preloader_advanced_section', 'Advanced Settings', null, 'advanced-preloader-advanced');
    add_settings_field('custom_css', 'Custom CSS', 'advanced_preloader_custom_css_field', 'advanced-preloader-advanced', 'advanced_preloader_advanced_section');
    // Add new display mode field (add to register_settings)
    add_settings_field(
        'text_display_mode',
        'Text Display Mode',
        'advanced_preloader_text_display_mode_field',
        'advanced-preloader-general',
        'advanced_preloader_main_section'
    );
}
add_action('admin_init', 'advanced_preloader_register_settings');

// Callback functions for settings fields
function advanced_preloader_enabled_field()
{
    $options = get_option('advanced_preloader_general', []); // Get options with a default empty array
    $enabled = isset($options['enabled']) ? $options['enabled'] : '1'; // Default to '1' if 'enabled' key is not set
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
    $image = isset($options['image']) ? $options['image'] : '';
    echo '<input type="text" name="advanced_preloader_general[image]" id="advanced_preloader_image" value="' . esc_attr($image) . '" style="display: none;" />'; // Hidden input field
    echo '<button type="button" class="button upload_image_button">Upload Image</button>
          <button type="button" class="button remove_image_button" style="' . (empty($image) ? 'display:none;' : '') . '">Remove Image</button>
          <div id="preloader_image_preview" style="margin-top: 10px;">';
    if (!empty($image)) {
        echo '<img src="' . esc_attr($image) . '" style="max-width: 200px; height: auto;" />';
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

// New callback function for display mode
function advanced_preloader_text_display_mode_field()
{
    $options = get_option('advanced_preloader_general');
    $mode = isset($options['text_display_mode']) ? $options['text_display_mode'] : 'full';
    echo '<select name="advanced_preloader_general[text_display_mode]" id="text_display_mode">
            <option value="full" ' . selected($mode, 'full', false) . '>Show Full Content</option>
            <option value="random" ' . selected($mode, 'random', false) . '>Show Random Line</option>
          </select>';
}

// Callback function for the layout order field
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

function advanced_preloader_custom_css_field()
{
    $options = get_option('advanced_preloader_advanced');
    $css = isset($options['custom_css']) ? $options['custom_css'] : '';
    echo '<textarea name="advanced_preloader_advanced[custom_css]" rows="5" cols="50">' . esc_textarea($css) . '</textarea>';
}

function advanced_preloader_display()
{
    // Get the settings from the database
    $general_options = get_option('advanced_preloader_general', []);
    $enabled = isset($general_options['enabled']) ? $general_options['enabled'] : '0';

    // Only display the preloader if it is enabled
    if ($enabled == '1') {
        $design_options = get_option('advanced_preloader_design', []);
        $animation_options = get_option('advanced_preloader_animation', []);
        $advanced_options = get_option('advanced_preloader_advanced', []);
        $preloader_type = isset($general_options['type']) ? $general_options['type'] : 'image';

        // General settings
        $preloader_image = isset($general_options['image']) ? $general_options['image'] : '';
        $preloader_text = isset($general_options['text']) ? $general_options['text'] : 'Loading...';
        $layout_order = isset($general_options['layout_order']) ? $general_options['layout_order'] : 'image-over-text';

        // Design settings
        $bg_color = isset($design_options['bg_color']) ? $design_options['bg_color'] : '#ffffff';
        $text_color = isset($design_options['text_color']) ? $design_options['text_color'] : '#000000';

        // Animation settings
        $delay_time = isset($animation_options['delay_time']) ? $animation_options['delay_time'] : '0s';
        $animation_speed = isset($animation_options['animation_speed']) ? $animation_options['animation_speed'] : 'slow';

        // Advanced settings (custom CSS)
        $custom_css = isset($advanced_options['custom_css']) ? $advanced_options['custom_css'] : '';

        // Output the preloader HTML with data attributes and layout class
        echo '<div id="advanced-preloader" 
                  class="' . esc_attr($layout_order) . '"
                  data-delay="' . esc_attr($delay_time) . '" 
                  data-animation-speed="' . esc_attr($animation_speed) . '" 
                  style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';">';

        // Show image only if type is image or both
        if (($preloader_type === 'image' || $preloader_type === 'both') && !empty($preloader_image)) {
            echo '<img src="' . esc_url($preloader_image) . '" alt="Preloader Image" />';
        }

        // Show text only if type is text or both
        if (($preloader_type === 'text' || $preloader_type === 'both') && !empty($preloader_text)) {
            $display_mode = isset($general_options['text_display_mode']) ? $general_options['text_display_mode'] : 'full';

            if ($display_mode === 'random') {
                $lines = array_filter(explode("\n", $preloader_text));
                $random_line = !empty($lines) ? $lines[array_rand($lines)] : $preloader_text;
                echo '<div class="preloader-text">' . wp_kses_post($random_line) . '</div>';
            } else {
                echo '<div class="preloader-text">' . wp_kses_post($preloader_text) . '</div>';
            }
        }

        echo '</div>';
        // Output custom CSS scoped to the preloader
        if (!empty($custom_css)) {
            // Clean the CSS input
            $clean_css = wp_strip_all_tags($custom_css);

            // Split CSS into individual rules
            $rules = array_filter(array_map('trim', explode('}', $clean_css)));

            $scoped_css = '';
            foreach ($rules as $rule) {
                // Skip empty rules and @-rules
                if (empty($rule) || strpos(trim($rule), '@') === 0) {
                    continue;
                }

                // Split into selector and properties
                $parts = explode('{', $rule);
                if (count($parts) < 2) {
                    continue;
                }

                $selector = trim($parts[0]);
                $properties = trim($parts[1]);

                // Handle multiple selectors
                $selectors = array_map('trim', explode(',', $selector));
                $scoped_selectors = [];

                foreach ($selectors as $s) {
                    // Skip keyframe animations and media queries
                    if (strpos($s, '@') === 0) {
                        $scoped_selectors[] = $s;
                        continue;
                    }

                    // Prepend #advanced-preloader unless already nested
                    $scoped_selectors[] = "#advanced-preloader $s";
                }

                $scoped_css .= implode(', ', $scoped_selectors) . " { $properties }\n";
            }

            echo '<style>' . esc_html($scoped_css) . '</style>';
        }
    }
}
add_action('wp_footer', 'advanced_preloader_display');