// main.js - Core UI Interaction Script

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const sidebarMenu = document.getElementById('sidebar-menu');

    if (mobileMenuBtn && sidebarMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebarMenu.classList.remove('hidden');
            sidebarMenu.classList.add('flex');
        });
    }

    if (closeMenuBtn && sidebarMenu) {
        closeMenuBtn.addEventListener('click', () => {
            sidebarMenu.classList.remove('flex');
            sidebarMenu.classList.add('hidden');
        });
    }

    // 2. Real-time Digital Clock
    const liveClock = document.getElementById('live-clock');
    if (liveClock) {
        function updateClock() {
            const now = new Date();
            let hours = now.getHours().toString().padStart(2, '0');
            let minutes = now.getMinutes().toString().padStart(2, '0');
            let seconds = now.getSeconds().toString().padStart(2, '0');
            liveClock.textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 3. Fading Out Flash Alerts
    const alertBox = document.getElementById('flash-alert');
    if (alertBox) {
        setTimeout(() => {
            alertBox.classList.add('opacity-0', 'translate-y-[-20px]');
            setTimeout(() => {
                alertBox.remove();
            }, 300);
        }, 4000);
    }
});
