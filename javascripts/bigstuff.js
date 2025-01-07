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
    Bigstuff.showMetadata = function(metaid, itemid) {
        var meta = $(metaid);
        meta.show();
        meta.position({
            my: "left+100 top",
            at: "left bottom",
            of: itemid,
            within: '#content',
        });
    }

    Bigstuff.hideMetadata = function(metaid) {
        var meta = $(metaid);
        meta.hide();
    }
})(jQuery)


