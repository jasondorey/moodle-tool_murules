var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Event category dropdown + single category card (events list).
 *
 * @module tool_murules/components/CategoryCards
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useMemo, useState } from "react";
function CategoryCards({ catalog, strings, onPick }) {
  const [selectedKey, setSelectedKey] = useState("");
  const selected = useMemo(
    () => catalog.find((c) => c.key === selectedKey) || null,
    [catalog, selectedKey]
  );
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-category-picker", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("label", { className: "form-label", htmlFor: "murules_event_category", children: strings.choose_category || "Category" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 27,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV(
        "select",
        {
          id: "murules_event_category",
          className: "form-select",
          value: selectedKey,
          onChange: (e) => setSelectedKey(e.target.value),
          children: [
            /* @__PURE__ */ jsxDEV("option", { value: "", children: strings.choose_category_placeholder || strings.choosedots || "\u2026" }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 36,
              columnNumber: 21
            }, this),
            catalog.map((cat) => /* @__PURE__ */ jsxDEV("option", { value: cat.key, children: [
              cat.label,
              " (",
              cat.count,
              ")"
            ] }, cat.key, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 38,
              columnNumber: 25
            }, this))
          ]
        },
        void 0,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 30,
          columnNumber: 17
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
      lineNumber: 26,
      columnNumber: 13
    }, this),
    !selected ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-secondary mb-0", children: strings.choose_category_help || "Select a category to see available events." }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
      lineNumber: 46,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("div", { className: "card tool_murules-category-card is-open border-primary", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "card-header d-flex align-items-center justify-content-between gap-2", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "fw-semibold", children: selected.label }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 52,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary", children: selected.count }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 53,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 51,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("ul", { className: "list-group list-group-flush", children: selected.items.map((item) => /* @__PURE__ */ jsxDEV("li", { className: "list-group-item p-0", children: /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "list-group-item list-group-item-action border-0 d-flex align-items-center justify-content-between gap-2 tool_murules-picker-item w-100 text-start",
          onClick: () => onPick(item.type),
          children: [
            /* @__PURE__ */ jsxDEV("span", { children: [
              /* @__PURE__ */ jsxDEV("span", { className: "d-block fw-semibold", children: item.name }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
                lineNumber: 64,
                columnNumber: 41
              }, this),
              /* @__PURE__ */ jsxDEV("span", { className: "small text-muted", children: strings.pickcondition_hint || "" }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
                lineNumber: 65,
                columnNumber: 41
              }, this)
            ] }, void 0, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 63,
              columnNumber: 37
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "btn btn-sm btn-primary", children: strings.pickcondition }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
              lineNumber: 69,
              columnNumber: 37
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
          lineNumber: 58,
          columnNumber: 33
        },
        this
      ) }, item.type, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 57,
        columnNumber: 29
      }, this)) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
        lineNumber: 55,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
      lineNumber: 50,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/CategoryCards.tsx",
    lineNumber: 25,
    columnNumber: 9
  }, this);
}
__name(CategoryCards, "CategoryCards");
export {
  CategoryCards as default
};
//# sourceMappingURL=CategoryCards.dev.js.map
