$(document).ready(function(){
    $('#search_client').keyup(function(){
        $('#result-search').html("");

        var utilisateur = $(this).val();
        var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
        if (utilisateur != '') {
            $.ajax({
                type: 'GET',
                url: currentUrl, // Utiliser l'URL actuelle
                data: { search: utilisateur },
                success: function(data){
                    if (data.length > 0) {
                        // Update the result container with the search results
                        for (var i = 0; i < data.length; i++) {
                            var clickableName = '<a href="' + currentUrl + '?id_client_search=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

                            $('#result-search').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
                        }
                    } else {
                        $('#result-search').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun collaborateur trouvé</div>");
                    }
                }
            });
        }
    });

   /* ===========================
   🔍  Recherche Contrat
    ============================ */
    if ($('#search_contrat').length) {

        $('#search_contrat').keyup(function () {
            $('#result_search_contrat').html("");

            var utilisateur = $(this).val();
            var currentUrl = window.location.pathname;

            var jour = $('#jour').length ? $('#jour').val() : '';
            var periode = $('#periode').length ? $('#periode').val() : '';
            var date1 = $('#date1').length ? $('#date1').val() : '';
            var date2 = $('#date2').length ? $('#date2').val() : '';

            if (utilisateur !== '') {
                $.ajax({
                    type: 'GET',
                    url: window.searchContratUrl ?? currentUrl,
                    data: { searchContrat: utilisateur },
                    success: function (data) {

                        if (data.length > 0) {
                            data.forEach(function (item) {

                                let params = `?id_contrat_search=${item.id}`;

                                if (jour) {
                                    params += `&jour=${jour}`;
                                }

                                if (periode) {
                                    params += `&periode=${periode}`;
                                }

                                if (date1) {
                                    params += `&date1=${date1}`;
                                }
                                if (date2) {
                                    params += `&date2=${date2}`;
                                }

                                const link = `
                                    <a href="${currentUrl}${params}">
                                        ${item.nom}
                                    </a>
                                `;

                                $('#result_search_contrat')
                                    .append(`<div style="text-decoration:underline;">${link}</div>`);
                            });

                        } else {
                            $('#result_search_contrat').html(
                                "<div style='font-size:20px;text-align:center;margin-top:10px'>Aucun contrat trouvé</div>"
                            );
                        }
                    }
                });
            }
        });
    }


    $('#search_agent_contrat').keyup(function () {
        $('#result-search').html("");

        var utilisateur = $(this).val().trim();
        var currentUrl = window.location.pathname;
        var currentParams = new URLSearchParams(window.location.search);

        // Récupère contrat et jour
        var id_contrat_search = currentParams.get('id_contrat_search');
        var jour = currentParams.get('jour');

        // Récupère aussi les options périodiques si actives
        var periodique = $('#periodicite').is(':checked') ? 1 : 0;
        var duree = $('[name="duree"]').val();
        var jours = [];
        $('[name="jours[]"]:checked').each(function () {
            jours.push($(this).val());
        });

        if (utilisateur !== '') {
            $.ajax({
                type: 'GET',
                url: currentUrl,
                data: { search: utilisateur, id_contrat_search: id_contrat_search, jour: jour },
                success: function (data) {
                    $('#result-search').empty();

                    if (data.length > 0) {
                        data.forEach(function (item) {
                            // On reconstruit l’URL avec TOUTES les infos
                            var url = currentUrl + '?id_contrat_search=' + encodeURIComponent(id_contrat_search)
                                    + '&jour=' + encodeURIComponent(jour)
                                    + '&id_client_search=' + encodeURIComponent(item.id);

                            if (periodique) url += '&periodique=1';
                            if (duree) url += '&duree=' + encodeURIComponent(duree);
                            if (jours.length > 0) url += '&' + jours.map(j => 'jours[]=' + encodeURIComponent(j)).join('&');

                            var clickableName = `
                                <a href="${url}" class="list-group-item list-group-item-action">
                                    <i class="fa fa-user text-primary me-2"></i>${item.nom}
                                </a>`;
                            $('#result-search').append(clickableName);
                        });
                    } else {
                        $('#result-search').html(
                            "<div class='text-center text-muted py-2'>Aucun agent trouvé</div>"
                        );
                    }
                }
            });
        } else {
            $('#result-search').empty();
        }
    });



    $('#search_all_user').keyup(function(){
        $('#result_search_all_user').html("");

        var utilisateur = $(this).val();
        var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
        if (utilisateur != '') {
            $.ajax({
                type: 'GET',
                url: currentUrl, // Utiliser l'URL actuelle
                data: { search_all_user : utilisateur },
                success: function(data){
                    if (data.length > 0) {
                        // Update the result container with the search results
                        for (var i = 0; i < data.length; i++) {
                            var clickableName = '<a href="' + currentUrl + '?id_user=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

                            $('#result_search_all_user').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
                        }
                    } else {
                        $('#result_search_all_user').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun collaborateur trouvé</div>");
                    }
                }
            });
        }
    });

    $('#search_personnel').keyup(function(){
        $('#result_search_personnel').html("");

        var utilisateur = $(this).val();
        var currentUrl = window.location.pathname; // Récupérer l'URL actuelle
        if (utilisateur != '') {
            $.ajax({
                type: 'GET',
                url: currentUrl, // Utiliser l'URL actuelle
                data: { search_personnel : utilisateur },
                success: function(data){
                    if (data.length > 0) {
                        // Update the result container with the search results
                        for (var i = 0; i < data.length; i++) {
                            var clickableName = '<a href="' + currentUrl + '?id_personnel=' + encodeURIComponent(data[i].id) + '">' + data[i].nom + '</a>';

                            $('#result_search_personnel').append('<div style="text-decoration: underline; ">' + clickableName + '</div>');
                        }
                    } else {
                        $('#result_search_personnel').html("<div style='font-size: 20px; text-align: center; margin-top: 10px'>Aucun collaborateur trouvé</div>");
                    }
                }
            });
        }
    });
});