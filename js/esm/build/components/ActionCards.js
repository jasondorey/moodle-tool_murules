import{jsx as t,jsxs as o}from"react/jsx-runtime";/**
 * Action picker cards (Connect-like THEN chooser).
 *
 * @module tool_murules/components/ActionCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function e({catalog:n,strings:a,onPick:c}){return t("div",{className:"tool_murules-action-cards row g-3",children:n.map(i=>t("div",{className:"col-12 col-md-6",children:t("button",{type:"button",className:"card h-100 w-100 text-start tool_murules-action-card btn p-0",onClick:()=>c(i.type),children:o("div",{className:"card-body d-flex align-items-center justify-content-between gap-2",children:[o("div",{children:[t("div",{className:"fw-semibold",children:i.name}),t("div",{className:"small text-muted",children:a.pickaction_hint||a.pickcondition_hint||""})]}),t("span",{className:"btn btn-sm btn-outline-primary",children:a.pickaction||a.pickcondition})]})})},i.type))})}export{e as default};
