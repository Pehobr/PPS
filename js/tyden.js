document.addEventListener('DOMContentLoaded', function() {
    console.log("Stránka Týdenního přehledu byla úspěšně načtena.");

    const dayCards = document.querySelectorAll('.day-card.day-reading');

    dayCards.forEach(card => {
        card.addEventListener('click', function(event) {
            // Nespouštět, pokud uživatel kliká přímo na audio přehrávač
            if (event.target.tagName.toLowerCase() === 'audio' || event.target.closest('audio')) {
                return;
            }
            
            const audioPlayer = this.querySelector('audio');
            if (audioPlayer) {
                // Pokud je audio pozastavené, přehraj ho. Jinak ho pozastav.
                if (audioPlayer.paused) {
                    // Pozastavíme všechna ostatní audia na stránce
                    document.querySelectorAll('audio').forEach(ap => {
                        if (ap !== audioPlayer) {
                            ap.pause();
                        }
                    });
                    audioPlayer.play();
                } else {
                    audioPlayer.pause();
                }
            }
        });
    });
});