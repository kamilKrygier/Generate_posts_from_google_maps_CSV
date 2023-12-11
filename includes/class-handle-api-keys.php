<?php

class Handle_API_keys{
    public static function get_API_key( $API_name ){
        if(!get_option( $API_name )) return false;
        else return get_option( $API_name );
    }
    
    // TODO Add validation if API key is not valid than return;
    public function change_API_key($API_name, $API_value){

        if (get_option($API_name) != $API_value) {
            
            $API_key_changed = $this->set_API_key($API_name, $API_value);
    
            return $API_key_changed ? true : false;

        } 
        
        else return false;

    }

    private function set_API_key( $API_name, $API_value ){
        // TODO store API keys hashed
        if(!get_option( $API_name )) add_option($API_name, $API_value, '', false);
        else update_option( $API_name, $API_value, false );

        return get_option( $API_name ) == $API_value ? true : false;
    }
}

?>