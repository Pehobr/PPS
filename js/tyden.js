document.addEventListener('DOMContentLoaded', function () {
    // --- Logika pro akordeon (zůstává stejná) ---
    const accordionButtons = document.querySelectorAll('.accordion-button');

    accordionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const content = button.nextElementSibling;
            const wasActive = button.classList.contains('active');

            accordionButtons.forEach(btn => {
                if (btn !== button) {
                    btn.classList.remove('active');
                    btn.nextElementSibling.style.maxHeight = null;
                }
            });

            if (wasActive) {
                button.classList.remove('active');
                content.style.maxHeight = null;
            } else {
                button.classList.add('active');
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });

    // --- NOVÁ LOGIKA PRO VLASTNÍ AUDIO PŘEHRÁVAČE ---
    const audioPlayers = document.querySelectorAll('.custom-audio-player-wrapper');

    audioPlayers.forEach(playerWrapper => {
        const audio = playerWrapper.querySelector('audio');
        const playPauseBtn = playerWrapper.querySelector('.play-pause-btn');
        const progressBarFill = playerWrapper.querySelector('.progress-bar-fill');
        const progressBarContainer = playerWrapper.querySelector('.progress-bar-container');
        const volumeSlider = playerWrapper.querySelector('.volume-slider');

        // Funkce pro přepnutí ikony
        function togglePlayPauseIcon() {
            if (audio.paused) {
                playPauseBtn.classList.remove('playing');
                playPauseBtn.classList.add('paused');
            } else {
                playPauseBtn.classList.remove('paused');
                playPauseBtn.classList.add('playing');
            }
        }
        
        // Ovládání play/pauzy
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                // Pozastavit všechny ostatní audia na stránce
                 document.querySelectorAll('audio').forEach(otherAudio => {
                    if (otherAudio !== audio) {
                        otherAudio.pause();
                    }
                });
                audio.play();
            } else {
                audio.pause();
            }
        });

        // Aktualizace ikony při změně stavu
        audio.addEventListener('play', togglePlayPauseIcon);
        audio.addEventListener('pause', togglePlayPauseIcon);
        audio.addEventListener('ended', togglePlayPauseIcon);
         // Když jiné audio začne hrát, toto se pozastaví a aktualizuje ikonu
        document.addEventListener('play', (event) => {
            if (event.target !== audio) {
                audio.pause();
            }
        }, true);


        // Aktualizace časové lišty
        audio.addEventListener('timeupdate', () => {
            const progress = (audio.currentTime / audio.duration) * 100;
            progressBarFill.style.width = `${progress}%`;
        });

        // Přetáčení kliknutím na lištu
        progressBarContainer.addEventListener('click', (e) => {
            const rect = progressBarContainer.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = progressBarContainer.clientWidth;
            const duration = audio.duration;
            audio.currentTime = (clickX / width) * duration;
        });

        // Ovládání hlasitosti
        volumeSlider.addEventListener('input', (e) => {
            audio.volume = e.target.value;
        });
        
        // Na začátku nastavíme výchozí ikonu
        togglePlayPauseIcon();
    });
});