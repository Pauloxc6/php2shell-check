document.addEventListener('DOMContentLoaded', () => {
    // Copiar blocos de código
    document.querySelectorAll('.copy-button').forEach(button => {
        button.addEventListener('click', () => {
            const pre = button.parentElement.querySelector('pre');
            const text = pre.innerText;
            navigator.clipboard.writeText(text).then(() => {
                button.textContent = 'Copied!';
                setTimeout(() => {
                    button.textContent = 'Copy';
                }, 2000);
            });
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
        const repoOwner = "Pauloxc6";
        const repoName = "php2shell-check";
        const badgeElement = document.getElementById("dynamic-version-badge");

        if (!badgeElement) return;

        fetch(`https://api.github.com/repos/${repoOwner}/${repoName}/releases/latest`)
            .then(response => {
                if (!response.ok) throw new Error("Error fetching version");
                return response.json();
            })
            .then(data => {
                badgeElement.textContent = data.tag_name;
            })
            .catch(error => {
                console.warn("Unable to upload version from GitHub, keeping fallback", error);
                badgeElement.textContent = "v1.4.1";
            });
    });