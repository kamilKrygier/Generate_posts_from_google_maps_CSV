<?php

use GuzzleHttp\Client;

class AI_Generate_Post{

    public static function handle_ai_generation_for_posts($is_WP_cron_callback, $posts_ids):void {

        if($is_WP_cron_callback){

            Utils::debug_log("IS_WP_CRON_CALLBACK (AI generated content)");

            $args = array(
                'post_type'         => 'post',
                'fields' => 'ids',
                'posts_per_page'    => 10,
                'meta_query'        => array(
                    array(
                        'key'       => 'ai_genrated_content',
                        'compare'   => 'NOT EXISTS'
                    ),
                )
            );

            $posts_ids = get_posts($args);

        } 
        else $posts_ids = $this->$posts_ids;
        

        // Split posts into batches
        $batches_ids = array_chunk($post_ids, 5);

        Utils::debug_log("BATCH STARTED - UPDATE POST WITH AI GENERATED CONTENT");

        foreach ($batches_ids as $batch_ids){

            $args = array(
                'post_type'         => 'post',
                'include'           => $batch_ids,
            );

            $posts = get_posts($args);
        
            foreach ($posts as $post) {

                $post_id = $post->ID;

                if(!get_post_meta($post_id, 'ai_genrated_content', true)){
                    
                    $post_content = $post->post_content;

                    Utils::debug_log("Working on post with ID=$post_id");
                    
                    // TODO add setting options $prompt and $maxTokens (now it's hardcoded)
                    // TODO add different languages support
                    
                    $prompt = "Podane dane: $post_content
                                    
                        Bazując na podanych danych, przygotuj opis na stronę internetową (pisz w trzeciej osobie liczby pojedynczej języka polskiego) w HTML (bez tagów doctype,head). Opis powinien być podzielony na konkretne działy (nagłówki h2):
                        - Informacje ogólne (Napisz 100 słów opisu o firmie, w którym zawrzesz informacje o ewentualnym asortymencie, obsłudze, lokalizacji, itd.),
                        - Podsumowanie opinii (podsumuj opinie od klientów i bazując na nich wykonaj podsumowanie firmy, czyli napisz parę słów o tym, o czym ludzie mówią w tych opiniach),
                        - Lokalizacja (Opowiedz więcej o okolicy w pobliżu podanego adresu firmy),
                        - Kontakt (Zachęć do kontaktu z firmą poprzez numer telefonu (jeśli podano), stronę internetową (jeśli podano) oraz osobiste odwiedziny pod podanym adresem (podaj adres)).
                        ";
            
                    
                    $generated_post_content = self::generateContentWithOpenAI($prompt, 2200, false);
            
                    if (!empty($generated_post_content)) {

                        Utils::debug_log("AI content has been generated for post with ID=$post_id");

                        $post_content .= $generated_post_content;
                        $post_updated = wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
            
                        if (!is_wp_error($post_updated)) {

                            Utils::debug_log("Post with ID=$post_id has been updated");
                            add_post_meta($post_id, 'ai_genrated_content', true);

                        } else Utils::debug_log("There was an error while updating post");

                    }

                } else Utils::debug_log("Post with ID=$post_id, already has AI generated content!");

            }

        }

        Utils::debug_log("BATCH ENDED");

        if(!$is_WP_cron_callback) set_transient('batch_process_generate_articles_content_complete', true, 5 * MINUTE_IN_SECONDS);

    }

    public static function generateContentWithOpenAI($prompt, $maxTokens, $testRun) {

        if(!$testRun->testRun) Utils::debug_log("~Begin content generation~");
            else Utils::debug_log("Begin OpenAI API Key Validation");

        require_once dirname(__DIR__) . '/vendor/autoload.php';
    
        $client = new Client(['base_uri' => 'https://api.openai.com/']);
    
        Utils::debug_log("~Current prompt:\n $prompt\n");
    
        // TODO Add finctionality - if api key is empty, than display custom message and log this info
        $OpenAI_API_key = $testRun->testRun ? $testRun->testRunKey : Handle_API_keys::get_API_key('OPENAI_API_KEY');
    
        try {
            $response = $client->post('v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $OpenAI_API_key,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $maxTokens
                ]
            ]);
    
            $body = $response->getBody();
            $content = json_decode($body, true);
            Utils::debug_log("Total tokens used (for current post): " . $content['usage']['total_tokens']);
    
            return $content['choices'][0]['message']['content'] ?? null;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            Utils::debug_log($e->getMessage());
            return null;
        }
    
    }

}