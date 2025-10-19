import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const dropdowns = [];

    document.querySelectorAll('[data-dropdown-toggle]').forEach((button) => {
        const menuId = button.getAttribute('data-dropdown-toggle');
        if (!menuId) {
            return;
        }

        const menu = document.getElementById(menuId);
        if (!menu) {
            return;
        }

        const closeMenu = () => {
            if (!menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
            button.setAttribute('aria-expanded', 'false');
        };

        const openMenu = () => {
            menu.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
        };

        dropdowns.push({ closeMenu });

        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isHidden = menu.classList.contains('hidden');

            dropdowns.forEach(({ closeMenu: close }) => close());

            if (isHidden) {
                openMenu();
            }
        });

        menu.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', () => {
        dropdowns.forEach(({ closeMenu }) => closeMenu());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            dropdowns.forEach(({ closeMenu }) => closeMenu());
        }
    });
});
