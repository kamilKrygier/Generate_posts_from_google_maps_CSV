<?php
/**
 * Plugin Name: SIXSILVER CSV to Posts plugin
 * Description: Import posts from a CSV file.
 * Version: 1.0
 * Author: Kamil Krygier
 * Author URI: https://www.linkedin.com/in/kamil-krygier-132940166
 * Text Domain: sixsilver-csv-to-posts
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Spatie\Browsershot\Browsershot;

function ctp_admin_styles_enqueue() {
	wp_enqueue_style( 'ctp_admin_style', plugin_dir_url(__FILE__) . "styles/dist/ctp_admin_style.css");
}
function ctp_styles_enqueue() {
	wp_enqueue_style( 'ctp_style', plugin_dir_url(__FILE__) . "styles/dist/ctp_style.css");
}

if(is_admin()) add_action('admin_enqueue_scripts', 'ctp_admin_styles_enqueue');
    else add_action('wp_enqueue_scripts', 'ctp_styles_enqueue');

// TODO Remember to add restrictions to Google Maps STATIC/PLACES API at console.cloud.google.com after moving it out of localhost



// DEBUG MODE FUNCTION
function debug_log($message) {
    if (WP_DEBUG) {
        $logFile = plugin_dir_path( __FILE__ ) . 'debug.log';
        
        // Check if file exists, if not create it with appropriate permissions
        if (!file_exists($logFile)) {
            touch($logFile);  // The touch() function sets access and modification time of file. If the file does not exist, it will be created.
            chmod($logFile, 0664);  // Set appropriate permissions
        }
        
        $current = file_get_contents($logFile);
        $current .= $message . "\n";  // Append received message to file content
        file_put_contents($logFile, $current);        
    };
}


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
        'google-places-scrapper',
        'google_places_scrapper'
    );

}
add_action('admin_menu', 'csv_to_posts_menu');


// Batch action - generate content with AI
function generateContentWithOpenAIBatchAction($actions) {
    $actions['generate_content_with_openai'] = 'Generate content with AI'; // Change 'Generate content with openai' to your desired action label
    return $actions;
}
add_filter('bulk_actions-edit-post', 'generateContentWithOpenAIBatchAction');


// Handle the Generate content with openai
function generateContentWithOpenAIBatchActionCallback() {
    $request_action = sanitize_text_field($_REQUEST['action']);
    if ($request_action === 'generate_content_with_openai') {
        $post_ids = (isset($_REQUEST['post']) && is_array($_REQUEST['post']) && array_map('intval', $_REQUEST['post'])) ? $_REQUEST['post'] : array();
        
        // Split the post IDs into batches of ...
        $batches = array_chunk($post_ids, 5);

        foreach ($batches as $batch){
            
            debug_log("BATCH STARTED - UPDATE POST WITH AI GENERATED CONTENT");
            foreach ($batch as $post_id) {
                if(!get_post_meta($post_id, 'ai_genrated_content', true)){
                    debug_log("Working on post with ID=$post_id");

                    // Get the post object by post ID
                    $post = get_post($post_id);

                    if($post instanceof WP_Post){
                        // Extract the post content from the post object
                        $post_content = apply_filters('the_content', $post->post_content);
                        $post_title = apply_filters('the_title', $post->post_title);

                        // MAKE OPENAI API CALL
                        $prompt = "Podane dane: $post_content
                        
                        Bazując na podanych danych, przygotuj opis na stronę internetową (pisz w trzeciej osobie liczby pojedynczej języka polskiego) w HTML (bez tagów doctype,head). Opis powinien być podzielony na konkretne działy (nagłówki h2):
                        - Informacje ogólne (Napisz 100 słów opisu o firmie, w którym zawrzesz informacje o ewentualnym asortymencie, obsłudze, lokalizacji, itd.),
                        - Podsumowanie opinii (podsumuj opinie od klientów i bazując na nich wykonaj podsumowanie firmy, czyli napisz parę słów o tym, o czym ludzie mówią w tych opiniach),
                        - Lokalizacja (Opowiedz więcej o okolicy w pobliżu podanego adresu firmy),
                        - Kontakt (Zachęć do kontaktu z firmą poprzez numer telefonu (jeśli podano), stronę internetową (jeśli podano) oraz osobiste odwiedziny pod podanym adresem (podaj adres)).
                        ";

                        $generated_post_content = generateContentWithOpenAI($prompt, 2200);

                        if(!empty($generated_post_content)){
                            debug_log("AI content has been generated for post with ID=$post_id");
                            $post_content .= $generated_post_content;
                            $post_updated = wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));

                            if(is_wp_error( $post_updated )) debug_log("There was an error while updating post");
                            else{
                                debug_log("Post with ID=$post_id has been updated");

                                // If AI generated content was added to post, than add info to post that it already contains AI generated content
                                add_post_meta( $post_id, 'ai_genrated_content', true);
                            }
                            
                        }
                    }
                } else debug_log("Post with ID=$post_id, already has AI generated content!");
            }
            debug_log("BATCH ENDED");
            set_transient('batch_process_generate_articles_content_complete', true, 5 * MINUTE_IN_SECONDS);
        }
    }
}
add_action('admin_action_generate_content_with_openai', 'generateContentWithOpenAIBatchActionCallback');

function display_batch_process_notice() {
    if (get_transient('batch_process_complete')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Batch process completed successfully!', 'your-text-domain'); ?></p>
        </div>
        <?php
        // Don't forget to delete the transient so that the message isn't shown on subsequent page loads
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


// Scrape Google Places
function google_places_scrapper(){
    include_once('maps_scrape.php');

    // If form not submitted, show the form
    echo '<h2>Map Scrapper</h2>';
    echo '<form method="post" action="">'; // Post to the same page
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


// Improved sanitizeForCsv function
function sanitizeForCsv($string) {
    $string = trim($string, '"');  // Remove surrounding double quotes
    
    // Remove newline characters and carriage returns
    $string = str_replace(["\n", "\r"], ' ', $string);

    // Escape double quotes
    $string = str_replace('"', '""', $string);

    return $string;
}


function signUrl($url, $secret){

    // parse the URL
    $parsedUrl = parse_url($url);
    // construct the URL to be signed
    $urlToSign = $parsedUrl['path'] . "?" . $parsedUrl['query'];
    
    // decode the private key into its binary format
    $decodedKey = str_replace(['-', '_'], ['+', '/'], $secret);
    $decodedKey = base64_decode($decodedKey);
    
    // create a signature using the private key and the URL-encoded string using HMAC SHA1
    $signature = hash_hmac('sha1', $urlToSign, $decodedKey, true);
    
    // encode the signature into base64 for use within a URL
    $encodedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $url . "&signature=" . $encodedSignature;

}


function generateContentWithOpenAI($prompt, $maxTokens) {
    debug_log("~Begin content generation~");
    // echo "<script>jQuery('.kk_spinner_wrapper').fadeIn().css('display', 'flex');</script>";

    $client = new Client(['base_uri' => 'https://api.openai.com/']);

    debug_log("~Current prompt:\n $prompt\n");

    try {
        $response = $client->post('v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens
            ]
        ]);

        $body = $response->getBody();
        $content = json_decode($body, true);
        debug_log("Total tokens used (for current post): " . $content['usage']['total_tokens']);

        return $content['choices'][0]['message']['content'] ?? null;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        debug_log($e->getMessage());
        return null;
    }
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

function download_image_to_media_library($path){
    // Ensure the required functions are available
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Download the image from the source URL
    $temp_file = download_url($signedUrl);

    // Check if there was an error
    if (is_wp_error($temp_file)) {
        throw new Exception($temp_file->get_error_message());
    }

    // Set up the array of arguments for wp_insert_attachment()
    $file_args = array(
        'name'     => basename($signedUrl),
        'type'     => wp_check_filetype(basename($signedUrl), null)['type'],
        'file'     => $temp_file,
        'post_title'     => basename($signedUrl),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insert the attachment
    $attachment_id = wp_insert_attachment($file_args, $temp_file);

    // Check for an error
    if (is_wp_error($attachment_id)) {
        @unlink($temp_file);
        throw new Exception($attachment_id->get_error_message());
    }

    // Generate attachment metadata
    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $temp_file);
    wp_update_attachment_metadata($attachment_id, $attachment_metadata);

    // return URL of the uploaded image
    return wp_get_attachment_url($attachment_id);

}