document.addEventListener('DOMContentLoaded', function () {
    const accordionButtons = document.querySelectorAll('.accordion-button');

    accordionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const content = button.nextElementSibling;
            const wasActive = button.classList.contains('active');

            // 1. Zavřít všechny ostatní panely
            accordionButtons.forEach(btn => {
                if (btn !== button) {
                    btn.classList.remove('active');
                    btn.nextElementSibling.style.maxHeight = null;
                }
            });

            // 2. Otevřít nebo zavřít kliknutý panel
            if (wasActive) {
                // Pokud byl aktivní, zavřít ho
                button.classList.remove('active');
                content.style.maxHeight = null;
            } else {
                // Pokud byl zavřený, otevřít ho
                button.classList.add('active');
                // Nastavit max-height na skutečnou výšku obsahu
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });
});