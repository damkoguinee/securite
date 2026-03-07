function formatMontant(input) {
    // Supprimer tous les espaces existants
    let valueWithoutSpaces = input.value.replace(/\s/g, '');
    // Utiliser une expression régulière pour ajouter un espace après chaque groupe de trois chiffres, sauf pour le premier groupe
    let formattedValue = valueWithoutSpaces.replace(/\B(?=(\d{3})+(?!\d))/g, ' '); // Ajouter un espace pour les milliers
    input.value = formattedValue;
}

// Exporter la fonction
window.formatMontant = formatMontant;