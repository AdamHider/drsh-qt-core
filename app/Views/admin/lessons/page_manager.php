<div class="container-fluid">
    <div class="row">
        <div class="col-3 border-end pt-2 sticky-top sticky-offset">   
            <h5>Pages</h5>
            <ul id="pageList" class="list-group"></ul>
            <button id="addPage" class="btn btn-secondary mt-2">Add Page</button>
       
        </div>
        <div class="col-9 pt-2">
            <div id="editor" class="editor"></div>
        </div>
    </div>
</div>

<script>
let pages = <?= isset($pages) ? $pages : '[]' ?>;
</script>

<link rel="stylesheet" href="<?=base_url('/assets/lesson_page_manager/css/main.css')?>" type="text/css">

<script src="<?=base_url('/assets/lesson_page_manager/js/defaults2.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/renderer.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/main2.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/pageManager.js')?>"></script>