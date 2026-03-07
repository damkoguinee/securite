// function initMap() {
//     // Création de la carte
//     const map = new google.maps.Map(document.getElementById('map'), {
//         center: { lat: 9.51706, lng: -13.699843 },
//         zoom: 5
//     });

//     // Récupérer les données depuis l'attribut de données
//     const lieuxVentesDataElement = document.getElementById('lieux-ventes-data');
//     const lieuxVentes = JSON.parse(lieuxVentesDataElement.textContent);

//     // Parcourir les lieux de vente et créer des marqueurs
//     lieuxVentes.forEach(function(lieu) {
//         const marker = new google.maps.Marker({
//             position: { lat: parseFloat(lieu.lat), lng: parseFloat(lieu.lng) },
//             map: map,
//             title: lieu.adresse
//         });

//         // Ajoutez des informations supplémentaires à la fenêtre d'info-bulle si nécessaire
//         const infowindow = new google.maps.InfoWindow({
//             content: "<strong>" + lieu.adresse + "</strong><br>" + lieu.ville
//         });

//         marker.addListener('click', function() {
//             infowindow.open(map, marker);
//         });
//     });
// }