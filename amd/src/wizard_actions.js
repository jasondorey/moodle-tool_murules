/**
 * Actions-step wizard: action-type reload (single-panel form).
 *
 * @module     tool_murules/wizard_actions
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    /**
     * @param {string} name
     * @return {HTMLElement|null}
     */
    const byName = (name) => document.getElementById('id_' + name);

    /**
     * @return {void}
     */
    const init = () => {
        const typeEl = byName('actiontype');
        if (!typeEl) {
            return;
        }
        const initial = typeEl.value;
        typeEl.addEventListener('change', () => {
            if (typeEl.value === initial) {
                return;
            }
            const url = new URL(window.location.href);
            url.searchParams.set('step', 'actions');
            url.searchParams.set('pick', typeEl.value);
            const idEl = byName('id');
            if (idEl && idEl.value && idEl.value !== '0') {
                url.searchParams.set('id', idEl.value);
            }
            window.location.href = url.toString();
        });
    };

    return {init: init};
});
