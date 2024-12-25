<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="/admin/courses/form" class="btn btn-success rounded-3"><i class="bi bi-plus-lg"></i> Добавить курс</a>
    </div>
    <div class="">
        <ul class="list-group">
            <?php foreach ($courses as $course): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-auto">
                            <h5><?= $course['title'] ?></h5>
                            <p><?= $course['description'] ?></p>
                        </div>
                        <div class="ms-auto">
                            <a href="/admin/courses/form/<?= $course['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Изменить</a>
                            <a href="/admin/courses/delete/<?= $course['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Удалить</a>
                        </div>
                    </div>
                    <h6>Секции:</h6>
                    <ul class="list-group mt-2">
                        <?php foreach ($course['sections'] as $section): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-auto">
                                    <h6><?= $section['title'] ?></h6>
                                    <p><?= $section['description'] ?></p>
                                </div>
                                <div class="ms-auto">
                                    <a href="/admin/course_sections/form/<?= $course['id'] ?>/<?= $section['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Изменить</a>
                                    <a href="/admin/course_sections/delete/<?= $course['id'] ?>/<?= $section['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Удалить</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <a href="/admin/course_sections/form/<?= $course['id'] ?>" class="list-group-item list-group-item-action">
                            
                            <h6><i class="bi bi-plus-lg"></i> Добавить секцию</h6>
                        </a>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?= $this->endSection() ?>
