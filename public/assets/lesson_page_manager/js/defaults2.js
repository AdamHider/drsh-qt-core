const replicaItemConfig = {
    label: 'Реплика',
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
    label: 'Блок',
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
    label: 'Вариант ответа',
    class: '',
    tag: 'div',
    fields: {
        text: { label: 'Вариант ответа',  type: 'input', class: '', nolabel: true, default: '' },
    }
};
const variantItemConfig = {
    label: 'Вариант',
    class: 'card p-2 mt-2',
    tag: 'div',
    fields: {
        mode: { type: 'hidden', class: '', nolabel: true, default: 'variant' },
        type:  { type: 'hidden', class: '', nolabel: true, default: 'input' },
        index: { label: 'Номер', type: 'number',  class: '', default: 1 },
        answer: { label: 'Правильный ответ', type: 'input', class: '', default: '' },
        variants: { label: 'Варианты ответа', type: 'array', class: '', contentclass:"", nolabel: true, collapsible: true, itemConfig: variantOptionConfig }
    }
};
const matchItemConfig = {
    label: 'Сопоставление',
    class: 'card p-2 mt-2',
    tag: 'div',
    fields: {
        mode: { type: 'hidden', class: '', nolabel: true, default: 'match' },
        type:  { type: 'hidden', class: '', nolabel: true, default: 'input' },
        index: { label: 'Номер', type: 'number', class: '', default: 1 },
        answer: { label: 'Ответ', type: 'input', class: '', default: '' }
    }
};


const formTemplates = {
    none: null,
    variant: variantItemConfig,
    match: matchItemConfig
};


const config = {
    title: { label: 'Заголовок', type: 'input', class: 'col-6', default: 'Diñleyik, tekrarlayıq', onchange: () => renderPageList() },
    subtitle: { label: 'Подзаголовок', type: 'input', class: 'col-6', default: '', onchange: () => renderPageList() },
    image: {
        label: 'Изображение',
        type: 'fieldGroup',
        contentclass:"row",
        class: 'mt-2',
        tag: 'div',
        collapsible: true,
        fields: {
            image: { type: 'image', class: 'main-image', nolabel: true, default: '' },
        }
    },
    other: {
        label: 'Прочее',
        type: 'fieldGroup',
        contentclass:"row align-items-end",
        class: 'mt-2',
        tag: 'div',
        collapsible: true,
        fields: {
            audio: { label: 'Есть озвучка', type: 'checkbox', class: 'col-6', default: true },
            index: { label: 'Номер страницы', type: 'number', class: 'col-6', default: 1 },
        }
    },
    templates: {
        label: 'Содержимое',
        type: 'fieldGroup',
        contentclass:"row align-items-end",
        class: 'mt-2',
        tag: 'div',
        fields: {
            page_template: { label: 'Тип страницы', type: 'select', class: 'col-8', options: [
                {value: 'none', label: 'Не выбран'}, {value: 'dialogue', label: 'Диалог'}, {value: 'grid', label: 'Блоки'}
            ], default: 'none' },
            form_template: { label: 'Тип полей', type: 'select', class: 'col-4', options: [
                {value: 'none', label: 'Без полей'}, {value: 'variant', label: 'Варианты ответа'}, {value: 'match', label: 'Сопоставление'}
            ], default: 'none' },
        }
    },
    template_config: {
        type: 'dynamic',
        fields: {
            none: null,
            dialogue: {
                replica_list: { label: 'Реплики', type: 'array', class: 'col-8 mt-2', contentclass:"row ms-2 show", collapsible: true, itemConfig: replicaItemConfig },
                input_list: { label: 'Поля',type: 'array', class: 'col-4 mt-2', contentclass:"show", collapsible: true, itemConfig: null }
            },
            grid: {
                grid_columns_count: { label: 'Количество блоков в ряду', type: 'select', class: 'col-8 grid-columns-count mt-2', options: [
                    {value: 1, label: '1'}, {value: 2, label: '2'}, {value: 3, label: '3'}, {value: 4, label: '4'}, {value: 5, label: '5'}
                ], default: 3, onchange: (e) => $(e.delegateTarget).closest('.editor').find('.column-item').parent().removeClass('row-cols-1 row-cols-2 row-cols-3 row-cols-4 row-cols-5').addClass('row-cols-'+$(e.delegateTarget).val())  },
                column_list: { label: 'Блоки', type: 'array', class: 'col-8 mt-2', contentclass:"row ms-2 show", collapsible: true, itemConfig: gridItemConfig },
                input_list: { label: 'Поля', type: 'array', class: 'col-4 mt-2', contentclass:"show", collapsible: true, itemConfig: null }
            }
        }
    }
};