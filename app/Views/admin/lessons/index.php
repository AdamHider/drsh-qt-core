<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <a href="/admin/lessons/create" class="btn btn-primary mb-3">Создать новый урок</a>
    <ul class="list-group">
        <?php foreach ($lessons as $lesson): ?>
            <li class="list-group-item">
                <h5><?= $lesson['title'] ?></h5>
                <p><?= $lesson['description'] ?></p>
                <a href="/admin/lessons/form/<?= $lesson['id'] ?>" class="btn btn-warning btn-sm">Изменить</a>
                <a href="/admin/lessons/delete/<?= $lesson['id'] ?>" class="btn btn-danger btn-sm">Удалить</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?= $this->endSection() ?>
