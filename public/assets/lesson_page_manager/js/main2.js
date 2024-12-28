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
    if (formTemplates[formTemplateType]) {
        config.template_config.fields.dialogue.input_list.itemConfig = formTemplates[formTemplateType];
        config.template_config.fields.grid.input_list.itemConfig = formTemplates[formTemplateType];
    }
}

function createField(fieldConfig, object, key, parentObject) {
    const fieldDiv = $('<div>').addClass('mb-3');

    const label = $('<label>').text(key).addClass('form-label');

    let input;
    if (fieldConfig.type === 'input') {
        input = $('<input>').attr('type', 'text').val(object[key] || fieldConfig.default).addClass('form-control');
    } else if (fieldConfig.type === 'number') {
        input = $('<input>').attr('type', 'number').val(object[key] || fieldConfig.default).addClass('form-control');
    } else if (fieldConfig.type === 'checkbox') {
        input = $('<input>').attr('type', 'checkbox').prop('checked', object[key] !== undefined ? object[key] : fieldConfig.default).addClass('form-check-input');
    } else if (fieldConfig.type === 'select') {
        input = $('<select>').addClass('form-select');
        fieldConfig.options.forEach(option => {
            const optionElement = $('<option>').attr('value', option).text(option);
            input.append(optionElement);
        });
        input.val(object[key] || fieldConfig.default);
    }

    if (!input) {
        console.error(`Unsupported field type: ${fieldConfig.type}`);
        return null;
    }

    input.addClass(fieldConfig.class).on('input change', function() {
        const value = (fieldConfig.type === 'checkbox') ? $(this).prop('checked') : $(this).val();
        parentObject[key] = value; // Обновляем поле в родительском объекте (template_config)
        updatePageData(); // Обновляем данные в pages
        if (key === 'page_template') {
            loadPage(selectedPageIndex); // Re-render the page when page_template changes
        }
        if (key === 'form_template') {
            updateInputListConfig(); // Update input_list config based on form_template
            loadPage(selectedPageIndex); // Re-render the page
        }
    });

    fieldDiv.append(label, input);
    return fieldDiv;
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
    const fieldDiv = $('<div>').addClass('mb-3');

    const label = $('<label>').text(key).addClass('form-label');
    const addButton = $('<button>', {
        text: `Add Item to ${key}`,
        class: 'btn btn-secondary btn-sm',
        click: (event) => addItem(event, key, fieldConfig.itemConfig)
    });

    fieldDiv.append(label);
    fieldDiv.append(addButton);

    const listDiv = $('<div>', { id: `${key}List` }).addClass('mt-2');
    fieldDiv.append(listDiv);

    // Render existing items
    if (object[key]) {
        object[key].forEach((item, index) => {
            const itemDiv = $('<div>').addClass('mt-2');
            Object.keys(fieldConfig.itemConfig).forEach(subKey => {
                const subFieldConfig = fieldConfig.itemConfig[subKey];
                const subFieldDiv = createField(subFieldConfig, item, subKey, item);
                if (subFieldDiv) {
                    itemDiv.append(subFieldDiv);
                }
            });
            listDiv.append(itemDiv);
        });
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

    const newItemDiv = $('<div>').addClass('mt-2');
    Object.keys(itemConfig).forEach(subKey => {
        const subFieldConfig = itemConfig[subKey];
        const subFieldDiv = createField(subFieldConfig, newItem, subKey, newItem);
        if (subFieldDiv) {
            newItemDiv.append(subFieldDiv);
        }
    });

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
    $('#editor').empty();
    initializeEditor(config, objectToEdit, $('#editor'));
    $('<div>', { id: 'templateConfigEditor' }).appendTo('#editor');
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
