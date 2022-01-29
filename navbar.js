const filterObject = {
    saleAmount:[Number.MIN_VALUE, 2000],
    propertyType: [],
    homeValue:[],
    /**
     * lengthOfOwnership: value 1 = 3 years or fewer, value 2 = 3 - 10 years, 
     *  value 3 = 10 - 20years, value 4 = 20+ years
     *  @default value is 1
     */
    lengthOfOwnership: [Number.MIN_VALUE, 3],
}

const lengthBar = document.querySelector("ul.length-bar");
const salesBar = document.querySelector("ul.sales-amount-list");
const propertyList = document.querySelector("ul#property-type");
const propertyListLi = propertyList.querySelectorAll("li");
const salesBarLi = salesBar.querySelectorAll("li");
const lengthBarLi = lengthBar.querySelectorAll("li");

const homeValueInputs = document.querySelectorAll("input.home-value");
const saleValueInputs = document.querySelectorAll("input.sale-value");
homeValueInputs.forEach(input => {
    input.onkeyup = () => filterObject.homeValue[Number(input.id) - 1] = input.valueAsNumber
});
saleValueInputs.forEach(input => {
    input.onkeyup = () => filterObject.saleAmount[Number(input.id) - 1] = input.valueAsNumber
})

// length of property progress bar
lengthBarLi.forEach(element => {
    element.onclick = event => {
        const id = element.id;
        switch (Number(id)) {
            case 1:
                filterObject.lengthOfOwnership = [Number.MIN_VALUE, 3];
                break;
            case 2:
                filterObject.lengthOfOwnership = [3.001, 5];
                break;
            case 3:
                filterObject.lengthOfOwnership = [5.001, 10];
                break;
            case 4:
                filterObject.lengthOfOwnership = [10.001, Number.MAX_VALUE];
                break;
        }
        lengthBarLi.forEach(el => {
            const _id = el.id;
            if (id === _id) {
                el.classList.add("active");
                el.classList.remove("after")
            } else {
                el.classList.remove("active");
                if (_id < id) el.classList.add("after");
                else el.classList.remove("after")
            } 
        })
    }
})
// sales amount progress bar
salesBarLi.forEach(element => {
    element.onclick = event => {
        const id = element.id;
        switch (Number(id)) {
            case 1:
                filterObject.saleAmount = [Number.MIN_VALUE, 2000];
                break;
            case 2:
                filterObject.saleAmount = [2001, 5000];
                break;
            case 3:
                filterObject.saleAmount = [5001, 10000];
                break;
            case 4:
                filterObject.saleAmount = [10001, Number.MAX_VALUE];
                break;
        }
        salesBarLi.forEach(el => {
            const _id = el.id;
            if (id === _id) {
                el.classList.add("active");
                el.classList.remove("after")
            } else {
                el.classList.remove("active");
                if (_id < id) el.classList.add("after");
                else el.classList.remove("after")
            } 
        })
    }
})

// property type list click handler
propertyListLi.forEach(li => {
    li.onclick = event => {
        if (li.classList.contains("active")) {
            li.classList.remove("active");
            filterObject.propertyType.splice(filterObject.propertyType.indexOf(li.textContent), 1)
        } else {
            li.classList.add("active");
            filterObject.propertyType.push(li.textContent)
        }
        console.log(filterObject)
    }
})



//search suggestions list
const searchDropdown = document.querySelector("div#search-dropdown");
const searchInput = document.querySelector("input#search-input")
const suggestions = document.querySelector("div#suggestions")
searchDropdown.onmouseleave = event => {
    suggestions.classList.remove("d-block")
}
searchInput.onkeyup = event => {
    const searchQuery = event.target.value;
    if (searchQuery.length > 0) {
        suggestions.classList.add("d-block")
    } else {
        suggestions.classList.remove("d-block")
    }
}



// for bottom filter in search and city page
const bottomFilterObject = {
    saleAmount:[Number.MIN_VALUE, 2000],
    propertyType: [],
    homeValue:[],
    /**
     * lengthOfOwnership: value 1 = 3 years or fewer, value 2 = 3 - 10 years, 
     *  value 3 = 10 - 20years, value 4 = 20+ years
     *  @default value is 1
     */
    lengthOfOwnership: [Number.MIN_VALUE, 3],
}
const bottomLengthBar = document.querySelector("div#bottom ul.length-bar");
const bottomSalesBar = document.querySelector("div#bottom ul.sales-amount-list");
const bottomPropertyList = document.querySelector("div#bottom ul#property-type");
const bottomPropertyListLi = bottomPropertyList.querySelectorAll("li");
const bottomSalesBarLi = bottomSalesBar.querySelectorAll("li");
const bottomLengthBarLi = bottomLengthBar.querySelectorAll("li");

const bottomHomeValueInputs = document.querySelectorAll("div#bottom input.home-value");
const bottomSaleValueInputs = document.querySelectorAll("div#bottom input.sale-value");
bottomHomeValueInputs.forEach(input => {
    input.onkeyup = () => bottomFilterObject.homeValue[Number(input.id) - 1] = input.valueAsNumber
});
bottomSaleValueInputs.forEach(input => {
    input.onkeyup = () => bottomFilterObject.saleAmount[Number(input.id) - 1] = input.valueAsNumber
})
// length of property progress bar for bottom filter
bottomLengthBarLi.forEach(element => {
    element.onclick = event => {
        const id = element.id;
        switch (Number(id)) {
            case 1:
                bottomFilterObject.lengthOfOwnership = [Number.MIN_VALUE, 3];
                break;
            case 2:
                bottomFilterObject.lengthOfOwnership = [3.001, 5];
                break;
            case 3:
                bottomFilterObject.lengthOfOwnership = [5.001, 10];
                break;
            case 4:
                bottomFilterObject.lengthOfOwnership = [10.001, Number.MAX_VALUE];
                break;
        }
        bottomLengthBarLi.forEach(el => {
            const _id = el.id;
            if (id === _id) {
                el.classList.add("active");
                el.classList.remove("after")
            } else {
                el.classList.remove("active");
                if (_id < id) el.classList.add("after");
                else el.classList.remove("after")
            } 
        })
    }
})
// sales amount progress bar
bottomSalesBarLi.forEach(element => {
    element.onclick = event => {
        const id = element.id;
        switch (Number(id)) {
            case 1:
                bottomFilterObject.saleAmount = [Number.MIN_VALUE, 2000];
                break;
            case 2:
                bottomFilterObject.saleAmount = [2001, 5000];
                break;
            case 3:
                bottomFilterObject.saleAmount = [5001, 10000];
                break;
            case 4:
                bottomFilterObject.saleAmount = [10001, Number.MAX_VALUE];
                break;
        }
        bottomSalesBarLi.forEach(el => {
            const _id = el.id;
            if (id === _id) {
                el.classList.add("active");
                el.classList.remove("after")
            } else {
                el.classList.remove("active");
                if (_id < id) el.classList.add("after");
                else el.classList.remove("after")
            } 
        })
    }
})

// property type list click handler
bottomPropertyListLi.forEach(li => {
    li.onclick = event => {
        if (li.classList.contains("active")) {
            li.classList.remove("active");
            bottomFilterObject.propertyType.splice(bottomFilterObject.propertyType.indexOf(li.textContent), 1)
        } else {
            li.classList.add("active");
            bottomFilterObject.propertyType.push(li.textContent)
        }
        console.log(bottomFilterObject)
    }
})