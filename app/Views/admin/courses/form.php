<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container pb-3">
    <?php if(isset($course['id'])) : ?>
    <form action="/admin/courses/save<?= $course ? '/' . $course['id'] : '' ?>" method="post">
    <?php else : ?>
    <form action="/admin/courses/create" method="post">
    <?php endif ?>
        <div class="d-flex justify-content-start mb-3">
            <a class="btn btn-danger rounded-3 me-2" href="/admin/courses"><i class="bi bi-x-lg"></i> Закрыть</a>
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
                <input type="text" name="title" id="title" class="form-control" value="<?= $course['title'] ?? '' ?>">
            </div>
            <div class="form-group mt-2">
                <label for="description">Описание</label>
                <textarea type="text" name="description" id="description" class="form-control"><?= $course['description'] ?? '' ?></textarea>
            </div>
            <div class="row mt-2">
                <div class="form-group col-4">
                    <label for="image">Изображение</label>
                    <div class="card ficker-image text-center">
                        <img src="<?= $course['image'] ?? '' ?>" class="card-img">
                        <div class="card-footer">
                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                        </div>
                        <input type="hidden" name="image" class="form-control" value="<?= $course['image'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-group col-4">
                    <label for="background_image">Фоновое изображение</label>
                    <div class="card ficker-image text-center">
                        <img src="<?= $course['background_image'] ?? '' ?>" class="card-img">
                        <div class="card-footer">
                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                        </div>
                        <input type="hidden" name="background_image" class="form-control" value="<?= $course['background_image'] ?? '' ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-2">
                <label for="language_id">Язык</label>
                <select name="language_id" class="form-select" id="language_id" value="<?= $course['language_id'] ?? '' ?>" required>
                    <option disabled value>---Не выбрано---</option>
                    <?php foreach($languages as $language) : ?>
                        <?php if(!empty($course['language_id']) && $language['id'] == $course['language_id']) : ?>
                            <option value="<?= $language['id'] ?>" selected><?= $language['title'] ?></option>
                        <?php else: ?>   
                            <option value="<?= $language['id'] ?>"><?= $language['title'] ?></option>
                        <?php endif; ?>   
                    <?php endforeach; ?>    
                </select>
            </div>
            <div class="form-group mt-2">
                <label for="published">Опубликовано</label>
                <select class="form-select" name="published" id="published">
                    <?php if(!empty($course['published']) && $course['published'] == 1) : ?>
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
                    <?php else: ?>   
                        <option value="0">No</option>
                        <option value="1" selected>Yes</option>
                    <?php endif; ?> 
                </select>
            </div>
            <div class="form-group mt-2">
                <label for="is_private">Приватный</label>
                <select class="form-select" name="is_private" id="is_private">
                    <?php if(!empty($course['published']) && $course['published'] == 1) : ?>
                        <option value="0">No</option>
                        <option value="1" selected>Yes</option>
                    <?php else: ?>   
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
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