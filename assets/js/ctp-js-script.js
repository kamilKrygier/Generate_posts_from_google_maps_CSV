jQuery(document).ready(function($) {
    $('body').append('<div class="kk_spinner_wrapper"><div class="kk_spinner"></div></div>');
    $('.ctp_page form').on('submit', function() {
        $('.kk_spinner_wrapper').fadeIn().css('display', 'flex');
    });
});