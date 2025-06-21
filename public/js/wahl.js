function copyButtonHandler(event, button, url) {
    event.preventDefault();
    navigator.clipboard.writeText(url).then(function () {
        button.textContent = "Kopiert!";
        setTimeout(() => {
            button.textContent = "Kopieren";
        }, 2000);
    });
}