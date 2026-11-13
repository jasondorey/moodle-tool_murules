/**
 * Action picker cards (Connect-like THEN chooser).
 *
 * @module tool_murules/components/ActionCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {CatalogAction} from '../types';

type Props = {
    catalog: CatalogAction[];
    strings: Record<string, string>;
    onPick: (type: string) => void;
};

export default function ActionCards({catalog, strings, onPick}: Props) {
    return (
        <div className="tool_murules-action-cards row g-3">
            {catalog.map((item) => (
                <div className="col-12 col-md-6" key={item.type}>
                    <button
                        type="button"
                        className="card h-100 w-100 text-start tool_murules-action-card btn p-0"
                        onClick={() => onPick(item.type)}
                    >
                        <div className="card-body d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <div className="fw-semibold">{item.name}</div>
                                <div className="small text-muted">
                                    {strings.pickaction_hint || strings.pickcondition_hint || ''}
                                </div>
                            </div>
                            <span className="btn btn-sm btn-outline-primary">
                                {strings.pickaction || strings.pickcondition}
                            </span>
                        </div>
                    </button>
                </div>
            ))}
        </div>
    );
}
