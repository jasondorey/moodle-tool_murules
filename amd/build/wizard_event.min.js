/**
 * Event-step wizard polish: condition panels + course→CM AJAX.
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
     */
    const fillSelect = (select, cms, chooseLabel, emptyLabel) => {
        const previous = select.value;
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
        if (previous && [...select.options].some((o) => o.value === previous)) {
            select.value = previous;
        } else {
            select.value = '0';
        }
        select.disabled = false;
    };

    /**
     * @param {{course:string,cm:string,mode:string}} pair
     * @param {string} chooseLabel
     * @param {string} emptyLabel
     * @param {string} loadingLabel
     * @return {Promise}
     */
    const refreshCm = (pair, chooseLabel, emptyLabel, loadingLabel) => {
        const courseEl = byName(pair.course);
        const cmEl = byName(pair.cm);
        if (!courseEl || !cmEl) {
            return Promise.resolve();
        }
        let courseid = 0;
        if (courseEl.multiple) {
            const selected = [...courseEl.selectedOptions].map((o) => parseInt(o.value, 10)).filter(Boolean);
            courseid = selected.length ? selected[0] : 0;
        } else {
            courseid = parseInt(courseEl.value, 10) || 0;
        }
        if (!courseid) {
            fillSelect(cmEl, [], chooseLabel, emptyLabel);
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
            fillSelect(cmEl, response.cms || [], chooseLabel, emptyLabel);
            return response;
        }).catch(Notification.exception);
    };

    /**
     * Show only the fieldset for the selected condition type.
     */
    const syncConditionPanels = () => {
        const typeEl = byName('conditiontype');
        if (!typeEl) {
            return;
        }
        const selected = typeEl.value;
        document.querySelectorAll('fieldset[id^="id_hdr_"]').forEach((fs) => {
            const type = fs.id.replace(/^id_hdr_/, '');
            const show = type === selected;
            fs.classList.toggle('tool_murules-condition-hidden', !show);
            fs.hidden = !show;
            // Keep collapsed headers from fighting us.
            if (show) {
                fs.classList.remove('collapsed');
            }
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

        Str.get_strings([
            {key: 'choosedots', component: 'moodle'},
            {key: 'nocms', component: 'tool_murules'},
            {key: 'loadingcms', component: 'tool_murules'},
        ]).then((strings) => {
            const chooseLabel = strings[0];
            const emptyLabel = strings[1];
            const loadingLabel = strings[2];

            const typeEl = byName('conditiontype');
            if (typeEl) {
                typeEl.addEventListener('change', syncConditionPanels);
                syncConditionPanels();
            }

            PAIRS.forEach((pair) => {
                const courseEl = byName(pair.course);
                if (!courseEl) {
                    return;
                }
                courseEl.addEventListener('change', () => {
                    refreshCm(pair, chooseLabel, emptyLabel, loadingLabel);
                });
            });
            return;
        }).catch(Notification.exception);
    };

    return {init: init};
});
