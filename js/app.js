const themeStorageKey = 'tecnologiasweb-theme';
const sidebarStorageKey = 'tecnologiasweb-sidebar-collapsed';
const sidebarSectionsStorageKey = 'tecnologiasweb-sidebar-sections-v1';
const rootElement = document.documentElement;
const collator = new Intl.Collator('es', { numeric: true, sensitivity: 'base' });

const applyTheme = (theme) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    rootElement.setAttribute('data-bs-theme', normalizedTheme);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const isDark = normalizedTheme === 'dark';
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute('aria-label', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
        button.setAttribute('title', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
        const icon = button.querySelector('[data-theme-icon]');
        icon?.classList.toggle('bi-moon-stars-fill', !isDark);
        icon?.classList.toggle('bi-sun-fill', isDark);
    });
};

const getSortType = (label) => {
    const normalizedLabel = label.toLocaleLowerCase('es');
    if (normalizedLabel.includes('fecha')) {
        return 'date';
    }
    if (
        normalizedLabel.includes('hora')
        || normalizedLabel.includes('horario')
        || normalizedLabel.includes('inicio')
        || normalizedLabel.includes('fin')
    ) {
        return 'time';
    }
    if (
        normalizedLabel.includes('id')
        || normalizedLabel.includes('semestre')
        || normalizedLabel.includes('tutores')
        || normalizedLabel.includes('calificacion')
    ) {
        return 'number';
    }
    return 'text';
};

const getSortValue = (text, type) => {
    const value = text.replace(/\s+/g, ' ').trim();
    if (value === '') {
        return '';
    }
    if (type === 'date') {
        return value;
    }
    if (type === 'time') {
        const time = value.match(/\b(\d{1,2}):(\d{2})\b/);
        return time ? (Number(time[1]) * 60) + Number(time[2]) : Number.POSITIVE_INFINITY;
    }
    if (type === 'number') {
        const number = value.match(/-?\d+(?:[.,]\d+)?/);
        return number ? Number(number[0].replace(',', '.')) : Number.POSITIVE_INFINITY;
    }
    return value.toLocaleLowerCase('es');
};

const compareSortValues = (first, second, type, direction) => {
    const firstEmpty = first === '';
    const secondEmpty = second === '';
    if (firstEmpty || secondEmpty) {
        if (firstEmpty && secondEmpty) {
            return 0;
        }
        return firstEmpty ? 1 : -1;
    }

    let comparison;
    if (type === 'number' || type === 'time') {
        comparison = first - second;
    } else if (type === 'date') {
        comparison = first.localeCompare(second);
    } else {
        comparison = collator.compare(first, second);
    }

    return direction === 'asc' ? comparison : -comparison;
};

const setupTableSorting = () => {
    document.querySelectorAll('table').forEach((table) => {
        const headers = [...table.querySelectorAll('thead th')];
        const sortableHeaders = headers.filter((header) => {
            const label = header.textContent.trim();
            return label !== '' && label.toLocaleLowerCase('es') !== 'acciones' && header.dataset.sortable !== 'false';
        });
        const rows = () => [...table.querySelectorAll('tbody tr')].filter((row) => row.cells.length === headers.length);

        sortableHeaders.forEach((header) => {
            const label = header.textContent.trim();
            const sortType = getSortType(label);
            const button = document.createElement('button');
            const icon = document.createElement('i');
            const text = document.createElement('span');

            button.className = 'table-sort';
            button.type = 'button';
            button.setAttribute('aria-label', `Ordenar por ${label} ascendente`);
            button.dataset.sortDirection = 'none';
            icon.className = 'bi bi-arrow-down-up';
            icon.setAttribute('aria-hidden', 'true');
            text.textContent = label;
            button.append(text, icon);
            header.textContent = '';
            header.append(button);
            header.setAttribute('aria-sort', 'none');

            button.addEventListener('click', () => {
                const direction = button.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                headers.forEach((currentHeader) => currentHeader.setAttribute('aria-sort', 'none'));
                sortableHeaders.forEach((currentHeader) => {
                    const currentButton = currentHeader.querySelector('.table-sort');
                    if (!currentButton || currentButton === button) {
                        return;
                    }
                    currentButton.dataset.sortDirection = 'none';
                    currentButton.setAttribute('aria-label', `Ordenar por ${currentButton.querySelector('span').textContent} ascendente`);
                    const currentIcon = currentButton.querySelector('i');
                    currentIcon.className = 'bi bi-arrow-down-up';
                });

                button.dataset.sortDirection = direction;
                button.setAttribute('aria-label', `Ordenar por ${label} ${direction === 'asc' ? 'descendente' : 'ascendente'}`);
                icon.className = direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
                header.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');

                rows()
                    .sort((firstRow, secondRow) => {
                        const first = getSortValue(firstRow.cells[header.cellIndex]?.textContent ?? '', sortType);
                        const second = getSortValue(secondRow.cells[header.cellIndex]?.textContent ?? '', sortType);
                        return compareSortValues(first, second, sortType, direction);
                    })
                    .forEach((row) => table.tBodies[0].append(row));
                table._tablePagination?.refresh();
            });
        });
    });
};

const setupTablePagination = () => {
    document.querySelectorAll('table').forEach((table) => {
        const headers = [...table.querySelectorAll('thead th')];
        const body = table.tBodies[0];
        if (!body || headers.length === 0) {
            return;
        }

        const wrapper = table.closest('.table-wrapper');
        const pagination = document.createElement('div');
        const summary = document.createElement('span');
        const sizeLabel = document.createElement('label');
        const sizeSelect = document.createElement('select');
        const controls = document.createElement('div');
        const previous = document.createElement('button');
        const pageNumber = document.createElement('span');
        const next = document.createElement('button');
        const count = table.closest('main')?.querySelector('[data-table-count]');
        const emptySearch = table.closest('main')?.querySelector('[data-search-empty]');
        const state = {
            page: 1,
            defaultPageSize: window.matchMedia('(max-width: 760px)').matches ? 6 : 10,
            pageSize: window.matchMedia('(max-width: 760px)').matches ? 6 : 10,
            query: '',
            customPageSize: false,
        };

        pagination.className = 'table-pagination';
        summary.className = 'table-page-summary';
        sizeLabel.className = 'table-page-size';
        sizeLabel.textContent = 'Mostrar';
        sizeSelect.setAttribute('aria-label', 'Cantidad de registros por pagina');
        [6, 10, 25, 50].forEach((size) => {
            const option = document.createElement('option');
            option.value = String(size);
            option.textContent = String(size);
            sizeSelect.append(option);
        });
        const allOption = document.createElement('option');
        allOption.value = 'all';
        allOption.textContent = 'Todos';
        sizeSelect.append(allOption);
        sizeSelect.value = String(state.pageSize);
        sizeLabel.append(sizeSelect);

        controls.className = 'table-page-controls';
        previous.className = 'table-page-button';
        previous.type = 'button';
        previous.innerHTML = '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Pagina anterior</span>';
        next.className = 'table-page-button';
        next.type = 'button';
        next.innerHTML = '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Pagina siguiente</span>';
        pageNumber.className = 'table-page-number';
        controls.append(previous, pageNumber, next);
        pagination.append(summary, sizeLabel, controls);
        wrapper?.append(pagination);

        const dataRows = () => [...body.querySelectorAll('tr')].filter((row) => row.cells.length === headers.length);
        const refresh = () => {
            const rows = dataRows();
            const filteredRows = rows.filter((row) => row.textContent.toLocaleLowerCase('es').includes(state.query));
            const rowNumberIndex = headers.findIndex((header) => header.dataset.rowNumber === 'true');
            if (rowNumberIndex >= 0) {
                filteredRows.forEach((row, index) => {
                    row.cells[rowNumberIndex].textContent = String(index + 1);
                });
            }
            const totalPages = state.pageSize === 'all' ? 1 : Math.max(1, Math.ceil(filteredRows.length / state.pageSize));
            state.page = Math.min(state.page, totalPages);
            const firstIndex = state.pageSize === 'all' ? 0 : (state.page - 1) * state.pageSize;
            const lastIndex = state.pageSize === 'all' ? filteredRows.length : firstIndex + state.pageSize;
            const visibleRows = new Set(filteredRows.slice(firstIndex, lastIndex));

            rows.forEach((row) => {
                row.hidden = !visibleRows.has(row);
            });
            if (emptySearch) {
                emptySearch.hidden = state.query === '' || filteredRows.length !== 0 || rows.length === 0;
            }
            if (count) {
                count.textContent = `${filteredRows.length} resultado${filteredRows.length === 1 ? '' : 's'}`;
            }

            const visibleStart = filteredRows.length === 0 ? 0 : firstIndex + 1;
            const visibleEnd = Math.min(lastIndex, filteredRows.length);
            summary.textContent = filteredRows.length === 0
                ? 'Sin registros'
                : `Mostrando ${visibleStart}-${visibleEnd} de ${filteredRows.length}`;
            pageNumber.textContent = `${state.page} / ${totalPages}`;
            previous.disabled = state.page <= 1;
            next.disabled = state.page >= totalPages;
            pagination.hidden = rows.length <= state.defaultPageSize && !state.customPageSize;
        };

        state.refresh = refresh;
        state.setQuery = (query) => {
            state.query = query.trim().toLocaleLowerCase('es');
            state.page = 1;
            refresh();
        };
        table._tablePagination = state;

        sizeSelect.addEventListener('change', () => {
            state.pageSize = sizeSelect.value === 'all' ? 'all' : Number(sizeSelect.value);
            state.customPageSize = true;
            state.page = 1;
            refresh();
        });
        previous.addEventListener('click', () => {
            if (state.page > 1) {
                state.page -= 1;
                refresh();
            }
        });
        next.addEventListener('click', () => {
            const rows = dataRows().filter((row) => row.textContent.toLocaleLowerCase('es').includes(state.query));
            const totalPages = state.pageSize === 'all' ? 1 : Math.max(1, Math.ceil(rows.length / state.pageSize));
            if (state.page < totalPages) {
                state.page += 1;
                refresh();
            }
        });
        refresh();
    });
};

const setupOfferPeriodType = () => {
    document.querySelectorAll('[data-offer-form]').forEach((form) => {
        const period = form.querySelector('[data-offer-period]');
        const type = form.querySelector('[data-offer-type]');
        if (!period || !type) {
            return;
        }

        const refresh = () => {
            const typeId = period.selectedOptions[0]?.dataset.periodTypeId ?? '';
            type.value = typeId;
            if (type.selectedIndex === -1) {
                type.value = '';
            }
        };

        period.addEventListener('change', refresh);
        refresh();
    });
};

const setupOfferCalendar = () => {
    document.querySelectorAll('[data-offer-calendar]').forEach((calendar) => {
        const form = calendar.closest('[data-offer-form]');
        const period = form?.querySelector('[data-offer-period]');
        const frequency = form?.querySelector('[data-offer-frequency]');
        const content = calendar.querySelector('[data-offer-calendar-content]');
        const empty = calendar.querySelector('[data-offer-calendar-empty]');
        const weekly = calendar.querySelector('[data-offer-calendar-weekly]');
        const weeksContainer = calendar.querySelector('[data-offer-calendar-weeks]');
        const grid = calendar.querySelector('[data-offer-calendar-grid]');
        const summary = calendar.querySelector('[data-offer-calendar-summary]');
        const detail = calendar.querySelector('[data-offer-calendar-detail]');
        if (!period || !frequency || !content || !grid || !weeksContainer) return;

        let selected = new Set(JSON.parse(calendar.dataset.selectedDates || '[]'));
        const toDate = (value) => value ? new Date(`${value}T00:00:00`) : null;
        const toIso = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        const range = () => {
            const option = period.selectedOptions[0];
            const start = toDate(option?.dataset.periodStart);
            const end = toDate(option?.dataset.periodEnd);
            if (!start || !end || start > end) return [];
            const dates = [];
            const cursor = new Date(start);
            while (cursor <= end) {
                dates.push(new Date(cursor));
                cursor.setDate(cursor.getDate() + 1);
            }
            return dates;
        };
        const weekNumber = (date) => {
            const dates = range();
            if (!dates.length) return 0;
            const firstMonday = new Date(dates[0]);
            firstMonday.setDate(firstMonday.getDate() - ((firstMonday.getDay() + 6) % 7));
            return Math.floor((date - firstMonday) / 604800000) + 1;
        };
        const updateSummary = () => {
            if (summary) summary.textContent = `${selected.size} fecha${selected.size === 1 ? '' : 's'} seleccionada${selected.size === 1 ? '' : 's'}`;
            if (detail) detail.textContent = selected.size ? 'Estas fechas pertenecen exclusivamente a esta oferta.' : 'Seleccione al menos una fecha antes de publicar la oferta.';
            calendar.dataset.selectedDates = JSON.stringify([...selected].sort());
            calendar.dispatchEvent(new Event('offer-calendar-change', { bubbles: true }));
        };
        const renderGrid = () => {
            const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
            const weekdayLabels = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
            const dates = range();
            grid.innerHTML = '';
            if (!dates.length) {
                updateSummary();
                return;
            }

            const start = dates[0];
            const end = dates[dates.length - 1];
            const periodStart = toIso(start);
            const periodEnd = toIso(end);
            const cursor = new Date(start.getFullYear(), start.getMonth(), 1);
            const lastMonth = new Date(end.getFullYear(), end.getMonth(), 1);

            while (cursor <= lastMonth) {
                const year = cursor.getFullYear();
                const month = cursor.getMonth();
                const monthCard = document.createElement('section');
                const heading = document.createElement('h3');
                const weekdays = document.createElement('div');
                const monthGrid = document.createElement('div');
                monthCard.className = 'offer-calendar-month';
                heading.className = 'offer-calendar-month-title';
                heading.textContent = cursor.toLocaleDateString('es', { month: 'long', year: 'numeric' });
                weekdays.className = 'offer-calendar-month-weekdays';
                monthGrid.className = 'offer-calendar-month-grid';

                weekdayLabels.forEach((weekday, index) => {
                    const header = document.createElement('span');
                    header.className = `offer-calendar-weekday${index === 0 ? ' is-weekend' : ''}`;
                    header.textContent = weekday;
                    weekdays.append(header);
                });
                monthCard.append(heading, weekdays, monthGrid);

                const firstWeekday = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                for (let blank = 0; blank < firstWeekday; blank++) {
                    const emptyCell = document.createElement('span');
                    emptyCell.className = 'offer-calendar-day is-empty';
                    emptyCell.setAttribute('aria-hidden', 'true');
                    monthGrid.append(emptyCell);
                }

                for (let dayNumber = 1; dayNumber <= daysInMonth; dayNumber++) {
                    const date = new Date(year, month, dayNumber);
                    const iso = toIso(date);
                    const inPeriod = iso >= periodStart && iso <= periodEnd;
                    const isSunday = date.getDay() === 0;
                    if (!inPeriod) {
                        const outside = document.createElement('span');
                        outside.className = 'offer-calendar-day is-outside-period';
                        outside.textContent = String(dayNumber);
                        outside.setAttribute('aria-hidden', 'true');
                        monthGrid.append(outside);
                        continue;
                    }

                    const label = document.createElement('label');
                    const number = document.createElement('strong');
                    const input = document.createElement('input');
                    label.className = `offer-calendar-date${isSunday ? ' is-disabled' : ''}`;
                    input.type = 'checkbox';
                    input.name = 'fechas[]';
                    input.value = iso;
                    input.disabled = isSunday;
                    input.checked = !isSunday && selected.has(iso);
                    input.setAttribute('aria-label', `${dayNames[date.getDay()]} ${dayNumber} de ${date.toLocaleDateString('es', { month: 'long' })}`);
                    input.addEventListener('change', () => {
                        if (input.checked) selected.add(iso); else selected.delete(iso);
                        updateSummary();
                    });
                    number.textContent = String(dayNumber);
                    label.append(number, input);
                    monthGrid.append(label);
                }

                while (monthGrid.children.length % 7 !== 0) {
                    const emptyCell = document.createElement('span');
                    emptyCell.className = 'offer-calendar-day is-empty';
                    emptyCell.setAttribute('aria-hidden', 'true');
                    monthGrid.append(emptyCell);
                }

                grid.append(monthCard);
                cursor.setMonth(cursor.getMonth() + 1);
            }
            updateSummary();
        };
        const generateWeekly = () => {
            const activeWeeks = new Set([...weeksContainer.querySelectorAll('[data-offer-calendar-week]:checked')].map((input) => Number(input.value)));
            const activeDays = new Set([...calendar.querySelectorAll('[data-offer-calendar-weekday]:checked')].map((input) => Number(input.value)));
            selected = new Set(range().filter((date) => {
                if (date.getDay() === 0) return false;
                return activeWeeks.has(weekNumber(date)) && activeDays.has(date.getDay());
            }).map(toIso));
            renderGrid();
        };
        const renderWeeks = () => {
            const weeks = [...new Set(range().map(weekNumber))];
            weeksContainer.innerHTML = '';
            const allLabel = document.createElement('label');
            const all = document.createElement('input');
            allLabel.className = 'offer-calendar-option offer-calendar-option-all';
            all.type = 'checkbox';
            all.checked = true;
            all.dataset.offerCalendarWeekAll = '';
            allLabel.append(all, document.createTextNode(' Todas'));
            weeksContainer.append(allLabel);
            weeks.forEach((week) => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                label.className = 'offer-calendar-option';
                input.type = 'checkbox';
                input.value = String(week);
                input.checked = true;
                input.dataset.offerCalendarWeek = '';
                input.addEventListener('change', () => {
                    all.checked = [...weeksContainer.querySelectorAll('[data-offer-calendar-week]')].every((item) => item.checked);
                    generateWeekly();
                });
                label.append(input, document.createTextNode(` Semana ${week}`));
                weeksContainer.append(label);
            });
            all.addEventListener('change', () => {
                weeksContainer.querySelectorAll('[data-offer-calendar-week]').forEach((input) => { input.checked = all.checked; });
                generateWeekly();
            });
        };
        const refresh = (reset = false) => {
            const dates = range();
            content.hidden = dates.length === 0;
            if (empty) empty.hidden = dates.length > 0;
            if (!dates.length) {
                selected.clear();
                updateSummary();
                return;
            }
            const validDates = new Set(dates.map(toIso));
            selected = new Set([...selected].filter((date) => validDates.has(date) && toDate(date).getDay() !== 0));
            if (reset) selected.clear();
            weekly.hidden = frequency.value !== 'semanal';
            renderWeeks();
            if (frequency.value === 'diaria' && !selected.size) {
                selected = new Set([...validDates].filter((iso) => toDate(iso).getDay() !== 0));
            } else if (frequency.value === 'semanal' && !selected.size) {
                calendar.querySelectorAll('[data-offer-calendar-weekday]').forEach((input) => { input.checked = Number(input.value) <= 5; });
                generateWeekly();
                return;
            }
            renderGrid();
        };

        const applySelection = (kind) => {
            if (!range().length) return;
            if (frequency.value === 'semanal') {
                calendar.querySelectorAll('[data-offer-calendar-weekday]').forEach((input) => {
                    const value = Number(input.value);
                    if (kind === 'limpiar') {
                        input.checked = false;
                    } else if (kind === 'todos') {
                        input.checked = value <= 6;
                    } else {
                        if (value <= 5) input.checked = true;
                        if (value === 7) input.checked = false;
                    }
                });
                if (kind !== 'limpiar') {
                    weeksContainer.querySelectorAll('[data-offer-calendar-week]').forEach((input) => { input.checked = true; });
                    const master = weeksContainer.querySelector('[data-offer-calendar-week-all]');
                    if (master) master.checked = true;
                }
                generateWeekly();
                return;
            }
            const validDates = new Set(range().map(toIso));
            if (kind === 'limpiar') {
                selected = new Set();
            } else {
                selected = new Set([...validDates].filter((iso) => {
                    const weekday = toDate(iso).getDay();
                    if (weekday === 0) return false;
                    return kind === 'todos' ? weekday <= 6 : weekday <= 5;
                }));
            }
            renderGrid();
        };

        calendar.querySelectorAll('[data-offer-calendar-weekday]').forEach((input) => input.addEventListener('change', generateWeekly));
        calendar.querySelectorAll('[data-offer-calendar-select]').forEach((button) => {
            button.addEventListener('click', () => applySelection(button.dataset.offerCalendarSelect || ''));
        });
        period.addEventListener('change', () => refresh(true));
        frequency.addEventListener('change', () => refresh(true));
        refresh();
    });
};

const setupOfferCareerFiltering = () => {
    document.querySelectorAll('[data-offer-form]').forEach((form) => {
        const career = form.querySelector('[data-offer-career]');
        const subject = form.querySelector('[data-offer-subject]');
        if (!career || !subject) {
            return;
        }

        const refresh = () => {
            const careerId = career.value;
            Array.from(subject.options).forEach((option) => {
                const matches = !option.value || (careerId && option.dataset.offerCareerId === careerId);
                option.disabled = !matches;
                option.hidden = !matches;
            });
            subject.disabled = !careerId;
            if (!careerId || subject.selectedOptions[0]?.disabled) {
                subject.value = '';
            }
        };

        career.addEventListener('change', refresh);
        refresh();
    });
};

const setupOfferRoomAvailability = () => {
    document.querySelectorAll('[data-offer-form]').forEach((form) => {
        const period = form.querySelector('[data-offer-period]');
        const url = form.dataset.roomAvailabilityUrl;
        if (!period || !url) {
            return;
        }

        const offerTurno = form.querySelector('[data-offer-turno]');
        const weeklyRoom = form.querySelector('[data-offer-weekly-room]');
        const weeklyStatus = form.querySelector('[data-offer-weekly-room-status]');
        if (offerTurno && weeklyRoom) {
            const weeklyBaseOptions = [...weeklyRoom.options].map((option) => ({ value: option.value, label: option.textContent }));
            let weeklyRequestNumber = 0;
            const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
            const selectedWeekdays = () => {
                const calendar = form.querySelector('[data-offer-calendar]');
                const dates = JSON.parse(calendar?.dataset.selectedDates || '[]');
                return [...new Set(dates.map((iso) => dayNames[new Date(`${iso}T00:00:00`).getDay()]))].filter((day) => day !== 'Domingo');
            };
            const refreshWeekly = async () => {
                const periodId = period.value;
                const turnoId = offerTurno.value;
                const selectedRoom = weeklyRoom.value;
                const requestId = ++weeklyRequestNumber;
                const restore = () => weeklyBaseOptions.forEach((baseOption) => {
                    const option = [...weeklyRoom.options].find((current) => current.value === baseOption.value);
                    if (option) {
                        option.disabled = false;
                        option.textContent = baseOption.label;
                        option.title = '';
                    }
                });
                const weekdays = selectedWeekdays();
                if (!periodId || !turnoId) {
                    restore();
                    if (weeklyStatus) weeklyStatus.textContent = 'Seleccione periodo y turno.';
                    return;
                }
                if (!weekdays.length) {
                    restore();
                    if (weeklyStatus) weeklyStatus.textContent = 'Seleccione fechas en el calendario para verificar el aula.';
                    return;
                }
                if (weeklyStatus) weeklyStatus.textContent = 'Consultando aulas disponibles para las fechas seleccionadas...';
                try {
                    const responses = await Promise.all(weekdays.map(async (day) => {
                        const query = new URLSearchParams({ periodo: periodId, dia: day, id_turno: turnoId });
                        if (form.dataset.offerId) query.set('excluir', form.dataset.offerId);
                        const response = await fetch(`${url}?${query.toString()}`, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('availability');
                        return { day, rooms: await response.json() };
                    }));
                    if (requestId !== weeklyRequestNumber) return;
                    const blocked = new Map();
                    responses.forEach(({ day, rooms }) => rooms.forEach((room) => {
                        if (room.bloqueada && !blocked.has(String(room.id_aula))) {
                            blocked.set(String(room.id_aula), `${day}: ${room.materia_bloqueante}`);
                        }
                    }));
                    let availableCount = 0;
                    weeklyBaseOptions.forEach((baseOption) => {
                        if (!baseOption.value) return;
                        const option = [...weeklyRoom.options].find((current) => current.value === baseOption.value);
                        if (!option) return;
                        const reason = blocked.get(baseOption.value);
                        option.disabled = Boolean(reason);
                        option.textContent = reason ? `${baseOption.label} (Ocupada: ${reason})` : baseOption.label;
                        option.title = reason ? `Ocupada el ${reason}` : 'Aula disponible en las fechas seleccionadas';
                        if (!reason) availableCount++;
                    });
                    if (weeklyRoom.value === selectedRoom && weeklyRoom.selectedOptions[0]?.disabled) {
                        weeklyRoom.value = '';
                        if (weeklyStatus) weeklyStatus.textContent = 'El aula seleccionada esta ocupada en uno de los dias.';
                    } else if (weeklyStatus) {
                        weeklyStatus.textContent = `${availableCount} aula${availableCount === 1 ? '' : 's'} disponible${availableCount === 1 ? '' : 's'} en las fechas seleccionadas.`;
                    }
                } catch (error) {
                    if (requestId !== weeklyRequestNumber) return;
                    restore();
                    if (weeklyStatus) weeklyStatus.textContent = 'No se pudo consultar la disponibilidad. La validacion se hara al guardar.';
                }
            };
            period.addEventListener('change', refreshWeekly);
            offerTurno.addEventListener('change', refreshWeekly);
            form.addEventListener('offer-calendar-change', refreshWeekly);
            refreshWeekly();
        }


    });
};

const setupSidebarSections = () => {
    const nav = document.querySelector('.sidebar-nav');
    if (!nav) return;

    let savedSections = {};
    try {
        savedSections = JSON.parse(localStorage.getItem(sidebarSectionsStorageKey) || '{}');
    } catch (error) {
        savedSections = {};
    }

    const saveSections = () => {
        try {
            localStorage.setItem(sidebarSectionsStorageKey, JSON.stringify(savedSections));
        } catch (error) {
            // The sidebar still works for this page when storage is unavailable.
        }
    };

    [...nav.children].filter((child) => child.classList.contains('nav-label')).forEach((label, index) => {
        const title = label.textContent.trim();
        const key = title.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const section = document.createElement('section');
        const toggle = document.createElement('button');
        const labelText = document.createElement('span');
        const icon = document.createElement('i');
        const content = document.createElement('div');
        const contentId = `sidebar-nav-section-${index}`;

        section.className = 'nav-section';
        section.dataset.navSection = key;
        toggle.className = 'nav-label nav-section-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-controls', contentId);
        labelText.textContent = title;
        icon.className = 'bi bi-chevron-down nav-section-chevron';
        icon.setAttribute('aria-hidden', 'true');
        toggle.append(labelText, icon);

        content.className = 'nav-section-content';
        content.id = contentId;
        let child = label.nextElementSibling;
        while (child && !child.classList.contains('nav-label')) {
            const next = child.nextElementSibling;
            content.append(child);
            child = next;
        }

        if (!content.childElementCount) {
            label.remove();
            return;
        }

        const hasSavedState = Object.prototype.hasOwnProperty.call(savedSections, key);
        const savedExpanded = hasSavedState ? Boolean(savedSections[key]) : true;
        const expanded = savedExpanded || Boolean(content.querySelector('.nav-link.is-active'));
        section.classList.toggle('is-collapsed', !expanded);
        toggle.setAttribute('aria-expanded', String(expanded));
        content.hidden = !expanded;

        toggle.addEventListener('click', () => {
            const willExpand = content.hidden;
            content.hidden = !willExpand;
            section.classList.toggle('is-collapsed', !willExpand);
            toggle.setAttribute('aria-expanded', String(willExpand));
            savedSections[key] = willExpand;
            saveSections();
        });

        section.append(toggle, content);
        nav.insertBefore(section, label);
        label.remove();
    });
};

const setupAccountProfileFields = () => {
    document.querySelectorAll('form').forEach((form) => {
        const roleSelect = form.querySelector('[data-account-role]');
        const profileSections = [...form.querySelectorAll('[data-account-profile-section]')];
        if (!roleSelect || !profileSections.length) return;

        const refresh = () => {
            const roleName = roleSelect.selectedOptions[0]?.dataset.roleName ?? '';
            profileSections.forEach((section) => {
                const visible = section.dataset.accountProfileSection === roleName;
                section.hidden = !visible;
                section.querySelectorAll('[data-account-profile-field]').forEach((field) => {
                    field.disabled = !visible;
                    field.required = visible && field.dataset.accountProfileRequired === 'true';
                });
            });
        };

        roleSelect.addEventListener('change', refresh);
        refresh();
    });
};

const setupTutorWithdrawalDialog = () => {
    const dialog = document.querySelector('[data-tutor-withdrawal-dialog]');
    if (!dialog) return;

    const idField = dialog.querySelector('[data-tutor-withdrawal-id]');
    const subject = dialog.querySelector('[data-tutor-withdrawal-subject]');
    const period = dialog.querySelector('[data-tutor-withdrawal-period]');
    const turno = dialog.querySelector('[data-tutor-withdrawal-turn]');
    const reason = dialog.querySelector('textarea[name="motivo"]');

    document.querySelectorAll('[data-tutor-withdrawal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = dialog.querySelector('form');
            form?.reset();
            if (idField) idField.value = button.dataset.idOfertaTutor ?? '';
            if (subject) subject.textContent = button.dataset.materia ?? '';
            if (period) period.textContent = button.dataset.periodo ?? '';
            if (turno) turno.textContent = button.dataset.turno ?? '';
            dialog.showModal();
            reason?.focus();
        });
    });

    dialog.querySelectorAll('[data-tutor-withdrawal-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.dataset.requestMethod === 'POST' && window.history.replaceState) {
        window.history.replaceState(null, document.title, window.location.href);
    }

    const sidebar = document.querySelector('[data-sidebar]') || document.getElementById('sidebar');
    const appShell = document.querySelector('[data-app-shell]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const collapseToggle = document.querySelector('[data-sidebar-collapse]');
    const backdrop = document.querySelector('[data-sidebar-close]');
    const currentTheme = rootElement.getAttribute('data-bs-theme') || 'light';

    setupSidebarSections();

    applyTheme(currentTheme);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = rootElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
            localStorage.setItem(themeStorageKey, nextTheme);
        });
    });

    const setSidebarCollapsed = (collapsed) => {
        if (!appShell) {
            return;
        }

        const isDesktop = window.matchMedia('(min-width: 761px)').matches;
        const isCollapsed = isDesktop && collapsed;
        appShell.classList.toggle('is-sidebar-collapsed', isCollapsed);
        collapseToggle?.setAttribute('aria-expanded', String(!isCollapsed));
        collapseToggle?.setAttribute('aria-label', isCollapsed ? 'Expandir navegacion' : 'Minimizar navegacion');
        collapseToggle?.setAttribute('title', isCollapsed ? 'Expandir navegacion' : 'Minimizar navegacion');

        document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
            const label = link.querySelector(':scope > span:not(.nav-icon)')?.textContent.trim();
            if (isCollapsed && label) {
                link.setAttribute('title', label);
            } else {
                link.removeAttribute('title');
            }
        });
    };

    const storedSidebarState = localStorage.getItem(sidebarStorageKey) === 'true';
    setSidebarCollapsed(storedSidebarState);
    collapseToggle?.addEventListener('click', () => {
        const willCollapse = !appShell?.classList.contains('is-sidebar-collapsed');
        localStorage.setItem(sidebarStorageKey, String(willCollapse));
        setSidebarCollapsed(willCollapse);
    });
    window.addEventListener('resize', () => {
        setSidebarCollapsed(localStorage.getItem(sidebarStorageKey) === 'true');
    });

    const closeSidebar = () => {
        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        toggle?.setAttribute('aria-expanded', 'false');
    };

    toggle?.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');
        backdrop?.classList.toggle('is-visible', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    backdrop?.addEventListener('click', closeSidebar);
    document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
        link.addEventListener('click', closeSidebar);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    setupTableSorting();
    setupTablePagination();

    document.querySelectorAll('[data-table-search]').forEach((search) => {
        const table = search.closest('main')?.querySelector('table');
        const pagination = table?._tablePagination;
        if (!pagination) {
            return;
        }

        let searchTimer;
        search.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => pagination.setQuery(search.value), 120);
        });
        if (search.value.trim() !== '') {
            pagination.setQuery(search.value);
        }
    });

    document.querySelectorAll('[data-account-role-filter]').forEach((form) => {
        const search = form.closest('main')?.querySelector('[data-table-search]');
        const searchState = form.querySelector('[data-account-role-search]');
        form.addEventListener('submit', () => {
            if (searchState) searchState.value = search?.value ?? '';
        });
    });

    document.querySelectorAll('[data-password-confirmation]').forEach((confirmation) => {
        const form = confirmation.form;
        const password = form?.querySelector('[data-password-field]');
        if (!password) {
            return;
        }

        const validatePasswords = () => {
            if (confirmation.value !== '' || password.value !== '') {
                confirmation.setCustomValidity(
                    confirmation.value === password.value ? '' : 'Las contrasenas no coinciden.'
                );
            } else {
                confirmation.setCustomValidity('');
            }
        };

        password.addEventListener('input', validatePasswords);
        confirmation.addEventListener('input', validatePasswords);
    });

    document.querySelectorAll('[data-photo-input]').forEach((input) => {
        const form = input.closest('[data-photo-form]');
        const preview = document.querySelector('[data-photo-preview]');
        const initials = document.querySelector('[data-photo-initials]');
        const filename = form?.querySelector('[data-photo-filename]');
        let previewUrl;

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = undefined;
            }
            if (!file) {
                input.setCustomValidity('');
                if (filename) filename.textContent = 'Elegir fotografia';
                return;
            }

            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const isValid = validTypes.includes(file.type) && file.size <= 2 * 1024 * 1024;
            input.setCustomValidity(isValid ? '' : 'Selecciona una imagen JPG, PNG o WebP de hasta 2 MB.');
            if (filename) filename.textContent = file.name;
            if (!isValid || !preview) {
                return;
            }

            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.hidden = false;
            if (initials) initials.hidden = true;
        });
    });

    document.querySelectorAll('[data-offer-filters]').forEach((filters) => {
        const page = filters.closest('main');
        const cards = Array.from(page?.querySelectorAll('[data-offer-card]') ?? []);
        const search = filters.querySelector('[data-offer-search]');
        const controls = Array.from(filters.querySelectorAll('[data-offer-filter]'));
        const selects = controls.filter((control) => control.tagName === 'SELECT');
        const buttons = controls.filter((control) => control.tagName === 'BUTTON');
        const buttonGroups = [...new Set(buttons.map((button) => button.dataset.offerFilter))]
            .map((key) => ({ key, buttons: buttons.filter((button) => button.dataset.offerFilter === key) }));
        const reset = filters.querySelector('[data-offer-reset]');
        const counts = Array.from(page?.querySelectorAll('[data-offer-count]') ?? []);
        const empty = page?.querySelector('[data-offer-filter-empty]');
        const normalize = (value) => value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        const controlValue = (control) => control.dataset.filterValue ?? control.value ?? '';

        const applyFilters = () => {
            const query = normalize(search?.value ?? '');
            let visible = 0;
            cards.forEach((card) => {
                const matchesSearch = !query || normalize(card.dataset.search ?? '').includes(query);
                const matchesSelects = selects.every((select) => {
                    const key = select.dataset.offerFilter;
                    const value = controlValue(select);
                    return !value || normalize(card.dataset[key] ?? '') === normalize(value);
                });
                const matchesButtons = buttonGroups.every(({ key, buttons: group }) => {
                    const activeButton = group.find((button) => button.classList.contains('is-active'));
                    const value = activeButton ? controlValue(activeButton) : '';
                    return !value || normalize(card.dataset[key] ?? '') === normalize(value);
                });
                const show = matchesSearch && matchesSelects && matchesButtons;
                card.hidden = !show;
                if (show) visible += 1;
            });
            counts.forEach((count) => {
                count.textContent = `${visible} resultado${visible === 1 ? '' : 's'}`;
            });
            if (empty) empty.hidden = visible > 0 || cards.length === 0;
        };

        search?.addEventListener('input', applyFilters);
        selects.forEach((select) => select.addEventListener('change', applyFilters));
        buttons.forEach((button) => button.addEventListener('click', () => {
            buttons
                .filter((current) => current.dataset.offerFilter === button.dataset.offerFilter)
                .forEach((current) => {
                    const active = current === button;
                    current.classList.toggle('is-active', active);
                    current.setAttribute('aria-pressed', String(active));
                });
            applyFilters();
        }));
        reset?.addEventListener('click', () => {
            if (search) search.value = '';
            selects.forEach((select) => { select.value = ''; });
            buttons.forEach((button) => {
                const active = controlValue(button) === '';
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', String(active));
            });
            applyFilters();
            search?.focus();
        });
    });

    document.querySelectorAll('[data-time-end]').forEach((end) => {
        const form = end.form;
        const start = form?.querySelector('[data-time-start]');
        if (!start) {
            return;
        }

        const validateTimes = () => {
            end.setCustomValidity(
                !start.value || !end.value || end.value > start.value
                    ? ''
                    : 'La hora final debe ser posterior a la inicial.'
            );
        };

        start.addEventListener('input', validateTimes);
        end.addEventListener('input', validateTimes);
    });

    setupOfferPeriodType();
    setupOfferCalendar();
    setupOfferCareerFiltering();
    setupOfferRoomAvailability();
    setupAccountProfileFields();
    setupTutorWithdrawalDialog();
});
