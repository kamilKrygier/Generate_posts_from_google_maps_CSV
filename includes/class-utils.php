<?php

class Utils{

    public static function remove_emoji($text) {

        // Match Emoticons, Miscellaneous Symbols and Pictographs, Transport and Map Symbols, and Supplementary Symbols
        $regex = '/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F700}-\x{1F77F}|\x{1F780}-\x{1F7FF}|\x{1F800}-\x{1F8FF}|\x{1F900}-\x{1F9FF}|\x{1FA00}-\x{1FA6F}|\x{1FA70}-\x{1FAFF}]/u';
        
        return preg_replace($regex, '', $text);

    }

    public static function debug_log($message) {
        if (WP_DEBUG) {
            $logFile = dirname(plugin_dir_path( __FILE__ )) . '/debug.log';
            
            // Check if file exists, if not create it with appropriate permissions
            if (!file_exists($logFile)) {
                touch($logFile);  // The touch() function sets access and modification time of file. If the file does not exist, it will be created.
                chmod($logFile, 0664);  // Set appropriate permissions
            }
            
            $current = file_get_contents($logFile);
            $current .= $message . "\n";
            file_put_contents($logFile, $current);        
        }

    }

    // Improved sanitizeForCsv function
    public static function sanitizeForCsv($string) {

        $string = trim($string, '"');  // Remove surrounding double quotes
        
        // Remove newline characters and carriage returns
        $string = str_replace(["\n", "\r"], ' ', $string);

        // Escape double quotes
        $string = str_replace('"', '""', $string);

        return $string;

    }

    public static function signUrl($url, $secret){

        // parse the URL
        $parsedUrl = parse_url($url);
        // construct the URL to be signed
        $urlToSign = $parsedUrl['path'] . "?" . $parsedUrl['query'];
        
        // decode the private key into its binary format
        $decodedKey = str_replace(['-', '_'], ['+', '/'], $secret);
        $decodedKey = base64_decode($decodedKey);
        
        // create a signature using the private key and the URL-encoded string using HMAC SHA1
        $signature = hash_hmac('sha1', $urlToSign, $decodedKey, true);
        
        // encode the signature into base64 for use within a URL
        $encodedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $url . "&signature=" . $encodedSignature;
    
    }

    public static function displayAdminNotice($snackBarType, $isDismissible, $snackBarMessage){
        /**
         * 
         * Available types:
         * - error - will display the message with a white background and a red left border.
         * - warning - will display the message with a white background and a yellow/orange left border.
         * - success - will display the message with a white background and a green left border.
         * - info - will display the message with a white background a blue left border.
         * 
         */

         if(!is_bool($isDismissible)) return;

         $isDismissibleText = $isDismissible ? 'is-dismissible' : '';

         printf('<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>', esc_attr($snackBarType), esc_attr($isDismissibleText), self::trim_not_alowed_tags($snackBarMessage));

    }

    private static function trim_not_alowed_tags($message){
        $allowed_tags = array(
            'b'         => array(),
            'strong'    => array(),
            'i'         => array(),
            'u'         => array(),
            'a'         => array(
                            'title' => array(),
                            'href'  => array(),
            ),
        );
        
        $escaped_message = wp_kses($message, $allowed_tags);
        return $escaped_message;
    }

    public static function uploadPlaceholderImage($imageURL){

        if (!filter_var($imageURL, FILTER_VALIDATE_URL) || !preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $imageURL)) return false; // Not a valid image URL

        // Check if the image is from the current website
        $site_url = parse_url(get_site_url());
        $image_url = parse_url($imageURL);

        if ($site_url['host'] !== $image_url['host']) {
            // Sideload the image to WordPress
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $sideloaded = media_sideload_image($imageURL, 0, null, 'src');

            if (is_wp_error($sideloaded)) {
                return false; // Sideload failed
            }

            $imageURL = $sideloaded;
        }

        // Update the WordPress option with the new image URL

        if(!get_option( 'ctp_placeholder_image' )) add_option('ctp_placeholder_image', $imageURL, '', false);
            else update_option( 'ctp_placeholder_image', $imageURL, false );
            
        return true;
        
    }

}