
const addStock = document.getElementById("addStockForm");
const openAddStock = document.getElementById("openAddStock");
const saveAddStock = document.getElementById("saveAddStock");
const closeX = document.querySelector(".close-btn");

openAddStock.onclick = function() {
  addStock.style.display = "block";
}

saveAddStock.onclick = function() {
    addStock.style.display = "none";
}
closeX.onclick = function() {
  addStock.style.display = "none";
}