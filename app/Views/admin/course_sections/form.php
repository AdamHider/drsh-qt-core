<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container pb-3">
    <?php if(isset($section['id'])) : ?>
    <form action="/admin/course_sections/save<?= $course_id ? '/' . $course_id : '' ?>/<?= $section ? '/' . $section['id'] : '' ?>" method="post">
    <?php else : ?>
    <form action="/admin/course_sections/create<?= $course_id ? '/' . $course_id : '' ?>" method="post">
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
        <?php if (session()->getFlashdata('status')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('status') ?>
            </div>
        <?php endif; ?>
        <div class="row rounded-3 shadow-sm border bg-white p-2  pb-3">
            <div class="form-group mt-2">
                <label for="description">Курс</label>
                <input type="text" disabled readonly class="form-control" value="<?= $course['title'] ?? '' ?>" >
            </div>
            <div class="form-group mt-2">
                <label for="title">Название</label>
                <input type="text" name="title" id="title" class="form-control" value="<?= $section['title'] ?? '' ?>">
            </div>
            <div class="form-group mt-2">
                <label for="description">Описание</label>
                <input type="text" name="description" id="description" class="form-control" value="<?= $section['description'] ?? '' ?>">
            </div>
            <div class="row mt-2">
                <div class="form-group col-4">
                    <label for="background_image">Фоновое изображение</label>
                    <div class="card ficker-image text-center">
                        <img src="<?= $section['background_image'] ?? '' ?>" class="card-img">
                        <div class="card-footer">
                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                        </div>
                        <input type="hidden" name="background_image" class="form-control" value="<?= $section['background_image'] ?? '' ?>">
                    </div>
                </div>
            </div>
            <div class="form-group mt-2">
                <label for="background_gradient">Фоновый градиент</label>
                <input type="text" name="background_gradient" id="background_gradient" class="form-control" value="<?= $section['background_gradient'] ?? '' ?>">
            </div>
            <div class="form-group mt-2">
                <label for="language_id">Язык</label>
                <select name="language_id" class="form-select" id="language_id" value="<?= $section['language_id'] ?? '' ?>">
                    <option disabled value>---Не выбрано---</option>
                    <?php foreach($languages as $language) : ?>
                        <?php if(!empty($section['language_id']) &&  $language['id'] == $section['language_id']) : ?>
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
                    <?php if(!empty($section['published']) && $section['published'] == 1) : ?>
                        <option value="0">Нет</option>
                        <option value="1" selected>Да</option>
                    <?php else: ?>   
                        <option value="0" selected>Нет</option>
                        <option value="1">Да</option>
                    <?php endif; ?> 
                </select>
            </div>
            <div class="form-group mt-2">
                <label for="is_private">Приватный</label>
                <select class="form-select" name="is_private" id="is_private">
                    <?php if(!empty($section['published']) && $section['published'] == 1) : ?>
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