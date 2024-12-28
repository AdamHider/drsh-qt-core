const replicaItemConfig = {
    name: { type: 'input', class: 'form-control', default: '' },
    image: { type: 'input', class: 'form-control', default: '' },
    text: { type: 'input', class: 'form-control', default: '' },
    float: { type: 'input', class: 'form-control', default: '' },
    animation: { type: 'input', class: 'form-control', default: '' },
    audio_link: { type: 'input', class: 'form-control', default: '' }
};

const gridItemConfig = {
    text: { type: 'input', class: 'form-control', default: '' },
    image: { type: 'input', class: 'form-control', default: '' },
    animation: { type: 'input', class: 'form-control', default: '' }
};

const variantItemConfig = {
    text: { type: 'input', class: 'form-control', default: '' },
    placeholder: { type: 'input', class: 'form-control', default: '' },
    type: { type: 'select', class: 'form-select', options: ['text', 'number', 'email'], default: 'text' }
};

const matchItemConfig = {
    text: { type: 'input', class: 'form-control', default: '' },
    match: { type: 'input', class: 'form-control', default: '' }
};

const formTemplates = {
    variant: variantItemConfig,
    match: matchItemConfig
};

const config = {
    audio: { type: 'checkbox', class: 'form-check-input', default: true },
    index: { type: 'number', class: 'form-control', default: 1 },
    title: { type: 'input', class: 'form-control', default: 'Diñleyik, tekrarlayıq' },
    subtitle: { type: 'input', class: 'form-control', default: '' },
    page_template: { type: 'select', class: 'form-select', options: ['dialogue', 'grid'], default: 'dialogue' },
    form_template: { type: 'select', class: 'form-select', options: ['variant', 'match'], default: 'variant' },
    template_config: {
        type: 'dynamic',
        fields: {
            dialogue: {
                replica_list: { type: 'array', class: 'group-list', itemConfig: replicaItemConfig },
                input_list: { type: 'array', class: 'group-list', itemConfig: variantItemConfig }
            },
            grid: {
                column_list: { type: 'array', class: 'group-list', itemConfig: gridItemConfig },
                input_list: { type: 'array', class: 'group-list', itemConfig: variantItemConfig },
                grid_columns_count: { type: 'number', class: 'form-control', default: 3 }
            }
        }
    }
};