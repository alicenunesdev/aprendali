
function navigate(section) {
    fetch('php/' + section + '.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('content').innerHTML = html;
        });
}
function toggleMenu() {
    const nav = document.getElementById('nav');
    nav.classList.toggle('show');
}
