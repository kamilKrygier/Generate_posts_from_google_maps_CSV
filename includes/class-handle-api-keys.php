<?php

class Handle_API_keys{
    
    public static function get_API_key( $API_name ){
        $API_key = get_option( $API_name );
        if(empty($API_key)){
            Utils::debug_log("No key found like $API_name in database!");
            return false;
        }
        
        require_once('class-api-key-encryption.php');
        $API_Key_Encryption = new API_Key_Encryption();

        return $API_Key_Encryption->decrypt($API_key);
    }
    
    
    public function change_API_key($API_name, $API_key){

        require_once('class-api-key-encryption.php');

        $API_Key_Encryption = new API_Key_Encryption();
        $API_key = $API_Key_Encryption->encrypt(sanitize_text_field( $API_key ));

        // Check if sended API key is equal to this inside DB
        if ($API_key == get_option($API_name)){
            Utils::debug_log("$API_name already exists in database!");
            return false;
        }

        $google_apis = array('MAPS_STATIC_API_KEY', 'GOOGLE_PLACES_API_KEY');

        if(in_array($API_name, $google_apis)) {

            Utils::debug_log("Validating API key: $API_name...");

            if(!$this->validate_Google_API_key($API_key)){

                Utils::debug_log("Unable to validate $API_name!");
                return false;

            }
        }
        
        else if($API_name == 'OPENAI_API_KEY'){

            Utils::debug_log("Validating API key: $API_name...");

            if(!$this->validate_OpenAI_API_key($API_key)){

                Utils::debug_log("Unable to validate $API_name!");
                return false;

            }

        }   

        Utils::debug_log("API key $API_name is valid!");
            
        $API_key_changed = $this->set_API_key($API_name, $API_key);

        return $API_key_changed ? true : false;

    }

    private function set_API_key( $API_name, $API_key ){
        
        if(!get_option( $API_name )) add_option($API_name, $API_key, '', false);
            else update_option( $API_name, $API_key, false );

        return get_option( $API_name ) == $API_key ? true : false;

    }

    private function validate_Google_API_key($API_key){
        
        $API_Key_Encryption = new API_Key_Encryption($API_key);
        $API_key = $API_Key_Encryption->decrypt($API_key);

        $google_places_base_search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json";

        $searchParams = [
            "query" => "atm in Warsaw",
            "key" => $API_key
        ];

        $response = Utils::fetchUrl($google_places_base_search_url, $searchParams);
        $encoded_response = json_encode($response);
    
        Utils::debug_log("Validaton response: \n $encoded_response");
    

        if ($encoded_response === FALSE) {

            Utils::debug_log("Validaton summary: REQUEST FAILED");
            return false;

        }

        return isset($response['status']) && $response['status'] == 'OK';    

    }

    private function validate_OpenAI_API_key($API_key){

        // TODO Add opeanai api key validation
        $API_Key_Encryption = new API_Key_Encryption($API_key);
        $API_key = $API_Key_Encryption->decrypt($API_key);

        return true;

    }

}

?>