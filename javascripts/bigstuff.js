if (!Bigstuff) {
    var Bigstuff = {};
}

(function ($) {    
    Bigstuff.dropDown = function(){
        var dropdownMenu = $('#mobile-nav');
        dropdownMenu.prepend('<a href="#" class="menu">Menu</a>');
        //Hide the rest of the menu
        $('#mobile-nav .navigation').hide();

        //function the will toggle the menu
        $('.menu').click(function() {
            $("#mobile-nav .navigation").slideToggle();
        });
    };
    Bigstuff.metadataHover = function() {
        $('.item.single-line h4')
            .on('mouseenter', function () {
                var meta = $(this).siblings('.item-meta');
                meta.show();
                meta.position({
                    my: 'left+100 top',
                    at: 'left bottom',
                    of: this,
                    within: '#content',
                });
                $(this).parent().one('mouseleave', function () {
                    meta.hide();
                });
            })
    };
})(jQuery)


