</main><!-- /.page-content -->
</div><!-- /.content-wrapper -->

<!-- ══════════════════════════════════════════
     Scripts — orden obligatorio:
     1. jQuery  2. Bootstrap  3. DataTables
     4. SweetAlert2  5. sidebar.js
══════════════════════════════════════════ -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Layout JS -->
<script src="<?= BASE_URL ?>/assets/js/sidebar.js"></script>

<!-- Agregar antes del cierre del body -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<!-- DataTables: configuración global en español -->
<script>
$(function () {
    if (typeof $.fn.DataTable !== 'undefined') {
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 25,
            responsive: true
        });
    }
});
</script>

</body>
</html>