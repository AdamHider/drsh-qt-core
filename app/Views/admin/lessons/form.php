<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container pb-3">
    <form action="/admin/lessons/save<?= $lesson ? '/' . $lesson['id'] : '' ?>" method="post">
        <div class="d-flex justify-content-end mb-3">
            <button type="submit" class="btn btn-primary rounded-3">Сохранить</button>
        </div>
        <div class="row rounded-3 shadow-sm border bg-white p-2  pb-3">



            <div class="accordion" id="accordionPanelsStayOpenExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                            Общая информация
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show" aria-labelledby="panelsStayOpen-headingOne">
                        <div class="accordion-body">
                            <div class="form-group mt-2">
                                <label for="title">Название</label>
                                <input type="text" name="title" id="title" class="form-control" value="<?= $lesson['title'] ?? '' ?>" required>
                            </div>
                            <div class="form-group mt-2">
                                <label for="description">Описание</label>
                                <textarea type="text" name="description" id="description" class="form-control" value="<?= $lesson['description'] ?? '' ?>" required><?= $lesson['description'] ?? '' ?></textarea>
                            </div>
                            <div class="form-group mt-2">
                                <label for="course_id">Курс</label>
                                <select name="course_id" class="form-select" id="course_id" value="<?= $lesson['course_id'] ?? '' ?>" required>
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php foreach($courses as $course) : ?>
                                        <?php if(!empty($lesson['course_id']) && $course['id'] == $lesson['course_id']) : ?>
                                            <option value="<?= $course['id'] ?>" selected><?= $course['title'] ?></option>
                                        <?php else: ?>   
                                            <option value="<?= $course['id'] ?>"><?= $course['title'] ?></option>
                                        <?php endif; ?>   
                                    <?php endforeach; ?>    
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="course_section_id">Раздел курса</label>
                                <select name="course_section_id" class="form-select" id="course_section_id" value="<?= $lesson['course_section_id'] ?? '' ?>" required>
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php foreach($course_sections as $course_section) : ?>
                                        <?php if(!empty($lesson['course_section_id']) && $course_section['id'] == $lesson['course_section_id']) : ?>
                                            <option value="<?= $course_section['id'] ?>" selected><?= $course_section['title'] ?></option>
                                        <?php else: ?>   
                                            <option value="<?= $course_section['id'] ?>"><?= $course_section['title'] ?></option>
                                        <?php endif; ?>   
                                    <?php endforeach; ?>    
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="language_id">Язык</label>
                                <select name="language_id" class="form-select" id="language_id" value="<?= $lesson['language_id'] ?? '' ?>" required>
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php foreach($languages as $language) : ?>
                                        <?php if(!empty($lesson['language_id']) && $language['id'] == $lesson['language_id']) : ?>
                                            <option value="<?= $language['id'] ?>" selected><?= $language['title'] ?></option>
                                        <?php else: ?>   
                                            <option value="<?= $language['id'] ?>"><?= $language['title'] ?></option>
                                        <?php endif; ?>   
                                    <?php endforeach; ?>    
                                </select>
                            </div>
                            <div class="row mt-2">
                                <div class="form-group col-4">
                                    <label for="image">Изображение</label>
                                    <div class="card ficker-image text-center">
                                        <img src="<?= $lesson['image'] ?? '' ?>" class="card-img">
                                        <div class="card-footer">
                                            <button class="btn btn-outline-secondary pick-image" type="button">Choose</button>
                                        </div>
                                        <input type="hidden" name="image" class="form-control" value="<?= $lesson['image'] ?? '' ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                            Страницы
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
                        <div class="accordion-body">
                            <div class="form-group mt-2">
                                <label for="type">Тип</label>
                                <select class="form-select" name="type" id="type" required>
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php if(!empty($lesson['type']) && $lesson['type'] == 'common') : ?>
                                        <option value="common" selected>Common</option>
                                        <option value="lexis">Lexis</option>
                                        <option value="grammar">Grammar</option>
                                    <?php elseif(!empty($lesson['type']) && $lesson['type'] == 'lexis') : ?>   
                                        <option value="common">Common</option>
                                        <option value="lexis" selected>Lexis</option>
                                        <option value="grammar">Grammar</option>
                                    <?php elseif(!empty($lesson['type']) && $lesson['type'] == 'grammar') : ?>   
                                        <option value="common">Common</option>
                                        <option value="lexis">Lexis</option>
                                        <option value="grammar" selected>Grammar</option>
                                    <?php else : ?>   
                                        <option value="common">Common</option>
                                        <option value="lexis">Lexis</option>
                                        <option value="grammar">Grammar</option>
                                    <?php endif; ?> 
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="pages">Страницы</label>
                                <input type="text" name="pages" id="pages" class="form-control" value="<?= $lesson['pages'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                            Стоимость и награды
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingThree">
                        <div class="accordion-body">
                            <div class="form-group mt-2">
                                <label for="cost_config">Конфигурация стоимости</label>
                                <input type="text" name="cost_config" id="cost_config" class="form-control" value="<?= $lesson['cost_config'] ?? '' ?>">
                            </div>
                            <div class="form-group mt-2">
                                <label for="reward_config">Конфигурация наград</label>
                                <input type="text" name="reward_config" id="reward_config" class="form-control" value="<?= $lesson['reward_config'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="panelsStayOpen-headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
                            Связи
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingFour">
                        <div class="accordion-body">
                            <div class="form-group mt-2">
                                <label for="parent_id">Родительский ID</label>
                                <select name="parent_id" class="form-select" id="parent_id" value="<?= $lesson['parent_id'] ?? '' ?>">
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php foreach($parent_lessons as $parent_lesson) : ?>
                                        <?php if(!empty($lesson['parent_id']) && $parent_lesson['id'] == $lesson['parent_id']) : ?>
                                            <option value="<?= $parent_lesson['id'] ?>" selected><?= $parent_lesson['title'] ?></option>
                                        <?php else: ?>   
                                            <option value="<?= $parent_lesson['id'] ?>"><?= $parent_lesson['title'] ?></option>
                                        <?php endif; ?>   
                                    <?php endforeach; ?>    
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="unblock_after">Разблокировать после</label>
                                <select name="unblock_after" class="form-select" id="unblock_after" value="<?= $lesson['unblock_after'] ?? '' ?>">
                                    <option disabled value selected>---Не выбрано---</option>
                                    <?php foreach($unblock_lessons as $unblock_lesson) : ?>
                                        <?php if(!empty($lesson['unblock_after']) && $unblock_lesson['id'] == $lesson['unblock_after']) : ?>
                                            <option value="<?= $unblock_lesson['id'] ?>" selected><?= $unblock_lesson['title'] ?></option>
                                        <?php else: ?>   
                                            <option value="<?= $unblock_lesson['id'] ?>"><?= $unblock_lesson['title'] ?></option>
                                        <?php endif; ?>   
                                    <?php endforeach; ?>    
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="published">Опубликовано</label>
                                <select class="form-select" name="published" id="published" required>
                                    <?php if(!empty($lesson['published']) && $lesson['published'] == 1) : ?>
                                        <option value="0">No</option>
                                        <option value="1" selected>Yes</option>
                                    <?php else: ?>   
                                        <option value="0" selected>No</option>
                                        <option value="1">Yes</option>
                                    <?php endif; ?> 
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label for="is_private">Приватный</label>
                                <select class="form-select" name="is_private" id="is_private">
                                    <?php if(!empty($lesson['published']) && $lesson['published'] == 1) : ?>
                                        <option value="0">No</option>
                                        <option value="1" selected>Yes</option>
                                    <?php else: ?>   
                                        <option value="0" selected>No</option>
                                        <option value="1">Yes</option>
                                    <?php endif; ?> 
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            
            
        </div>
    </form>
</div>
<?= $this->endSection() ?>