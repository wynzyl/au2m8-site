/**
 * Tag suggestions: a native <datalist> only matches the start of the whole value, so after the
 * first comma it stops helping. Rebuild its options as "tags typed so far, " + each unused tag
 * so the browser keeps suggesting the tag currently being typed.
 */
document.querySelectorAll('input[data-tag-suggestions]').forEach((input) => {
    const suggestions = JSON.parse(input.dataset.tagSuggestions || '[]');
    const datalist = document.getElementById(input.getAttribute('list'));

    if (!datalist || suggestions.length === 0) {
        return;
    }

    const refresh = () => {
        const prefix = input.value
            .split(',')
            .slice(0, -1)
            .map((tag) => tag.trim())
            .filter((tag) => tag !== '');
        const used = prefix.map((tag) => tag.toLowerCase());

        datalist.replaceChildren(
            ...suggestions
                .filter((tag) => !used.includes(tag.toLowerCase()))
                .map((tag) => {
                    const option = document.createElement('option');
                    option.value = prefix.length === 0 ? tag : `${prefix.join(', ')}, ${tag}`;

                    return option;
                }),
        );
    };

    input.addEventListener('input', refresh);
    refresh();
});
