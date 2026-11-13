/**
 * Event-step wizard: condition-type reload + course→CM AJAX.
 *
 * @module     tool_murules/wizard_event
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    const PAIRS = [
        {course: 'qs_courseid', cm: 'qs_cmid', mode: 'quiz'},
        {course: 'ac_courseid', cm: 'ac_cmid', mode: 'activity'},
    ];

    /**
     * @param {string} name
     * @return {HTMLElement|null}
     */
    const byName = (name) => document.getElementById('id_' + name);

    /**
     * @param {HTMLSelectElement} select
     * @param {Array<{id:number,name:string}>} cms
     * @param {string} chooseLabel
     * @param {string} emptyLabel
     * @param {string|null} preferred
     */
    const fillSelect = (select, cms, chooseLabel, emptyLabel, preferred) => {
        const previous = preferred || select.value;
        select.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '0';
        placeholder.textContent = cms.length ? chooseLabel : emptyLabel;
        select.appendChild(placeholder);
        cms.forEach((cm) => {
            const opt = document.createElement('option');
            opt.value = String(cm.id);
            opt.textContent = cm.name;
            select.appendChild(opt);
        });
        if (previous && previous !== '0' && [...select.options].some((o) => o.value === previous)) {
            select.value = previous;
        } else {
            select.value = '0';
        }
        select.disabled = false;
        select.dispatchEvent(new Event('change', {bubbles: true}));
    };

    /**
     * @param {HTMLElement} courseEl
     * @return {number}
     */
    const readCourseId = (courseEl) => {
        if (courseEl.multiple) {
            const selected = [...courseEl.selectedOptions].map((o) => parseInt(o.value, 10)).filter(Boolean);
            return selected.length ? selected[0] : 0;
        }
        return parseInt(courseEl.value, 10) || 0;
    };

    /**
     * @param {{course:string,cm:string,mode:string}} pair
     * @param {string} chooseLabel
     * @param {string} emptyLabel
     * @param {string} loadingLabel
     * @param {string|null} preferredCm
     * @return {Promise}
     */
    const refreshCm = (pair, chooseLabel, emptyLabel, loadingLabel, preferredCm) => {
        const courseEl = byName(pair.course);
        const cmEl = byName(pair.cm);
        if (!courseEl || !cmEl) {
            return Promise.resolve();
        }
        const courseid = readCourseId(courseEl);
        if (!courseid) {
            fillSelect(cmEl, [], chooseLabel, emptyLabel, null);
            return Promise.resolve();
        }
        cmEl.disabled = true;
        const loading = document.createElement('option');
        loading.value = '0';
        loading.textContent = loadingLabel;
        cmEl.innerHTML = '';
        cmEl.appendChild(loading);

        return Ajax.call([{
            methodname: 'tool_murules_get_course_cms',
            args: {courseid: courseid, mode: pair.mode},
        }])[0].then((response) => {
            fillSelect(cmEl, response.cms || [], chooseLabel, emptyLabel, preferredCm || null);
            return response;
        }).catch((error) => {
            cmEl.disabled = false;
            Notification.exception(error);
        });
    };

    /**
     * Reload Event step when condition type changes (single-panel form).
     *
     * @param {HTMLSelectElement} typeEl
     */
    const bindTypeReload = (typeEl) => {
        const initial = typeEl.value;
        typeEl.addEventListener('change', () => {
            if (typeEl.value === initial) {
                return;
            }
            const url = new URL(window.location.href);
            url.searchParams.set('step', 'event');
            url.searchParams.set('pick', typeEl.value);
            // Preserve edit id if present.
            const idEl = byName('id');
            if (idEl && idEl.value && idEl.value !== '0') {
                url.searchParams.set('id', idEl.value);
            }
            window.location.href = url.toString();
        });
    };

    /**
     * @return {void}
     */
    const init = () => {
        const form = document.querySelector('form.mform');
        if (!form) {
            return;
        }

        const typeEl = byName('conditiontype');
        if (typeEl) {
            bindTypeReload(typeEl);
        }

        Str.get_strings([
            {key: 'choosedots', component: 'moodle'},
            {key: 'nocms', component: 'tool_murules'},
            {key: 'loadingcms', component: 'tool_murules'},
        ]).then((strings) => {
            const chooseLabel = strings[0];
            const emptyLabel = strings[1];
            const loadingLabel = strings[2];

            PAIRS.forEach((pair) => {
                const courseEl = byName(pair.course);
                const cmEl = byName(pair.cm);
                if (!courseEl || !cmEl) {
                    return;
                }
                const preferredCm = cmEl.value && cmEl.value !== '0' ? cmEl.value : null;
                courseEl.addEventListener('change', () => {
                    refreshCm(pair, chooseLabel, emptyLabel, loadingLabel, null);
                });
                // If course already chosen but CM list is empty/placeholder-only, hydrate via AJAX.
                const courseid = readCourseId(courseEl);
                const onlyPlaceholder = cmEl.options.length <= 1;
                if (courseid && onlyPlaceholder) {
                    refreshCm(pair, chooseLabel, emptyLabel, loadingLabel, preferredCm);
                }
            });
            return;
        }).catch(Notification.exception);
    };

    return {init: init};
});
