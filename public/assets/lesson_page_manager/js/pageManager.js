function renderPageList() {
    $('#pageList').empty();
    pages.forEach((page, index) => {
        const listItem = $('<li>', {
            class: 'list-group-item  d-flex justify-content-between align-items-start',
            text: page.title,
            click: () => loadPage(index)
        });
        const deleteButton = $('<button>').addClass('btn btn-danger btn-sm ms-2').html('<i class="bi bi-trash"></i>').on('click', (e) => {
            e.preventDefault()
            if(confirm(`Вы точно хотите удалить страницу?`)) deletePage(index)
        });
        listItem.append(deleteButton);
        $('#pageList').append(listItem);
    });
}

function addPage(event) {
    event.preventDefault();
    const newPage = {
        icon: "listen",
        audio: true,
        index: pages.length + 1,
        title: "New Page",
        subtitle: "",
        page_template: "none",
        form_template: "none",
        template_config: {},
        title_translation: "Новый Страница",
        page_container_class: "one-column",
        subtitle_translation: ""
    };
    pages.push(newPage);
    renderPageList();
}
function deletePage(index) {
    pages.splice(index, 1); // Удаляем страницу по индексу
    if (selectedPageIndex === index) {
        selectedPageIndex = null; // Сбрасываем выбранную страницу, если она была удалена
    } else if (selectedPageIndex > index) {
        selectedPageIndex--; // Корректируем индекс выбранной страницы, если индекс изменился
    }
    renderPageList(); // Обновляем список страниц
    $('#editor').empty(); // Очищаем редактор
}

$(document).ready(function() {
    $('#addPage').on('click', addPage);
    renderPageList();
});
