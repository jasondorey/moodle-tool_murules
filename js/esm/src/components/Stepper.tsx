/**
 * Connect-like wizard stepper (Moodle/Bootstrap native).
 *
 * @module tool_murules/components/Stepper
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {WizardStep} from '../types';

const STEPS: WizardStep[] = ['event', 'actions', 'review'];

type Props = {
    current: WizardStep;
    strings: Record<string, string>;
};

export default function Stepper({current, strings}: Props) {
    const currentIndex = STEPS.indexOf(current);
    const nextHint = current === 'event'
        ? strings.wizard_next_actions
        : current === 'actions'
            ? strings.wizard_next_review
            : '';

    return (
        <nav
            className="tool_murules-stepper mb-4"
            aria-label={strings.wizard_steps_label || 'Wizard'}
        >
            <ol className="tool_murules-stepper-list list-unstyled d-flex flex-wrap align-items-stretch gap-0 mb-2">
                {STEPS.map((key, idx) => {
                    const done = idx < currentIndex;
                    const active = key === current;
                    let stateClass = 'tool_murules-stepper-item';
                    if (active) {
                        stateClass += ' is-active';
                    } else if (done) {
                        stateClass += ' is-done';
                    }
                    return (
                        <li key={key} className={stateClass}>
                            {idx > 0 ? <span className="tool_murules-stepper-connector" aria-hidden="true" /> : null}
                            <div className="tool_murules-stepper-body">
                                <span
                                    className={`tool_murules-stepper-index${done ? ' text-bg-success' : active ? ' text-bg-primary' : ' text-bg-secondary'}`}
                                    aria-hidden="true"
                                >
                                    {done ? '✓' : idx + 1}
                                </span>
                                <span className="tool_murules-stepper-label">
                                    {strings[`wizard_${key}`] || key}
                                </span>
                            </div>
                            {active ? <span className="visually-hidden">{strings.wizard_current_step || 'Current step'}</span> : null}
                        </li>
                    );
                })}
            </ol>
            <p className="tool_murules-wizard-progress text-muted small mb-0" aria-live="polite">
                {strings.wizard_progress
                    ? strings.wizard_progress
                        .replace('{$a->current}', String(currentIndex + 1))
                        .replace('{$a->total}', String(STEPS.length))
                        .replace('{$a->label}', strings[`wizard_${current}`] || current)
                    : `Step ${currentIndex + 1} of ${STEPS.length}`}
            </p>
            {nextHint ? (
                <p className="tool_murules-wizard-next text-muted small mb-0 fw-semibold">{nextHint}</p>
            ) : null}
        </nav>
    );
}
