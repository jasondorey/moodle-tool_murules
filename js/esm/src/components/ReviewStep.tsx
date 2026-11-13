/**
 * Review + save step for the React wizard.
 *
 * @module tool_murules/components/ReviewStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {estimateMatches, saveRule} from '../services/api';
import type {ActionItem, ConditionItem, Draft, FieldOption} from '../types';

type Props = {
    draft: Draft;
    strings: Record<string, string>;
    actionLimits: FieldOption[];
    returnurl: string;
    onBack: () => void;
    onDraftChange: (patch: Partial<Draft>) => void;
};

export default function ReviewStep({
    draft,
    strings,
    actionLimits,
    returnurl,
    onBack,
    onDraftChange,
}: Props) {
    const [estimate, setEstimate] = useState('');
    const [estimateLoading, setEstimateLoading] = useState(false);
    const [estimateError, setEstimateError] = useState('');
    const [estimateRetry, setEstimateRetry] = useState(0);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [nameError, setNameError] = useState('');

    const whenSummary = draft.conditions
        .map((c: ConditionItem) => c.description || c.conditiontype)
        .join(' AND ');
    const thenSummary = draft.actions
        .map((a: ActionItem) => a.description || a.actiontype)
        .join('; ');

    useEffect(() => {
        let cancelled = false;
        setEstimateLoading(true);
        setEstimateError('');
        estimateMatches(
            draft.conditions.map((c) => ({
                conditiontype: c.conditiontype,
                conditionconfig: c.conditionconfig,
            })),
        ).then((res) => {
            if (!cancelled) {
                setEstimate(res.label);
            }
        }).catch((err: Error) => {
            if (!cancelled) {
                setEstimate('');
                setEstimateError(err.message || strings.estimate_error || 'Could not estimate matches');
            }
        }).finally(() => {
            if (!cancelled) {
                setEstimateLoading(false);
            }
        });
        return () => {
            cancelled = true;
        };
    }, [draft.conditions, estimateRetry, strings.estimate_error]);

    const onSave = async () => {
        setError('');
        setNameError('');
        if (!draft.name.trim()) {
            setNameError(strings.error_rulename || 'Enter a rule name.');
            return;
        }
        setBusy(true);
        try {
            const result = await saveRule({
                id: draft.id || 0,
                name: draft.name.trim(),
                enabled: draft.enabled ? 1 : 0,
                actionlimit: draft.actionlimit || 'once',
                includesuspended: draft.includesuspended ? 1 : 0,
                conditions: draft.conditions,
                actions: draft.actions,
            });
            if (!result.ok) {
                if (result.errors.name) {
                    setNameError(result.errors.name);
                }
                setError(result.message || strings.save_error || strings.react_wizard_error || 'Save failed');
                return;
            }
            window.location.href = returnurl;
        } catch (err) {
            setError(err instanceof Error ? err.message : String(err));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="tool_murules-review-step">
            <h3 className="h4">{strings.wizard_review}</h3>
            <p className="text-muted">{strings.wizard_review_help}</p>

            <div className="mb-3">
                <label className="form-label" htmlFor="murules_rulename">{strings.rulename} *</label>
                <input
                    id="murules_rulename"
                    type="text"
                    className={`form-control${nameError ? ' is-invalid' : ''}`}
                    value={draft.name}
                    onChange={(e) => onDraftChange({name: e.target.value})}
                />
                {nameError ? <div className="invalid-feedback d-block">{nameError}</div> : null}
            </div>

            <div className="form-check mb-3">
                <input
                    id="murules_enabled"
                    className="form-check-input"
                    type="checkbox"
                    checked={!!draft.enabled}
                    onChange={(e) => onDraftChange({enabled: e.target.checked ? 1 : 0})}
                />
                <label className="form-check-label" htmlFor="murules_enabled">{strings.enabled}</label>
            </div>

            <div className="mb-3">
                <label className="form-label" htmlFor="murules_actionlimit">{strings.actionlimit}</label>
                <select
                    id="murules_actionlimit"
                    className="form-select"
                    value={draft.actionlimit}
                    onChange={(e) => onDraftChange({actionlimit: e.target.value})}
                >
                    {actionLimits.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
            </div>

            <div className="form-check mb-4">
                <input
                    id="murules_suspended"
                    className="form-check-input"
                    type="checkbox"
                    checked={!!draft.includesuspended}
                    onChange={(e) => onDraftChange({includesuspended: e.target.checked ? 1 : 0})}
                />
                <label className="form-check-label" htmlFor="murules_suspended">
                    {strings.includesuspended}
                </label>
            </div>

            <h4 className="h5">{strings.wizard_review_recap}</h4>
            <div className="mb-2">
                <div className="small text-muted text-uppercase">{strings.when}</div>
                <div className="tool_murules-recap tool_murules-recap-when">{whenSummary}</div>
            </div>
            <div className="mb-3">
                <div className="small text-muted text-uppercase">{strings.then}</div>
                <div className="tool_murules-recap">{thenSummary}</div>
            </div>
            <div className="mb-3">
                <strong>{strings.matchingusers}:</strong>{' '}
                {estimateLoading ? (
                    <span className="text-muted">{strings.estimating || 'Estimating…'}</span>
                ) : estimate ? (
                    <span className="text-muted">{estimate}</span>
                ) : null}
                {estimateError ? (
                    <div className="alert alert-warning mt-2 py-2" role="alert">
                        <span className="me-2">{estimateError}</span>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={() => setEstimateRetry((n) => n + 1)}
                        >
                            {strings.retry || 'Retry'}
                        </button>
                    </div>
                ) : null}
            </div>

            {error ? (
                <div className="alert alert-danger" role="alert">
                    <p className="mb-2">{error}</p>
                    <button type="button" className="btn btn-sm btn-outline-danger" disabled={busy} onClick={onSave}>
                        {strings.retry || 'Retry'}
                    </button>
                </div>
            ) : null}

            <div className="d-flex flex-wrap gap-2">
                <button type="button" className="btn btn-primary" disabled={busy} onClick={onSave}>
                    {busy ? (strings.saving || strings.save) : strings.save}
                </button>
                <button type="button" className="btn btn-outline-secondary" disabled={busy} onClick={onBack}>
                    {strings.back}
                </button>
            </div>
        </div>
    );
}
