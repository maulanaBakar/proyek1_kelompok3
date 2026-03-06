// =======================
// NAVBAR ACTIVE
// =======================
function setActiveNav(element){
    document.querySelectorAll(".nav-item").forEach(item=>{
        item.classList.remove("active");
    });
    element.classList.add("active");
}


// =======================
// STAT CARD
// =======================
function setActiveStat(element){

    document.querySelectorAll(".stat-card").forEach(card=>{
        card.classList.remove("active");
    });

    element.classList.add("active");

    let mode = element.innerText.toLowerCase();

    const yearRows = document.querySelectorAll(".year-row");
    const monthRows = document.querySelectorAll(".month-row");
    const weekRows = document.querySelectorAll(".week-row");
    const trxRows = document.querySelectorAll(".trx-row");
    const accordions = document.querySelectorAll(".accordion-content");


    // =================
    // PERHARI
    // =================
    if(mode.includes("perhari")){

        yearRows.forEach(r => r.style.display = "none");
        monthRows.forEach(r => r.style.display = "none");
        weekRows.forEach(r => r.style.display = "none");

        // buka semua container supaya trx terlihat
        accordions.forEach(a => a.style.display = "block");

        trxRows.forEach(r => r.style.display = "flex");

    }


    // =================
    // PERBULAN
    // =================
    if(mode.includes("perbulan")){

        yearRows.forEach(r => r.style.display = "none");
        monthRows.forEach(r => r.style.display = "flex");
        weekRows.forEach(r => r.style.display = "flex");
        trxRows.forEach(r => r.style.display = "flex");

    }


    // =================
    // PERTAHUN
    // =================
    if(mode.includes("pertahun")){

        yearRows.forEach(r => r.style.display = "flex");
        monthRows.forEach(r => r.style.display = "flex");
        weekRows.forEach(r => r.style.display = "flex");
        trxRows.forEach(r => r.style.display = "flex");

    }

}


// =======================
// ACCORDION
// =======================
function toggleAccordion(id){

    const element = document.getElementById(id);

    if(element.style.display === "block"){
        element.style.display = "none";
    }else{
        element.style.display = "block";
    }

}

