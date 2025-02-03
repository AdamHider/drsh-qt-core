<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container pb-3">
    <?php if(isset($quest['id'])) : ?>
    <form action="/admin/quests/save<?= $quest_group_id ? '/' . $quest_group_id : '' ?>/<?= $quest ? '/' . $quest['id'] : '' ?>" method="post">
    <?php else : ?>
    <form action="/admin/quests/create<?= $quest_group_id ? '/' . $quest_group_id : '' ?>" method="post">
    <?php endif ?>
        <div class="d-flex justify-content-start mb-3">
            <a class="btn btn-danger rounded-3 me-2" href="/admin/quest_groups"><i class="bi bi-x-lg"></i> Закрыть</a>
            <button type="submit" class="btn btn-primary rounded-3"><i class="bi bi-floppy"></i> Сохранить</button>
        </div>
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="row rounded-3 shadow-sm border bg-white p-2  pb-3">
            <div class="form-group mt-2">
                <label for="title">Название</label>
                <input type="text" name="description[title]" id="title" class="form-control" value="<?= $quest['description']['title'] ?? '' ?>" required>
            </div>
            <div class="form-group mt-2">
                <label for="description">Описание</label>
                <textarea type="text" name="description[description]" id="description" class="form-control"><?= $quest['description']['description'] ?? '' ?></textarea>
            </div>
            <div class="accordion mt-2" id="accordionReward">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            Целевые действия
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionReward">
                        <div class="accordion-body">
                            <div class="form-group mt-2">
                                <label for="type">Действие</label>
                                <select class="form-select" name="type" id="type" required>
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php if(!empty($quest['code']) && $quest['code'] == 'lesson') : ?>
                                        <option value="lesson" selected>Исследовать планету</option>
                                        <option value="skill">Изучить технологию</option>
                                        <option value="resource">Собрать ресурсы</option>
                                    <?php elseif(!empty($quest['code']) && $quest['code'] == 'skill') : ?>   
                                        <option value="lesson">Исследовать планету</option>
                                        <option value="skill" selected>Изучить технологию</option>
                                        <option value="resource">Собрать ресурсы</option>
                                    <?php elseif(!empty($quest['code']) && $quest['code'] == 'resource') : ?>   
                                        <option value="lesson">Исследовать планету</option>
                                        <option value="skill">Изучить технологию</option>
                                        <option value="resource" selected>Собрать ресурсы</option>
                                    <?php else : ?>   
                                        <option value="lesson">Исследовать планету</option>
                                        <option value="skill">Изучить технологию</option>
                                        <option value="resource">Собрать ресурсы</option>
                                    <?php endif; ?> 
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="target_item">Выбранные цели</label>     
                                <div id="selected_targets">
                                    <ul class="list-group"></ul>
                                </div>    
                                <ul id="target_selector" class="list-group" style="margin-top: -1px">
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="border-top-right-radius: 0; border-top-left-radius: 0;">
                                        <select name="target_item" class="form-select me-2" id="target_item" value="<?= $quest['target_item'] ?? '' ?>">
                                            <option value="0" selected>---Не выбрано---</option> 
                                        </select>
                                        <button type="button" class="btn btn-success" id="addTargetItem"><i class="bi bi-plus-square"></i></button>
                                    </li>
                                </ul>
                                <input type="hidden" name="target" value="<?= $quest['target']?>"/>
                            </div>
                            <div class="form-group mt-2">
                                <label for="title">Целевое значение</label>
                                <input type="text" name="value" id="value" class="form-control" value="<?= $quest['value'] ?? '' ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Конфигурация стоимости
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionReward">
                        <div class="accordion-body">
                            <input type="hidden" name="reward_config" id="reward_resource_config" class="form-control" value="<?= esc($quest['reward_config']) ?? '' ?>">
                            <?php foreach($resources as $resource) : ?>
                                <div class="mb-2 reward-resources resource">
                                    <label for="costResouce<?=$resource['code']?>" class="form-label"><?= $resource['code'] ?></label>
                                    <input type="text" class="form-control" data-code="<?=$resource['code']?>" id="costResouce<?=$resource['code']?>" value="<?=json_decode($quest['reward_config'], true)[$resource['code']] ?? 0?>">
                                </div>
                            <?php endforeach; ?> 
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Страницы
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionReward">
                        <div class="accordion-body">
                            <div id="page_manager">
                                <div class="pages-container">

                                </div>
                                <div class="blank-page">
                                    <div class="form-group col-4">
                                        <label for="page_image">Изображение</label>
                                        <div class="card ficker-image text-center">
                                            <img src="" class="card-img">
                                            <div class="card-footer">
                                                <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                                            </div>
                                            <input type="hidden" name="page_image" id="page_image" class="form-control" value="" required>
                                        </div>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label for="page_title">Заголовок</label>
                                        <input type="text" name="page_title" id="page_title" class="form-control"/>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label for="page_description">Описание</label>
                                        <textarea type="text" name="page_description" id="page_description" class="form-control"></textarea>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label for="page_answer">Ответ</label>
                                        <input type="text" name="page_answer" id="page_answer" class="form-control"/>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success mt-2" id="addPageItem"><i class="bi bi-plus-square"></i> Добавить страницу</button>
                            </div>
                            <input type="hidden" name="pages" id="pages" class="form-control" value="<?= $quest['pages'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group mt-2">
                <label for="published">Опубликован</label>
                <select class="form-select" name="published" id="published">
                    <?php if(empty($quest['published']) || $quest['published'] == 0) : ?>
                        <option value="0" selected>Нет</option>
                        <option value="1">Да</option>
                    <?php else: ?>   
                        <option value="0">Нет</option>
                        <option value="1" selected>Да</option>
                    <?php endif; ?> 
                </select>
            </div>
            <div class="form-group mt-2">
                <label for="is_private">Приватный</label>
                <select class="form-select" name="is_private" id="is_private">
                    <?php if(empty($quest['published']) || $quest['published'] == 0) : ?>
                        <option value="0">Нет</option>
                        <option value="1" selected>Да</option>
                    <?php else: ?>   
                        <option value="0" selected>Нет</option>
                        <option value="1">Да</option>
                    <?php endif; ?> 
                </select>
            </div>
        </div>
    </form>
</div>

<?= view('misc/filepicker') ?>

<script>
    
    var rewardConfig = <?= !empty($quest['reward_config']) ? $quest['reward_config'] : '[]' ?> 
    var pages = <?= !empty($quest['pages']) ? $quest['pages'] : '[]' ?> 

    
    const lists = {
        lesson: <?php echo json_encode($lessons); ?>,
        skill: <?php echo json_encode($skills); ?>,
        resource: <?php echo json_encode($resources); ?>
    }
    var actual_list = [];

    function initControls () {
        $('.pick-image').on('click', (e) => {
            let input = $(e.delegateTarget).closest('.input-group').find('input')
            let container = $(e.delegateTarget).closest('.form-group');
            let modal = new bootstrap.Modal(document.getElementById('pickerModal'), {})
            initFileExplorer({
                filePickerElement: '#file_picker',
                multipleMode: false,
                pickerMode: true,
                onPicked: (url) => {
                    $(container).find('input').val(url).trigger("input");
                    $(container).find('img').prop('src', url)
                    modal.hide()
                }
            });
            modal.show()
        })
        $('select[name="type"]').on('change', () => {
            actual_list = []
            $('input[name="target"]').val(null)
            renderTargetSelect()
            renderTargetItems()
        })
        $('.reward-resources input').on('change', (e) => {
            let code = $(e.delegateTarget).attr('data-code')
            let quantity = $(e.delegateTarget).val()
            rewardConfig[code] = quantity
            if(quantity == 0){
                delete rewardConfig[code]
            }
            $('[name="reward_config"]').val(JSON.stringify(rewardConfig))
            renderResources()
        })
    }

    function renderTargetSelect () {
        var current_type = $('select[name="type"]').val();
        var select = $('select[name="target_item"]');
        select.empty()
        select.append('<option value="0" selected>---Не выбрано---</option>')
        var current_list = lists[current_type]
        var selected_items = $('input[name="target"]').val().split(',');
        actual_list = current_list.filter((item) => selected_items.indexOf(item.id) === -1  )
        for(var i in actual_list){
            select.append('<option value="'+actual_list[i].id+'">'+actual_list[i].title+'</option>')
        }
        $('#addTargetItem').off('click')
        $('#addTargetItem').on('click', (e) => {
            addTarget()
        })
        $('#addPageItem').off('click')
        $('#addPageItem').on('click', (e) => {
            addPage()
        })
        

    }
    function renderTargetItems () {
        var element = $('#selected_targets .list-group');
        element.empty();
        var current_type = $('select[name="type"]').val();
        var current_list = lists[current_type]
        var selected_items = [];
        if($('input[name="target"]').val() != ""){
            selected_items = $('input[name="target"]').val().split(',');
        } 
        if(selected_items.length > 0 && current_type !== 'resource'){
            $('#value').attr('type', 'hidden')
            $('#value').val(selected_items.length)
        } else {
            $('#value').attr('type', 'text')
        }
        for(var i in selected_items){
            var selected_item = current_list.find((item) => {return item.id == selected_items[i]})
            var list_element = $('<li class="list-group-item d-flex justify-content-between align-items-center">');
            list_element.html(selected_item.title);
            var deleteButton = $('<button type="button" class="btn btn-danger" data-id="'+selected_item.id+'"><i class="bi bi-trash-fill"></i></button>').click((e) => {
                removeTarget($(e.delegateTarget).attr('data-id'))
            })
            list_element.append(deleteButton)
            element.prepend(list_element)
        }
    }

    function addTarget(){
        const value = $('select[name="target_item"]').val()
        if(value == 0) return
        var selected_items = [];
        if($('input[name="target"]').val() != ""){
            selected_items = $('input[name="target"]').val().split(',');
        } 
        if(selected_items.indexOf(value) === -1){
            selected_items.push(value)
            $('input[name="target"]').val(selected_items.join(','))
        }
        renderTargetSelect()
        renderTargetItems()
    }
    function removeTarget(id){
        var selected_items = $('input[name="target"]').val().split(',');
        var result = [];
        for(var i in selected_items){
            if(selected_items[i] != id) result.push(selected_items[i])
        }
        if(result.length == 0){
            $('input[name="target"]').val('')
        } else {
            $('input[name="target"]').val(result.join(','))
        }
        
        renderTargetSelect()
        renderTargetItems()
    }

    function renderResources(){
        $('.reward-resources input').each((index, el) => {
            if($(el).val()*1 > 0){
                $(el).closest('.resource').addClass('positive').addClass('is-open')
                $(el).closest('.resource').find('.btn').attr('data-action', 'close').html('<i class="bi bi-x-lg"></i>').removeClass('btn-primary').addClass('btn-danger')
            } else {
                $(el).closest('.resource').removeClass('positive').removeClass('is-open')
                $(el).closest('.resource').find('.btn').attr('data-action', 'open').html('<i class="bi bi-plus-lg"></i>').removeClass('btn-danger').addClass('btn-primary')
            }
        })
    }

    function renderPages(){
        const container = $('#page_manager .pages-container')
        container.empty()
        for(var i in pages){
            const page = $('<div class="page-item card mt-2"></div>')
            const pageInner = $('<div>').addClass('row g-0')
            const pageLeft = $('<div>').addClass('col text-center').append($('<img>').attr('src', pages[i].image).addClass('page-image'))
            const deletePage = $('<button type="button" class="btn btn-danger" data-index="'+i+'"><i class="bi bi-trash-fill"></i></button>').on('click', (e) => {
                removePage($(e.delegateTarget).attr('data-index'))
            })
            const pageRight = $('<div>').addClass('col-9').append(
                $('<div>').addClass('card-body').append(
                    $('<b></b>').html(pages[i].title).addClass('page-title card-title'),
                    $('<div></div>').html(pages[i].description).addClass('page-description card-text'),
                    $('<div></div>').html(pages[i].answer).addClass('page-answer card-text'),
                )
            )
            page.append(pageInner.append(pageLeft, pageRight), deletePage)
            container.append(page)
        }
        $('#pages').val(JSON.stringify(pages))
    }
    function addPage(){
        const page = {
            image: $('#page_image').val(),
            title: $('#page_title').val(),
            description: $('#page_description').val(),
            answer: $('#page_answer').val()
        }
        $('#page_image').val('')
        $('.ficker-image img').prop('src', '')
        $('#page_title').val('')
        $('#page_description').val('')
        $('#page_answer').val('')
        pages.push(page)
        renderPages()
    }
    function removePage(index){
        var result = [];
        for(var i in pages){
            if(i != index) result.push(pages[i]);
        }
        console.log(result)
        pages = result
        renderPages()
    }

    renderResources()
    initControls()
    renderTargetSelect()
    renderTargetItems()
    renderPages()
    
</script>
<style>
    #selected_targets li:last-child{
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }
    .page-item{

    }
    .page-item .page-image{
        width: 70px;
    }
    .page-item .btn{
        position: absolute;
        top: 10px;
        right: 10px;
    }
</style> 
<?= $this->endSection() ?>