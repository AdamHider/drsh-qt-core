<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <form action="/admin/lessons/save<?= $lesson ? '/' . $lesson['id'] : '' ?>" method="post">
        <div class="d-flex justify-content-end p-2">
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
        <div class="row rounded shadow-sm border bg-white p-2">
            <div class="form-group">
                <label for="course_id">Курс</label>
                <input type="text" name="course_id" id="course_id" class="form-control" value="<?= $lesson['course_id'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="course_section_id">Раздел курса</label>
                <input type="text" name="course_section_id" id="course_section_id" class="form-control" value="<?= $lesson['course_section_id'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="language_id">Язык</label>
                <input type="text" name="language_id" id="language_id" class="form-control" value="<?= $lesson['language_id'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="title">Название</label>
                <input type="text" name="title" id="title" class="form-control" value="<?= $lesson['title'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="description">Описание</label>
                <input type="text" name="description" id="description" class="form-control" value="<?= $lesson['description'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="type">Тип</label>
                <input type="text" name="type" id="type" class="form-control" value="<?= $lesson['type'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="pages">Страницы</label>
                <input type="text" name="pages" id="pages" class="form-control" value="<?= $lesson['pages'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="cost_config">Конфигурация стоимости</label>
                <input type="text" name="cost_config" id="cost_config" class="form-control" value="<?= $lesson['cost_config'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="reward_config">Конфигурация наград</label>
                <input type="text" name="reward_config" id="reward_config" class="form-control" value="<?= $lesson['reward_config'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="image">Изображение</label>
                <input type="text" name="image" id="image" class="form-control" value="<?= $lesson['image'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="published">Опубликовано</label>
                <input type="text" name="published" id="published" class="form-control" value="<?= $lesson['published'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="parent_id">Родительский ID</label>
                <input type="text" name="parent_id" id="parent_id" class="form-control" value="<?= $lesson['parent_id'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="unblock_after">Разблокировать после</label>
                <input type="text" name="unblock_after" id="unblock_after" class="form-control" value="<?= $lesson['unblock_after'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="is_private">Приватный</label>
                <input type="text" name="is_private" id="is_private" class="form-control" value="<?= $lesson['is_private'] ?? '' ?>">
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>