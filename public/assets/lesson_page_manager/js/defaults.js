let defaultPage = {
    audio: 'no',
    index: 0,
    title: '',
    subtitle: '',
    page_template: '',
    form_template: '',
    template_config: {}
};
const defaultTemplateConfigs = {
    dialogue: {
        image: {
            source: '',
            position: '',
            animation: { type: 'fadeIn', sort_order: 1 }
        },
        replica_list: [],
        input_list: [],
    },
    answerQuestion: {
        block_list: [],
        input_list: [],
    },
    grid: {
        column_list: [],
        input_list: [],
        grid_columns_count: 3,
    }
};
const defaulFormConfigs = {
    variant: {
        mode: 'variant',
        type: 'input',
        index: 1,
        answer: '',
        variants: []
    },
    match: {
        mode: 'match',
        type: 'input',
        index: 1,
        answer: ''
    }
}
let defaultDialogueReplica =  {
    name: '',
    image: '',
    text: '',
    float: '',
    animation: { type: 'fadeInFromBottom', sort_order: 1 },
    audio_link: ''
}
let defaulGridColumn = {
    text: '',
    image: '',
    animation: { type: "fadeInFromRight", sort_order: 6 }
}
let defaultAnswerQuestionBlock = {
    text: '',
    float: '',
    image: '',
    animation: { type: 'fadeInFromLeft', sort_order: 1 },
    image_animation: { type: 'fadeInFromRight', sort_order: 1 }
}