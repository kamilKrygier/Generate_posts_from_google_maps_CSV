<?php

class Handle_API_keys{
    public function get_API_key( $API_name ){
        if(empty(get_option( $API_name ))) return '';
        else return get_option( $API_name );
    }

    public function change_API_key($API_name, $API_value){
        if(get_option( $API_name ) != $API_value) $API_key = $this->set_API_key( $API_name, $API_value );
        
        return $API_key ? $API_name . " - " . __('Saved', 'default') . "<br>" : '';
    }

    private function set_API_key( $API_name, $API_value ){
        // TODO store API keys hashed
        if(empty(get_option( $API_name ))) add_option($API_name, $API_value, '', false);
        else update_option( $API_name, $API_value, false );

        return get_option( $API_name ) == $API_value ? true : false;
    }
}

?>