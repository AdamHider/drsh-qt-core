<div class="modal modal-xl" id="pickerModal" tabindex="-1" aria-hidden="true"  aria-labelledby="imagePickerModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePickerModalLabel">Choose an image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <div id="file_picker"></div>
            </div>
        </div>
    </div>
</div>
<script src="<?=base_url('assets/file_explorer/js/main.js')?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/file_explorer/css/main.css')?>" type="text/css">
<script>
    const basePath = "<?=base_url('admin/media/')?>";
</script>