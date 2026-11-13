/**
 * Event category dropdown + single category card (events list).
 *
 * @module tool_murules/components/CategoryCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useMemo, useState} from 'react';
import type {CatalogCategory} from '../types';

type Props = {
    catalog: CatalogCategory[];
    strings: Record<string, string>;
    onPick: (type: string) => void;
};

export default function CategoryCards({catalog, strings, onPick}: Props) {
    const [selectedKey, setSelectedKey] = useState('');
    const selected = useMemo(
        () => catalog.find((c) => c.key === selectedKey) || null,
        [catalog, selectedKey],
    );

    return (
        <div className="tool_murules-category-picker">
            <div className="mb-3">
                <label className="form-label" htmlFor="murules_event_category">
                    {strings.choose_category || 'Category'}
                </label>
                <select
                    id="murules_event_category"
                    className="form-select"
                    value={selectedKey}
                    onChange={(e) => setSelectedKey(e.target.value)}
                >
                    <option value="">{strings.choose_category_placeholder || strings.choosedots || '…'}</option>
                    {catalog.map((cat) => (
                        <option key={cat.key} value={cat.key}>
                            {cat.label} ({cat.count})
                        </option>
                    ))}
                </select>
            </div>

            {!selected ? (
                <div className="alert alert-secondary mb-0">
                    {strings.choose_category_help || 'Select a category to see available events.'}
                </div>
            ) : (
                <div className="card tool_murules-category-card is-open border-primary">
                    <div className="card-header d-flex align-items-center justify-content-between gap-2">
                        <span className="fw-semibold">{selected.label}</span>
                        <span className="badge text-bg-secondary">{selected.count}</span>
                    </div>
                    <ul className="list-group list-group-flush">
                        {selected.items.map((item) => (
                            <li className="list-group-item p-0" key={item.type}>
                                <button
                                    type="button"
                                    className="list-group-item list-group-item-action border-0 d-flex align-items-center justify-content-between gap-2 tool_murules-picker-item w-100 text-start"
                                    onClick={() => onPick(item.type)}
                                >
                                    <span>
                                        <span className="d-block fw-semibold">{item.name}</span>
                                        <span className="small text-muted">
                                            {strings.pickcondition_hint || ''}
                                        </span>
                                    </span>
                                    <span className="btn btn-sm btn-primary">
                                        {strings.pickcondition}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
