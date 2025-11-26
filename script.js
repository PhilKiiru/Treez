
const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const btnPopup = document.querySelector('.btnLogin-popup');
const iconClose = document.querySelector('.icon-close');
const welcomeCard = document.getElementById('homepageWelcomeCard');

if (registerLink) {
    registerLink.addEventListener('click', ()=> {
        wrapper.classList.add('active');
        if (welcomeCard) welcomeCard.style.display = 'none';
    });
}

if (loginLink) {
    loginLink.addEventListener('click', ()=> {
        wrapper.classList.remove('active');
        if (welcomeCard) welcomeCard.style.display = 'none';
    });
}

if (btnPopup) {
    btnPopup.addEventListener('click', ()=> {
        wrapper.classList.add('active-popup');
        if (welcomeCard) welcomeCard.style.display = 'none';
    });
}

if (iconClose) {
    iconClose.addEventListener('click', ()=> {
        wrapper.classList.remove('active-popup');
        if (welcomeCard) welcomeCard.style.display = 'flex';
    });
}
