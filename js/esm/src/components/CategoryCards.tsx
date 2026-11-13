/**
 * Category cards for the Event picker (Connect-like, Bootstrap cards).
 *
 * @module tool_murules/components/CategoryCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useState} from 'react';
import type {CatalogCategory} from '../types';

type Props = {
    catalog: CatalogCategory[];
    strings: Record<string, string>;
    onPick: (type: string) => void;
};

export default function CategoryCards({catalog, strings, onPick}: Props) {
    const [openKey, setOpenKey] = useState(catalog[0]?.key || '');

    return (
        <div className="tool_murules-category-cards row g-3">
            {catalog.map((cat) => {
                const open = openKey === cat.key;
                return (
                    <div className="col-12 col-md-6" key={cat.key}>
                        <div className={`card h-100 tool_murules-category-card${open ? ' is-open border-primary' : ''}`}>
                            <button
                                type="button"
                                className="card-header tool_murules-category-card-header btn text-start w-100 d-flex align-items-center justify-content-between gap-2"
                                aria-expanded={open}
                                onClick={() => setOpenKey(open ? '' : cat.key)}
                            >
                                <span className="fw-semibold">{cat.label}</span>
                                <span className="badge text-bg-secondary">{cat.count}</span>
                            </button>
                            {open ? (
                                <ul className="list-group list-group-flush">
                                    {cat.items.map((item) => (
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
                            ) : (
                                <div className="card-body py-2">
                                    <p className="card-text small text-muted mb-0">
                                        {strings.category_card_hint || 'Expand to choose an event'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
