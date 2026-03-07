$(document).ready(function(){
    let timer; // timer global

    $('#search_ajax').keyup(function(){
        clearTimeout(timer); // Annule le précédent timer à chaque frappe

        const utilisateur = $(this).val();
        const currentUrl = window.location.pathname;

        timer = setTimeout(function(){
            $('#result_search').html(""); // Vide les anciens résultats

            if (utilisateur !== '') {
                $.ajax({
                    type: 'GET',
                    url: currentUrl,
                    data: { search: utilisateur },
                    success: function(data){
                        if (data.length > 0) {
                            for (let i = 0; i < data.length; i++) {
                                const clickableName = '<a href="' + currentUrl + '?id_search=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';
                                $('#result_search').append('<div style="text-decoration: underline;">' + clickableName + '</div>');
                            }
                        } else {
                            $('#result_search').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucune correspondance trouvée</div>");
                        }
                    }
                });
            }
        }, 300); // 300ms d'attente après la dernière frappe
    });

    // $('#search_client').keyup(function(){
    //     clearTimeout(timer); // Annule le précédent timer à chaque frappe
    //     var utilisateur = $(this).val();
    //     var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
    //     timer = setTimeout(function(){
    //         $('#result-search').html("");
    //         if (utilisateur != '') {
    //             $.ajax({
    //                 type: 'GET',
    //                 url: currentUrl, // Utiliser l'URL actuelle
    //                 data: { search: utilisateur },
    //                 success: function(data){
    //                     if (data.length > 0) {
    //                         // Update the result container with the search results
    //                         for (var i = 0; i < data.length; i++) {
    //                             var clickableName = '<a href="' + currentUrl + '?id_client_search=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

    //                             $('#result-search').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
    //                         }
    //                     } else {
    //                         $('#result-search').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun collaborateur trouvé</div>");
    //                     }
    //                 }
    //             });
    //         }
    //     }, 300);
    // });

    $('#search_client').keyup(function(){
    clearTimeout(timer); // Annule le précédent timer à chaque frappe
    var utilisateur = $(this).val();
    var currentUrl = window.location.pathname; // Chemin de base, sans query string

    // Récupère le paramètre "start" depuis l'URL
    var urlParams = new URLSearchParams(window.location.search);
    var startParam = urlParams.get('start'); // peut être null si non défini

    timer = setTimeout(function(){
        $('#result-search').html("");
        if (utilisateur != '') {
            $.ajax({
                type: 'GET',
                url: currentUrl,
                data: {
                    search: utilisateur,
                    start: startParam // <-- on renvoie le paramètre start dans l’AJAX
                },
                success: function(data){
                    if (data.length > 0) {
                        for (var i = 0; i < data.length; i++) {
                            // Construit dynamiquement le lien avec start (s'il existe)
                            var link = currentUrl + '?id_client_search=' + encodeURIComponent(data[i].id);
                            if (startParam) {
                                link += '&start=' + encodeURIComponent(startParam);
                            }

                            var clickableName = '<a href="' + link + '">' + data[i].nom + '</a>';

                            $('#result-search').append('<div style="text-decoration: underline;">' + clickableName + '</div>');
                        }
                    } else {
                        $('#result-search').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun collaborateur trouvé</div>");
                    }
                }
            });
        }
    }, 300);
});



    $('#search_adresse').keyup(function(){
        clearTimeout(timer); // Annule le précédent timer à chaque frappe
        var utilisateur = $(this).val();
        var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
        timer = setTimeout(function(){
            $('#result-search').html("");            
            if (utilisateur != '') {
                $.ajax({
                    type: 'GET',
                    url: currentUrl, // Utiliser l'URL actuelle
                    data: { search: utilisateur },
                    success: function(data){
                        if (data.length > 0) {
                            // Update the result container with the search results
                            for (var i = 0; i < data.length; i++) {
                                var clickableName = '<a href="' + currentUrl + '?id_adresse_search=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

                                $('#result-search').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
                            }
                        } else {
                            $('#result-search').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucune adresse trouvée</div>");
                        }
                    }
                });
            }
        }, 300); // 300ms d'attente après la dernière frappe
    });
});


    

    
    