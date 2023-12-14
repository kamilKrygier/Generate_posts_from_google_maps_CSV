<?php
// INFO
// multiple types can be used (use | to separate them) during API call
// @see https://stackoverflow.com/questions/6845254/google-places-api-using-multiple-name-parameters-in-the-places-search

// TODO Add additional categories Jeweler, Jewelry buyer, Jewelry repair service, Jewelry store, Diamond dealer, Goldsmith, Gold dealer,

// Process the code after form is submitted
if (isset($_POST['submit']) && isset($_POST['place_category'])) {

    // TODO Add functionality - if api key is empty, than display custom message and log this info
    $GOOGLE_PLACES_API_KEY = Handle_API_keys::get_API_key('GOOGLE_PLACES_API_KEY');

    // All main Google Places Categories (10.2023)
    $place_category = ["accounting","airport","amusement_park","aquarium","art_gallery","atm","bakery","bank","bar","beauty_salon","bicycle_store","book_store","bowling_alley","bus_station","cafe","campground","car_dealer","car_rental","car_repair","car_wash","casino","cemetery","church","city_hall","clothing_store","convenience_store","courthouse","dentist","department_store","doctor","drugstore","electrician","electronics_store","embassy","fire_station","florist","funeral_home","furniture_store","gas_station","gym","hair_care","hardware_store","hindu_temple","home_goods_store","hospital","insurance_agency","jewelry_store","laundry","lawyer","library","light_rail_station","liquor_store","local_government_office","locksmith","lodging","meal_delivery","meal_takeaway","mosque","movie_rental","movie_theater","moving_company","museum","night_club","painter","park","parking","pet_store","pharmacy","physiotherapist","plumber","police","post_office","primary_school","real_estate_agency","restaurant","roofing_contractor","rv_park","school","secondary_school","shoe_store","shopping_mall","spa","stadium","storage","store","subway_station","supermarket","synagogue","taxi_stand","tourist_attraction","train_station","transit_station","travel_agency","university","veterinary_care","zoo"];

    echo "<script>jQuery('.kk_spinner_wrapper').fadeIn();</script>";

    // Declare endpoints
    $google_places_base_search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json";
    $google_places_base_details_url = "https://maps.googleapis.com/maps/api/place/details/json";

    // Check if exported_csv folder exists
    $directory = __DIR__ . "/exported_csv/";

    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        Utils::debug_log("exported_csv folder has been created!");
    }


    // Declare path to save CSV in
    $slugified_place_category = Utils::sanitizeForCsv($_POST['place_category']);
    $output_path = __DIR__ . "/exported_csv/$slugified_place_category.csv";

    // 100 biggest cities in Poland
    // Replace with your target cities
    $cities = ["Warszawa","Kraków","Łódź","Wrocław","Poznań","Gdańsk","Szczecin","Bydgoszcz","Lublin","Białystok","Katowice","Gdynia","Częstochowa","Radom","Sosnowiec","Toruń","Kielce","Gliwice","Zabrze","Bytom","Olsztyn","Bielsko-Biała","Rzeszów","Ruda Śląska","Rybnik","Tychy","Dąbrowa Górnicza","Opole","Elbląg","Płock","Wałbrzych","Gorzów Wielkopolski","Włocławek","Tarnów","Chorzów","Zielona Góra","Koszalin","Kalisz","Legnica","Grudziądz","Świętochłowice","Jaworzno","Jastrzębie-Zdrój","Malbork","Słupsk","Mysłowice","Konin","Piotrków Trybunalski","Inowrocław","Lubin","Ostrów Wielkopolski","Gniezno","Suwałki","Głogów","Chełm","Tomaszów Mazowiecki","Przemyśl","Stargard","Zamość","Kędzierzyn-Koźle","Lębork","Leszno","Świdnica","Piekary Śląskie","Tarnowskie Góry","Siemianowice Śląskie","Ostrołęka","Starachowice","Zawiercie","Zgierz","Pruszków","Świnoujście","Radomsko","Skierniewice","Kutno","Ciechanów","Wodzisław Śląski","Będzin","Racibórz","Sieradz","Chrzanów","Kluczbork","Nowy Sącz","Jarosław","Otwock","Jawor","Nysa","Konstantynów Łódzki","Lubliniec","Piaseczno","Piła","Sochaczew","Środa Wielkopolska","Września","Krosno","Ostrowiec Świętokrzyski","Wołomin","Mielec","Tczew","Biała Podlaska"];

    $csvData = [];
    
    Utils::debug_log("\nGoogle Places Scrape has been started!");


    foreach ($cities as $city) {
        $searchParams = [
            "query" => "{$_POST['place_category']} in $city",
            "key" => $GOOGLE_PLACES_API_KEY
        ];

        Utils::debug_log("Curently checking: {$_POST['place_category']} in $city");

        $places = Utils::fetchUrl($google_places_base_search_url, $searchParams);

        if (isset($places["results"])) {
            foreach ($places["results"] as $place) {
                Utils::debug_log("Checking place: {$place['place_id']}");

                $detailsParams = [
                    "place_id" => $place["place_id"],
                    "fields" => "name,formatted_phone_number,formatted_address,opening_hours,website,reviews,types,price_level,geometry",
                    "key" => $GOOGLE_PLACES_API_KEY,
                    "language" => "pl" // Polish
                ];

                $details = Utils::fetchUrl($google_places_base_details_url, $detailsParams);
                $reviews = array_slice($details["result"]["reviews"] ?? [], 0, 6);

                Utils::debug_log("Place name: {$details['result']['name']}");
                
                if($details['result']['name'] == '' || empty($details['result']['name'] || !isset($details['result']['name']))) continue;

                if($details["result"]["types"][0] == $_POST['place_category']) $rowData = [
                    "Name" => Utils::sanitizeForCsv($details["result"]["name"] ?? ''),
                    "Phone" => Utils::sanitizeForCsv($details["result"]["formatted_phone_number"] ?? ''),
                    "Address" => Utils::sanitizeForCsv($details["result"]["formatted_address"] ?? ''),
                    "Hours" => Utils::sanitizeForCsv(implode(", ", $details["result"]["opening_hours"]["weekday_text"] ?? [])),
                    "Website" => Utils::sanitizeForCsv($details["result"]["website"] ?? ''),
                    "Review 1" => Utils::sanitizeForCsv($reviews[0]["text"] ?? ''),
                    "Review 2" => Utils::sanitizeForCsv($reviews[1]["text"] ?? ''),
                    "Review 3" => Utils::sanitizeForCsv($reviews[2]["text"] ?? ''),
                    "Review 4" => Utils::sanitizeForCsv($reviews[3]["text"] ?? ''),
                    "Review 5" => Utils::sanitizeForCsv($reviews[4]["text"] ?? ''),
                    "Review 6" => Utils::sanitizeForCsv($reviews[5]["text"] ?? ''),
                    "Type" => Utils::sanitizeForCsv(implode(", ", $details["result"]["types"] ?? [])),
                    "Price Level" => $details["result"]["price_level"] ?? '',
                    "Latitude" => $details["result"]["geometry"]["location"]["lat"] ?? '',
                    "Longitude" => $details["result"]["geometry"]["location"]["lng"] ?? '',
                ]; else continue;
                
                $csvData[] = $rowData;
            }
        } else Utils::debug_log("No place has been returned");
    }

    // Check if the file exists and remove it
    if (file_exists($output_path)) {
        unlink($output_path);
    }

    $output = fopen($output_path, "w");

    if ($output === false) {
        Utils::debug_log("Failed to open the CSV file for writing.");
        die("Failed to open the CSV file for writing.");
    }

    fputcsv($output, ["Nazwa", "Telefon", "Adres", "Godziny otwarcia", "Strona internetowa", "Opinia 1", "Opinia 2", "Opinia 3", "Opinia 4", "Opinia 5", "Opinia 6", "Typ", "Wysokość cen", "Latitude", "Longitude"]);

    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }

    Utils::debug_log("Google Places Scrape has finished!\n");
    echo "Google Places Scrape has finished!";
    fclose($output);

    // Exit after CSV generation to prevent the WordPress dashboard from rendering below the CSV output.
    exit;
}
