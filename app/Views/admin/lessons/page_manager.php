<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">   
            <h5>Pages</h5>
            <ul id="pageList" class="list-group"></ul>
            <button id="addPage" class="btn btn-secondary mt-2">Add Page</button>
       
        </div>
        <div class="col-md-8">
            <div id="editor" class="editor"></div>
        </div>
    </div>
</div>

<script>
let pages = <?= isset($pages) ? $pages : '[]' ?>;
</script>

<script src="<?=base_url('/assets/lesson_page_manager/js/defaults2.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/main2.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/pageManager.js')?>"></script>