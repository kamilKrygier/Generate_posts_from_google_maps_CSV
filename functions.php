<?php
/**
 * Plugin Name: SIXSILVER CSV to Posts plugin
 * Description: Import posts from a CSV file.
 * Version: 1.0
 * Author: Kamil Krygier
 * Author URI: https://www.linkedin.com/in/kamil-krygier-132940166
 * Text Domain: sixsilver-csv-to-posts
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// TODO Remember to add restrictions to Google Maps API at console.cloud.google.com


// DEBUG MODE FUNCTION
function debug_log($message) {
    if (WP_DEBUG) {
        $logFile = plugin_dir_path( __FILE__ ) . 'logfile.log';
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


                        // TEMPORARY BATCH TEST
                            // echo 'Name: ' . $nazwaItem . '<br><br>';
                            // echo 'Longitude: ' . $longitudeItem . '<br><br>';
                            // echo 'Latitude: ' . $latitudeItem . '<br><br>';
                            // echo 'Address: ';
                            // var_dump($addressArray);
                            // echo '<br><br>';
                            // if(!is_array($openingHours)) echo 'Opening hours: ' . $openingHours . '<br><br>';
                            // else {
                            //     echo 'Opening hours: ';
                            //     var_dump($openingHours);
                            //     echo '<br><br>';
                            // }
                            // echo 'URL: ' . $URLItem . '<br><br>';
                            // echo 'Reviews: ';
                            // var_dump($reviewItems);
                            // echo '<br><br>';           
                            // echo 'Business Category: ' . $businessCategory . '<br><br>';
                            // echo 'Prices: ' . $pricesItem . '<br><br>';

                    // Create pretty place name
                    $pretty_place_name = $nazwaItem . ' ' . $addressArray['city']['city_name'] . ', '. $addressArray['street'];

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
                    // TODO uncomment below line at the end
                    // $post_id = wp_insert_post( $post_data );
                    
                    if( $post_id ){

                        debug_log("Post was created with the ID= $post_id");
                        set_post_thumbnail( $post_id, $placeholder_id );

                    } else debug_log("Failed to create post.");
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

    // TODO Check Plugin (Google maps scrapper) on GitHub and combine all (remember to enable Places API)

    // TODO Add loading icon while batch in progress
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