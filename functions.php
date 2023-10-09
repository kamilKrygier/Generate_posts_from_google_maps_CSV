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

// TODO Merge this plugin with Google maps scrapper (on GitHub)
// TODO Remember to add restrictions to Google Maps API at console.cloud.google.com



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
    }
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

function generateContentWithOpenAI($prompt) {
    echo "<script>jQuery('.kk_spinner_wrapper').fadeIn().css('display', 'flex');</script>";

    $client = new Client(['base_uri' => 'https://api.openai.com/']);

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
                'max_tokens' => 2000
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

// function upload_page_screenshot($URLItem, $pretty_place_name){

//     // Declare image name and temporary path to file
//     $imageName = sanitize_text_field($pretty_place_name);
//     $upload_dir = wp_upload_dir();
//     $temp_filename = $upload_dir['basedir'] . '/'.$pretty_place_name.'.jpg';

//     // Get temporary screenshot
//     if(!empty($URLItem) && filter_var($URLItem, FILTER_VALIDATE_URL))
//         Browsershot::url($URLItem)
//             ->setScreenshotType('jpeg', 86)
//             ->windowSize(1240, 720)
//             ->save($temp_filename);
//     else return false;

//     // Upload the screenshot to the WordPress media library
//     $file_array = array(
//         'name'     => $pretty_place_name. '.jpg',
//         'tmp_name' => $temp_filename
//     );

//     require_once(ABSPATH . 'wp-admin/includes/file.php');
//     require_once(ABSPATH . 'wp-admin/includes/media.php');
//     require_once(ABSPATH . 'wp-admin/includes/image.php');

//     $attachment_id = media_handle_sideload($file_array, 0);
    
//     if (is_wp_error($attachment_id)) {
//         @unlink($temp_filename);
//         return false;
//     }

//     $screenshotImageArray = array($attachment_id, wp_get_attachment_url($attachment_id));

//     // Return the URL of the uploaded image
//     return $screenshotImageArray;
// }