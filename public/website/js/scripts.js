(function($) {
    "use strict";

    $(window).on('load', function(){
      $("#preloader").fadeOut();
    });

    $(document).on('click','.fkr-navbar .has-submenu', function(e){
        if($(window).width() <= 992){
            e.preventDefault();
            $(this).next('.submenu').slideToggle();
            $(this).toggleClass('active-submenu');
        }
    });

    $(window).on('resize', function(){
        if($(window).width() > 992){
            $('.fkr-navbar .submenu').css('display', 'block');
            $('.fkr-navbar .has-submenu').removeClass('active-submenu');
        }else{
            $('.fkr-navbar .submenu').css('display', 'none');
        }
    });

    $(document).on('click','.comments .single-comment .reply-btn', function(e){
        e.preventDefault();
        $(this).next(".reply-form").slideToggle();
    });


    $(document).on('change', '.plan_type', function(){
      $('input[name="' + this.name + '"]').not(this).prop('checked', false);
    });

    $(document).on('change', '.plan_type', function(){
      if($(this).val() == 'monthly'){
        $('.monthly-plan').fadeIn();
        $('.yearly-plan').fadeOut();
        $('.lifetime-plan').fadeOut();
      }else if($(this).val() == 'yearly'){
        $('.monthly-plan').fadeOut();
        $('.yearly-plan').fadeIn();
        $('.yearly-plan').removeClass('d-none');
        $('.lifetime-plan').fadeOut();
      }else if($(this).val() == 'lifetime'){
        $('.monthly-plan').fadeOut();
        $('.yearly-plan').fadeOut();
        $('.lifetime-plan').fadeIn();
        $('.lifetime-plan').removeClass('d-none');
      }
    });

    $(document).on('submit', '#email_subscription', function(e){
        e.preventDefault();
        var elem = $(this);
        var link = $(this).attr("action");
        $(elem).find("button[type=submit]").prop("disabled", true);
    
        $.ajax({
            method: "POST",
            url: link,
            data: new FormData(this),
            mimeType: "multipart/form-data",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
              $("#preloader").css("display", "block");
            }, success: function (data) {
              $(elem).find("button[type=submit]").attr("disabled", false);
              $("#preloader").css("display", "none");
              var json = JSON.parse(data);
              
              if (json['result'] == "success") {
                $.toast({
                  text: json['message'],
                  showHideTransition: 'slide',
                  icon: 'success',
                  position: 'top-right'
                });
              }else{
                if (Array.isArray(json['message'])) {
                  jQuery.each(json['message'], function (i, val) {
                    $.toast({
                      text: val,
                      showHideTransition: 'slide',
                      icon: 'error',
                      position: 'top-right'
                    });
                  });
                } else {
                  $.toast({
                    text: json['message'],
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right'
                  });
                }
              }
            }
        });
    });

    // Enable Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    document.addEventListener("DOMContentLoaded", function(){
      window.addEventListener('scroll', function() {
          if (window.scrollY > 50) {
            document.getElementById('main_navbar').classList.add('sticky-navbar');
            // add padding top to show content behind navbar
            var navbar_height = document.querySelector('.navbar').offsetHeight;
            document.body.style.paddingTop = navbar_height + 'px';
          } else {
            document.getElementById('main_navbar').classList.remove('sticky-navbar');
             // remove padding top from body
            document.body.style.paddingTop = '0';
          } 
      });
    });

})(jQuery);