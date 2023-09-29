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
}
add_action('admin_menu', 'csv_to_posts_menu');


// LET THE MAGIC HAPPEN
function csv_to_posts_upload_page(){


    // VARIABLES
    $batch_size = 10;
    $placeholder_id = 6;  // TODO Replace with placeholder ID or page screenshot


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {

            // Check if it's a CSV file
            $fileType = mime_content_type($_FILES['csv_file']['tmp_name']);
            $validMimeTypes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/comma-separated-values', 'text/x-comma-separated-values'];

            if (!in_array($fileType, $validMimeTypes)) {
                debug_log('Please upload a valid CSV file.');
                return;
            }

            // Parse the CSV
            $csvRows = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
            $header = array_shift($csvRows);

            $totalRows = count($csvRows);
            $batches = ceil($totalRows / $batch_size);
            debug_log("Total Rows: $totalRows");
            debug_log("Processing in $batches batches of $batch_size");

            $requiredColumns = [
                'Nazwa', 'Telefon', 'Adres', 'Godziny otwarcia', 'Strona internetowa',
                'Opinia 1', 'Opinia 2', 'Opinia 3', 'Opinia 4', 'Opinia 5', 'Typ',
                'Wysokość cen', 'longitude', 'latitude'
            ];
            
            foreach($requiredColumns as $column) {
                if(!in_array($column, $header)) {
                    debug_log("Missing required column: $column");
                    return;
                }
            }

            for ($i = 0; $i < $batches; $i++) {

                $batchRows = array_splice($csvRows, 0, $batch_size);
                debug_log("Processing batch " . ($i + 1));   

                foreach ($batchRows as $row) {

                    // Set variables from CSV items 
                    $nazwaItem = str_replace(['"', "'"], '',$row[array_search('Nazwa', $header)]); // Remove both " and '
                    $phone = $row[array_search('Telefon', $header)]; // TODO Check if Google validates phone number on upload. There could be no reason to do it twice
                    $longitudeItem = $row[array_search('longitude', $header)];
                    $latitudeItem = $row[array_search('latitude', $header)];
                    $addressItem = $row[array_search('Adres', $header)];
                    $openingHours = $row[array_search( 'Godziny otwarcia', $header)];
                    $URLItem = $row[array_search('Strona internetowa', $header)];
                    $reviewItems = array(
                        "review_1" => $row[array_search( 'Opinia 1', $header)],
                        "review_2" => $row[array_search( 'Opinia 2', $header)],
                        "review_3" => $row[array_search( 'Opinia 3', $header)],
                        "review_4" => $row[array_search( 'Opinia 4', $header)],
                        "review_5" => $row[array_search( 'Opinia 5', $header)],
                        "review_6" => $row[array_search( 'Opinia 6', $header)]
                    );
                    $typeItem = $row[array_search('Typ', $header)];
                    $pricesItem = $row[array_search('Wysokość cen', $header)];

                    
                    // ------------------------ VALIDATE CSV ITEMS


                    // Validate and process Address
                    $addressParts = explode(', ', $addressItem);

                    // Prepare the address array
                    $addressArray = [
                        'street' => trim($addressParts[0]),
                        'city' => [],
                        'country' => trim($addressParts[2])
                    ];

                    // Split city details
                    $cityParts = explode(' ', trim($addressParts[1]));
                    $addressArray['city']['post_code'] = trim($cityParts[0]);
                    $addressArray['city']['city_name'] = trim($cityParts[1]);


                    // Validate opening hours
                    $openingHoursParts = explode(',', $openingHours);
                    if(empty($openingHoursParts) || empty($openingHoursParts[0])) $pretty_opening_hours = "Nie podano";
                        else{
                            // $pretty_opening_hours = $openingHoursParts;
                            $pretty_opening_hours = "<ul>";
                                foreach($openingHoursParts as $openingHoursElement){
                                    $pretty_opening_hours .= "<li><span>$openingHoursElement</span></li>";
                                }
                            $pretty_opening_hours .= "</ul>";
                        }

                    // Validate page URL
                    if(!filter_var($URLItem, FILTER_VALIDATE_URL)) return;
                    

                    // Validate Type of business
                    $typeItemParts = explode(',', $typeItem);
                    switch($typeItemParts[0]){
                        case 'jewelry_store':
                            $businessCategory = 'Salon jubilerski';
                            break;
                        default:
                            $businessCategory = 'Inne';
                    }
                    

                    // Validate prices
                    $pricesItem = (!empty($pricesItem)) ? $pricesItem : "Nie podano";


                    // Validate Longitude and Latitude and build map URL
                    if(!is_numeric($longitudeItem) || !is_numeric($latitudeItem)) {
                        debug_log('Invalid longitude or latitude in one of the rows.');
                        return;
                    }else{
                        $url = "https://maps.googleapis.com/maps/api/staticmap?center=$longitudeItem,$latitudeItem&zoom=18&size=1200x600&scale=2&markers=size:mid|color:red|$longitudeItem,$latitudeItem&key=" . MAPS_STATIC_API_KEY;
                        $signedUrl = signUrl($url, MAPS_STATIC_API_SECRET);
                    }


                    // ------------------------ GENERATE SINGLE-POST
                        /** ------------ // TODO MILESTONES
                         * 
                         * Handle Post Creation + AI 
                         * Implement Screenshot Logic
                         * 
                         */


                    // Create pretty place name
                    $pretty_place_name = $nazwaItem . ' ' . $addressArray['city']['city_name'] . ', '. $addressArray['street'];


                    // MAKE OPENAI API CALL
                    // $post_content = generateArticle($nazwaItem, $longitudeItem, $latitudeItem, $addressArray, $openingHours, $reviewItems, $businessCategory, $pricesItem);
                    $prompt = " Przygotuj mi artykuł wordpress (składnia edytora Gutenberg) w języku polskim, pod pozycjonowanie w Google. Musi zawierać przynajmniej 800 słów oraz być podzielony na nagłówki (h2 oraz h3, ponieważ tytuł nie będzie generowany przez AI), paragrafy oraz sekcję FAQ. Bazuj na podanych zmiennych:
                                Nazwa firmy: $nazwaItem,
                                Długość geogreficzna: $longitudeItem,
                                Szerokość geograficzna: $latitudeItem,
                                Adres: " . $addressArray['street'] . ', ' . $addressArray['city']['post_code'] . ' ' . $addressArray['city']['city_name'] . ', ' . $addressArray['country'] . ",
                                Godziny otwarcia: " . $openingHours . " (ale nie wyświetlaj dodatkowo ich na stronie),
                                Adres strony internetowej: $URLItem,
                                Opinie klientów: " . implode(", ", $reviewItems) . ",
                                Kategoria biznesu: $businessCategory,
                                Przedział cenowy produktow: $pricesItem";
                    

                    // Generate post content with OpenAI
                    $generated_post_content = generateContentWithOpenAI($prompt);


                    if($generated_post_content){

                        // Declare new variable for post content or clear existing one
                        $post_content = "";

                        // GET PAGE SCREENSHOT
                        $page_screenshot = upload_page_screenshot($URLItem, $pretty_place_name);


                        // INCLUDE POST CONTENT
                        include('post-content.php');


                        // INSERT POST
                        // Check if the category exists
                        $category_exists = term_exists($businessCategory, 'category'); 
                        
                        // If it doesn't exist, create it
                        if (!$category_exists) {
                            wp_insert_term(
                                $businessCategory, // the term 
                                'category', // the taxonomy
                                array(
                                    'slug' => sanitize_title($businessCategory)
                                )
                            );
                        }

                        $catID = get_cat_ID ( $businessCategory );

                        $post_data = array(
                            'post_title'        => $pretty_place_name,
                            'post_content'      => $post_content,
                            'post_status'       => 'publish',
                            'post_type'         => 'post',
                            'post_author'       => get_current_user_id(),
                            'post_category'     => array($catID),
                            'comment_status'    => 'closed',
                        );
                        
                        // Insert the post and get the post ID
                        $post_id = wp_insert_post( $post_data );
                        echo "<script>jQuery('.kk_spinner_wrapper').fadeOut();</script>";
                        if( $post_id ){

                            debug_log("Post was created with the ID= $post_id");
                            
                            if($snap) set_post_thumbnail( $post_id, $snap[0] );
                            else set_post_thumbnail( $post_id, $placeholder_id );

                            echo "Post was created with the ID= $post_id, with attachment with ID= $placeholder_id";

                        } else{
                            debug_log("Failed to create post with ID= $post_id");
                            echo "Failed to create post with ID= $post_id";
                        }
                    } else debug_log("OpenAI doesn't returned post content.");
                }
                debug_log("Processed batch " . ($i + 1));
            }
            debug_log('All batches has been finished!');
            return;
        }
    }


    // SHOW UPLOAD BUTTON
    echo '<h1>CSV to Posts</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" /><br>';
    echo '<input type="submit" value="Upload" />';
    echo '</form>';
    echo '<div class="kk_spinner_wrapper"><div class="kk_spinner"></div></div>';
    echo '<style>.kk_spinner_wrapper{display: none; position: fixed;width: 100vw;height: 100vh;z-index:99999;justify-content: center;align-items: center;gap:2em;left: 0;top: 0;right: 0;bottom: 0;background-color: rgba(255,255,255,.8);}.kk_spinner{display: grid;place-items: center;width: 150px;height: 150px;border-radius: 50%;background: conic-gradient(from 180deg at 50% 50%,rgba(82, 0, 255, 0) 0deg,#5200ff 360deg);animation: spin 2s infinite linear;}.kk_spinner::before {content: "";border-radius: 50%;width: 80%;height: 80%;background-color: #FFF;}@keyframes spin {to {transform: rotate(1turn);}}</style>';

    // TODO Check Plugin (Google maps scrapper) on GitHub and combine all (remember to enable Places API)
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
                'max_tokens' => 1024
            ]
        ]);

        $body = $response->getBody();
        $content = json_decode($body, true);

        return $content['choices'][0]['message']['content'] ?? null;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        debug_log($e->getMessage());
        return null;
    }
}

function upload_page_screenshot($URLItem, $pretty_place_name){

    // Declare image name and temporary path to file
    $imageName = sanitize_text_field($pretty_place_name);
    $upload_dir = wp_upload_dir();
    $temp_filename = $upload_dir['basedir'] . '/'.$pretty_place_name.'.jpg';

    // Get temporary screenshot
    if(!empty($URLItem) && filter_var($URLItem, FILTER_VALIDATE_URL))
        Browsershot::url($URLItem)
            ->setScreenshotType('jpeg', 86)
            ->windowSize(1240, 720)
            ->save($temp_filename);
    else return false;

    // Upload the screenshot to the WordPress media library
    $file_array = array(
        'name'     => $pretty_place_name. '.jpg',
        'tmp_name' => $temp_filename
    );

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_sideload($file_array, 0);
    
    if (is_wp_error($attachment_id)) {
        @unlink($temp_filename);
        return false;
    }

    $screenshotImageArray = array($attachment_id, wp_get_attachment_url($attachment_id));

    // Return the URL of the uploaded image
    return $screenshotImageArray;
}