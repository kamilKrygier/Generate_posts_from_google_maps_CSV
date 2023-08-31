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
                
                $longitudeIndex = array_search('longitude', $header);
                $latitudeIndex = array_search('latitude', $header);
                
                if(!is_numeric($row[$longitudeIndex]) || !is_numeric($row[$latitudeIndex])) {
                    echo "Invalid longitude or latitude in one of the rows.";
                    return;
                }
                
                // TODO Add other validations as required
                // TODO The logic to create posts goes here...
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