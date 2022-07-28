document.addEventListener('DOMContentLoaded', (e) => {
    const urlSearchParams = new URLSearchParams(window.location.hash);
    const accessToken = urlSearchParams.get('access_token');

    if (accessToken) {
        window.location.href = `/discord/check?access_token=${accessToken}`;
        return;
    }

    window.location.href = "/";
});