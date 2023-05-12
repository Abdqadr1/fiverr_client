
const form = document.querySelector("#datasource-form");
const fileInput = form.querySelector("#csv");
const fileDiv = form.querySelector("#file-div");
const fileSpan = fileDiv.getElementsByTagName('span')[0];
const stateInput = form.querySelector("#state");
const countyInput = form.querySelector("#county");
const municipalityInput = form.querySelector("#municipality");

stateInput.addEventListener("change", async e => {
    const val = e.target.value;
    const result = await fetch(`get_states_data.php?which=state&value=${val}`);
    const data = await result.json();
    countyInput.value = '';
    countyInput.innerHTML = `<option value="" hidden>Select county</option>`;
    data.forEach(c => {
        countyInput.innerHTML += `<option value="${c.id}">${c.name}</option>`;
    });
});

countyInput.addEventListener("change", async e => {
    const val = e.target.value;
    const result = await fetch(`get_states_data.php?which=county&value=${val}`);
    const data = await result.json();
    municipalityInput.value = "";
    municipalityInput.innerHTML = `<option value="" hidden>Select municipality</option>`;
    data.forEach(c => {
        municipalityInput.innerHTML += `<option value="${c.id}">${c.name}</option>`;
    });
});




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