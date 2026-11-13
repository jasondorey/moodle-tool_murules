/**
 * Actions step: multi-action THEN editors.
 *
 * @module tool_murules/components/ActionsStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {loadSchema, validateConfig} from '../services/api';
import type {ActionItem, CatalogAction, EditorSchema} from '../types';
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
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (!pickType) {
            setSchema(null);
            return;
        }
        let cancelled = false;
        setBusy(true);
        loadSchema('action', pickType)
            .then((s) => {
                if (!cancelled) {
                    setSchema(s);
                    setValues({...s.defaults});
                    setErrors({});
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
    }, [pickType]);

    const addAction = async (andContinue: boolean) => {
        if (!pickType || !schema) {
            return;
        }
        setBusy(true);
        try {
            const result = await validateConfig('action', pickType, values);
            if (!result.ok) {
                setErrors(result.errors);
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
        } finally {
            setBusy(false);
        }
    };

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

            <h4 className="h5">{strings.actions_heading}</h4>
            <p className="text-muted small">{strings.actions_help}</p>
            {actions.length === 0 ? (
                <div className="alert alert-secondary">{strings.noactionsyet}</div>
            ) : (
                <>
                    <ul className="list-group mb-3">
                        {actions.map((act, idx) => (
                            <li
                                key={`${act.actiontype}-${idx}`}
                                className="list-group-item d-flex align-items-center justify-content-between gap-2"
                            >
                                <div className="me-auto">
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
                    <div className="mb-3 d-flex flex-wrap gap-2">
                        <button type="button" className="btn btn-primary" onClick={onContinue}>
                            {strings.continuereview}
                        </button>
                        <button type="button" className="btn btn-outline-secondary" onClick={onBack}>
                            {strings.back}
                        </button>
                    </div>
                </>
            )}

            {!pickType ? (
                <div className="mt-3">
                    <h4 className="h5">
                        {actions.length ? strings.configure_next_action : strings.select_action}
                    </h4>
                    <div className="list-group tool_murules-action-picker">
                        {catalog.map((item) => (
                            <button
                                key={item.type}
                                type="button"
                                className="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                onClick={() => setPickType(item.type)}
                            >
                                <span>{item.name}</span>
                                <span className="btn btn-sm btn-outline-primary">{strings.pickcondition}</span>
                            </button>
                        ))}
                    </div>
                    {!actions.length ? (
                        <button type="button" className="btn btn-link mt-3 px-0" onClick={onBack}>
                            {strings.back}
                        </button>
                    ) : null}
                </div>
            ) : (
                <div className="mt-3">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <h4 className="h5 mb-0">{strings.configure_next_action}</h4>
                        <button type="button" className="btn btn-link btn-sm" onClick={() => setPickType(null)}>
                            {strings.changecondition}
                        </button>
                    </div>
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
                    ) : (
                        <p className="text-muted">{busy ? '…' : ''}</p>
                    )}
                </div>
            )}
        </div>
    );
}
