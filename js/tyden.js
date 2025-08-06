// --- TESTOVACÍ VÝPIS ---
console.log("Načtena nová verze skriptu tyden.js - v.3");

document.addEventListener('DOMContentLoaded', function () {
    
    // --- Logika pro akordeon (hlavní tlačítka dnů) ---
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

    // --- Logika pro vlastní audio přehrávače ---
    const audioPlayers = document.querySelectorAll('.custom-audio-player-wrapper');

    audioPlayers.forEach(playerWrapper => {
        const audio = playerWrapper.querySelector('audio');
        if (!audio) return;
        const playPauseBtn = playerWrapper.querySelector('.play-pause-btn');
        const progressBarFill = playerWrapper.querySelector('.progress-bar-fill');
        const progressBarContainer = playerWrapper.querySelector('.progress-bar-container');
        const volumeSlider = playerWrapper.querySelector('.volume-slider');

        function togglePlayPauseIcon() {
            if (audio.paused) {
                playPauseBtn.classList.remove('playing');
                playPauseBtn.classList.add('paused');
            } else {
                playPauseBtn.classList.remove('paused');
                playPauseBtn.classList.add('playing');
            }
        }
        
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
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

        audio.addEventListener('play', togglePlayPauseIcon);
        audio.addEventListener('pause', togglePlayPauseIcon);
        audio.addEventListener('ended', togglePlayPauseIcon);

        document.addEventListener('play', (event) => {
            if (event.target !== audio) {
                audio.pause();
            }
        }, true);

        audio.addEventListener('timeupdate', () => {
            if(audio.duration){
                const progress = (audio.currentTime / audio.duration) * 100;
                progressBarFill.style.width = `${progress}%`;
            }
        });

        progressBarContainer.addEventListener('click', (e) => {
            const rect = progressBarContainer.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = progressBarContainer.clientWidth;
            const duration = audio.duration;
            if(duration) {
                audio.currentTime = (clickX / width) * duration;
            }
        });

        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                audio.volume = e.target.value;
            });
        }
        
        togglePlayPauseIcon();
    });

    // --- LOGIKA PRO TLAČÍTKA JAZYK/INSPIRACE/SLOVNÍK ---
    const extraButtons = document.querySelectorAll('.extra-button');

    extraButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const content = document.getElementById(targetId);

            if (content) {
                const wasActive = button.classList.contains('active');
                
                const parentWrapper = button.closest('.extra-content-wrapper');
                if (parentWrapper) {
                    parentWrapper.querySelectorAll('.extra-button').forEach(btn => {
                        if (btn !== button) btn.classList.remove('active');
                    });
                    parentWrapper.querySelectorAll('.extra-content').forEach(cont => {
                        if (cont.id !== targetId) cont.style.maxHeight = null;
                    });
                }

                if (wasActive) {
                    button.classList.remove('active');
                    content.style.maxHeight = null;
                } else {
                    button.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + "px";
                }

                const mainAccordionContent = button.closest('.accordion-content');
                if (mainAccordionContent) {
                    setTimeout(function() {
                        mainAccordionContent.style.maxHeight = mainAccordionContent.scrollHeight + "px";
                    }, 400); 
                }
            }
        });
    });
    
    // --- FINÁLNÍ OPRAVENÁ LOGIKA PRO VLASTNÍ MOBILNÍ MENU ---
    const mobilniTlacitko = document.getElementById('vlastni-mobilni-toggle');
    const menuKontejner = document.getElementById('header-aside'); 

    if (mobilniTlacitko && menuKontejner) {
        mobilniTlacitko.addEventListener('click', function() {
            // Přepneme třídu, která menu zobrazí nebo skryje
            menuKontejner.classList.toggle('mobil-menu-otevrene');
        });
    }
}); // <-- Zde správně končí listener 'DOMContentLoaded'