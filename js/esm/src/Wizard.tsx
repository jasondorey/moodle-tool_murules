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
import Stepper from './components/Stepper';
import {loadBootstrap} from './services/api';
import type {BootstrapPayload, Draft, WizardProps, WizardStep} from './types';

export default function Wizard({ruleid = 0, returnurl}: WizardProps) {
    const [boot, setBoot] = useState<BootstrapPayload | null>(null);
    const [draft, setDraft] = useState<Draft | null>(null);
    const [step, setStep] = useState<WizardStep>('event');
    const [error, setError] = useState('');
    const [retryToken, setRetryToken] = useState(0);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setError('');
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
                    setBoot(null);
                    setDraft(null);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [ruleid, retryToken]);

    const strings = boot?.strings || {};

    const whenSummary = useMemo(() => {
        if (!draft) {
            return '';
        }
        return draft.conditions.map((c) => c.description || c.conditiontype).join(' AND ');
    }, [draft]);

    if (loading) {
        return (
            <div className="tool_murules-react-wizard" aria-busy="true">
                <div className="placeholder-glow">
                    <span className="placeholder col-7 mb-3" />
                    <span className="placeholder col-12 mb-2" />
                    <span className="placeholder col-10 mb-2" />
                </div>
                <p className="text-muted">{strings.react_wizard_loading || 'Loading…'}</p>
            </div>
        );
    }

    if (error || !boot || !draft) {
        return (
            <div className="tool_murules-react-wizard">
                <div className="alert alert-danger" role="alert">
                    <p className="mb-2">{error || strings.react_wizard_error || 'Could not load wizard'}</p>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => setRetryToken((n) => n + 1)}
                    >
                        {strings.retry || 'Retry'}
                    </button>
                </div>
                <a className="btn btn-link px-0" href={returnurl}>{strings.cancelwizard || 'Cancel'}</a>
            </div>
        );
    }

    return (
        <div className="tool_murules-react-wizard">
            <header className="tool_murules-wizard-header mb-3">
                <h2 className="h3 mb-1">
                    {draft.id ? strings.editrule : strings.createrule}
                </h2>
                <p className="text-muted mb-0">
                    {strings.wizard_intro || strings.wizard_event_help}
                </p>
            </header>

            <Stepper current={step} strings={strings} />

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
