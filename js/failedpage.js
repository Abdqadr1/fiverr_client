
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
            formData.append(`${name}[]`, value);
        }
        
    }
    console.log(formData);
    fetch("skip.php", {
        method: "POST",
        body: formData,
    })
        .then(res => res.text())
        .then((r) => {
            console.log(r);
            //window.location = "statusbar.php"
        })
        .catch(err => { console.log(err) });
})