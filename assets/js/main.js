$(document).ready(function() {
    var isMenuExpanded = false;

    function calculateMenuHeight() {
        var menuHeight = 70;

        $('nav .navbar-nav').children().each(function() {
            if ($(this).hasClass('nav-item')) {
                menuHeight += $(this).outerHeight(true);
                if ($(this).children('.dropdown-menu').length) {
                    var dropdownMenu = $(this).children('.dropdown-menu');
                    var dropdownHeight = 0;
                    if (dropdownMenu.is(':visible')) {
                        dropdownMenu.children().each(function() {
                            dropdownHeight += $(this).outerHeight(true);
                        });
                    }
                    menuHeight += dropdownHeight;
                }
            }
        });

        return menuHeight;
    }

    function toggleCarouselMargin() {
        var carousel = $('.carousel');
        var menuHeight = calculateMenuHeight();

        if (isMenuExpanded) {
            carousel.css('margin-top', menuHeight + 'px');
        } else {
            carousel.css('margin-top', '5px');
        }
    }

    $('.navbar-toggler').click(function() {
        isMenuExpanded = !isMenuExpanded;
        toggleCarouselMargin();
    });

    $('nav .dropdown').on('shown.bs.dropdown', function() {
        isMenuExpanded = true;
        toggleCarouselMargin();
    });

    $('nav .dropdown').on('hidden.bs.dropdown', function() {
        isMenuExpanded = false;
        toggleCarouselMargin();
    });
    // ... votre code existant ...

    // Ajoutez ces lignes pour gérer l'animation de la div promo
    var promoDiv = $('.promo');
    setTimeout(function() {
        promoDiv.addClass('show');
    }, 500);

    
});



window.onscroll = function() { scrollFunction() };

function scrollFunction() {
    var scrollButton = document.getElementById("scrollButton");
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        scrollButton.style.display = "block";
    } else {
        scrollButton.style.display = "none";
    }
}

function scrollToTop() {
    document.documentElement.scrollTop = 0;
}

function alerteS(){
    return(confirm('Etes-vous sûr de vouloir supprimer?'));
}

function alerteCloture(){
    return(confirm('Etes-vous sûr de vouloir Clôtuer ?'));
}

function alerteConfirmation(){
    return(confirm('Confirmez-vous cette opération ?'));
}




