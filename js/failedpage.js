
const tableBody = document.getElementById('table-body');
const tryAgainForm = document.getElementById('try-again-form');

tryAgainForm.addEventListener('submit', e => {
    // code ...
    e.preventDefault();
    const formData = new FormData(e.target);
    const rows = tableBody.getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const tr = rows[i];
        const tds = tr.getElementsByTagName('td');
        for (let j = 0; j < tds.length; j++) {
            const td = tds[j];
            let name = td.getAttribute('data-name');
            const value = td.textContent ?? "";
            if (!name) continue;
            name = name.replace('\ufeff', '');
            name = name.replace(/[^a-zA-Z0-9_ ]+/gi, '');
            name = name.replace(/\s+/g, '_');
            formData.append(`${name}[]`, value);
        }
        
    }
    fetch("skip.php", {
        method: "POST",
        body: formData,
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP status: ${res.status} ${res.statusText}`);
            }
            return res.text();
        })
        .then((r) => {
            window.location = "statusbar.php"
        })
        .catch(err => { console.log(err) });
})