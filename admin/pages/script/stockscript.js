
const addStock = document.getElementById("addStockForm");
const openAddStock = document.getElementById("openAddStock");
const addProd = document.getElementById("addProdForm");
const openAddProd = document.getElementById("openAddProd");

openAddStock.addEventListener("click", () => {
  addStock.style.display = "flex";
});

openAddProd.addEventListener("click", () => {
  addProd.style.display = "flex";
});

document.querySelectorAll('.close-btn').forEach(btn => {
  btn.addEventListener("click", () => {
    addStock.style.display = "none";
    addProd.style.display = "none";
  });
});

window.addEventListener("click", event => {
  if (event.target === addStock) {
    addStock.style.display = "none";
  }
  if (event.target === addProd) {
    addProd.style.display = "none";
  }
});


