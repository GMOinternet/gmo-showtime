(function($){

$('.showtime').each(function(){
    var transition = $(this).attr('data-transition');
    $(this).nivoSlider({
        effect: transition,    // Specify sets like: 'fold,fade,sliceDown'
        slices: 15,                                 // For slice animations
        boxCols: 8,                                 // For box animations
        boxRows: 4,                                 // For box animations
        animSpeed: 500,                             // Slide transition speed
        pauseTime: 3000,                            // How long each slide will show
        startSlide: 0,                              // Set starting Slide (0 index)
        directionNav: true,                         // Next & Prev navigation
        controlNav: true,                           // 1,2,3... navigation
        controlNavThumbs: false,                    // Use thumbnails for Control Nav
        pauseOnHover: true,                         // Stop animation while hovering
        manualAdvance: false,                       // Force manual transitions
        prevText: 'Prev',                           // Prev directionNav text
        nextText: 'Next',                           // Next directionNav text
        randomStart: false,                         // Start on a random slide
        beforeChange: function(){},                 // Triggers before a slide transition
        afterChange: function(){},                  // Triggers after a slide transition
        slideshowEnd: function(){},                 // Triggers after all slides have been shown
        lastSlide: function(){},                    // Triggers when last slide is shown
        afterLoad: function(){}                     // Triggers when slider has loaded
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
});

// load
$(".slide a img").on("load",function(){
	var t;
	var th = $(this).height();
	var ph = $(this).parent().height();
	if (th > ph) {
		t = -((th - ph) / 2);
	} else {
		t = (ph - th) / 2;
	}
	$(this).css("top", t+"px");
});

})(jQuery);
