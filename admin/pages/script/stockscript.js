
const editForm = document.getElementById("editForm");
const openEdit = document.getElementById("openEdit");
const saveEdit = document.getElementById("saveEdit");
const closeX = document.querySelector(".close-btn");

openEdit.onclick = function() {
  editForm.style.display = "block";
}

saveEdit.onclick = function() {
    editForm.style.display = "none";
}
closeX.onclick = function() {
  editForm.style.display = "none";
}