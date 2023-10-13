<?php

    // BUSINESS/COMPANY INFO
    $post_content .= '<div class="wp-block-columns are-vertically-aligned-center is-layout-flex wp-container-3 wp-block-columns-is-layout-flex">';

        $post_content .= '<div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow">';
            $post_content .= '<h2 class="wp-block-heading">Informacje o firmie:</h2>';
            $post_content .= '<ul>';
            
            // INFO LIST
                // TODO Add email to CSV
                // $post_content .= '<li>{{HERE PLACE "Email"}}</li>';
                $post_content .= (!empty($phone)) ? "<li><span>Telefon: <a href='tel:$phone'>$phone</a></span></li>" : "";
                // $post_content .= (!empty($mail)) ? "<li><span>Mail: <a href='mailto:$mail'>$mail</a></span></li>" : "";
                $post_content .= (!empty($URLItem)) ? "<li><span>Strona internetowa: <a href='$URLItem'>$URLItem</a></span></li>" : "";
                $post_content .= (!empty($addressItem)) ? "<li><span>Adres: $addressItem</span></li>" : "";
                $post_content .= (!empty($pretty_opening_hours)) ? "<li><span>Godziny otwarcia: $pretty_opening_hours</span></li>" : "";
                $post_content .= (!empty($businessCategory)) ? "<li><span>Kategoria: $businessCategory</span></li>" : "";
                $post_content .= (!empty($pricesItem)) ? "<li><span>Wysokość cen: $pricesItem</span></li>" : "";
                // -----------------------------------------
            $post_content .= '</ul>';
        $post_content .= '</div>';

        // WEBSITE SCREENSHOT
        if (isset($page_screenshot) && $page_screenshot){
            $post_content .= '<div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow">';

                $post_content .= '<figure class="wp-block-image size-large"><img src="' . $page_screenshot . '"></figure>';
                
            $post_content .= '</div>';
        }
        // -----------------------------------------

    $post_content .= '</div>';

    $post_content .= '<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>';

    $post_content .= '<h2 class="wp-block-heading">Najnowsze opinie o firmie:</h2>';

    $post_content .= '<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>';

    // GOOGLE PLACES REVIEWS (6)
    // TODO Add sixth review to CSV
        $post_content .= '<div class="wp-block-group is-content-justification-center is-nowrap is-layout-flex wp-container-4 wp-block-group-is-layout-flex">';
            $post_content .= '<div class="ctp_review_wrapper">';
            foreach($reviewItems as $reviewItem){
                $post_content .= $reviewItem !== "" ? "<div class='ctp_review_item'>" . $reviewItem . "</div>" : "";
            }
            $post_content .= '</div>';
        $post_content .= '</div>';
    // -----------------------------------------

    $post_content .= '<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>';

    $post_content .= '<h2 class="wp-block-heading">Jak dojechać?</h2>';

    // GOOGLE STATIC MAPS API IMAGE
        $post_content .= "<img class='ctp_map_image' src='$signedUrl' alt='$pretty_place_name'><br>";
        $post_content .= "<div class='wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex'><div class='wp-block-button'><a class='wp-block-button__link wp-element-button' href='https://www.google.com/maps/search/$latitudeItem,$longitudeItem'>Jak dojadę ?</a></div></div>";
    // -----------------------------------------
    

    // DISPLAY AI GENERATED CONTENT
        $post_content .= '<h2 class="wp-block-heading">Więcej o firmie:</h2>';

        $post_content .= $generated_post_content;
    // -----------------------------------------