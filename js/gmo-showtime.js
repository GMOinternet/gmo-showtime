(function($){

$('.showtime').each(function(){
    var showtime = $(this);

    var pages = 1
    if (parseInt($(this).attr('data-columns')) > 0) {
        pages = parseInt($(this).attr('data-columns'));
    }

    var transition = 'fade';
    if ($(this).attr('data-transition')) {
        var transition = $(this).attr('data-transition');
    }

    if (!parseInt($(this).attr('data-show_title'))) {
        $('h2', this).hide();
    }

    var args = {};
    if (pages == 1) {
        args = {
            transitionStyle : transition,
            singleItem : true,
            autoPlay: 3000,
            navigation : true,
            navigationText : ['<span class="genericon genericon-leftarrow"></span>','<span class="genericon genericon-rightarrow"></span>']
        };
    } else {
        args = {
            items : pages,
            itemsTablet: [768, 2],
            itemsMobile : [479, 1],
            autoPlay: 3000,
            navigation : true,
            navigationText : ['<span class="genericon genericon-leftarrow"></span>','<span class="genericon genericon-rightarrow"></span>']
        };
    }

    showtime.owlCarousel(args);

});

})(jQuery);
