
function createFormGroup(labelText, inputId, inputName, inputValue, index, callbacks = null) {
    const formGroup = $('<div>', { class: 'form-group' });
    const label = $('<label>', { for: `${inputId}_${index}`, text: labelText });
    formGroup.append(label);
    const input = $('<input>', {
        type: 'text',
        class: 'form-control page-input',
        id: `${inputId}_${index}`,
        name: inputName,
        'data-index': index,
        value: inputValue
    });
    if(callbacks && callbacks.click) input.on('click', (e) => { callbacks.click(e) })
    if(callbacks && callbacks.input) input.on('input', (e) => { callbacks.input(e) })
    if(callbacks && callbacks.change) input.on('change', (e) => { callbacks.change(e) })

    formGroup.append(input);
    return formGroup;
}
function createFormTextarea(labelText, inputId, inputName, inputValue, index, callbacks = null) {
    const formGroup = $('<div>', { class: 'form-group' });
    const label = $('<label>', { for: `${inputId}_${index}`, text: labelText });
    formGroup.append(label);
    const input = $('<textarea>', {
        type: 'text',
        class: 'form-control page-input',
        id: `${inputId}_${index}`,
        name: inputName,
        'data-index': index,
        value: inputValue,
        html: inputValue
    });
    if(callbacks && callbacks.click) input.on('click', (e) => { callbacks.click(e) })
    if(callbacks && callbacks.input) input.on('input', (e) => { callbacks.input(e) })
    if(callbacks && callbacks.change) input.on('change', (e) => { callbacks.change(e) })

    formGroup.append(input);
    return formGroup;
}
function createFormSelect(labelText, inputId, inputName, inputValue, index, selectOptions, callbacks = null) {
    const formGroup = $('<div>', { class: 'form-group' });
    const label = $('<label>', { for: `${inputId}_${index}`, text: labelText });
    formGroup.append(label);
    const select = $('<select>', {
        class: 'form-select page-select',
        id: `${inputId}_${index}`,
        name: inputName,
        'data-index': index
    });
    selectOptions.forEach(option => {
        const optionElement = $('<option>', {
            value: option.value,
            text: option.text,
            disabled: option.disabled,
            selected: option.selected
        });
        select.append(optionElement);
    });
    if(callbacks && callbacks.click) input.on('click', (e) => { callbacks.click(e) })
    if(callbacks && callbacks.input) input.on('input', (e) => { callbacks.input(e) })
    if(callbacks && callbacks.change) input.on('change', (e) => { callbacks.change(e) })

    formGroup.append(select);
    return formGroup;
}
function createFormImage(labelText, inputId, inputName, inputValue, index, callbacks) {
    const formGroup = $('<div>', { class: 'card ficker-image text-center' });
    const label = $('<label>', { for: `${inputId}_${index}`, text: labelText });
    formGroup.append(label);
    const image = $('<img>', {src: inputValue, class: 'card-img'})
    const input = $('<input>', {
        type: 'hidden',
        class: 'form-control page-input',
        id: `${inputId}_${index}`,
        name: inputName,
        'data-index': index,
        value: inputValue
    });
    formGroup.on('click', (e) => { 
        let container = $(e.delegateTarget);
        let modal = new bootstrap.Modal(document.getElementById('pickerModal'), {})
        initFileExplorer({
            filePickerElement: '#file_picker',
            multipleMode: false,
            pickerMode: true,
            onPicked: (url) => {
                $(container).find('input').val(url);
                $(container).find('img').prop('src', url)
                if(callbacks && callbacks.click) callbacks.click(url)
                modal.hide()
            }
        });
        modal.show()})

    formGroup.append(image);
    formGroup.append(input);
    return formGroup;
}

function createFormAudio(labelText, inputId, inputName, inputValue, index, callbacks) {
    const formGroup = $('<div>', { class: 'card ficker-audio text-center me-2' });
    const label = $('<label>', { for: `${inputId}_${index}`, text: labelText });
    formGroup.append(label);
    const image = $('<audio controls src="'+inputValue+'">', {src: inputValue, class: 'card-img'})
    const input = $('<input>', {
        type: 'hidden',
        class: 'form-control page-input',
        id: `${inputId}_${index}`,
        name: inputName,
        'data-index': index,
        value: inputValue
    });
    formGroup.on('click', (e) => { 
        let container = $(e.delegateTarget);
        let modal = new bootstrap.Modal(document.getElementById('pickerModal'), {})
        initFileExplorer({
            filePickerElement: '#file_picker',
            multipleMode: false,
            pickerMode: true,
            onPicked: (url) => {
                $(container).find('input').val(url);
                $(container).find('audio').prop('src', url).prop('type', 'audio/mpeg')
                if(callbacks && callbacks.click) callbacks.click(url)
                modal.hide()
            }
        });
        modal.show()})

    formGroup.append(image);
    formGroup.append(input);
    return formGroup;
}

