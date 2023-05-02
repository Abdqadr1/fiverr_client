
const progressBar = document.getElementById("progress-bar");
const progressText = document.getElementById("progress-text");
const successRows = document.getElementById("success-rows");
const errorRows = document.getElementById("error-rows");
const iframe = document.getElementById('progress-frame');
const feedback = document.getElementById("feedback");

const spinner = document.getElementById("spinner");

const cancelBtn = document.getElementById("cancel-btn");
const continueBtn = document.getElementById("continue-btn");
let source;

cancelBtn.addEventListener('click', e => {
    if (confirm('Are you sure you want to do this?')) {
            iframe.src = "";
            window.location = "datasource.php";
    }
});



window.process_progress = 0;
function continuePage() {
    if (process_progress >= 100) {
        window.location = "failedpage.php";
    }
}

function setProgress(success, errorArray, all) {
    // console.log(success, errorArray, all);
    const errors = JSON.parse(errorArray);
    const failed = errors.length;
    let progress = (success + failed) * (100 / all);
    progress = Math.floor(progress);
    window.process_progress = progress;
    progressBar.style.width = progress + "%";
    progressText.textContent = `Finished ${success + failed} of ${all} | ${progress}% Complete`;
    successRows.textContent = `${success} rows ✔`;
    errorRows.textContent = `${failed} rows 🗙`;

    feedback.innerHTML = "";
    for (let i = 0; i < failed; i++) {
        let errObj = errors[i];
        const el = `<div class='d-flex'>
                <div class='w-25'>Row ${errObj.row_num}</div>
                <div class='w-75'><strong>Error: </strong><span class='small'>${errObj.message}</span></div>
            </div>`;
        feedback.innerHTML += el;
    }

    if (progress >= 100) spinner?.remove();
}

function serverError(message) {
    spinner.remove();
    alert(message);
}

window.onload = function (e) {
    iframe.src = "statusbar_back.php";
}