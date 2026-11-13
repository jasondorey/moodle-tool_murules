/**
 * React create/edit rule wizard (Event → Actions → Review).
 *
 * Mounted via data-react-component="@moodle/lms/tool_murules/Wizard".
 *
 * @module tool_murules/Wizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useMemo, useState} from 'react';
import ActionsStep from './components/ActionsStep';
import EventStep from './components/EventStep';
import ReviewStep from './components/ReviewStep';
import {loadBootstrap} from './services/api';
import type {BootstrapPayload, Draft, WizardProps, WizardStep} from './types';

const STEPS: WizardStep[] = ['event', 'actions', 'review'];

export default function Wizard({ruleid = 0, returnurl}: WizardProps) {
    const [boot, setBoot] = useState<BootstrapPayload | null>(null);
    const [draft, setDraft] = useState<Draft | null>(null);
    const [step, setStep] = useState<WizardStep>('event');
    const [error, setError] = useState('');

    useEffect(() => {
        let cancelled = false;
        loadBootstrap(ruleid)
            .then((payload) => {
                if (cancelled) {
                    return;
                }
                setBoot(payload);
                setDraft(payload.draft);
            })
            .catch((err: Error) => {
                if (!cancelled) {
                    setError(err.message || 'Failed to load wizard');
                }
            });
        return () => {
            cancelled = true;
        };
    }, [ruleid]);

    const strings = boot?.strings || {};
    const stepIndex = STEPS.indexOf(step);

    const whenSummary = useMemo(() => {
        if (!draft) {
            return '';
        }
        return draft.conditions.map((c) => c.description || c.conditiontype).join(' AND ');
    }, [draft]);

    if (error) {
        return <div className="alert alert-danger">{error}</div>;
    }
    if (!boot || !draft) {
        return <p className="text-muted">{strings.react_wizard_loading || 'Loading…'}</p>;
    }

    return (
        <div className="tool_murules-react-wizard">
            <nav className="tool_murules-wizard-steps mb-3" aria-label={strings.wizard_steps_label || 'Wizard'}>
                <ul className="nav nav-pills flex-wrap gap-1">
                    {STEPS.map((key, idx) => {
                        let cls = 'nav-link';
                        if (key === step) {
                            cls += ' active';
                        } else if (idx < stepIndex) {
                            cls += ' text-success';
                        }
                        return (
                            <li className="nav-item" key={key}>
                                <span className={cls} aria-current={key === step ? 'step' : undefined}>
                                    <span className="badge text-bg-light text-dark me-1" aria-hidden="true">
                                        {idx + 1}
                                    </span>
                                    {strings[`wizard_${key}`] || key}
                                </span>
                            </li>
                        );
                    })}
                </ul>
                <p className="tool_murules-wizard-progress text-muted small mb-0">
                    {strings.wizard_progress
                        ? strings.wizard_progress
                            .replace('{$a->current}', String(stepIndex + 1))
                            .replace('{$a->total}', String(STEPS.length))
                            .replace('{$a->label}', strings[`wizard_${step}`] || step)
                        : `Step ${stepIndex + 1} of ${STEPS.length}`}
                </p>
                {step === 'event' ? (
                    <p className="tool_murules-wizard-next text-muted small mb-0">{strings.wizard_next_actions}</p>
                ) : null}
                {step === 'actions' ? (
                    <p className="tool_murules-wizard-next text-muted small mb-0">{strings.wizard_next_review}</p>
                ) : null}
            </nav>

            {step === 'event' ? (
                <EventStep
                    catalog={boot.conditionCatalog}
                    conditions={draft.conditions}
                    strings={strings}
                    onChange={(conditions) => setDraft({...draft, conditions})}
                    onContinue={() => setStep('actions')}
                />
            ) : null}

            {step === 'actions' ? (
                <ActionsStep
                    catalog={boot.actionCatalog}
                    actions={draft.actions}
                    whenSummary={whenSummary}
                    strings={strings}
                    onChange={(actions) => setDraft({...draft, actions})}
                    onBack={() => setStep('event')}
                    onContinue={() => setStep('review')}
                />
            ) : null}

            {step === 'review' ? (
                <ReviewStep
                    draft={draft}
                    strings={strings}
                    actionLimits={boot.actionLimits}
                    returnurl={returnurl}
                    onBack={() => setStep('actions')}
                    onDraftChange={(patch) => setDraft({...draft, ...patch})}
                />
            ) : null}

            <div className="mt-4">
                <a className="btn btn-link px-0" href={returnurl}>{strings.cancelwizard}</a>
            </div>
        </div>
    );
}
