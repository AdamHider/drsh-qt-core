function updateTemplateConfig() {
    const templateType = objectToEdit.page_template;
    const existingConfig = objectToEdit.template_config;

    if (config.template_config.fields[templateType]) {
        Object.keys(config.template_config.fields[templateType]).forEach(key => {
            if (!existingConfig[key]) {
                existingConfig[key] = config.template_config.fields[templateType][key].default;
            }
        });
    }

    $('#templateConfigEditor').empty();
    initializeEditor(config.template_config.fields[templateType], existingConfig, $('#templateConfigEditor'));
}
function updateInputListConfig() {
    const formTemplateType = objectToEdit.form_template;
    config.template_config.fields.dialogue.input_list.itemConfig = formTemplates[formTemplateType];
    config.template_config.fields.grid.input_list.itemConfig = formTemplates[formTemplateType];
}

function createField(fieldConfig, object, key, parentObject) {
    if (fieldConfig.type === 'fieldGroup') {
        return createFieldGroup(fieldConfig, object || {}, parentObject);
    }
    let fieldDiv = $(`<${fieldConfig.tag ?? 'div'}>`).addClass(`${fieldConfig.class}`);
    let label;
    if(fieldConfig.nolabel){
        fieldDiv.addClass(`mt-3`);
    } else {
        label = $('<label>').text(key).addClass('form-label');
    }
    let input;
    if (fieldConfig.type === 'input') {
        input = $('<input>').attr('type', 'text').val(object[key] || fieldConfig.default).addClass('form-control form-control-sm').attr('placeholder', key);
    } else if (fieldConfig.type === 'textarea') {
        input = $('<textarea>').attr('type', 'text').val(object[key] || fieldConfig.default).html(object[key] || fieldConfig.default).addClass('form-control form-control-sm').attr('placeholder', key);
    } else if (fieldConfig.type === 'number') {
        input = $('<input>').attr('type', 'number').val(object[key] || fieldConfig.default).addClass('form-control form-control-sm').attr('placeholder', key);;
    } else if (fieldConfig.type === 'checkbox') {
        input = $('<input>').attr('type', 'checkbox').prop('checked', object[key] !== undefined ? object[key] : fieldConfig.default).addClass('form-check-input form-check-input-sm me-2');
    } else if (fieldConfig.type === 'select') {
        input = $('<select>').addClass('form-select form-select-sm');
        fieldConfig.options.forEach(option => {
            const optionElement = $('<option>').attr('value', option).text(option);
            input.append(optionElement);
        });
        input.val(object[key] || fieldConfig.default);
    }  else if (fieldConfig.type === 'image') {
        input = $('<div>').append($('<input>').attr('type', 'hidden').val(object[key] || fieldConfig.default), $('<img>', {src: (object[key] || fieldConfig.default), class: 'card-img'}));
        input.on('click', (e) => { 
            let container = $(e.delegateTarget);
            let modal = new bootstrap.Modal(document.getElementById('pickerModal'), {})
            initFileExplorer({
                filePickerElement: '#file_picker',
                multipleMode: false,
                pickerMode: true,
                onPicked: (value) => {
                    $(container).find('input').val(value);
                    $(container).find('img').prop('src', value)
                    $(input).val(value).trigger('input')
                    modal.hide()
                }
            });
            modal.show()})
    } else if (fieldConfig.type === 'audio') {
        input = $('<div>').append($('<input class="form-control form-control-sm">').attr('type', 'text').val(object[key] || fieldConfig.default), $('<audio>', {src: (object[key] || fieldConfig.default)})).addClass('input-group input-group-sm audio-container');
        const playButton = $('<button>').on('click', (e) => {
            e.preventDefault();
            const audio = $(e.delegateTarget).closest('.audio-container').find('audio').get(0)
            if($(e.delegateTarget).hasClass('playing')){
                audio.pause();
                $(e.delegateTarget).removeClass('playing')
                $(e.delegateTarget).find('i').removeClass('bi-pause').addClass('bi-play')
            } else {
                audio.play();
                $(e.delegateTarget).addClass('playing')
                $(e.delegateTarget).find('i').removeClass('bi-play').addClass('bi-pause')
            }
        }).addClass('btn btn-primary').append($('<i class="bi bi-play"></i>'));
        input.append(playButton)
        input.find('input').on('click', (e) => { 
            let container = $(e.delegateTarget).closest('.audio-container');
            let modal = new bootstrap.Modal(document.getElementById('pickerModal'), {})
            initFileExplorer({
                filePickerElement: '#file_picker',
                multipleMode: false,
                pickerMode: true,
                onPicked: (value) => {
                    $(container).find('input').val(value);
                    $(container).find('audio').prop('src', value)
                    $(input).val(value).trigger('input')
                    modal.hide()
                }
            });
            modal.show()})
    } else if (fieldConfig.type === 'array') {
        input = createArrayField(fieldConfig, object, key);
    }

    if (!input) {
        console.error(`Unsupported field type: ${fieldConfig.type}`);
        return null;
    }

    input.on('input change', function(e) {
        const value = (fieldConfig.type === 'checkbox') ? $(this).prop('checked') : $(this).val();
        parentObject[key] = value; // Обновляем поле в родительском объекте (template_config)
        if (key === 'page_template') {
            updatePageData(); // Обновляем данные в pages
            loadPage(selectedPageIndex); // Re-render the page when page_template changes
        }
        if (key === 'form_template') {
            updatePageData(); // Обновляем данные в pages
            updateInputListConfig(); // Update input_list config based on form_template
            loadPage(selectedPageIndex); // Re-render the page
        }
        if(fieldConfig.onchange) fieldConfig.onchange(e)
        updatePageData(); // Обновляем данные в pages
    });


    fieldDiv.append(label, input);
    

    return fieldDiv;
}

function createFieldGroup(fieldConfig, object, parentObject) {
    let groupDiv = $(`<${fieldConfig.tag}>`).addClass(fieldConfig.class);
    
    let groupContent = $(`<div>`).addClass(fieldConfig.contentclass);

    if(fieldConfig.collapsible){
        const id = Math.floor(Math.random() * 10000);
        const label = $('<label>').text(fieldConfig.label ?? '').addClass('btn-link').attr('data-bs-toggle', 'collapse').attr('data-bs-target', `#collapse_${id}`).on('click', (e) => {
            e.preventDefault()
            if($(e.delegateTarget).hasClass('collapsed')){
                $(e.delegateTarget).find('i').addClass('bi-chevron-down').removeClass('bi-chevron-up')
            } else {
                $(e.delegateTarget).find('i').addClass('bi-chevron-up').removeClass('bi-chevron-down')
            }
        }).append('<i class="ms-2 bi bi-chevron-down"></i>');
        groupDiv.append(label)
        groupContent.addClass('collapse show').prop('id', `collapse_${id}`)
    } 

    Object.keys(fieldConfig.fields).forEach(key => {
        const subFieldConfig = fieldConfig.fields[key];
        const subFieldDiv = createField(subFieldConfig, object, key, parentObject);
        if (subFieldDiv) {
            groupContent.append(subFieldDiv);
        }
    });
    groupDiv.append(groupContent)
    return groupDiv;
}


function createObjectField(fieldConfig, object, key, parentObject) {
    const fieldDiv = $('<div>').addClass('mb-3');

    const label = $('<label>').text(key).addClass('form-label');
    fieldDiv.append(label);

    Object.keys(fieldConfig.fields).forEach(subKey => {
        const subFieldConfig = fieldConfig.fields[subKey];
        const subObject = object[key] || {};
        const subFieldDiv = createField(subFieldConfig, subObject, subKey, subObject);
        if (subFieldDiv) {
            fieldDiv.append(subFieldDiv);
        }
        object[key] = subObject; // Обновляем поле в объекте
    });

    parentObject[key] = object; // Обновляем поле в родительском объекте (template_config)
    updatePageData(); // Обновляем данные в pages
    return fieldDiv;
}

function createArrayField(fieldConfig, object, key) {
    const fieldDiv = $('<div>').addClass(fieldConfig.class);

    const label = $('<label>').text(`${fieldConfig.label ?? key}`).addClass('form-label w-75');
    
    fieldDiv.append(label);
    const listDiv = $('<div>', { id: `${key}List` }).addClass(fieldConfig.contentclass);
    if(fieldConfig.collapsible){
        const id = Math.floor(Math.random() * 10000);
        label.addClass('btn-link').attr('data-bs-toggle', 'collapse').attr('data-bs-target', `#${key}List`).on('click', (e) => {
            e.preventDefault()
            if($(e.delegateTarget).hasClass('collapsed')){
                $(e.delegateTarget).find('i').addClass('bi-chevron-down').removeClass('bi-chevron-up')
            } else {
                $(e.delegateTarget).find('i').addClass('bi-chevron-up').removeClass('bi-chevron-down')
            }
        }).append('<i class="ms-2 bi bi-chevron-down"></i>');
        listDiv.addClass('collapse show')
    } 
    fieldDiv.append(listDiv);

    // Render existing items
    if (object[key]) {
        object[key].forEach((item, index) => {
            let itemDiv = $(`<${fieldConfig.itemConfig.tag}>`).addClass(fieldConfig.itemConfig.class);
            Object.keys(fieldConfig.itemConfig.fields).forEach(subKey => {
                const subFieldConfig = fieldConfig.itemConfig.fields[subKey];
                const subFieldDiv = createField(subFieldConfig, item, subKey, item);
                if (subFieldDiv) {
                    itemDiv.append(subFieldDiv);
                }
            });
            listDiv.append(itemDiv);
            
            if(fieldConfig.itemConfig.render) {
                console.log(itemDiv)
                fieldConfig.itemConfig.render(itemDiv)
            }
        });
    }
    if(fieldConfig.itemConfig){
        const addButton = $('<div>').append($('<button>', {
            text: `Add ${fieldConfig.itemConfig.label}`,
            class: 'btn btn-success',
            click: (event) => addItem(event, key, fieldConfig.itemConfig)
        }).append($('<i>').addClass('bi bi-plus'))).addClass('text-center rounded bg-light p-2 my-2');

        fieldDiv.append(addButton);
    }
    return fieldDiv;
}

function addItem(event, arrayKey, itemConfig) {
    event.preventDefault();

    if (!objectToEdit.template_config[arrayKey]) {
        objectToEdit.template_config[arrayKey] = [];
    }
    const newItem = {};
    Object.keys(itemConfig).forEach(subKey => {
        newItem[subKey] = itemConfig[subKey].default;
    });

    objectToEdit.template_config[arrayKey].push(newItem);

    let newItemDiv = $(`<${itemConfig.tag}>`).addClass(itemConfig.class);
    Object.keys(itemConfig.fields).forEach(subKey => {
        const subFieldConfig = itemConfig.fields[subKey];
        const subFieldDiv = createField(subFieldConfig, newItem, subKey, newItem);
        if (subFieldDiv) {
            newItemDiv.append(subFieldDiv);
        }
    });
    console.log()
    $(`#${arrayKey}List`).append(newItemDiv);
    updatePageData(); // Обновляем данные в pages
}

function initializeEditor(config, object, parent) {
    Object.keys(config).forEach(key => {
        const fieldConfig = config[key];
        let fieldDiv;
        if (fieldConfig.type === 'object') {
            fieldDiv = createObjectField(fieldConfig, object[key] || {}, key, object);
        } else if (fieldConfig.type === 'array') {
            fieldDiv = createArrayField(fieldConfig, object, key);
        } else {
            fieldDiv = createField(fieldConfig, object, key, object);
        }
        if (fieldDiv) {
            parent.append(fieldDiv);
        }
    });
}

function loadPage(index) {
    selectedPageIndex = index;
    objectToEdit = JSON.parse(JSON.stringify(pages[selectedPageIndex])); // Глубокое клонирование объекта
    updateInputListConfig(); // Обновляем input_list конфигурацию на основе form_template
    $('#editor').empty();
    initializeEditor(config, objectToEdit, $('#editor'));
    $('<div>', { id: 'templateConfigEditor', class:'row' }).appendTo('#editor');
    updateTemplateConfig();
    updateInputListConfig(); // Обновляем input_list конфигурацию на основе form_template
}

function updatePageData() {
    pages[selectedPageIndex] = JSON.parse(JSON.stringify(objectToEdit)); // Обновляем данные в pages
}

$(document).ready(function() {
    $('#addReplica').on('click', (event) => {
        event.preventDefault();
        addItem(event, 'replica_list', replicaItemConfig);
    });
});
