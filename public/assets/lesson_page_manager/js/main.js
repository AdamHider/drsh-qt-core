

$('#addPageBtn').click(function(e) {
    e.preventDefault();
    pages.push({ ...defaultPage });
    renderPagesList(pages);
    updatePagesInput();
});
function init(){
    initControls();
    renderPagesList(pages);
}
function updatePagesInput() {
    $('#pagesInput').val(JSON.stringify(pages));
}
function initControls(){
    $(document).off('input', '.page-input');
    $(document).on('input', '.page-input', function(e) {
        const index = $(e.target).data('index');
        const field = $(e.target).attr('name');
        pages[index][field] = $(e.target).val();
        updatePagesInput();
    });
    $(document).off('change', '.page-select');
    $(document).on('change', '.page-select', function(e) {
        const index = $(e.target).data('index');
        const field = $(e.target).attr('name');
        pages[index][field] = $(this).val();
        if (field === 'page_template' && pages[index][field]) {
            pages[index].template_config = { ...defaultTemplateConfigs[pages[index][field]] };
        }

        if (field === 'form_template' && pages[index][field]) {
            pages[index].template_config.input_list = [{ ...defaulFormConfigs[pages[index][field]] }];
        }
        renderPageForm(pages[index], index)

        updatePagesInput();
    });
    $(document).off('input', '.template-config')
    $(document).on('input', '.template-config', function(e) {
        const index = $(e.target).data('index');
        pages[index].template_config = JSON.parse($(this).val());
        updatePagesInput();
    });
}

init()