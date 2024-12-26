<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="/admin/lessons/form" class="btn btn-primary rounded-3"><i class="bi bi-plus-lg"></i> Добавить урок</a>
    </div>
    <div class="">
        <ul class="list-group rounded-3 shadow-sm bg-white">
            <?php foreach ($lessons as $lesson): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="me-auto">
                        <h5><?= $lesson['title'] ?></h5>
                        <p><?= $lesson['description'] ?></p>
                    </div>
                    <div class="ms-auto"> 
                        <a href="/admin/lessons/form/<?= $lesson['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Изменить</a>
                        <a href="/admin/lessons/delete/<?= $lesson['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-pencil"></i> Удалить</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?= $this->endSection() ?>
