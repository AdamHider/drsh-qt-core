<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="/admin/quest_groups/form" class="btn btn-success rounded-3"><i class="bi bi-plus-lg"></i> Добавить группу квестов</a>
    </div>
    <div class="pb-5">
        <ul class="list-group">
            <?php foreach ($quest_groups as $quest_group): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-auto">
                            <h5><?= $quest_group['description']['title'] ?></h5>
                            <p><?= $quest_group['description']['description'] ?></p>
                        </div>
                        <div class="ms-auto">
                            <a href="/admin/quest_groups/form/<?= $quest_group['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="/admin/quest_groups/delete/<?= $quest_group['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                    <h6>Квесты:</h6>
                    <ul class="list-group mt-2">
                        <?php foreach ($quest_group['quests'] as $quest): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="me-auto">
                                    <h6><?= $quest['title'] ?></h6>
                                    <p><?= $quest['description'] ?></p>
                                </div>
                                <div class="ms-auto">
                                    <a href="/admin/quests/form/<?= $quest_group['id'] ?>/<?= $quest['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                    <a href="/admin/quests/delete/<?= $quest_group['id'] ?>/<?= $quest['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <a href="/admin/quests/form/<?= $quest['id'] ?>" class="list-group-item list-group-item-action">
                            <h6><i class="bi bi-plus-lg"></i> Добавить секцию</h6>
                        </a>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?= $this->endSection() ?>
