const products = [
    { id:1, name:"Whey Protein Powder", status:"In Stock", image:"../../images/whey-protein.jpg" },
    { id:2, name:"Bottled Water", status:"In Stock", image:"../../images/bottled-water.jpeg" },
    { id:3, name:"Ice Pack", status:"Out of Stock", image:"../../images/ice-pack.jpg" },
    { id:4, name:"Muscle Roller", status:"In Stock", image:"../../images/muscle-roller.jpeg" },
    { id:5, name:"Recovery Bar", status:"Low Stock", image:"../../images/recovery-bar.jpg" },
    { id:6, name:"Workout Supplement", status:"In Stock", image:"../../images/workout-supplement.jpg" },
    { id:7, name:"Resistance Bands", status:"In Stock", image:"../../images/resistance-bands.jpeg" },
    { id:8, name:"Mouth Guards", status:"In Stock", image:"../../images/mouth-guards.jpg" }
];

const grid = document.getElementById("productsGrid");

products.forEach(p => {
    const card = document.createElement("div");
    card.className = "product-card";
    card.innerHTML = `
        <img src="${p.image}" alt="${p.name}">
        <h3>${p.name}</h3>
        <p class="status ${p.status.replace(/\s+/g,'-').toLowerCase()}">${p.status}</p>
    `;
    grid.appendChild(card);
});
