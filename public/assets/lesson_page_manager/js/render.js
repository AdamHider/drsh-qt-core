function renderPagesList(pages) {
    const pagesList = $('#pagesList');
    pagesList.empty();
    pages.forEach((page, index) => {
        const listItem = $('<li>', {
            class: 'list-group-item d-flex justify-content-between align-items-center',
            'data-index': index,
            text: page.title || 'New Page'
        }).on('click', function() {
            const index = $(this).data('index');
            renderPageForm(pages[index], index);
        });

        const deleteBtn = $('<button>', {
            class: 'btn btn-danger btn-sm deletePageBtn',
            'data-index': index,
            text: 'Delete'
        }).on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const index = $(this).data('index');
            pages.splice(index, 1);
            renderPagesList(pages);
            $('#pageFormContainer').empty();
            updatePagesInput();
        });

        listItem.append(deleteBtn);
        pagesList.append(listItem);
    });
    initControls();
}

function renderPageForm(page, index) {
    const form = $('<form>', { id: 'pageForm' });
    const rowTitle = $('<div class="row align-items-end">');
    rowTitle.append($('<div class="col-5">').append(createFormGroup('Title', 'page_title', 'title', page.title, index)));
    rowTitle.append($('<div class="col-5">').append(createFormGroup('Subtitle', 'page_subtitle', 'subtitle', page.subtitle, index)));
    rowTitle.append($('<div class="col-2">').append($('<a class="btn btn-primary" data-bs-toggle="collapse" href="#pageDetails" role="button"><i class="bi bi-three-dots-vertical"></i></button>')));

    form.append(rowTitle)  

    const collapse = $('<div class="collapse" id="pageDetails">');
    collapse.append(createFormSelect('Audio', 'page_audio', 'audio', page.audio, index, [
        { value: '', text: 'Выбрать', disabled: true, selected: !page.audio },
        { value: 'no', text: 'Нет', selected: page.audio === 'no' },
        { value: 'yes', text: 'Да', selected: page.audio === 'yes' }
    ]));

    collapse.append(createFormGroup('Index', 'page_index', 'index', page.index, index));

    form.append(collapse)    
    form.append(createFormSelect('Page Template', 'page_template', 'page_template', page.page_template, index, [
        { value: '', text: 'Выбрать', disabled: true, selected: !page.page_template },
        { value: 'dialogue', text: 'Dialogue', selected: page.page_template === 'dialogue' },
        { value: 'chat', text: 'Chat', selected: page.page_template === 'chat' },
        { value: 'answerQuestion', text: 'Answer Question', selected: page.page_template === 'answerQuestion' },
        { value: 'grid', text: 'Grid', selected: page.page_template === 'grid' }
    ]));

    const templateConfigContainer = $('<div>', { class: 'form-group', id: `templateConfigContainer_${index}` });
    form.append(templateConfigContainer);

    form.append(createFormSelect('Form Template', 'form_template', 'form_template', page.form_template, index, [
        { value: '', text: 'Выбрать', disabled: true, selected: !page.form_template },
        { value: 'variant', text: 'Variant', selected: page.form_template === 'variant' },
        { value: 'chat', text: 'Chat', selected: page.form_template === 'chat' },
        { value: 'match', text: 'Match', selected: page.form_template === 'match' },
        { value: 'grid', text: 'Grid', selected: page.form_template === 'grid' }
    ]));

    const formConfigContainer = $('<div>', { class: 'form-group', id: `formConfigContainer_${index}` });
    form.append(formConfigContainer);
    
    if(page.page_template == 'dialogue' ){
        const addReplicaBtn = $('<button>', {
            type: 'button',
            class: 'btn btn-secondary addReplicaBtn',
            'data-index': index,
            text: 'Add Replica'
        }).on('click', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            pages[index].template_config.replica_list.push({ ...defaultDialogueReplica });
            renderReplicaList(pages[index].template_config.replica_list, index);
            updatePagesInput();
        });
        form.append(addReplicaBtn);

        const replicaListContainer = $('<div>', { id: `replicaListContainer_${index}`, class:'list-group' });
        form.append(replicaListContainer);
    }

    $('#pageFormContainer').html(form);

    // Render template config if exists
    if (page.template_config) {
        renderTemplateConfig(page.template_config, index);
    }

    // Render replica list if exists
    if (page.template_config.replica_list) {
        renderReplicaList(page.template_config.replica_list, index);
    }
    initControls();
}


function renderTemplateConfig(config, index) {
    const templateConfigContainer = $(`#templateConfigContainer_${index}`);
    templateConfigContainer.empty();

    for (const [key, value] of Object.entries(config)) {
        if (key === 'input_list') continue;
        if (key === 'replica_list') {
            renderReplicaList(value, index);
        } else if (key === 'image') {
            templateConfigContainer.append(renderTemplateImage(value, index));
        } else if (typeof value === 'object' && !Array.isArray(value)) {
            templateConfigContainer.append(createFormGroup(key, `${key}_${index}`, key, JSON.stringify(value), index));
        } else {
            templateConfigContainer.append(createFormGroup(key, `${key}_${index}`, key, value, index));
        }
    }
    initControls();
}

function renderFormConfig(config, index) {
    const formConfigContainer = $(`#formConfigContainer_${index}`);
    formConfigContainer.empty();

    config.forEach((input, idx) => {
        formConfigContainer.append(createFormGroup(`Input ${idx + 1}`, `input_${index}_${idx}`, `input${idx + 1}`, JSON.stringify(input), index));
    });
    initControls();
}

function renderReplicaList(replicaList, index) {
    const replicaListContainer = $(`#replicaListContainer_${index}`);
    replicaListContainer.empty();
    replicaList.forEach((replica, replicaIndex) => {
        const replicaItem = $('<div>', { class: 'card replica-item mb-3 me-5' });
        const cardBody = $('<div>', { class: 'card-body d-flex ' });
        const cardFooter = $('<div>', { class: 'card-footer d-flex g-2 justify-content-end' });
        const replicaItemRight = $('<div>', { class: 'w-25 p-2' });
        const replicaItemLeft = $('<div>', { class: 'w-75 p-2' });
        replicaItemLeft.append(createFormGroup('Name', `replica_name_${index}_${replicaIndex}`, 'name', replica.name, index, {
            input: (e) => {
                pages[index].template_config.replica_list[replicaIndex].name = $(e.delegateTarget).val()
            }
        }));
        replicaItemRight.append(createFormImage('Image', `replica_image_${index}_${replicaIndex}`, 'image', replica.image, index, {
            click: (val) => {
                pages[index].template_config.replica_list[replicaIndex].image = val
            }
        }));
        replicaItemLeft.append(createFormTextarea('Text', `replica_text_${index}_${replicaIndex}`, 'text', replica.text, index, {
            input: (e) => {
                pages[index].template_config.replica_list[replicaIndex].text = $(e.delegateTarget).val()
            }
        }));
        cardFooter.append(createFormAudio('Audio Link', `replica_audio_link_${index}_${replicaIndex}`, 'audio_link', replica.audio_link, index, {
            input: (e) => {
                pages[index].template_config.replica_list[replicaIndex].name = $(e.delegateTarget).val()
            }
        }));
        /*
        cardFooter.append(createFormGroup('Animation', `replica_animation_${index}_${replicaIndex}`, 'animation', JSON.stringify(replica.animation), index, {
            input: (e) => {
                pages[index].template_config.replica_list[replicaIndex].name = $(e.delegateTarget).val()
            }
        }));
        */
        const deleteReplicaBtn = $('<button>', {
            type: 'button',
            class: 'btn btn-danger btn-sm deleteReplicaBtn',
            'data-page-index': index,
            'data-replica-index': replicaIndex,
            text: 'Delete Replica'
        }).on('click', function(e) {
            e.preventDefault();
            const pageIndex = $(this).data('page-index');
            const replicaIndex = $(this).data('replica-index');
            pages[pageIndex].template_config.replica_list.splice(replicaIndex, 1);
            renderReplicaList(pages[pageIndex].template_config.replica_list, pageIndex);
            updatePagesInput();
        });
        cardFooter.append(deleteReplicaBtn);

        cardBody.append(replicaItemRight)
        cardBody.append(replicaItemLeft)
        replicaItem.append(cardBody)
        replicaItem.append(cardFooter)
        replicaListContainer.append(replicaItem);
    });
    initControls();
}


function renderTemplateImage(imageConfig, index) {
    const imageContainer = $(`<div class="image-container row my-2">`);
    imageContainer.empty();
    imageContainer.append($('<div class="col-4">').append(createFormImage('Image', `page_image_${index}`, 'image', imageConfig.source, index, {
        click: (val) => {
            pages[index].template_config.image.source = val
        }
    })));
    imageContainer.append($('<div class="col-2">').append($('<a class="btn btn-primary" data-bs-toggle="collapse" href="#imageCollapse" role="button"><i class="bi bi-three-dots-vertical"></i></button>')));
    const collapse = $('<div class="col-12 collapse" id="imageCollapse">');
    collapse.append(createFormGroup('Image Position', `page_image_position_${index}`, 'position', imageConfig.position, index, {
        input: (e) => {
            pages[index].template_config.image.position = $(e.delegateTarget).val()
        }
    }));
    imageContainer.append(collapse);
    initControls();
    return imageContainer;
}
