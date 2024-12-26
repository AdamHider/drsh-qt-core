let defaultPage = {
    audio: 'no',
    index: 0,
    title: '',
    subtitle: '',
    page_template: '',
    form_template: '',
    template_config: {}
};

$('#addPageBtn').click(function(e) {
    e.preventDefault();
    pages.push({ ...defaultPage });
    renderPagesList(pages);
    updatePagesInput();
});

function updatePagesInput() {
    $('#pagesInput').val(JSON.stringify(pages));
}

$(document).on('input', '.page-input', function() {
    const index = $(this).data('index');
    const field = $(this).attr('name');
    pages[index][field] = $(this).val();
    updatePagesInput();
});

$(document).on('change', '.page-select', function() {
    const index = $(this).data('index');
    const field = $(this).attr('name');
    pages[index][field] = $(this).val();
    updatePagesInput();
});

$(document).on('input', '.template-config', function() {
    const index = $(this).data('index');
    pages[index].template_config = JSON.parse($(this).val());
    updatePagesInput();
});

$(document).on('click', '.deletePageBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const index = $(this).data('index');
    pages.splice(index, 1);
    renderPagesList(pages);
    $('#pageFormContainer').empty();
    updatePagesInput();
});

$(document).on('click', '.list-group-item', function() {
    const index = $(this).data('index');
    renderPageForm(pages[index], index);
});

// Initial render
renderPagesList(pages);