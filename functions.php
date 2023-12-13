<?php
/**
 * Plugin Name: CSV to Posts plugin
 * Description: Import posts from a CSV file.
 * Version: 1.0
 * Author: Kamil Krygier
 * Author URI: https://www.linkedin.com/in/kamil-krygier-132940166
 * Text Domain: csv-to-posts
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require 'vendor/autoload.php';
require 'includes/class-handle-api-keys.php';
require 'includes/class-utils.php';
require_once 'includes/admin-notices.php';
require_once('includes/class-handle-ai-post-generation.php');

// Don't forget to install GuzzleHttp
use GuzzleHttp\Client;

function ctp_admin_styles_enqueue() {
	wp_enqueue_style( 'ctp_admin_style', plugin_dir_url(__FILE__) . "styles/dist/ctp_admin_style.css");
}
function ctp_styles_enqueue() {
	wp_enqueue_style( 'ctp_style', plugin_dir_url(__FILE__) . "styles/dist/ctp_style.css");
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

// TODO remove bellow commented code when batch action 'generate_content_with_openai' works properly
// Handle the Generate content with openai
// function generateContentWithOpenAIBatchActionCallback() {
//     $request_action = sanitize_text_field($_REQUEST['action']);
//     if ($request_action === 'generate_content_with_openai') {
//         $post_ids = (isset($_REQUEST['post']) && is_array($_REQUEST['post']) && array_map('intval', $_REQUEST['post'])) ? $_REQUEST['post'] : array();
        
//         // Split the post IDs into batches of 5
//         $batches = array_chunk($post_ids, 5);

//         foreach ($batches as $batch){
            
//             Utils::debug_log("BATCH STARTED - UPDATE POST WITH AI GENERATED CONTENT");
//             foreach ($batch as $post_id) {
//                 if(!get_post_meta($post_id, 'ai_genrated_content', true)){
//                     Utils::debug_log("Working on post with ID=$post_id");

//                     // Get the post object by post ID
//                     $post = get_post($post_id);

//                     if($post instanceof WP_Post){
//                         // Extract the post content from the post object
//                         $post_content = apply_filters('the_content', $post->post_content);
//                         $post_title = apply_filters('the_title', $post->post_title);

//                         // MAKE OPENAI API CALL
//                         $prompt = "Podane dane: $post_content
                        
//                         Bazując na podanych danych, przygotuj opis na stronę internetową (pisz w trzeciej osobie liczby pojedynczej języka polskiego) w HTML (bez tagów doctype,head). Opis powinien być podzielony na konkretne działy (nagłówki h2):
//                         - Informacje ogólne (Napisz 100 słów opisu o firmie, w którym zawrzesz informacje o ewentualnym asortymencie, obsłudze, lokalizacji, itd.),
//                         - Podsumowanie opinii (podsumuj opinie od klientów i bazując na nich wykonaj podsumowanie firmy, czyli napisz parę słów o tym, o czym ludzie mówią w tych opiniach),
//                         - Lokalizacja (Opowiedz więcej o okolicy w pobliżu podanego adresu firmy),
//                         - Kontakt (Zachęć do kontaktu z firmą poprzez numer telefonu (jeśli podano), stronę internetową (jeśli podano) oraz osobiste odwiedziny pod podanym adresem (podaj adres)).
//                         ";

//                         $generated_post_content = AI_Generate_Post::generateContentWithOpenAI($prompt, 2200);

//                         if(!empty($generated_post_content)){
//                             Utils::debug_log("AI content has been generated for post with ID=$post_id");
//                             $post_content .= $generated_post_content;
//                             $post_updated = wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));

//                             if(is_wp_error( $post_updated )) Utils::debug_log("There was an error while updating post");
//                             else{
//                                 Utils::debug_log("Post with ID=$post_id has been updated");

//                                 // If AI generated content was added to post, than add info to post that it already contains AI generated content
//                                 add_post_meta( $post_id, 'ai_genrated_content', true);
//                             }
                            
//                         }
//                     }
//                 } else Utils::debug_log("Post with ID=$post_id, already has AI generated content!");
//             }
//             Utils::debug_log("BATCH ENDED");
//             set_transient('batch_process_generate_articles_content_complete', true, 5 * MINUTE_IN_SECONDS);
//         }
//     }
// }
// add_action('admin_action_generate_content_with_openai', 'generateContentWithOpenAIBatchActionCallback');

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
    echo '<h1>CSV to Posts</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" /><br>';
    echo '<input type="submit" value="Upload" />';
    echo '</form>';
    echo '<div class="kk_spinner_wrapper"><div class="kk_spinner"></div></div>';
    echo '<style></style>';

}


// Scrape Google Places page
function google_places_scrapper_page(){

    include_once('maps_scrape.php');

    // If form not submitted, show the form
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
    echo '<div class="kk_spinner_wrapper"><div class="kk_spinner"></div></div>';
}

// Settings page
function settings_page(){

    echo "<h2>".__('Settings', 'default')."</h2>";
    echo "<form method='post' action=''>"; 
    echo "<label>Google MAPS STATIC API KEY \n<input type='text' placeholder='Here place your api key' name='MAPS_STATIC_API_KEY'></label><br>";
    echo "<label>Google MAPS STATIC API SECRET \n<input type='text' placeholder='Here place your api key' name='MAPS_STATIC_API_SECRET'></label><br>";
    echo "<label>Google PLACES API KEY \n<input type='text' placeholder='Here place your api key' name='GOOGLE_PLACES_API_KEY'></label><br>";
    echo "<label>OpenAI API KEY \n<input type='text' placeholder='Here place your api key' name=''></label><br>";
    echo "<label>Placeholder image URL: \n<input type='text' placeholder='Here place your placeholder image URL' name='ctp_placeholder_image'></label><br>";
    echo "<input type='submit' name='submit' value='".__('Save', 'default')."'>";
    echo "</form>";

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

// TODO check if posts gets ai generated content
add_action('run_ai_generation_for_posts', function(){

    AI_Generate_Post::handle_ai_generation_for_posts(true, array());

});

// TODO check if batch action works
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
    
    $api_key = Handle_API_keys::get_API_key('MAPS_STATIC_API_KEY') ? Handle_API_keys::get_API_key('MAPS_STATIC_API_KEY') : '';
    $api_secret = Handle_API_keys::get_API_key('MAPS_STATIC_API_SECRET') ? Handle_API_keys::get_API_key('MAPS_STATIC_API_SECRET') : '';

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