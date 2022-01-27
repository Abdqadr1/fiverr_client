//modal
const compareBtn = document.querySelector("li a#compareBtn")
const modalTag = document.querySelector("div#modal");
const myModal = new bootstrap.Modal(modalTag, {
  keyboard: false
})
const selectedTax = []; //array to hold the selected tax lien for comparison
const allCheckInputs = document.querySelectorAll("input.check");
const noteDiv = document.querySelector("div#note")
allCheckInputs.forEach(input => {
    input.onchange = () => {
        if (input.checked) {
            selectedTax.push(input)
        } else {
            selectedTax.splice(selectedTax.indexOf(input), 1)
            // console.log(selectedTax);
        }
        if (selectedTax.length == 2) {
            noteDiv.textContent = "2 of 2 items selected. Ready to compare!"
        } else if (selectedTax.length < 2) {
            noteDiv.textContent = `${selectedTax.length} of 2 items selected.`
        } else {
            noteDiv.textContent = "You've selected one too many.. can't compare more than two!"
        }
    }
})
compareBtn.onclick = event => {
    if (selectedTax.length === 2) {
        myModal.show();
    }
}

// google pie chart
const tdGraphics = document.querySelectorAll("td.graphic")
google.charts.load('current', { 'packages': ['corechart'] });
tdGraphics.forEach(td => {
    const chartDiv = td.querySelector("div#pieChart");
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
        const data = google.visualization.arrayToDataTable([
            ['Property Type', 'Number'],
            ['Single Family',  2],
            ['Multi Family', 4],
            ['Commercial',  6]
        ]);
        const options = {
            backgroundColor: 'transparent',
            legend:'none',
            width: '100%',
            height: '100%',
            pieSliceText: 'none',
            chartArea: {
                left: "3%",
                top: "3%",
                height: "200",
                width: "300",
        }
    };

        const chart = new google.visualization.PieChart(chartDiv);

        chart.draw(data, options);
    }
})