function renderPageList() {
    $('#pageList').empty();
    pages.forEach((page, index) => {
        const listItem = $('<li>', {
            class: 'list-group-item',
            text: page.title,
            click: () => loadPage(index)
        });
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
        page_template: "dialogue",
        template_config: {},
        title_translation: "Новый Страница",
        page_container_class: "one-column",
        subtitle_translation: ""
    };
    pages.push(newPage);
    renderPageList();
}

$(document).ready(function() {
    $('#addPage').on('click', addPage);
    renderPageList();
});
