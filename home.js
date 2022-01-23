const filterObject = {

}
const LengthBar = document.querySelector("ul.length-bar");
const li = LengthBar.querySelectorAll("li");
li.forEach(element => {
    element.onclick = event => {
        const id = element.id;
        li.forEach(el => {
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