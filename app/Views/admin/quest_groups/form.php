<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container pb-3">
    <?php if(isset($quest_group['id'])) : ?>
    <form action="/admin/quest_groups/save<?= $quest_group ? '/' . $quest_group['id'] : '' ?>" method="post">
    <?php else : ?>
    <form action="/admin/quest_groups/save" method="post">
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
                <input type="text" name="description[title]" id="title" class="form-control" value="<?= $quest_group['description']['title'] ?? '' ?>" required>
            </div>
            <div class="form-group mt-2">
                <label for="description">Описание</label>
                <textarea type="text" name="description[description]" id="description" class="form-control"><?= $quest_group['description']['description'] ?? '' ?></textarea>
            </div>
            <div class="form-group mt-2">
                <label for="description">Цвет</label>
                <input type="text" name="color" id="color" class="form-control" value="<?= $quest_group['color'] ?? '' ?>" required>
            </div>
            <div class="row mt-2">
                <div class="form-group col-4">
                    <label for="image">Изображение</label>
                    <div class="card ficker-image text-center">
                        <img src="<?= $quest_group['image_avatar'] ?? '' ?>" class="card-img">
                        <div class="card-footer">
                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                        </div>
                        <input type="hidden" name="image_avatar" class="form-control" value="<?= $quest_group['image_avatar'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="form-group col-4">
                    <label for="background_image">Фоновое изображение</label>
                    <div class="card ficker-image text-center">
                        <img src="<?= $quest_group['image_full'] ?? '' ?>" class="card-img">
                        <div class="card-footer">
                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                        </div>
                        <input type="hidden" name="image_full" class="form-control" value="<?= $quest_group['image_full'] ?? '' ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-group mt-2">
                <label for="unblock_after">Разблокировать после</label>
                <select name="unblock_after" class="form-select" id="unblock_after" value="<?= $quest_group['unblock_after'] ?? '' ?>">
                    <option value="0" selected>---Не выбрано---</option>
                    <?php foreach($unblock_quest_groups as $unblock_quest_group) : ?>
                        <?php if(!empty($quest_group['unblock_after']) && $unblock_quest_group['id'] == $quest_group['unblock_after']) : ?>
                            <option value="<?= $unblock_quest_group['id'] ?>" selected><?= $unblock_quest_group['title'] ?></option>
                        <?php else: ?>   
                            <option value="<?= $unblock_quest_group['id'] ?>"><?= $unblock_quest_group['title'] ?></option>
                        <?php endif; ?>   
                    <?php endforeach; ?>    
                </select>
            </div>
            <div class="form-group mt-2">
                <label for="published">Опубликован</label>
                <select class="form-select" name="published" id="published">
                    <?php if(empty($quest_group['published']) || $quest_group['published'] == 0) : ?>
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
                    <?php if(empty($quest_group['published']) || $quest_group['published'] == 0) : ?>
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
</script>
<?= $this->endSection() ?>