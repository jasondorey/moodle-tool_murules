/**
 * Event step: category cards + multi-condition AND editors.
 *
 * @module tool_murules/components/EventStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {loadSchema, validateConfig} from '../services/api';
import type {CatalogCategory, ConditionItem, EditorSchema} from '../types';
import CategoryCards from './CategoryCards';
import SchemaForm from './SchemaForm';

type Props = {
    catalog: CatalogCategory[];
    conditions: ConditionItem[];
    strings: Record<string, string>;
    onChange: (conditions: ConditionItem[]) => void;
    onContinue: () => void;
};

export default function EventStep({catalog, conditions, strings, onChange, onContinue}: Props) {
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
        loadSchema('condition', pickType)
            .then((s) => {
                if (cancelled) {
                    return;
                }
                setSchema(s);
                setValues({...s.defaults});
                setErrors({});
                setFormError('');
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

    const addCondition = async (andContinue: boolean) => {
        if (!pickType || !schema) {
            return;
        }
        setBusy(true);
        setFormError('');
        try {
            const result = await validateConfig('condition', pickType, values);
            if (!result.ok) {
                setErrors(result.errors);
                setFormError(strings.fix_has_errors || 'Fix the highlighted fields and try again.');
                return;
            }
            onChange([
                ...conditions,
                {
                    conditiontype: pickType,
                    conditionconfig: result.config,
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

    const removeAt = (idx: number) => {
        onChange(conditions.filter((_, i) => i !== idx));
    };

    const pickedName = catalog
        .flatMap((c) => c.items)
        .find((i) => i.type === pickType)?.name;

    return (
        <div className="tool_murules-event-step">
            <h3 className="h4">{strings.wizard_event}</h3>
            <p className="text-muted">{strings.wizard_event_help || strings.pickcondition_help}</p>

            <section className="tool_murules-saved-block mb-4" aria-label={strings.conditions_and_heading}>
                <h4 className="h5">{strings.conditions_and_heading}</h4>
                <p className="text-muted small">{strings.conditions_and_help}</p>
                {conditions.length === 0 ? (
                    <div className="alert alert-secondary mb-0">{strings.noconditionsyet}</div>
                ) : (
                    <>
                        <ul className="list-group mb-3 tool_murules-saved-list">
                            {conditions.map((cond, idx) => (
                                <li
                                    key={`${cond.conditiontype}-${idx}`}
                                    className="list-group-item d-flex align-items-center justify-content-between gap-2"
                                >
                                    <div className="me-auto">
                                        <span className="badge text-bg-primary me-2">AND</span>
                                        <span className="badge text-bg-secondary me-2">{idx + 1}</span>
                                        {cond.description || cond.conditiontype}
                                    </div>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() => removeAt(idx)}
                                    >
                                        {strings.removecondition}
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <button type="button" className="btn btn-primary" onClick={onContinue}>
                            {strings.continueactions}
                        </button>
                    </>
                )}
            </section>

            {!pickType ? (
                <section aria-label={strings.pickcondition_heading}>
                    <h4 className="h5">
                        {conditions.length
                            ? strings.configure_next_condition
                            : strings.pickcondition_heading}
                    </h4>
                    <p className="text-muted small">{strings.pickcondition_help}</p>
                    <CategoryCards catalog={catalog} strings={strings} onPick={setPickType} />
                </section>
            ) : (
                <section className="tool_murules-condition-editor card border-primary">
                    <div className="card-header d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <h4 className="h5 mb-0">{strings.configure_condition}</h4>
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
                            {strings.changecondition}
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
                            <p className="text-muted mb-0">{strings.loading_editor || strings.loadingcms || '…'}</p>
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
                                        onClick={() => addCondition(conditions.length === 0)}
                                    >
                                        {conditions.length
                                            ? strings.addanothercondition
                                            : strings.saveandcontinue}
                                    </button>
                                    {!conditions.length ? (
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            disabled={busy}
                                            onClick={() => addCondition(false)}
                                        >
                                            {strings.addanothercondition}
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
