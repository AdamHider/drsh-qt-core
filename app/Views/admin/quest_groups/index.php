<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="/admin/quest_groups/form" class="btn btn-success rounded-3"><i class="bi bi-plus-lg"></i> Добавить курс</a>
    </div>
    <div class="">
        <ul class="list-group">
            <?php foreach ($quest_groups as $quest_group): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-auto">
                            <h5><?= $quest_group['title'] ?></h5>
                            <p><?= $quest_group['description'] ?></p>
                        </div>
                        <div class="ms-auto">
                            <a href="/admin/courses/form/<?= $quest_group['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Изменить</a>
                            <a href="/admin/courses/delete/<?= $quest_group['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Удалить</a>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?= $this->endSection() ?>
