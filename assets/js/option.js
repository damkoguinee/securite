// pour ajouter des options dynamiquement
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addOptionDimensionBtn').addEventListener('click', function(event) {
        event.preventDefault(); // Empêche le comportement par défaut du bouton

        var newOptionDimensionInput = document.getElementById('newOptionDimension');
        var newOptionDimensionValue = newOptionDimensionInput.value.trim();
        if (newOptionDimensionValue !== '') {
            var select = document.getElementById('dimension');
            var option = document.createElement('option');
            option.value = newOptionDimensionValue;
            option.text = newOptionDimensionValue;
            select.add(option);
            newOptionDimensionInput.value = ''; // Efface le champ de saisie après l'ajout
        }
    });
});

// pour ajouter des options dynamiquement
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addOptionEpaisseurBtn').addEventListener('click', function(event) {
        event.preventDefault(); // Empêche le comportement par défaut du bouton

        var newOptionEpaisseurInput = document.getElementById('newOptionEpaisseur');
        var newOptionEpaisseurValue = newOptionEpaisseurInput.value.trim();
        if (newOptionEpaisseurValue !== '') {
            var select = document.getElementById('epaisseur');
            var option = document.createElement('option');
            option.value = newOptionEpaisseurValue;
            option.text = newOptionEpaisseurValue;
            select.add(option);
            newOptionEpaisseurInput.value = ''; // Efface le champ de saisie après l'ajout
        }
    });
});
