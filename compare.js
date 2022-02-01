//modal
const compareBtn = document.querySelector("li a#compareBtn")
const compareModalTag = document.querySelector("div#compareModal");
const avgReportModalTag = document.querySelector("div#avgReportModal");
const regularLienModalTag = document.querySelector("div#regularLienModal");

// pop ups 
const regularLienModal = new bootstrap.Modal(regularLienModalTag, {keyboard: false})
const avgReportModal = new bootstrap.Modal(avgReportModalTag, {keyboard: false})
const compareModal = new bootstrap.Modal(compareModalTag, { keyboard: false })

let selectedTax = []; //array to hold the selected tax lien for comparison
let selectedIds = []; // array to hold the lien numbers of selected lien

const findLienNo = (input, id) => {
    let text = "";
    if (id == null) {
        const parent = input.parentElement.parentElement;
        text = parent.querySelector("#lienNo").textContent
    } else text = id;
    if (text.indexOf('#') == 0) {
        return text.substring(1);
        }
    return text;
}
const allCheckInputs = document.querySelectorAll("input.check");
const noteDiv = document.querySelector("div#note")
allCheckInputs.forEach(input => {
    input.onclick = event => event.stopPropagation();
    input.onchange = () => {
        const id = findLienNo(input, null);
        if (input.checked) {
            selectedTax.push(input)
            selectedIds.push(id)
        } else {
            selectedTax.splice(selectedTax.indexOf(input), 1)
            selectedIds.splice(selectedIds.indexOf(id), 1)
        }
        // add selected ids list to the url
        const url = new URL(window.location);
        url.searchParams.set('myList', selectedIds.join(","));
        history.pushState({}, '', url);
        if (selectedTax.length == 2) {
            noteDiv.textContent = "2 of 2 items selected. Ready to compare!"
        } else if (selectedTax.length < 2) {
            noteDiv.textContent = `${selectedTax.length} of 2 items selected.`
        } else {
            noteDiv.textContent = "You've selected one too many.. can't compare more than two!"
        }
    }
})

// add selected liens to My List
const addList = document.querySelector("ul#addList")
const addBtn = document.querySelector("a#addBtn")
addBtn.onclick = event => {
    event.preventDefault();
    if (selectedTax.length > 0) {
        addList.classList.remove("d-none")
        addList.innerHTML = `<span class="legend">Newly Added</span>`;
        selectedTax.forEach(tax => {
            const parent = tax.parentElement.parentElement.parentElement.parentElement
            const id = parent.querySelector("#lienNo")
            const address = parent.querySelector("address")
            const li = document.createElement("li");
            li.innerHTML = `<div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #${findLienNo(null, id.textContent)}</span>
                                    <span class="address">${address.textContent}</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>`;
            const deleteBtn = li.querySelector("div i.bi.bi-trash");
            deleteBtn.onclick = event => deleteFromList(event.target)
            addList.appendChild(li)
        })
    }
}


// select all button
const selectAllBtn = document.querySelector("a#selectAllBtn")
selectAllBtn.onclick = event => {
    selectedIds = [];
    selectedTax = [];
    allCheckInputs.forEach(input => {
        input.checked = true;
        const id = findLienNo(input, null);
        selectedTax.push(input)
        selectedIds.push(id);
    })
    
    // add selected ids list to the url
    const url = new URL(window.location);
    url.searchParams.set('myList', selectedIds.join(","));
    history.pushState({}, '', url);

    if (selectedTax.length == 2) {
        noteDiv.textContent = "2 of 2 items selected. Ready to compare!"
    } else if (selectedTax.length < 2) {
        noteDiv.textContent = `${selectedTax.length} of 2 items selected.`
    } else {
        noteDiv.textContent = "You've selected one too many.. can't compare more than two!"
    }
}

// deselect all button
const deselectAllBtn = document.querySelector("a#deselectAllBtn")
deselectAllBtn.onclick = event => {
    selectedIds = [];
    selectedTax = [];
    allCheckInputs.forEach(input => {
        input.checked = false;
    })
    // add selected ids list to the url
    const url = new URL(window.location);
    url.searchParams.set('myList', selectedIds.join(","));
    history.pushState({}, '', url);
    noteDiv.textContent = "select any 2 items to compare";
}

//  comparing two liens
compareBtn.onclick = event => {
    if (selectedTax.length === 2) {
        compareModal.show();
    }
}
// option for pie chart, Don't change unless needed
const options = {
    width: '100%',
    height: '100%',
    backgroundColor: '#8c8c8c',
    legend: 'none',
    pieSliceText: 'label',
    chartArea: {
        left: "3%",
        top: "3%",
        height: "200",
        width: "300",
    },
    is3D: true
};
// calculate average report button
const tdGraphics = document.querySelector("td.graphic");
let chart;
const avgReportBtn = document.querySelector("a#avgReportBtn");
avgReportBtn.onclick = event => {
    if (selectedTax.length > 1) {
        avgReportModal.show()
    } else {
        console.log("cannot calculate report for one lien or less")
    }
    
    /**
     * To show pie chart with correct data. You have to change the data variable below
     */
    let data = [
        ['Property Type', 'Number'],
        ['Single Family', 2],
        ['Multi Family', 4],
        ['Commercial', 6]
    ]
    
    chart.draw(google.visualization.arrayToDataTable(data), options);
}

// registering click for all tax lien for pop up
const allTaxLiens = document.querySelectorAll("div#lien")
allTaxLiens.forEach(lien => {
    lien.onclick = () => {
        regularLienModal.show();
    }
})

// google pie chart
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
function drawChart() {
    chart = new google.visualization.PieChart(document.getElementById('pieChart'));
     /**
     * To show pie chart with correct data. You have to change the data variable below
     */
    let data = [
        ['Property Type', 'Number'],
        ['Single Family', 2],
        ['Multi Family', 4],
        ['Commercial', 6]
    ]
    
    // charts in city page
    const chartDivs = document.querySelectorAll("div#cityChart");
    chartDivs.forEach(div => {
        let cityChart = new google.visualization.PieChart(div); 
        const options = {
            width: '80%',
            height: '100%',
            backgroundColor: '#8c8c8c',
            legend: 'none',
            pieSliceText: 'label',
            chartArea: {
                left: "1%",
                top: "3%",
                height: "140",
                width: "170",
            },
            is3D: true
        };
        cityChart.draw(google.visualization.arrayToDataTable(
            data) /** You can define your custom data, just follow the same array pattern */,
            options /** don't change this unless you have to */
        );
    })
    
    
    chart.draw(google.visualization.arrayToDataTable(data), options);
}


// delete icon for myList
const deleteBtns = document.querySelectorAll("div#myListSide i.bi.bi-trash");
deleteBtns.forEach(btn => {
    btn.onclick = event => deleteFromList(event.target)
})
// delete liens from myList function
const deleteFromList = (btn) => {
     const li = btn.parentElement.parentElement.parentElement;
    const ul = li.parentElement;
    ul.removeChild(li);
}