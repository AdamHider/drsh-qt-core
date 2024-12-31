<div class="container-fluid">
    <div class="row">
        <div class="col-3 border-end">  
            <div class="sticky-top sticky-offset pt-2">
                <h5>Страницы</h5>
                <ul id="pageList" class="list-group"></ul>
                <div class="d-flex justify-content-center">   
                    <button id="addPage" class="btn btn-success mt-2"><i class="bi bi-plus-lg me-2"></i>Новая страница</button>
                </div>
            </div>
        </div>
        <div class="col-9 pt-2">
            <div id="editor" class="editor row"></div>
        </div>
    </div>
</div>

<script>
let pages = <?= isset($pages) ? $pages : '[]' ?>;
</script>

<script src="<?=base_url('/assets/lesson_page_manager/js/defaults.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/main.js')?>"></script>
<script src="<?=base_url('/assets/lesson_page_manager/js/pageManager.js')?>"></script>