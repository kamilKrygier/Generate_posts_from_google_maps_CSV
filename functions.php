<?php
/**
 * Plugin Name: CSV to Posts plugin
 * Description: Import posts from a CSV file and scrape Google places. REMEMBER TO INSTALL GUZZLEHTTP (composer require guzzlehttp/guzzle). Also important thing is to increase memory_limit=512M (can be decreased to 256M if not scraping Google Places) and max_execution_time (Hard to say how long execution time should be. It depends of maps scraping and CSVs size. When scraping or importing CSVs just set it to 600).
 * Version: 1.0
 * Author: Kamil Krygier
 * Author URI: https://github.com/kamilKrygier/
 * Text Domain: csv-to-posts
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// TODO check if db options with api keys will be deleted, script will create new ones 

// require 'vendor/autoload.php';
require 'includes/class-handle-api-keys.php';
require 'includes/class-utils.php';
require_once 'includes/admin-notices.php';
require_once 'includes/class-handle-ai-post-generation.php';

// Don't forget to install GuzzleHttp
// use GuzzleHttp\Client;

function ctp_admin_styles_enqueue() {
	wp_enqueue_style( 'ctp_admin_style', plugin_dir_url(__FILE__) . "assets/styles/dist/ctp_admin_style.css");
    wp_enqueue_script( 'ctp_js_script', plugin_dir_url(__FILE__) . "assets/js/ctp-js-script.js", array( 'jquery' ) );
}
function ctp_styles_enqueue() {
	wp_enqueue_style( 'ctp_style', plugin_dir_url(__FILE__) . "assets/styles/dist/ctp_style.css"); 
}

if(is_admin()) add_action('admin_enqueue_scripts', 'ctp_admin_styles_enqueue');
    else add_action('wp_enqueue_scripts', 'ctp_styles_enqueue');

// Remember to add restrictions to Google Maps STATIC/PLACES API at console.cloud.google.com after moving it out of localhost

// CREATE PAGE IN WORDPRESS ADMIN
function csv_to_posts_menu(){
    add_menu_page(
        'CSV to Posts',
        'CSV to Posts',
        'manage_options',
        'csv-to-posts',
        'csv_to_posts_upload_page'
    );

    add_submenu_page(
        'csv-to-posts',
        'Google Places Scrapper',
        'Google Places Scrapper',
        'manage_options',
        'ctp-google-places-scrapper',
        'google_places_scrapper_page'
    );

    add_submenu_page(
        'csv-to-posts',
        'Settings',
        'Settings',
        'manage_options',
        'ctp-settings',
        'settings_page'
    );

}
add_action('admin_menu', 'csv_to_posts_menu');


// Batch action - generate content with AI
function generateContentWithOpenAIBatchAction($actions) {
    $actions['generate_content_with_openai'] = 'Generate content with AI'; // Change 'Generate content with openai' to your desired action label
    return $actions;
}
add_filter('bulk_actions-edit-post', 'generateContentWithOpenAIBatchAction');

function display_batch_process_notice() {
    if (get_transient('batch_process_complete')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Batch process completed successfully!', 'your-text-domain'); ?></p>
        </div>
        <?php
        delete_transient('batch_process_complete');
    }
}
add_action('admin_notices', 'display_batch_process_notice');


// Upload posts from CSV
function csv_to_posts_upload_page(){
    include_once('upload_from_csv.php');
    
    // SHOW UPLOAD BUTTON
    echo '<div class="ctp_page">';
    echo '<h1>CSV to Posts</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" /><br>';
    echo '<input type="submit" value="Upload" />';
    echo '</form>';
    echo '<style></style>';
    echo '</div>';

}


// Scrape Google Places page
function google_places_scrapper_page(){

    include_once('maps_scrape.php');

    echo '<div class="ctp_page">';
    echo '<h2>Map Scrapper</h2>';
    echo '<form method="post" action="">'; 
    echo '<label for="place_category">Choose a category:</label>';
    echo '<select name="place_category" id="place_category">';
    foreach ($place_category as $category) {
        echo "<option value=\"$category\">$category</option>";
    }
    echo '</select><br>';
    echo '<input type="submit" name="submit" value="Scrape CSV from Google Places">';
    echo '</form>';
    echo '</div>';
}

// Settings page
function settings_page(){

    echo '<div class="ctp_page">';
    echo "<h2>".__('Settings', 'default')."</h2>";
    echo "<form method='post' action=''>"; 
    echo "<label>Google MAPS STATIC API KEY \n<input type='text' placeholder='Here place your api key' name='MAPS_STATIC_API_KEY'></label><br>";
    echo "<label>Google MAPS STATIC API SECRET \n<input type='text' placeholder='Here place your api key' name='MAPS_STATIC_API_SECRET'></label><br>";
    echo "<label>Google PLACES API KEY \n<input type='text' placeholder='Here place your api key' name='GOOGLE_PLACES_API_KEY'></label><br>";
    echo "<label>OpenAI API KEY \n<input type='text' placeholder='Here place your api key' name='OPENAI_API_KEY'></label><br>";
    echo "<label>Placeholder image URL: \n<input type='text' placeholder='Here place image URL' name='ctp_placeholder_image'></label><br>";
    echo "<input type='submit' name='submit' value='".__('Save', 'default')."'>";
    echo "</form>";
    echo '</div>';

}

// Add a new /wp-admin/edit.php?mode=list column header
function add_ai_generated_column_header($columns) {
    $columns['ai_generated'] = 'AI Generated'; // 'AI Generated' is the column title
    return $columns;
}
add_filter('manage_posts_columns', 'add_ai_generated_column_header');

// Display data in the new /wp-admin/edit.php?mode=list column
function add_ai_generated_column_content($column_name, $post_id) {
    if ('ai_generated' === $column_name) {
        $is_ai_generated = get_post_meta($post_id, 'ai_genrated_content', true);

        if ($is_ai_generated) {
            echo 'AI generated';
        } else {
            echo '—';
        }
    }
}
add_action('manage_posts_custom_column', 'add_ai_generated_column_content', 10, 2);

// TODO check if posts gets ai generated content using cron
add_action('run_ai_generation_for_posts', function(){

    AI_Generate_Post::handle_ai_generation_for_posts(true, array());

});

add_action('admin_action_generate_content_with_openai', function(){

    $request_action = sanitize_text_field($_REQUEST['action']);

    if ($request_action === 'generate_content_with_openai') 
        if(isset($_REQUEST['post']) && is_array($_REQUEST['post']) && array_map('intval', $_REQUEST['post'])) 
            AI_Generate_Post::handle_ai_generation_for_posts(false, $_REQUEST['post']);

});

function custom_cron_job_recurrence($schedules){
    $schedules['every_hour'] = array(
        'interval'  => 3600,
        'display'   => 'Every Hour'
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_job_recurrence');

if (!wp_next_scheduled('run_ai_generation_for_posts')) {
    wp_schedule_event(time(), 'every_hour', 'run_ai_generation_for_posts');
}

function ctp_map_image_shortcode($atts) {

    $api_key = Handle_API_keys::get_API_key('MAPS_STATIC_API_KEY');
    $api_secret = Handle_API_keys::get_API_key('MAPS_STATIC_API_SECRET');

    if($api_key == ''){
        Utils::debug_log("Received empty API KEY (MAPS_STATIC_API_KEY)");
        return false;
    }

    if($api_secret == ''){
        Utils::debug_log("Received empty API KEY SECRET (MAPS_STATIC_API_SECRET)");
        return false;
    }

    $atts = shortcode_atts(
        array(
            'center' => '50.294329,18.663958', // default location if none provided
            'alt' => '', // default alt
        ),
        $atts
    );

    // Get Google Static Map Image
    $map_url = "https://maps.googleapis.com/maps/api/staticmap?center={$atts['center']}&zoom=18&size=1200x600&scale=2&markers=size:mid|color:red|{$atts['center']}&key={$api_key}";
    $signedUrl = Utils::signUrl($map_url, $api_secret);

    return "<img src='{$signedUrl}' alt='{$atts['alt']}'>";

}
add_shortcode('ctp_map_image', 'ctp_map_image_shortcode');

// Set placeholder image on plugin activation if not set
register_activation_hook( __FILE__, array( 'Utils', 'set_placeholder_image_on_plugin_activation' ) );