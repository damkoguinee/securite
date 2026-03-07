$(document).ready(function(){
    $('#search_product').keyup(function(){
        $('#result-search-product').html("");

        var utilisateur = $(this).val();
        var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
        if (utilisateur != '') {
            $.ajax({
                type: 'GET',
                url: currentUrl, // Utiliser l'URL actuelle
                data: { search_product: utilisateur },
                success: function(data){
                console.log(data);

                    if (data.length > 0) {
                        // Update the result container with the search results
                        for (var i = 0; i < data.length; i++) {
                            var clickableName = '<a href="' + currentUrl + '?id_product_search=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

                            $('#result-search-product').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
                        }
                    } else {
                        $('#result-search-product').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun utilisateur</div>");
                    }
                }
            });
        }
    });
});


