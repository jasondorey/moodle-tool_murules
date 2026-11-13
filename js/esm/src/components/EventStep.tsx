/**
 * Event step: category accordion + multi-condition AND editors.
 *
 * @module tool_murules/components/EventStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {loadSchema, validateConfig} from '../services/api';
import type {CatalogCategory, ConditionItem, EditorSchema} from '../types';
import SchemaForm from './SchemaForm';

type Props = {
    catalog: CatalogCategory[];
    conditions: ConditionItem[];
    strings: Record<string, string>;
    onChange: (conditions: ConditionItem[]) => void;
    onContinue: () => void;
};

export default function EventStep({catalog, conditions, strings, onChange, onContinue}: Props) {
    const [openCat, setOpenCat] = useState(catalog[0]?.key || '');
    const [pickType, setPickType] = useState<string | null>(null);
    const [schema, setSchema] = useState<EditorSchema | null>(null);
    const [values, setValues] = useState<Record<string, unknown>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const [loadError, setLoadError] = useState('');

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
            })
            .catch((err: Error) => {
                if (!cancelled) {
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
    }, [pickType, strings.react_wizard_error]);

    const addCondition = async (andContinue: boolean) => {
        if (!pickType || !schema) {
            return;
        }
        setBusy(true);
        try {
            const result = await validateConfig('condition', pickType, values);
            if (!result.ok) {
                setErrors(result.errors);
                return;
            }
            const next = [
                ...conditions,
                {
                    conditiontype: pickType,
                    conditionconfig: result.config,
                    description: result.description,
                },
            ];
            onChange(next);
            setPickType(null);
            setSchema(null);
            setValues({});
            setErrors({});
            if (andContinue || conditions.length === 0) {
                // First condition: continue unless user will add another via separate button.
            }
            if (andContinue) {
                onContinue();
            }
        } finally {
            setBusy(false);
        }
    };

    const removeAt = (idx: number) => {
        onChange(conditions.filter((_, i) => i !== idx));
    };

    return (
        <div className="tool_murules-event-step">
            <h3 className="h4">{strings.wizard_event}</h3>
            <p className="text-muted">{strings.wizard_event_help || strings.pickcondition_help}</p>

            <h4 className="h5">{strings.conditions_and_heading}</h4>
            <p className="text-muted small">{strings.conditions_and_help}</p>
            {conditions.length === 0 ? (
                <div className="alert alert-secondary">{strings.noconditionsyet}</div>
            ) : (
                <>
                    <ul className="list-group mb-3">
                        {conditions.map((cond, idx) => (
                            <li
                                key={`${cond.conditiontype}-${idx}`}
                                className="list-group-item d-flex align-items-center justify-content-between gap-2"
                            >
                                <div className="me-auto">
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
                    <div className="mb-3">
                        <button type="button" className="btn btn-primary" onClick={onContinue}>
                            {strings.continueactions}
                        </button>
                    </div>
                </>
            )}

            {!pickType ? (
                <>
                    <h4 className="h5 mt-3">
                        {conditions.length
                            ? strings.configure_next_condition
                            : strings.pickcondition_heading}
                    </h4>
                    <p className="text-muted small">{strings.pickcondition_help}</p>
                    <div className="accordion tool_murules-event-picker" id="murules_react_picker">
                        {catalog.map((cat) => {
                            const open = openCat === cat.key;
                            return (
                                <div className="accordion-item" key={cat.key}>
                                    <h3 className="accordion-header h6 mb-0">
                                        <button
                                            type="button"
                                            className={`accordion-button${open ? '' : ' collapsed'}`}
                                            aria-expanded={open}
                                            onClick={() => setOpenCat(open ? '' : cat.key)}
                                        >
                                            <span className="tool_murules-picker-catname">{cat.label}</span>
                                            <span className="badge text-bg-secondary ms-2">{cat.count}</span>
                                        </button>
                                    </h3>
                                    <div className={`accordion-collapse collapse${open ? ' show' : ''}`}>
                                        <div className="accordion-body p-0">
                                            <ul className="list-group list-group-flush">
                                                {cat.items.map((item) => (
                                                    <li className="list-group-item p-0 border-0" key={item.type}>
                                                        <button
                                                            type="button"
                                                            className="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 tool_murules-picker-item w-100 text-start"
                                                            onClick={() => setPickType(item.type)}
                                                        >
                                                            <div className="me-auto">
                                                                <div className="fw-semibold">{item.name}</div>
                                                                <div className="small text-muted">
                                                                    {strings.pickcondition_hint || ''}
                                                                </div>
                                                            </div>
                                                            <span className="btn btn-sm btn-outline-primary">
                                                                {strings.pickcondition}
                                                            </span>
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </>
            ) : (
                <div className="tool_murules-condition-editor mt-3">
                    <div className="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h4 className="h5 mb-0">{strings.configure_condition}</h4>
                        <button
                            type="button"
                            className="btn btn-link btn-sm"
                            onClick={() => {
                                setPickType(null);
                                setSchema(null);
                                setErrors({});
                            }}
                        >
                            {strings.changecondition}
                        </button>
                    </div>
                    {loadError ? <div className="alert alert-danger">{loadError}</div> : null}
                    {busy && !schema ? <p className="text-muted">{strings.loadingcms || '…'}</p> : null}
                    {schema ? (
                        <>
                            <SchemaForm
                                schema={schema}
                                values={values}
                                errors={errors}
                                strings={strings}
                                onChange={setValues}
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
            )}
        </div>
    );
}
