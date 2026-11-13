/**
 * Schema-driven config form (Formslib replacement for the wizard).
 *
 * @module tool_murules/components/SchemaForm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useMemo, useState, type ReactNode} from 'react';
import {loadCourseModules, searchCourses} from '../services/api';
import type {EditorField, EditorSchema, FieldShowIf} from '../types';

type Props = {
    schema: EditorSchema;
    values: Record<string, unknown>;
    errors?: Record<string, string>;
    strings: Record<string, string>;
    onChange: (next: Record<string, unknown>) => void;
};

function visible(field: EditorField, values: Record<string, unknown>): boolean {
    const showif = field.showif as FieldShowIf | undefined;
    if (!showif) {
        return true;
    }
    const current = String(values[showif.field] ?? '');
    if (showif.equals !== undefined) {
        return current === showif.equals;
    }
    if (showif.in) {
        return showif.in.includes(current);
    }
    if (showif.notin) {
        return !showif.notin.includes(current);
    }
    return true;
}

function toDatetimeLocal(ts: unknown): string {
    const n = Number(ts) || 0;
    if (!n) {
        return '';
    }
    const d = new Date(n * 1000);
    const pad = (x: number) => String(x).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fromDatetimeLocal(value: string): number {
    if (!value) {
        return 0;
    }
    const ms = Date.parse(value);
    return Number.isFinite(ms) ? Math.floor(ms / 1000) : 0;
}

export default function SchemaForm({schema, values, errors = {}, strings, onChange}: Props) {
    const [courseOptions, setCourseOptions] = useState<Array<{id: number; name: string}>>([]);
    const [courseQuery, setCourseQuery] = useState('');
    const [cmsByField, setCmsByField] = useState<Record<string, Array<{id: number; name: string}>>>({});

    const courseFields = useMemo(
        () => schema.fields.filter((f) => f.type === 'course'),
        [schema.fields],
    );
    const cmFields = useMemo(
        () => schema.fields.filter((f) => f.type === 'coursemodule'),
        [schema.fields],
    );

    useEffect(() => {
        let cancelled = false;
        const preferred = courseFields.length ? Number(values[courseFields[0].name] || 0) : 0;
        searchCourses(courseQuery, preferred).then((courses) => {
            if (!cancelled) {
                setCourseOptions(courses);
            }
        }).catch(() => {
            if (!cancelled) {
                setCourseOptions([]);
            }
        });
        return () => {
            cancelled = true;
        };
    }, [courseQuery, courseFields, values]);

    useEffect(() => {
        let cancelled = false;
        Promise.all(
            cmFields.map(async (field) => {
                const courseid = Number(values[field.dependsOn || 'courseid'] || 0);
                const mode = field.mode === 'quiz' ? 'quiz' : 'activity';
                const cms = await loadCourseModules(courseid, mode);
                return [field.name, cms] as const;
            }),
        ).then((entries) => {
            if (!cancelled) {
                setCmsByField(Object.fromEntries(entries));
            }
        }).catch(() => {
            if (!cancelled) {
                setCmsByField({});
            }
        });
        return () => {
            cancelled = true;
        };
    }, [cmFields, values]);

    const setValue = (name: string, value: unknown) => {
        const next = {...values, [name]: value};
        // Reset dependent CM when course changes.
        cmFields.forEach((field) => {
            if (field.dependsOn === name) {
                next[field.name] = 0;
            }
        });
        onChange(next);
    };

    return (
        <div className="tool_murules-schema-form">
            {schema.fields.map((field) => {
                if (!visible(field, values)) {
                    return null;
                }
                const err = errors[field.name];
                const id = `murules_field_${field.name}`;
                const commonLabel = (
                    <label className="form-label" htmlFor={id}>
                        {field.label}
                        {field.required ? ' *' : ''}
                    </label>
                );

                let control: ReactNode = null;
                if (field.type === 'course') {
                    control = (
                        <div className="d-flex flex-column gap-2">
                            <input
                                type="search"
                                className="form-control form-control-sm"
                                placeholder={strings.search_courses || 'Course'}
                                value={courseQuery}
                                onChange={(e) => setCourseQuery(e.target.value)}
                                aria-label={strings.search_courses || 'Course'}
                            />
                            <select
                                id={id}
                                className={`form-select${err ? ' is-invalid' : ''}`}
                                value={String(values[field.name] ?? 0)}
                                onChange={(e) => setValue(field.name, Number(e.target.value) || 0)}
                            >
                                <option value="0">{strings.choosedots || '…'}</option>
                                {courseOptions.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>
                    );
                } else if (field.type === 'coursemodule') {
                    const cms = cmsByField[field.name] || [];
                    control = (
                        <select
                            id={id}
                            className={`form-select${err ? ' is-invalid' : ''}`}
                            value={String(values[field.name] ?? 0)}
                            onChange={(e) => setValue(field.name, Number(e.target.value) || 0)}
                            disabled={!Number(values[field.dependsOn || 'courseid'] || 0)}
                        >
                            <option value="0">
                                {cms.length ? (strings.choosedots || '…') : (strings.nocms || 'No activities')}
                            </option>
                            {cms.map((cm) => (
                                <option key={cm.id} value={cm.id}>{cm.name}</option>
                            ))}
                        </select>
                    );
                } else if (field.type === 'select') {
                    control = (
                        <select
                            id={id}
                            className={`form-select${err ? ' is-invalid' : ''}`}
                            value={String(values[field.name] ?? '')}
                            onChange={(e) => setValue(field.name, e.target.value)}
                        >
                            {(field.options || []).map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    );
                } else if (field.type === 'textarea') {
                    control = (
                        <textarea
                            id={id}
                            className={`form-control${err ? ' is-invalid' : ''}`}
                            rows={5}
                            value={String(values[field.name] ?? '')}
                            onChange={(e) => setValue(field.name, e.target.value)}
                        />
                    );
                } else if (field.type === 'datetime') {
                    control = (
                        <input
                            id={id}
                            type="datetime-local"
                            className={`form-control${err ? ' is-invalid' : ''}`}
                            value={toDatetimeLocal(values[field.name])}
                            onChange={(e) => setValue(field.name, fromDatetimeLocal(e.target.value))}
                        />
                    );
                } else if (field.type === 'number') {
                    control = (
                        <input
                            id={id}
                            type="number"
                            className={`form-control${err ? ' is-invalid' : ''}`}
                            value={values[field.name] === undefined || values[field.name] === null
                                ? ''
                                : String(values[field.name])}
                            onChange={(e) => setValue(
                                field.name,
                                e.target.value === '' ? '' : Number(e.target.value),
                            )}
                        />
                    );
                } else {
                    control = (
                        <input
                            id={id}
                            type="text"
                            className={`form-control${err ? ' is-invalid' : ''}`}
                            value={String(values[field.name] ?? '')}
                            onChange={(e) => setValue(field.name, e.target.value)}
                        />
                    );
                }

                return (
                    <div className="mb-3" key={field.name}>
                        {commonLabel}
                        {control}
                        {err ? <div className="invalid-feedback d-block">{err}</div> : null}
                    </div>
                );
            })}
        </div>
    );
}
