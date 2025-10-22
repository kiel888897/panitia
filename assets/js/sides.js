const SELECTOR_SIDEBAR_WRAPPER = ".sidebar-wrapper";
const Default = {
  scrollbarTheme: "os-theme-light",
  scrollbarAutoHide: "leave",
  scrollbarClickScroll: true,
};
document.addEventListener("DOMContentLoaded", function () {
  const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
  if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== "undefined") {
    OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
      scrollbars: {
        theme: Default.scrollbarTheme,
        autoHide: Default.scrollbarAutoHide,
        clickScroll: Default.scrollbarClickScroll,
      },
    });
  }
});
$(document).ready(function () {
  // Inisialisasi DataTables pada tabel
  $("table").DataTable({
    paging: true, // Aktifkan pagination
    searching: true, // Aktifkan fitur pencarian
    lengthChange: false, // Menonaktifkan pilihan jumlah item per halaman
    pageLength: 10, // Menentukan jumlah baris per halaman
    language: {
      search: "Cari:",
      paginate: {
        previous: "Prev",
        next: "Next",
      },
    },
  });
});
