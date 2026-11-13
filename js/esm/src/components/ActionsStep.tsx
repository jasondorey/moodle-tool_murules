/**
 * Actions step: card picker + multi-action THEN editors.
 *
 * @module tool_murules/components/ActionsStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {loadSchema, validateConfig} from '../services/api';
import type {ActionItem, CatalogAction, EditorSchema} from '../types';
import ActionCards from './ActionCards';
import SchemaForm from './SchemaForm';

type Props = {
    catalog: CatalogAction[];
    actions: ActionItem[];
    whenSummary: string;
    strings: Record<string, string>;
    onChange: (actions: ActionItem[]) => void;
    onBack: () => void;
    onContinue: () => void;
};

export default function ActionsStep({
    catalog,
    actions,
    whenSummary,
    strings,
    onChange,
    onBack,
    onContinue,
}: Props) {
    const [pickType, setPickType] = useState<string | null>(null);
    const [schema, setSchema] = useState<EditorSchema | null>(null);
    const [values, setValues] = useState<Record<string, unknown>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState('');
    const [busy, setBusy] = useState(false);
    const [loadError, setLoadError] = useState('');
    const [schemaRetry, setSchemaRetry] = useState(0);

    useEffect(() => {
        if (!pickType) {
            setSchema(null);
            return;
        }
        let cancelled = false;
        setBusy(true);
        setLoadError('');
        loadSchema('action', pickType)
            .then((s) => {
                if (!cancelled) {
                    setSchema(s);
                    setValues({...s.defaults});
                    setErrors({});
                    setFormError('');
                }
            })
            .catch((err: Error) => {
                if (!cancelled) {
                    setSchema(null);
                    setLoadError(err.message || strings.react_wizard_error || 'Error');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setBusy(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [pickType, schemaRetry, strings.react_wizard_error]);

    const addAction = async (andContinue: boolean) => {
        if (!pickType || !schema) {
            return;
        }
        setBusy(true);
        setFormError('');
        try {
            const result = await validateConfig('action', pickType, values);
            if (!result.ok) {
                setErrors(result.errors);
                setFormError(strings.form_has_errors || 'Fix the highlighted fields and try again.');
                return;
            }
            onChange([
                ...actions,
                {
                    actiontype: pickType,
                    actionconfig: result.config,
                    description: result.description,
                },
            ]);
            setPickType(null);
            setSchema(null);
            setValues({});
            setErrors({});
            if (andContinue) {
                onContinue();
            }
        } catch (err) {
            setFormError(err instanceof Error ? err.message : String(err));
        } finally {
            setBusy(false);
        }
    };

    const pickedName = catalog.find((i) => i.type === pickType)?.name;

    return (
        <div className="tool_murules-actions-step">
            <h3 className="h4">{strings.wizard_actions}</h3>
            <p className="text-muted">{strings.wizard_actions_help || strings.actions_help}</p>
            {whenSummary ? (
                <div className="mb-3">
                    <div className="small text-muted text-uppercase">{strings.when}</div>
                    <div className="tool_murules-recap">{whenSummary}</div>
                </div>
            ) : null}

            <section className="tool_murules-saved-block mb-4" aria-label={strings.actions_heading}>
                <h4 className="h5">{strings.actions_heading}</h4>
                <p className="text-muted small">{strings.actions_help}</p>
                {actions.length === 0 ? (
                    <div className="alert alert-secondary mb-0">{strings.noactionsyet}</div>
                ) : (
                    <>
                        <ul className="list-group mb-3 tool_murules-saved-list">
                            {actions.map((act, idx) => (
                                <li
                                    key={`${act.actiontype}-${idx}`}
                                    className="list-group-item d-flex align-items-center justify-content-between gap-2"
                                >
                                    <div className="me-auto">
                                        <span className="badge text-bg-primary me-2">THEN</span>
                                        <span className="badge text-bg-secondary me-2">{idx + 1}</span>
                                        {act.description || act.actiontype}
                                    </div>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() => onChange(actions.filter((_, i) => i !== idx))}
                                    >
                                        {strings.removeaction}
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="d-flex flex-wrap gap-2">
                            <button type="button" className="btn btn-primary" onClick={onContinue}>
                                {strings.continuereview}
                            </button>
                            <button type="button" className="btn btn-outline-secondary" onClick={onBack}>
                                {strings.back}
                            </button>
                        </div>
                    </>
                )}
            </section>

            {!pickType ? (
                <section aria-label={strings.select_action}>
                    <h4 className="h5">
                        {actions.length ? strings.configure_next_action : strings.select_action}
                    </h4>
                    <p className="text-muted small">{strings.pickaction_help || ''}</p>
                    <ActionCards catalog={catalog} strings={strings} onPick={setPickType} />
                    {!actions.length ? (
                        <button type="button" className="btn btn-link mt-3 px-0" onClick={onBack}>
                            {strings.back}
                        </button>
                    ) : null}
                </section>
            ) : (
                <section className="card border-primary">
                    <div className="card-header d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h4 className="h5 mb-0">{strings.configure_action || strings.configure_next_action}</h4>
                            {pickedName ? <div className="small text-muted">{pickedName}</div> : null}
                        </div>
                        <button
                            type="button"
                            className="btn btn-link btn-sm"
                            onClick={() => {
                                setPickType(null);
                                setSchema(null);
                                setErrors({});
                                setFormError('');
                                setLoadError('');
                            }}
                        >
                            {strings.changeaction || strings.changecondition}
                        </button>
                    </div>
                    <div className="card-body">
                        {loadError ? (
                            <div className="alert alert-danger" role="alert">
                                <p className="mb-2">{loadError}</p>
                                <button
                                    type="button"
                                    className="btn btn-sm btn-outline-danger"
                                    onClick={() => setSchemaRetry((n) => n + 1)}
                                >
                                    {strings.retry || 'Retry'}
                                </button>
                            </div>
                        ) : null}
                        {busy && !schema ? (
                            <p className="text-muted mb-0">{strings.loading_editor || '…'}</p>
                        ) : null}
                        {formError ? <div className="alert alert-warning" role="alert">{formError}</div> : null}
                        {schema ? (
                            <>
                                <SchemaForm
                                    schema={schema}
                                    values={values}
                                    errors={errors}
                                    strings={strings}
                                    onChange={(next) => {
                                        setValues(next);
                                        setFormError('');
                                    }}
                                />
                                <div className="d-flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        className="btn btn-primary"
                                        disabled={busy}
                                        onClick={() => addAction(actions.length === 0)}
                                    >
                                        {actions.length ? strings.addanotheraction : strings.saveandcontinue}
                                    </button>
                                    {!actions.length ? (
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            disabled={busy}
                                            onClick={() => addAction(false)}
                                        >
                                            {strings.addanotheraction}
                                        </button>
                                    ) : null}
                                </div>
                            </>
                        ) : null}
                    </div>
                </section>
            )}
        </div>
    );
}
