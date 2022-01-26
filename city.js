import "./navbar.js"
// tabs nav
const myCarousel = document.querySelector('#slider')
var carousel = new bootstrap.Carousel(myCarousel, {
  interval: 0,
  wrap: false
})
myCarousel.addEventListener('slid.bs.carousel', function (event) {
    const id = carousel._activeElement.id;
    links.forEach(link => {
        if (id === link.id) {
            link.classList.add("active")
        } else {
            link.classList.remove("active")
        }
    })

})

const carouselItems = document.querySelectorAll("div.carousel-item");

const tabList = document.querySelector("ul.nav-tabs")
const thirdSlideTabList = document.querySelector("ul#third-slide-tab-list");
const thirdSlideTabListLinks = thirdSlideTabList.querySelectorAll("a.nav-link");
let activeTab = "2020";
thirdSlideTabListLinks.forEach(link => {
    link.onclick = (event) => {
        const text = link.textContent;
        event.preventDefault();
        thirdSlideTabListLinks.forEach(l => {
            if (l.textContent === text) {
                l.classList.add("active")
            } else {
                l.classList.remove("active")
            }
        })
    }
})

const links = tabList.querySelectorAll("a.nav-link");
const tabContent = document.querySelector("div.tab-content")
links.forEach(link => {
    link.onclick = (event) => {
        const text = link.textContent;
        event.preventDefault();
        links.forEach(l => {
            if (l.textContent === text) {
                l.classList.add("active")
                carouselItems.forEach(item => {
                    if (item.id === l.id) item.classList.add("active")
                    else item.classList.remove("active");
                })
            } else {
                l.classList.remove("active")
            }
        })
    }
})

// google pie chart
const chartDiv = document.querySelector("div#pieChart");
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
function drawChart() {
    const data = google.visualization.arrayToDataTable([
          ['Property Type', 'Number'],
          ['Single Family',  2],
          ['Multi Family', 4],
          ['Commercial',  6]
    ]);
    const options = {
        title: 'Property Type',
        is3D: true,
        pieSliceText: 'none',
        pieSliceTextStyle: {
            display: 'none'
        },
        legend: {
            position: 'right',
            textStyle: {fontSize: 14, fontName: 'Overpass' }
        },
        slices: {
            // 0: { color: '#e0e0e0' },
            // 1: { color: '#e6e1e1' },
            // 2: {color: '#8c8c8c'}
        },
        chartArea:{width:'90%'}
    };

    const chart = new google.visualization.PieChart(chartDiv);

    chart.draw(data, options);
}
