<?php

class Utils{

    public function remove_emoji($text) {

        // Match Emoticons, Miscellaneous Symbols and Pictographs, Transport and Map Symbols, and Supplementary Symbols
        $regex = '/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F700}-\x{1F77F}|\x{1F780}-\x{1F7FF}|\x{1F800}-\x{1F8FF}|\x{1F900}-\x{1F9FF}|\x{1FA00}-\x{1FA6F}|\x{1FA70}-\x{1FAFF}]/u';
        
        return preg_replace($regex, '', $text);

    }

    public function debug_log($message) {

        if (WP_DEBUG) {
            $logFile = plugin_dir_path( __FILE__ ) . 'debug.log';
            
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
    public function sanitizeForCsv($string) {

        $string = trim($string, '"');  // Remove surrounding double quotes
        
        // Remove newline characters and carriage returns
        $string = str_replace(["\n", "\r"], ' ', $string);

        // Escape double quotes
        $string = str_replace('"', '""', $string);

        return $string;

    }

}

?>