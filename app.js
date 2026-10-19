// app.js - JavaScript ng StockWise (ini-load ng header.php)

// 1) CLIENT-SIDE VALIDATION
// Sinusuri ang form bago ipadala sa server. Gumagana sa lahat ng <form class="needs-validation">
document.addEventListener("DOMContentLoaded", function () {
  var forms = document.querySelectorAll("form.needs-validation");
  forms.forEach(function (form) {
    form.addEventListener("submit", function (event) {
      // Kung may "password" at "password2" na field, dapat magkapareho
      var p1 = form.querySelector("#password");
      var p2 = form.querySelector("#password2");
      if (p1 && p2) {
        p2.setCustomValidity(p1.value !== p2.value ? "Passwords do not match" : "");
      }
      if (!form.checkValidity()) {
        event.preventDefault();   // huwag ituloy ang submit kung may mali
      }
      form.classList.add("was-validated");
    });
  });

  // 2) LIVE SEARCH + CATEGORY FILTER (dashboard lang, walang page reload)
  var search = document.getElementById("search");
  var catFilter = document.getElementById("catFilter");
  if (search && catFilter) {
    search.addEventListener("input", filterRows);
    catFilter.addEventListener("change", filterRows);
  }
});

// Itinatago ang mga row na hindi tugma sa tinype o sa napiling category
function filterRows() {
  var q    = document.getElementById("search").value.toLowerCase();
  var cat  = document.getElementById("catFilter").value;
  var rows = document.querySelectorAll("#itemsBody tr.item-row");
  var shown = 0;

  rows.forEach(function (row) {
    // hanapin lang sa Name at Category (hindi kasama ang Edit/Delete buttons)
    var text      = row.cells[0].textContent + " " + row.cells[1].textContent;
    var textMatch = text.toLowerCase().indexOf(q) !== -1;
    var catMatch  = (cat === "" || row.dataset.cat === cat);
    var visible   = textMatch && catMatch;
    row.style.display = visible ? "" : "none";
    if (visible) { shown++; }
  });

  // Ipakita ang "No items found." kung walang tumugma
  document.getElementById("noRows").style.display = (shown === 0) ? "" : "none";
}

// 3) Styled confirmation dialog for logout and deleting inventory items
var confirmDialog = document.getElementById("confirmDialog");
var confirmTitle = document.getElementById("confirmTitle");
var confirmDetail = document.getElementById("confirmDetail");
var acceptConfirm = document.getElementById("acceptConfirm");
var cancelConfirm = document.getElementById("cancelConfirm");
var pendingConfirmForm = null;
var pendingConfirmUrl = null;

function openConfirm(message, detail, form, url, actionLabel) {
  if (!confirmDialog) return;
  pendingConfirmForm = form || null;
  pendingConfirmUrl = url || null;
  confirmTitle.textContent = message || "Are you sure?";
  confirmDetail.textContent = detail || "Please confirm this action.";
  acceptConfirm.textContent = actionLabel || (url ? "Yes, Log Out" : "Yes, Delete");
  acceptConfirm.className = "btn " + (url ? "btn-primary" : "btn-danger");
  confirmDialog.classList.add("show");
  confirmDialog.setAttribute("aria-hidden", "false");
  acceptConfirm.focus();
}

function closeConfirm() {
  if (!confirmDialog) return;
  confirmDialog.classList.remove("show");
  confirmDialog.setAttribute("aria-hidden", "true");
  pendingConfirmForm = null;
  pendingConfirmUrl = null;
}

document.querySelectorAll("[data-confirm-url]").forEach(function (button) {
  button.addEventListener("click", function () {
    openConfirm(button.dataset.confirmMessage, button.dataset.confirmDetail, null, button.dataset.confirmUrl, "Yes, Log Out");
  });
});

document.querySelectorAll("form[data-confirm-message]").forEach(function (form) {
  form.addEventListener("submit", function (event) {
    if (form.dataset.confirmed === "yes") {
      form.dataset.confirmed = "no";
      return;
    }
    event.preventDefault();
    openConfirm(form.dataset.confirmMessage, form.dataset.confirmDetail, form, null, "Yes, Delete");
  });
});

if (cancelConfirm) cancelConfirm.addEventListener("click", closeConfirm);
if (confirmDialog) confirmDialog.addEventListener("click", function (event) {
  if (event.target === confirmDialog) closeConfirm();
});
if (acceptConfirm) acceptConfirm.addEventListener("click", function () {
  if (pendingConfirmUrl) {
    window.location.href = pendingConfirmUrl;
    return;
  }
  if (pendingConfirmForm) {
    var form = pendingConfirmForm;
    closeConfirm();
    form.dataset.confirmed = "yes";
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
  }
});
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") closeConfirm();
});
