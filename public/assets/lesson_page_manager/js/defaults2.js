const fieldGroupConfig = {
    type: 'fieldGroup',
    class: 'field-group',
    tag: 'div',
    fields: {
        groupName: { type: 'input', class: '', default: '' },
        groupDescription: { type: 'input', class: '', default: '' }
    }
};

const replicaItemConfig = {
    label: 'Replica',
    class: 'chat-message mt-2 p-2 row rounded w-75',
    render: (e) => {$(e).removeClass('left right').addClass($(e).find('.float-select select').val())},
    tag: 'div',
    fields: {
        image: {
            type: 'fieldGroup',
            class: 'field-group col-3',
            tag: 'div',
            nolabel: true,
            fields: {
                image: { type: 'image', class: 'chat-avatar', nolabel: true, default: '' },
            }
        },
        name: {
            type: 'fieldGroup',
            class: 'field-group col-9',
            tag: 'div',
            nolabel: true,
            fields: {
                name: { type: 'input', class: 'chat-name', nolabel: true, default: '' },
                text: { type: 'textarea', class: ' chat-text', nolabel: true, default: '' },
                other: {
                    type: 'fieldGroup',
                    class: 'field-group',
                    contentclass: "row",
                    tag: 'div',
                    nolabel: true,
                    fields: {
                        animation: { type: 'input', class: ' chat-animation col-4', nolabel: true, default: '' },
                        audio_link: { type: 'audio', class: ' chat-audio col-4', nolabel: true, default: '' },
                        float: { type: 'select', class: 'col-4 float-select', options: ['left', 'right'], nolabel: true, default: 'left', onchange: (e) => $(e.delegateTarget).closest('.chat-message').removeClass('left right').addClass($(e.delegateTarget).val()) },
                    }
                },
            }
        },
    }
};

const gridItemConfig = {
    label: 'Column',
    class: 'col column-item',
    render: (e) => {$(e).parent().removeClass('row-cols-1 row-cols-2 row-cols-3 row-cols-4 row-cols-5').addClass('row-cols-'+$('.editor').find('.grid-columns-count select').val())},
    tag: 'div',
    fields: {
        image: { type: 'image', class: '', nolabel: true, default: '' },
        text: { type: 'textarea', class: ' ', nolabel: true, default: '' },
        animation: { type: 'input', class: ' ', nolabel: true, default: '' },
    }
};

const variantOptionConfig = {
    label: 'Variant option',
    class: '',
    tag: 'div',
    fields: {
        text: { type: 'input', class: '', nolabel: true, default: '' },
    }
};
const variantItemConfig = {
    label: 'Variant input',
    class: 'card p-2 mt-2',
    tag: 'div',
    fields: {
        mode: { type: 'hidden', class: '', nolabel: true, default: 'variant' },
        type:  { type: 'hidden', class: '', nolabel: true, default: 'input' },
        index: { type: 'number', class: '', default: 1 },
        answer: { type: 'input', class: '', default: '' },
        variants: { type: 'array', class: '', contentclass:"", nolabel: true, collapsible: true, itemConfig: variantOptionConfig }
    }
};
const matchItemConfig = {
    label: 'Match input',
    class: 'card p-2 mt-2',
    tag: 'div',
    fields: {
        mode: { type: 'hidden', class: '', nolabel: true, default: 'match' },
        type:  { type: 'hidden', class: '', nolabel: true, default: 'input' },
        index: { type: 'number', class: '', default: 1 },
        answer: { type: 'input', class: '', default: '' }
    }
};


const formTemplates = {
    none: null,
    variant: variantItemConfig,
    match: matchItemConfig
};


const config = {
    
    title: { type: 'input', class: '', default: 'Diñleyik, tekrarlayıq' },
    subtitle: { type: 'input', class: '', default: '' },
    other: {
        label: 'Прочее',
        type: 'fieldGroup',
        contentclass:"row align-items-end",
        class: '',
        tag: 'div',
        collapsible: true,
        fields: {
            audio: { type: 'checkbox', class: 'col-6', default: true },
            index: { type: 'number', class: 'col-6', default: 1 },
        }
    },
    templates: {
        label: 'Содержимое',
        type: 'fieldGroup',
        contentclass:"row align-items-end",
        class: '',
        tag: 'div',
        fields: {
            page_template: { type: 'select', class: 'col-8', options: ['dialogue', 'grid'], default: 'dialogue' },
            form_template: { type: 'select', class: 'col-4', options: ['none', 'variant', 'match'], default: 'none' },
        }
    },
    template_config: {
        type: 'dynamic',
        fields: {
            dialogue: {
                replica_list: { label: 'Replicas', type: 'array', class: 'col-8', contentclass:"row ms-2", collapsible: true, itemConfig: replicaItemConfig },
                input_list: { label: 'Fields',type: 'array', class: 'col-4', contentclass:"", collapsible: true, itemConfig: null }
            },
            grid: {
                grid_columns_count: { type: 'select', class: 'col-8 grid-columns-count', options: [1, 2, 3, 4, 5], default: 3, onchange: (e) => $(e.delegateTarget).closest('.editor').find('.column-item').parent().removeClass('row-cols-1 row-cols-2 row-cols-3 row-cols-4 row-cols-5').addClass('row-cols-'+$(e.delegateTarget).val())  },
                column_list: { label: 'Items', type: 'array', class: 'col-8', contentclass:"row ms-2", collapsible: true, itemConfig: gridItemConfig },
                input_list: { label: 'Fields', type: 'array', class: 'col-4', contentclass:"", collapsible: true, itemConfig: null }
            }
        }
    }
};