<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <button type="button" class="btn btn-primary mb-3" id="addPageBtn">Add Page</button>
            <ul class="list-group" id="pagesList"></ul>
        </div>
        <div class="col-md-8">
            <div id="pageFormContainer"></div>
        </div>
    </div>
</div>

<script>
let pages = <?= isset($pages) ? $pages : '[]' ?>;
</script>
<script src="<?=base_url('/assets/lesson_page_manager/js/render.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/main.js')?>"></script>