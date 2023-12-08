<?php

add_action('admin_notices', function() {

// TODO check if all needed API keys and placeholder image are stored in DB
if(isset($_GET['page']) && $_GET['page'] == 'csv-to-posts' && ( !Handle_API_keys::get_API_key('MAPS_STATIC_API_KEY') || !Handle_API_keys::get_API_key('MAPS_STATIC_API_SECRET') || !Handle_API_keys::get_API_key('OPENAI_API_KEY') ))
    Utils::displayAdminNotice('error', true, 'Go to <a title="Go to settings" href="?page=ctp-settings">settings page</a> to <b>set up all API keys to use this plugin</b>');

if(isset($_GET['page']) && $_GET['page'] == 'ctp-google-places-scrapper' && ( !Handle_API_keys::get_API_key('GOOGLE_PLACES_API_KEY') ))
    Utils::displayAdminNotice('error', true, 'Go to <a title="Go to settings" href="?page=ctp-settings">settings page</a> to <b>set up all API keys to use this plugin</b>');
});

?>