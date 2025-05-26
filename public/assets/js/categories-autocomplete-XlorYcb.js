document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('categories-input');
    const list = document.getElementById('categories-list');
    const hiddenInput = document.getElementById('categories-hidden');
    if (!input || !list || !hiddenInput) return; // safety check

    let selectedCategories = hiddenInput.value ? hiddenInput.value.split(',').map(id => parseInt(id)) : [];

    function renderSelected() {
        const selectedTitles = availableCategories
            .filter(cat => selectedCategories.includes(cat.id))
            .map(cat => cat.title);
        input.value = selectedTitles.join(', ');
        hiddenInput.value = selectedCategories.join(',');
    }

    function filterCategories(query) {
        query = query.toLowerCase();
        return availableCategories.filter(cat => cat.title.toLowerCase().includes(query) && !selectedCategories.includes(cat.id));
    }

    input.addEventListener('input', function() {
        const query = input.value;
        if(!query) {
            list.innerHTML = '';
            return;
        }
        const filtered = filterCategories(query);
        if(filtered.length === 0) {
            list.innerHTML = '<div class="list-group-item disabled">Aucune catégorie trouvée</div>';
            return;
        }
        list.innerHTML = filtered.map(cat => `
            <button type="button" class="list-group-item list-group-item-action">${cat.title}</button>
        `).join('');
    });

    list.addEventListener('click', function(e) {
        if(e.target.tagName === 'BUTTON') {
            const selectedTitle = e.target.textContent;
            const cat = availableCategories.find(c => c.title === selectedTitle);
            if(cat && !selectedCategories.includes(cat.id)) {
                selectedCategories.push(cat.id);
                renderSelected();
            }
            list.innerHTML = '';
        }
    });

    document.addEventListener('click', function(e) {
        if(!input.contains(e.target) && !list.contains(e.target)) {
            list.innerHTML = '';
        }
    });

    renderSelected();
});
