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

function csv_to_posts_upload_page(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {

            // Check if it's a CSV file
            $fileType = mime_content_type($_FILES['csv_file']['tmp_name']);
            $validMimeTypes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/comma-separated-values', 'text/x-comma-separated-values'];

            if (!in_array($fileType, $validMimeTypes)) {
                echo 'Please upload a valid CSV file.';
                return;
            }

            // Parse the CSV
            $csvRows = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
            $header = array_shift($csvRows);

            $requiredColumns = [
                'Nazwa', 'Telefon', 'Adres', 'Godziny otwarcia', 'Strona internetowa',
                'Opinia 1', 'Opinia 2', 'Opinia 3', 'Opinia 4', 'Opinia 5', 'Typ',
                'Wysokość cen', 'longitude', 'latitude'
            ];
            
            foreach($requiredColumns as $column) {
                if(!in_array($column, $header)) {
                    echo "Missing required column: $column";
                    return;
                }
            }
            
            // Validate data for each row if needed.
            foreach ($csvRows as $row) {

                // Set variables from CSV items 
                $nazwaItem = str_replace(['"', "'"], '',$row[array_search('Nazwa', $header)]); // Remove both " and '
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
                    "review_5" => $row[array_search( 'Opinia 5', $header)]
                );
                $typeItem = $row[array_search('Typ', $header)];
                $pricesItem = $row[array_search('Wysokość cen', $header)];


                // TODO Check if Google validates phone number on upload. There could be no reason to do it twice

                
                // ------------------------ VALIDATE CSV ITEMS


                // Validate and process Address
                $parts = explode(', ', $addressItem);

                // Prepare the address array
                $addressArray = [
                    'precise' => trim($parts[0]),
                    'city' => [],
                    'country' => trim($parts[2])
                ];

                // Split city details
                $cityParts = explode(' ', trim($parts[1]));
                $addressArray['city']['post_code'] = trim($cityParts[0]);
                $addressArray['city']['city_name'] = trim($cityParts[1]);


                // Validate opening hours
                $openingHoursParts = explode(',', $openingHours);
                if(empty($openingHoursParts) || empty($openingHoursParts[0])){
                    unset($openingHoursParts);
                    $openingHours = "Nie podano";
                }else{
                    $openingHours = $openingHoursParts;
                    unset($openingHoursParts);
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


                // Validate Longitude and Latitude
                if(!is_numeric($longitudeItem) || !is_numeric($latitudeItem)) {
                    echo "Invalid longitude or latitude in one of the rows.";
                    return;
                }


                // ------------------------ GENERATE SINGLE-POST
                    // ------------ STEPS TO DO
                        /**
                         * 
                         * Handle Post Creation
                         * Implement Selenium and Screenshot Logic
                         * Implement the Google Maps API
                         * 
                         */


            }
            
            echo 'Posts imported successfully!';
            return;
        }
    }

    echo '<h1>CSV to Posts</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" /><br>';
    echo '<input type="submit" value="Upload" />';
    echo '</form>';
}