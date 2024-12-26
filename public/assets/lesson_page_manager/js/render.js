function renderPagesList(pages) {
    $('#pagesList').empty();
    pages.forEach((page, index) => {
        const listItem = $(`
            <li class="list-group-item d-flex justify-content-between align-items-center" data-index="${index}">
                ${page.title || 'New Page'}
                <button class="btn btn-danger btn-sm deletePageBtn" data-index="${index}">Delete</button>
            </li>
        `);
        $('#pagesList').append(listItem);
    });
}

function renderPageForm(page, index) {
    const form = $(`
        <form id="pageForm">
            <div class="form-group">
                <label for="page_audio_${index}">Audio</label>
                <select class="form-select page-select" id="page_audio_${index}" name="audio" data-index="${index}">
                    <option value="no" ${!page.audio ? 'selected' : ''}>Нет</option>
                    <option value="yes" ${page.audio ? 'selected' : ''}>Да</option>
                </select>
            </div>
            <div class="form-group">
                <label for="page_index_${index}">Index</label>
                <input type="number" class="form-control page-input" id="page_index_${index}" name="index" data-index="${index}" value="${page.index}">
            </div>
            <div class="form-group">
                <label for="page_title_${index}">Title</label>
                <input type="text" class="form-control page-input" id="page_title_${index}" name="title" data-index="${index}" value="${page.title}">
            </div>
            <div class="form-group">
                <label for="page_subtitle_${index}">Subtitle</label>
                <input type="text" class="form-control page-input" id="page_subtitle_${index}" name="subtitle" data-index="${index}" value="${page.subtitle}">
            </div>
            <div class="form-group">
                <label for="page_template_${index}">Page Template</label>
                <select class="form-select page-select" id="page_template_${index}" name="page_template" data-index="${index}">
                    <option value="" disabled ${!page.page_template ? 'selected' : ''}>Выбрать</option>
                    <option value="dialogue" ${page.page_template === 'dialogue' ? 'selected' : ''}>Dialogue</option>
                    <option value="chat" ${page.page_template === 'chat' ? 'selected' : ''}>Chat</option>
                    <option value="answerQuestion" ${page.page_template === 'answerQuestion' ? 'selected' : ''}>Answer Question</option>
                    <option value="grid" ${page.page_template === 'grid' ? 'selected' : ''}>Grid</option>
                </select>
            </div>
            <div class="form-group">
                <label for="form_template_${index}">Form Template</label>
                <select class="form-select page-select" id="form_template_${index}" name="form_template" data-index="${index}">
                    <option value="" disabled ${!page.form_template ? 'selected' : ''}>Выбрать</option>
                    <option value="variant" ${page.form_template === 'variant' ? 'selected' : ''}>Variant</option>
                    <option value="chat" ${page.form_template === 'chat' ? 'selected' : ''}>Chat</option>
                    <option value="match" ${page.form_template === 'match' ? 'selected' : ''}>Match</option>
                    <option value="grid" ${page.form_template === 'grid' ? 'selected' : ''}>Grid</option>
                </select>
            </div>
            <div class="form-group">
                <label for="template_config_${index}">Template Config</label>
                <input type="text" class="form-control template-config" data-index="${index}" id="template_config_${index}" name="template_config_${index}" value='${JSON.stringify(page.template_config)}'>
            </div>
        </form>
    `);
    $('#pageFormContainer').html(form);
}
