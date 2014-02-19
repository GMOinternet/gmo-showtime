(function($){

$('.showtime').each(function(){
    var slider = this;
    var transition = $(this).attr('data-transition');
    $(this).nivoSlider({
        effect: transition,    // Specify sets like: 'fold,fade,sliceDown'
        slices: 15,                                 // For slice animations
        boxCols: 8,                                 // For box animations
        boxRows: 4,                                 // For box animations
        animSpeed: 300,                             // Slide transition speed
        pauseTime: 5000,                            // How long each slide will show
        startSlide: 0,                              // Set starting Slide (0 index)
        directionNav: true,                         // Next & Prev navigation
        controlNav: false,                           // 1,2,3... navigation
        controlNavThumbs: false,                    // Use thumbnails for Control Nav
        pauseOnHover: true,                         // Stop animation while hovering
        manualAdvance: false,                       // Force manual transitions
        prevText: 'Prev',                           // Prev directionNav text
        nextText: 'Next',                           // Next directionNav text
        randomStart: false,                         // Start on a random slide
        beforeChange: function(){
            $('.nivo-caption').fadeOut(500);
        },                 // Triggers before a slide transition
        afterChange: function() {
            $('.nivo-caption').fadeIn(500);
            showCaption(slider);
        },
        slideshowEnd: function(){},                 // Triggers after all slides have been shown
        lastSlide: function(){},                    // Triggers when last slide is shown
        afterLoad: function() {
            showCaption(slider);
        }
    });

    function showCaption(slide) {
        var current = $('.nivo-imageLink', slide).filter(function(){
            if ($(this)[0].style.display == 'block') {
                return true;
            }
        });

        var title = $('img:first', current).attr('title');
        var content = $('img:first', current).attr('data-content');

        $('.nivo-caption:first', slide).html('<h2>'+title+'</h2><div class="content">'+content+'</div>');
    }
});

/*
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
            autoPlay: 10000,
            navigation : true,
            navigationText : ['<span class="genericon genericon-leftarrow"></span>','<span class="genericon genericon-rightarrow"></span>']
        };
    } else {
        args = {
            items : pages,
            itemsTablet: [768, 2],
            itemsMobile : [479, 1],
            autoPlay: 10000,
            navigation : true,
            navigationText : ['<span class="genericon genericon-leftarrow"></span>','<span class="genericon genericon-rightarrow"></span>']
        };
    }

    showtime.owlCarousel(args);
*/


})(jQuery);
