<?php

add_action('admin_notices', function() {
    $currentPage = $_GET['page'] ?? '';

    // Check for missing API keys on specific pages
    if ($currentPage === 'csv-to-posts' && (!Handle_API_keys::get_API_key('MAPS_STATIC_API_KEY') || !Handle_API_keys::get_API_key('MAPS_STATIC_API_SECRET') || !Handle_API_keys::get_API_key('OPENAI_API_KEY'))) {
        Utils::displayAdminNotice('error', true, 'Go to <a title="Go to settings" href="?page=ctp-settings">settings page</a> to <b>set up all API keys to use this plugin</b>');
    } elseif ($currentPage === 'ctp-google-places-scrapper' && !Handle_API_keys::get_API_key('GOOGLE_PLACES_API_KEY')) {
        Utils::displayAdminNotice('error', true, 'Go to <a title="Go to settings" href="?page=ctp-settings">settings page</a> to <b>set up all API keys to use this plugin</b>');
    }

    // Handle POST requests on settings page
    if ($currentPage === 'ctp-settings' && isset($_POST['submit'])) {
        $handleApiKeys = new Handle_API_keys();
        $apiKeys = ['MAPS_STATIC_API_KEY', 'MAPS_STATIC_API_SECRET', 'GOOGLE_PLACES_API_KEY', 'OPENAI_API_KEY'];

        foreach ($apiKeys as $apiKey) {
            if (!empty($_POST[$apiKey])) {

                $response = $handleApiKeys->change_API_key($apiKey, $_POST[$apiKey]);

                $noticeType = $response ? 'success' : 'error';

                $message = $response ? 
                                        sprintf("%s - %s.", $apiKey, __('Saved', 'default')) : 
                                        sprintf(__("%s - Save failed.", 'default') . ' (Check for duplicates)', $apiKey);

                Utils::displayAdminNotice($noticeType, true, $message);

            }
        }

        if (!empty($_POST['ctp_placeholder_image'])) {

            $placeholderImageId = Utils::get_placeholder_image()->imageID ?? '';

            if ($_POST['ctp_placeholder_image'] != $placeholderImageId) {

                $uploadSuccess = Utils::set_placeholder_image($_POST['ctp_placeholder_image']);
                
                $noticeType = $uploadSuccess ? 'success' : 'error';

                $message = $uploadSuccess ? 'Success: Placeholder image uploaded' : 'Error: Placeholder image not uploaded';

                Utils::displayAdminNotice($noticeType, true, $message);

            }

        }
    }
});

?>