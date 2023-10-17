<?php

// VARIABLES
$batch_size = 10;
$placeholder_id = 16;  // Replace with placeholder ID or page screenshot


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // echo "<script>jQuery('.kk_spinner_wrapper').fadeIn();</script>";

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
            'Opinia 1', 'Opinia 2', 'Opinia 3', 'Opinia 4', 'Opinia 5', 'Opinia 6', 'Typ',
            'Wysokość cen', 'Latitude', 'Longitude'
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
                $phone = $row[array_search('Telefon', $header)];
                $longitudeItem = $row[array_search('Longitude', $header)];
                $latitudeItem = $row[array_search('Latitude', $header)];
                $addressItem = $row[array_search('Adres', $header)];
                $openingHours = $row[array_search( 'Godziny otwarcia', $header)];
                $URLItem = $row[array_search('Strona internetowa', $header)];
                $reviewItems = array(
                    "review_1" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 1', $header)]),
                    "review_2" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 2', $header)]),
                    "review_3" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 3', $header)]),
                    "review_4" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 4', $header)]),
                    "review_5" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 5', $header)]),
                    "review_6" => str_replace(['"', "'"], '', $row[array_search( 'Opinia 6', $header)])
                );
                $typeItem = $row[array_search('Typ', $header)];
                $pricesItem = $row[array_search('Wysokość cen', $header)];

                
                // ------------------------ VALIDATE CSV ITEMS


                // Validate and process Address
                $addressParts = explode(', ', $addressItem);

                $addressParts[0] = isset($addressParts[0]) ? $addressParts[0] : '';
                $addressParts[2] = isset($addressParts[2]) ? $addressParts[2] : '';

                // Prepare the address array
                $addressArray = [
                    'street' => trim($addressParts[0]),
                    'city' => [],
                    'country' => trim($addressParts[2])
                ];

                // Split city details
                $secondFromLast = isset($addressParts[count($addressParts) - 2]) ? trim($addressParts[count($addressParts) - 2]) : '';
                $cityParts = explode(' ', $secondFromLast);
                debug_log("Current cityParts = $secondFromLast");

                $addressArray['city']['post_code'] = isset($cityParts[0]) ? trim($cityParts[0]) : '';
                $addressArray['city']['city_name'] = isset($cityParts[1]) ? trim($cityParts[1]) : '';


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
                if(!filter_var($URLItem ?? '', FILTER_VALIDATE_URL) && $URLItem !== "") continue;

                
                // Parse the URL
                if($URLItem != ""){
                    $parsedURL = parse_url($URLItem);
                    $parsedURL = $parsedURL['scheme'] . '://' . $parsedURL['host'] . $parsedURL['path'];
                }

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
                // TODO prices are integers! Add switch to handle this
                $pricesItem = (!empty($pricesItem)) ? $pricesItem : "Nie podano";


                // Validate Longitude and Latitude and build map URL
                // TODO Save image with URL $signedUrl to media library to do not use API that often :)
                // TODO Restrict this API key later if plugin will be move somewhere else than localhost
                if(!is_numeric($longitudeItem) || !is_numeric($latitudeItem)) {
                    debug_log('Invalid longitude or latitude in one of the rows.');
                    continue;
                }else{
                    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$latitudeItem,$longitudeItem&zoom=18&size=1200x600&scale=2&markers=size:mid|color:red|$latitudeItem,$longitudeItem&key=" . MAPS_STATIC_API_KEY;
                    // debug_log($mapUrl);
                    $signedUrl = signUrl($mapUrl, MAPS_STATIC_API_SECRET);
                }

                // Create pretty place name
                $pretty_place_name = $nazwaItem . ' ' . $addressArray['city']['city_name'] . ', '. $addressArray['street'];

                if(!get_page_by_path(sanitize_title($pretty_place_name), OBJECT, 'post')){

                    // Declare new variable for post content or clear existing one
                    $post_content = "";

                    // GET PAGE SCREENSHOT
                    // TODO Add screenshot functionality (currently replaced by placeholder)
                    // $page_screenshot = upload_page_screenshot($URLItem, $pretty_place_name);


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
                    // echo "<script>jQuery('.kk_spinner_wrapper').fadeOut();</script>";
                    if( $post_id ){

                        debug_log("Post was created with the ID= $post_id");
                        
                        if(isset($page_screenshot) && $page_screenshot) set_post_thumbnail( $post_id, $page_screenshot[0] );
                        else set_post_thumbnail( $post_id, $placeholder_id );

                        echo "Post was created with the ID= $post_id, with attachment with ID= $placeholder_id<br>";

                    } else{
                        debug_log("Failed to create post with ID= $post_id");
                        echo "Failed to create post with ID= $post_id<br>";
                    }
                } else debug_log("Post $pretty_place_name is already created"); 
            }
            debug_log("Processed batch " . ($i + 1));
        }
        debug_log('All batches has been finished!');
        echo "All batches has been finished!<br>";
        return;
    }
}