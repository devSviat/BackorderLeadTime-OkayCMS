/* Живий перегляд терміну за правилами Helpers/LeadTimeLabels.php і LeadTerm.php. */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('fn_sviat_blt');
    if (!form) {
        return;
    }
    var texts = JSON.parse(form.dataset.texts);
    var max = parseInt(form.dataset.max, 10);
    var today = form.dataset.today.split('-').map(Number);
    var defaultBox = form.querySelector('.fn_sviat_blt_default');
    var rows = Array.prototype.slice.call(form.querySelectorAll('.fn_sviat_blt_row'));
    var tbody = rows.length ? rows[0].parentNode : null;
    var emptyRow = form.querySelector('.fn_sviat_blt_empty');
    var search = form.querySelector('.fn_sviat_blt_search');
    var filter = 'all';
    var sort = 'name';

    function fmt(template, values) {
        var i = 0;
        return template.replace(/%[ds]/g, function () { return values[i++]; });
    }

    function daysWord(n) {
        var p1 = n % 10, p2 = n % 100, teen = p2 >= 11 && p2 <= 19;
        if (texts.day_2) {
            if (p1 === 1 && !teen) return texts.day_1;
            if (p1 >= 2 && p1 <= 4 && !teen) return texts.day_2;
            return texts.day_5;
        }
        return n === 1 ? texts.day_1 : texts.day_5;
    }

    /* null — обидва порожні; false — помилка; інакше {from, to}. */
    function parse(box) {
        var from = box.querySelector('.fn_sviat_blt_from').value.trim();
        var to = box.querySelector('.fn_sviat_blt_to').value.trim();
        if (from === '' && to === '') return null;
        if (!/^\d+$/.test(from) || !/^\d+$/.test(to)) return false;
        from = parseInt(from, 10);
        to = parseInt(to, 10);
        return from >= 1 && to >= from && to <= max ? {from: from, to: to} : false;
    }

    function dateLabel(term) {
        /* Дата сервера, а не браузера: інакше біля півночі перегляд розійдеться з вітриною. */
        var d = new Date(today[0], today[1] - 1, today[2] + term.to);
        return d.getDate() + ' ' + texts.months[d.getMonth()];
    }

    function termText(term) {
        return (term.from === term.to ? term.to : term.from + '–' + term.to) + ' ' + daysWord(term.to);
    }

    function sentence(term) {
        var word = daysWord(term.to);
        var shipsIn = term.from === term.to
            ? fmt(texts.ships_in_exact, [term.to, word])
            : fmt(texts.ships_in, [term.from, term.to, word]);
        var by = fmt(texts.estimated_by, [dateLabel(term)]);
        return shipsIn + ', ' + by.charAt(0).toLowerCase() + by.slice(1);
    }

    /* Червоне ставимо лише на change: під час набору «2» перед «21» рядок ще не помилковий. */
    function markError(box, isError, final) {
        if (isError && !final) return;
        box.querySelectorAll('.form-control').forEach(function (input) {
            input.classList.toggle('error', isError);
        });
    }

    function updateRow(row, def, final) {
        var own = parse(row);
        var term = own === null ? def : own;
        markError(row, own === false, final);
        row.querySelector('.fn_sviat_blt_preview').textContent = term
            ? termText(term) + ' · ' + fmt(form.dataset.by, [dateLabel(term)])
            : '—';
        row.querySelector('.fn_sviat_blt_general').hidden = !(own === null && def);
        row.dataset.own = own === null ? '' : '1';
    }

    function updateAll(final) {
        var def = parse(defaultBox);
        markError(defaultBox, def === false, final);
        form.querySelector('.fn_sviat_blt_sentence').textContent = def ? '«' + sentence(def) + '»' : '—';
        rows.forEach(function (row) {
            row.querySelector('.fn_sviat_blt_from').placeholder = def ? def.from : '';
            row.querySelector('.fn_sviat_blt_to').placeholder = def ? def.to : '';
            updateRow(row, def || null, false);
        });
        countOwn();
    }

    function countOwn() {
        form.querySelector('.fn_sviat_blt_count_own').textContent =
            rows.filter(function (row) { return row.dataset.own; }).length;
    }

    /* Рядки лише ховаються: збереження — повна заміна, тож відфільтровані теж мають піти у POST. */
    function applyFilter() {
        var query = search.value.trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (row) {
            var visible = (!query || row.querySelector('.fn_sviat_blt_name').textContent.toLowerCase().indexOf(query) !== -1)
                && (filter !== 'own' || row.dataset.own)
                && (filter !== 'backorders' || row.dataset.backorders > 0);
            row.hidden = !visible;
            shown += visible ? 1 : 0;
        });
        emptyRow.hidden = shown > 0;
    }

    function applySort() {
        var sorted = rows.slice().sort(function (a, b) {
            if (sort !== 'name') {
                var diff = (b.dataset.backorders - a.dataset.backorders) * (sort === 'desc' ? 1 : -1);
                if (diff) return diff;
            }
            return a.dataset.index - b.dataset.index;
        });
        sorted.forEach(function (row) { tbody.insertBefore(row, emptyRow); });
        form.querySelectorAll('.fn_sviat_blt_sort').forEach(function (button) {
            var th = button.parentNode;
            var active = button.dataset.sort === 'name' ? sort === 'name' : sort !== 'name';
            if (active) {
                th.setAttribute('aria-sort', sort === 'asc' || sort === 'name' ? 'ascending' : 'descending');
            } else {
                th.removeAttribute('aria-sort');
            }
        });
    }

    function onEdit(e) {
        if (e.target === search) {
            applyFilter();
            return;
        }
        var final = e.type === 'change';
        var row = e.target.closest('.fn_sviat_blt_row');
        if (row) {
            updateRow(row, parse(defaultBox) || null, final);
            countOwn();
        } else {
            updateAll(final);
        }
        if (filter === 'own' && final) applyFilter();
    }

    form.addEventListener('input', onEdit);
    form.addEventListener('change', onEdit);

    search.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    form.addEventListener('click', function (e) {
        var chip = e.target.closest('.fn_sviat_blt_filter');
        if (chip) {
            filter = chip.dataset.filter;
            form.querySelectorAll('.fn_sviat_blt_filter').forEach(function (button) {
                var on = button === chip;
                button.classList.toggle('is-active', on);
                button.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            applyFilter();
        }
        var sortButton = e.target.closest('.fn_sviat_blt_sort');
        if (sortButton) {
            sort = sortButton.dataset.sort === 'name' ? 'name' : (sort === 'desc' ? 'asc' : 'desc');
            applySort();
        }
    });

    rows.forEach(function (row) {
        row.dataset.own = parse(row) === null ? '' : '1';
    });
});
