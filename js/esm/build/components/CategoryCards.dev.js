var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Category cards for the Event picker (Connect-like, Bootstrap cards).
 *
 * @module tool_murules/components/CategoryCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useState } from "react";
function CategoryCards({ catalog, strings, onPick }) {
  const [openKey, setOpenKey] = useState(catalog[0]?.key || "");
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-category-cards row g-3", children: catalog.map((cat) => {
    const open = openKey === cat.key;
    return /* @__PURE__ */ jsxDEV("div", { className: "col-12 col-md-6", children: /* @__PURE__ */ jsxDEV("div", { className: `card h-100 tool_murules-category-card${open ? " is-open border-primary" : ""}`, children: [
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "card-header tool_murules-category-card-header btn text-start w-100 d-flex align-items-center justify-content-between gap-2",
          "aria-expanded": open,
          onClick: () => setOpenKey(open ? "" : cat.key),
          children: [
            /* @__PURE__ */ jsxDEV("span", { className: "fw-semibold", children: cat.label }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 33,
              columnNumber: 33
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary", children: cat.count }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 34,
              columnNumber: 33
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 27,
          columnNumber: 29
        },
        this
      ),
      open ? /* @__PURE__ */ jsxDEV("ul", { className: "list-group list-group-flush", children: cat.items.map((item) => /* @__PURE__ */ jsxDEV("li", { className: "list-group-item p-0", children: /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "list-group-item list-group-item-action border-0 d-flex align-items-center justify-content-between gap-2 tool_murules-picker-item w-100 text-start",
          onClick: () => onPick(item.type),
          children: [
            /* @__PURE__ */ jsxDEV("span", { children: [
              /* @__PURE__ */ jsxDEV("span", { className: "d-block fw-semibold", children: item.name }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
                lineNumber: 46,
                columnNumber: 53
              }, this),
              /* @__PURE__ */ jsxDEV("span", { className: "small text-muted", children: strings.pickcondition_hint || "" }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
                lineNumber: 47,
                columnNumber: 53
              }, this)
            ] }, void 0, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 45,
              columnNumber: 49
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "btn btn-sm btn-primary", children: strings.pickcondition }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 51,
              columnNumber: 49
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 40,
          columnNumber: 45
        },
        this
      ) }, item.type, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 39,
        columnNumber: 41
      }, this)) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 37,
        columnNumber: 33
      }, this) : /* @__PURE__ */ jsxDEV("div", { className: "card-body py-2", children: /* @__PURE__ */ jsxDEV("p", { className: "card-text small text-muted mb-0", children: strings.category_card_hint || "Expand to choose an event" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 60,
        columnNumber: 37
      }, this) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 59,
        columnNumber: 33
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
      lineNumber: 26,
      columnNumber: 25
    }, this) }, cat.key, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
      lineNumber: 25,
      columnNumber: 21
    }, this);
  }) }, void 0, false, {
    fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
    lineNumber: 21,
    columnNumber: 9
  }, this);
}
__name(CategoryCards, "CategoryCards");
export {
  CategoryCards as default
};
//# sourceMappingURL=CategoryCards.dev.js.map
