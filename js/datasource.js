
const form = document.querySelector("#datasource-form");
const fileInput = form.querySelector("#csv");
const fileDiv = form.querySelector("#file-div");
const fileSpan = fileDiv.getElementsByTagName('span')[0];

fileInput.addEventListener('change', e => fileSpan.textContent = e.target.files[0]?.name)

form.addEventListener('submit', e => {
    const file = fileInput.files[0];
    const extension = file?.name.split('.').pop();
    let okay = true;

    if (!file) {
        fileDiv.classList.add('border', 'border-danger');
        fileSpan.textContent = "Select file";
        okay = false;
    } else if (extension !== "csv") { 
        alert("Invalid file format");
        okay = false;
    }else if (file.size > 5000) { 
        alert("File is bigger than 5KB");
        okay = false;
    }

    if(!okay) e.preventDefault();
})