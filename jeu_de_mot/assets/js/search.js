const $ = require('jquery');

$(document).ready(function() {

    $(":checkbox").click(function(){
        var id = $(this).attr('id');
        if($(this).prop("checked") == false){
            $('#asso-' + id).hide();
        } else {
            $('#asso-' + id).show();
        }
    });

    $(":button").click(function(){
        var kids = $(this).parent().parent().children('.removable');
        if($(this).hasClass('plus')) {
            //console.log(kids);
            kids.show();
            $(this).html("<i class=\"fas fa-minus-circle fa-lg\"></i>");
            $(this).removeClass("plus");
            kids.addClass("d-inline-block");
            $(this).addClass("moins");
        } else {
            kids.hide();
            $(this).html("<i class=\"fas fa-plus-circle fa-lg\"></i>");
            $(this).removeClass("moins");
            kids.removeClass("d-inline-block");
            $(this).addClass("plus");
        }
    });

});