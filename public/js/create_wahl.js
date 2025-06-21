// Warte bis das DOM geladen ist, damit es manipuliert werden kann
document.addEventListener('DOMContentLoaded', () => {
    // Div aller Wahlgänge
    const wahlgangGroup = document.getElementById('wahlgaenge');
    // Template für einen Wahlgang
    const wahlgangTemplate = document.getElementById('wahlgang-template');
    // Button zum Hinzufügen eines Wahlgangs
    const addWahlgangButton = document.getElementById('add-wahlgang');
    // Index für unique IDs der Wahlgänge
    let wahlgangCount = 0;

    addWahlgangButton.addEventListener('click', function () {

        // Klonen des Templates für einen neuen Wahlgang
        let newWahlgang = wahlgangTemplate.cloneNode(true);
        wahlgangCount += 1;
        // Setzen einer neuen ID für den Wahlgang
        newWahlgang.id = "wahlgang_g" + wahlgangCount;
        // Sichtbar machen des neuen Wahlgangs
        newWahlgang.classList.remove('d-none');

        // Entfernen-Knopf funktional machen
        let remove = newWahlgang.getElementsByClassName("remove-wahlgang")[0];
        remove.addEventListener('click', function () {
            newWahlgang.remove();
        });

        wahlgangGroup.appendChild(newWahlgang);
    })
})