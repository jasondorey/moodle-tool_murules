var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Action picker cards (Connect-like THEN chooser).
 *
 * @module tool_murules/components/ActionCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function ActionCards({ catalog, strings, onPick }) {
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-action-cards row g-3", children: catalog.map((item) => /* @__PURE__ */ jsxDEV("div", { className: "col-12 col-md-6", children: /* @__PURE__ */ jsxDEV(
    "button",
    {
      type: "button",
      className: "card h-100 w-100 text-start tool_murules-action-card btn p-0",
      onClick: () => onPick(item.type),
      children: /* @__PURE__ */ jsxDEV("div", { className: "card-body d-flex align-items-center justify-content-between gap-2", children: [
        /* @__PURE__ */ jsxDEV("div", { children: [
          /* @__PURE__ */ jsxDEV("div", { className: "fw-semibold", children: item.name }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
            lineNumber: 28,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV("div", { className: "small text-muted", children: strings.pickaction_hint || strings.pickcondition_hint || "" }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
            lineNumber: 29,
            columnNumber: 33
          }, this)
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
          lineNumber: 27,
          columnNumber: 29
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "btn btn-sm btn-outline-primary", children: strings.pickaction || strings.pickcondition }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
          lineNumber: 33,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
        lineNumber: 26,
        columnNumber: 25
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
      lineNumber: 21,
      columnNumber: 21
    },
    this
  ) }, item.type, false, {
    fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
    lineNumber: 20,
    columnNumber: 17
  }, this)) }, void 0, false, {
    fileName: "public/admin/tool/murules/js/esm/src/components/ActionCards.tsx",
    lineNumber: 18,
    columnNumber: 9
  }, this);
}
__name(ActionCards, "ActionCards");
export {
  ActionCards as default
};
//# sourceMappingURL=ActionCards.dev.js.map
