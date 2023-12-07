<?php

// VARIABLES
$batch_size = 10;
$placeholder_id = 16;  // Replace with placeholder ID or page screenshot
$Utils = new Utils();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // echo "<script>jQuery('.kk_spinner_wrapper').fadeIn();</script>";

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {

        // Check if it's a CSV file
        $fileType = mime_content_type($_FILES['csv_file']['tmp_name']);
        $validMimeTypes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/comma-separated-values', 'text/x-comma-separated-values'];

        if (!in_array($fileType, $validMimeTypes)) {
            $Utils->debug_log('Please upload a valid CSV file.');
            return;
        }

        // Parse the CSV
        $csvRows = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
        $header = array_shift($csvRows);

        $totalRows = count($csvRows);
        $batches = ceil($totalRows / $batch_size);
        $Utils->debug_log("Total Rows: $totalRows");
        $Utils->debug_log("Processing in $batches batches of $batch_size");

        $requiredColumns = [
            'Nazwa', 'Telefon', 'Adres', 'Godziny otwarcia', 'Strona internetowa',
            'Opinia 1', 'Opinia 2', 'Opinia 3', 'Opinia 4', 'Opinia 5', 'Opinia 6', 'Typ',
            'Wysokość cen', 'Latitude', 'Longitude'
        ];
        
        foreach($requiredColumns as $column) {
            if(!in_array($column, $header)) {
                $Utils->debug_log("Missing required column: $column");
                return;
            }
        }

        for ($i = 0; $i < $batches; $i++) {

            $batchRows = array_splice($csvRows, 0, $batch_size);
            $Utils->debug_log("Processing batch " . ($i + 1));   

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
                $typeItem = (isset($row[array_search('Typ', $header)]) || !empty($row[array_search('Typ', $header)])) ? trim($row[array_search('Typ', $header)]) : '';
                $typeItemParts = explode(',', $typeItem);
                
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
                // $Utils->debug_log("Current cityParts (post code and city name) = $secondFromLast");

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
                    $parsedURL = isset($parsedURL['path']) ? $parsedURL['scheme'] . '://' . $parsedURL['host'] . $parsedURL['path'] : $parsedURL['scheme'] . '://' . $parsedURL['host'];
                }


                // Validate Type of business
                $businessCategoriesMapping = [
                    'accounting' => 'Księgowość',
                    'airport' => 'Lotnisko',
                    'amusement_park' => 'Park Rozrywki',
                    'aquarium' => 'Akwarium',
                    'art_gallery' => 'Galeria Sztuki',
                    'atm' => 'Bankomat',
                    'bakery' => 'Piekarnia',
                    'bank' => 'Bank',
                    'bar' => 'Bar',
                    'beauty_salon' => 'Salon Piękności',
                    'bicycle_store' => 'Sklep rowerowy',
                    'book_store' => 'Księgarnia',
                    'bowling_alley' => 'Kręgielnia',
                    'bus_station' => 'Dworzec Autobusowy',
                    'cafe' => 'Kawiarnia',
                    'campground' => 'Kemping',
                    'car_dealer' => 'Dealer Samochodowy',
                    'car_rental' => 'Wypożyczalnia Samochodów',
                    'car_repair' => 'Warsztat Samochodowy',
                    'car_wash' => 'Myjnia Samochodowa',
                    'casino' => 'Kasyno',
                    'cemetery' => 'Cmentarz',
                    'church' => 'Kościół',
                    'city_hall' => 'Ratusz',
                    'clothing_store' => 'Sklep Odzieżowy',
                    'convenience_store' => 'Sklep Spożywczy',
                    'courthouse' => 'Sąd',
                    'dentist' => 'Dentysta',
                    'department_store' => 'Dom Handlowy',
                    'doctor' => 'Lekarz',
                    'drugstore' => 'Drogeria',
                    'electrician' => 'Elektryk',
                    'electronics_store' => 'Sklep Elektroniczny',
                    'embassy' => 'Ambasada',
                    'fire_station' => 'Straż Pożarna',
                    'florist' => 'Kwiaciarnia',
                    'funeral_home' => 'Dom Pogrzebowy',
                    'furniture_store' => 'Sklep Meblowy',
                    'gas_station' => 'Stacja Benzynowa',
                    'gym' => 'Siłownia',
                    'hair_care' => 'Salon Fryzjerski',
                    'hardware_store' => 'Sklep Budowlany',
                    'hindu_temple' => 'Świątynia Hinduistyczna',
                    'home_goods_store' => 'Sklep Domowy',
                    'hospital' => 'Szpital',
                    'insurance_agency' => 'Agencja Ubezpieczeniowa',
                    'jewelry_store' => 'Salon Jubilerski',
                    'laundry' => 'Pralnia',
                    'lawyer' => 'Prawnik',
                    'library' => 'Biblioteka',
                    'light_rail_station' => 'Stacja Kolejki Miejskiej',
                    'liquor_store' => 'Sklep Alkoholowy',
                    'local_government_office' => 'Urząd Miejski',
                    'locksmith' => 'Ślusarz',
                    'lodging' => 'Zakwaterowanie',
                    'meal_delivery' => 'Dostawa Jedzenia',
                    'meal_takeaway' => 'Jedzenie na Wynos',
                    'mosque' => 'Meczet',
                    'movie_rental' => 'Wypożyczalnia Filmów',
                    'movie_theater' => 'Kino',
                    'moving_company' => 'Firma Przeprowadzkowa',
                    'museum' => 'Muzeum',
                    'night_club' => 'Klub Nocny',
                    'painter' => 'Malarz',
                    'park' => 'Park',
                    'parking' => 'Parking',
                    'pet_store' => 'Sklep Zoologiczny',
                    'pharmacy' => 'Apteka',
                    'physiotherapist' => 'Fizjoterapeuta',
                    'plumber' => 'Hydraulik',
                    'police' => 'Policja',
                    'post_office' => 'Poczta',
                    'primary_school' => 'Szkoła Podstawowa',
                    'real_estate_agency' => 'Agencja Nieruchomości',
                    'restaurant' => 'Restauracja',
                    'roofing_contractor' => 'Dekarz',
                    'rv_park' => 'Parking dla Kamperów',
                    'school' => 'Szkoła',
                    'secondary_school' => 'Gimnazjum',
                    'shoe_store' => 'Sklep Obuwniczy',
                    'shopping_mall' => 'Centrum Handlowe',
                    'spa' => 'Spa',
                    'stadium' => 'Stadion',
                    'storage' => 'Magazyn',
                    'store' => 'Sklep',
                    'subway_station' => 'Stacja Metra',
                    'supermarket' => 'Supermarket',
                    'synagogue' => 'Synagoga',
                    'taxi_stand' => 'Postój Taxi',
                    'tourist_attraction' => 'Atrakcja Turystyczna',
                    'train_station' => 'Dworzec Kolejowy',
                    'transit_station' => 'Stacja Przejazdowa',
                    'travel_agency' => 'Biuro Podróży',
                    'university' => 'Uniwersytet',
                    'veterinary_care' => 'Opieka Weterynaryjna',
                    'zoo' => 'Zoo'
                ];
                
                $businessCategory = $businessCategoriesMapping[$typeItemParts[0]] ?? 'Inne';
            

                // Validate prices
                switch($pricesItem){
                    case 5:
                        $pricesItem = "Bardzo wysokie";
                        break;
                    case 4:
                        $pricesItem = "Wysokie";
                        break;
                    case 3:
                        $pricesItem = "Średnie";
                        break;
                    case 2:
                        $pricesItem = "Niskie";
                        break;
                    case 1:
                        $pricesItem = "Bardzo niskie";
                        break;
                    default:
                        $pricesItem = "Nie podano";
                }

                // Create pretty place name
                $pretty_place_name = $nazwaItem . ' ' . $addressArray['city']['city_name'] . ', '. $addressArray['street'];

                // Validate Longitude and Latitude and build map URL
                if(!is_numeric($longitudeItem) || !is_numeric($latitudeItem)) {
                    $Utils->debug_log('Invalid longitude or latitude in one of the rows.');
                    continue;
                }

                if(!get_page_by_path(sanitize_title($pretty_place_name), OBJECT, 'post')){

                    // Declare new variable for post content or clear existing one
                    $post_content = "";


                    // INCLUDE POST CONTENT
                    include('post-content.php');


                    // INSERT POST
                    // Check if the category exists
                    // If it doesn't exist, create it
                    if (!term_exists($businessCategory, 'category')) {
                        $inserted_term = wp_insert_term(
                            $businessCategory, // the term 
                            'category', // the taxonomy
                            array(
                                'slug' => sanitize_title($businessCategory)
                            )
                        );
                        if(is_wp_error($inserted_term)) $Utils->debug_log("term_exists($businessCategory, 'category') - wp_error has occured");
                    }

                    $parentCat = get_term_by('slug', sanitize_title($businessCategory), 'category');

                    if(!term_exists( $addressArray['city']['city_name'], 'category' )) {
                        $inserted_term = wp_insert_term(
                            $addressArray['city']['city_name'],
                            'category',
                            array(
                                'parent' => $parentCat->term_id,
                                'slug' => sanitize_title($addressArray['city']['city_name']),
                            )
                        );
                        if(is_wp_error($inserted_term)) $Utils->debug_log("term_exists({$addressArray['city']['city_name']}, 'category') - wp_error has occured");
                    }

                    $subcategory = get_term_by('slug', sanitize_title($addressArray['city']['city_name']), 'category');

                    $Utils = new Utils();
                    $post_content = $Utils->remove_emoji($post_content);

                    // FIXME there might be an issue with compatibility with instant indexing plugin

                    $post_data = array(
                        'post_title'        => $pretty_place_name,
                        'post_content'      => wp_kses_post($post_content),
                        'post_status'       => 'publish',
                        'post_type'         => 'post',
                        'post_author'       => get_current_user_id(),
                        'post_category'     => array($parentCat->term_id, $subcategory->term_id),
                        'comment_status'    => 'closed',
                    );
                    
                    // Insert the post and get the post ID
                    $post_id = wp_insert_post( $post_data );
                    if( $post_id ){

                        $Utils->debug_log("Post was created with the ID= $post_id");
                        
                        if(isset($page_screenshot) && $page_screenshot) set_post_thumbnail( $post_id, $page_screenshot[0] );
                        else set_post_thumbnail( $post_id, $placeholder_id );

                        echo "Post was created with the ID= $post_id, with attachment with ID= $placeholder_id<br>";

                    } else{
                        $Utils->debug_log("Failed to create post with ID= $post_id");
                        if(is_wp_error($post_id)) $Utils->debug_log('Error creating post: ' . $post_id->get_error_message());
                        else $Utils->debug_log("Failed to create post. Unknown error.");
                        echo "Failed to create post with ID= $post_id<br>";
                    }
                } else $Utils->debug_log("Post $pretty_place_name is already created"); 
            }
            $Utils->debug_log("Processed batch " . ($i + 1));
        }
        $Utils->debug_log('All batches has been finished!');
        echo "All batches has been finished!<br>";
        return;
    }
}
