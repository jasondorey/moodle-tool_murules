var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Event step: category accordion + multi-condition AND editors.
 *
 * @module tool_murules/components/EventStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { loadSchema, validateConfig } from "../services/api";
import SchemaForm from "./SchemaForm";
function EventStep({ catalog, conditions, strings, onChange, onContinue }) {
  const [openCat, setOpenCat] = useState(catalog[0]?.key || "");
  const [pickType, setPickType] = useState(null);
  const [schema, setSchema] = useState(null);
  const [values, setValues] = useState({});
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const [loadError, setLoadError] = useState("");
  useEffect(() => {
    if (!pickType) {
      setSchema(null);
      return;
    }
    let cancelled = false;
    setBusy(true);
    setLoadError("");
    loadSchema("condition", pickType).then((s) => {
      if (cancelled) {
        return;
      }
      setSchema(s);
      setValues({ ...s.defaults });
      setErrors({});
    }).catch((err) => {
      if (!cancelled) {
        setLoadError(err.message || strings.react_wizard_error || "Error");
      }
    }).finally(() => {
      if (!cancelled) {
        setBusy(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [pickType, strings.react_wizard_error]);
  const addCondition = /* @__PURE__ */ __name(async (andContinue) => {
    if (!pickType || !schema) {
      return;
    }
    setBusy(true);
    try {
      const result = await validateConfig("condition", pickType, values);
      if (!result.ok) {
        setErrors(result.errors);
        return;
      }
      const next = [
        ...conditions,
        {
          conditiontype: pickType,
          conditionconfig: result.config,
          description: result.description
        }
      ];
      onChange(next);
      setPickType(null);
      setSchema(null);
      setValues({});
      setErrors({});
      if (andContinue || conditions.length === 0) {
      }
      if (andContinue) {
        onContinue();
      }
    } finally {
      setBusy(false);
    }
  }, "addCondition");
  const removeAt = /* @__PURE__ */ __name((idx) => {
    onChange(conditions.filter((_, i) => i !== idx));
  }, "removeAt");
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-event-step", children: [
    /* @__PURE__ */ jsxDEV("h3", { className: "h4", children: strings.wizard_event }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 103,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.wizard_event_help || strings.pickcondition_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 104,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: strings.conditions_and_heading }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 106,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.conditions_and_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 107,
      columnNumber: 13
    }, this),
    conditions.length === 0 ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-secondary", children: strings.noconditionsyet }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 109,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("ul", { className: "list-group mb-3", children: conditions.map((cond, idx) => /* @__PURE__ */ jsxDEV(
        "li",
        {
          className: "list-group-item d-flex align-items-center justify-content-between gap-2",
          children: [
            /* @__PURE__ */ jsxDEV("div", { className: "me-auto", children: [
              /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary me-2", children: idx + 1 }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                lineNumber: 119,
                columnNumber: 37
              }, this),
              cond.description || cond.conditiontype
            ] }, void 0, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 118,
              columnNumber: 33
            }, this),
            /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "btn btn-outline-danger btn-sm",
                onClick: () => removeAt(idx),
                children: strings.removecondition
              },
              void 0,
              false,
              {
                fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                lineNumber: 122,
                columnNumber: 33
              },
              this
            )
          ]
        },
        `${cond.conditiontype}-${idx}`,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 114,
          columnNumber: 29
        },
        this
      )) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 112,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: onContinue, children: strings.continueactions }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 133,
        columnNumber: 25
      }, this) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 132,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 111,
      columnNumber: 17
    }, this),
    !pickType ? /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5 mt-3", children: conditions.length ? strings.configure_next_condition : strings.pickcondition_heading }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 142,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.pickcondition_help }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 147,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "accordion tool_murules-event-picker", id: "murules_react_picker", children: catalog.map((cat) => {
        const open = openCat === cat.key;
        return /* @__PURE__ */ jsxDEV("div", { className: "accordion-item", children: [
          /* @__PURE__ */ jsxDEV("h3", { className: "accordion-header h6 mb-0", children: /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: `accordion-button${open ? "" : " collapsed"}`,
              "aria-expanded": open,
              onClick: () => setOpenCat(open ? "" : cat.key),
              children: [
                /* @__PURE__ */ jsxDEV("span", { className: "tool_murules-picker-catname", children: cat.label }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 160,
                  columnNumber: 45
                }, this),
                /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary ms-2", children: cat.count }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 161,
                  columnNumber: 45
                }, this)
              ]
            },
            void 0,
            true,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 154,
              columnNumber: 41
            },
            this
          ) }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 153,
            columnNumber: 37
          }, this),
          /* @__PURE__ */ jsxDEV("div", { className: `accordion-collapse collapse${open ? " show" : ""}`, children: /* @__PURE__ */ jsxDEV("div", { className: "accordion-body p-0", children: /* @__PURE__ */ jsxDEV("ul", { className: "list-group list-group-flush", children: cat.items.map((item) => /* @__PURE__ */ jsxDEV("li", { className: "list-group-item p-0 border-0", children: /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 tool_murules-picker-item w-100 text-start",
              onClick: () => setPickType(item.type),
              children: [
                /* @__PURE__ */ jsxDEV("div", { className: "me-auto", children: [
                  /* @__PURE__ */ jsxDEV("div", { className: "fw-semibold", children: item.name }, void 0, false, {
                    fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                    lineNumber: 175,
                    columnNumber: 65
                  }, this),
                  /* @__PURE__ */ jsxDEV("div", { className: "small text-muted", children: strings.pickcondition_hint || "" }, void 0, false, {
                    fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                    lineNumber: 176,
                    columnNumber: 65
                  }, this)
                ] }, void 0, true, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 174,
                  columnNumber: 61
                }, this),
                /* @__PURE__ */ jsxDEV("span", { className: "btn btn-sm btn-outline-primary", children: strings.pickcondition }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 180,
                  columnNumber: 61
                }, this)
              ]
            },
            void 0,
            true,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 169,
              columnNumber: 57
            },
            this
          ) }, item.type, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 168,
            columnNumber: 53
          }, this)) }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 166,
            columnNumber: 45
          }, this) }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 165,
            columnNumber: 41
          }, this) }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 164,
            columnNumber: 37
          }, this)
        ] }, cat.key, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 152,
          columnNumber: 33
        }, this);
      }) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 148,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 141,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-condition-editor mt-3", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "d-flex align-items-center justify-content-between gap-2 mb-2", children: [
        /* @__PURE__ */ jsxDEV("h4", { className: "h5 mb-0", children: strings.configure_condition }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 197,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-link btn-sm",
            onClick: () => {
              setPickType(null);
              setSchema(null);
              setErrors({});
            },
            children: strings.changecondition
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 198,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 196,
        columnNumber: 21
      }, this),
      loadError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", children: loadError }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 210,
        columnNumber: 34
      }, this) : null,
      busy && !schema ? /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.loadingcms || "\u2026" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 211,
        columnNumber: 40
      }, this) : null,
      schema ? /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV(
          SchemaForm,
          {
            schema,
            values,
            errors,
            strings,
            onChange: setValues
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 214,
            columnNumber: 29
          },
          this
        ),
        /* @__PURE__ */ jsxDEV("div", { className: "d-flex flex-wrap gap-2", children: [
          /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "btn btn-primary",
              disabled: busy,
              onClick: () => addCondition(conditions.length === 0),
              children: conditions.length ? strings.addanothercondition : strings.saveandcontinue
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 222,
              columnNumber: 33
            },
            this
          ),
          !conditions.length ? /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "btn btn-outline-secondary",
              disabled: busy,
              onClick: () => addCondition(false),
              children: strings.addanothercondition
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 233,
              columnNumber: 37
            },
            this
          ) : null
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 221,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 213,
        columnNumber: 25
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 195,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
    lineNumber: 102,
    columnNumber: 9
  }, this);
}
__name(EventStep, "EventStep");
export {
  EventStep as default
};
//# sourceMappingURL=EventStep.dev.js.map
