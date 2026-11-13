var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Event step: category cards + multi-condition AND editors.
 *
 * @module tool_murules/components/EventStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { loadSchema, validateConfig } from "../services/api";
import CategoryCards from "./CategoryCards";
import SchemaForm from "./SchemaForm";
function EventStep({ catalog, conditions, strings, onChange, onContinue }) {
  const [pickType, setPickType] = useState(null);
  const [schema, setSchema] = useState(null);
  const [values, setValues] = useState({});
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [busy, setBusy] = useState(false);
  const [loadError, setLoadError] = useState("");
  const [schemaRetry, setSchemaRetry] = useState(0);
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
      setFormError("");
    }).catch((err) => {
      if (!cancelled) {
        setSchema(null);
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
  }, [pickType, schemaRetry, strings.react_wizard_error]);
  const addCondition = /* @__PURE__ */ __name(async (andContinue) => {
    if (!pickType || !schema) {
      return;
    }
    setBusy(true);
    setFormError("");
    try {
      const result = await validateConfig("condition", pickType, values);
      if (!result.ok) {
        setErrors(result.errors);
        setFormError(strings.fix_has_errors || "Fix the highlighted fields and try again.");
        return;
      }
      onChange([
        ...conditions,
        {
          conditiontype: pickType,
          conditionconfig: result.config,
          description: result.description
        }
      ]);
      setPickType(null);
      setSchema(null);
      setValues({});
      setErrors({});
      if (andContinue) {
        onContinue();
      }
    } catch (err) {
      setFormError(err instanceof Error ? err.message : String(err));
    } finally {
      setBusy(false);
    }
  }, "addCondition");
  const removeAt = /* @__PURE__ */ __name((idx) => {
    onChange(conditions.filter((_, i) => i !== idx));
  }, "removeAt");
  const pickedName = catalog.flatMap((c) => c.items).find((i) => i.type === pickType)?.name;
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-event-step", children: [
    /* @__PURE__ */ jsxDEV("h3", { className: "h4", children: strings.wizard_event }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 111,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.wizard_event_help || strings.pickcondition_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 112,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("section", { className: "tool_murules-saved-block mb-4", "aria-label": strings.conditions_and_heading, children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: strings.conditions_and_heading }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 115,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.conditions_and_help }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 116,
        columnNumber: 17
      }, this),
      conditions.length === 0 ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-secondary mb-0", children: strings.noconditionsyet }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 118,
        columnNumber: 21
      }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV("ul", { className: "list-group mb-3 tool_murules-saved-list", children: conditions.map((cond, idx) => /* @__PURE__ */ jsxDEV(
          "li",
          {
            className: "list-group-item d-flex align-items-center justify-content-between gap-2",
            children: [
              /* @__PURE__ */ jsxDEV("div", { className: "me-auto", children: [
                /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-primary me-2", children: "AND" }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 128,
                  columnNumber: 41
                }, this),
                /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary me-2", children: idx + 1 }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                  lineNumber: 129,
                  columnNumber: 41
                }, this),
                cond.description || cond.conditiontype
              ] }, void 0, true, {
                fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
                lineNumber: 127,
                columnNumber: 37
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
                  lineNumber: 132,
                  columnNumber: 37
                },
                this
              )
            ]
          },
          `${cond.conditiontype}-${idx}`,
          true,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 123,
            columnNumber: 33
          },
          this
        )) }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 121,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: onContinue, children: strings.continueactions }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 142,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 120,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 114,
      columnNumber: 13
    }, this),
    !pickType ? /* @__PURE__ */ jsxDEV("section", { "aria-label": strings.pickcondition_heading, children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: conditions.length ? strings.configure_next_condition : strings.pickcondition_heading }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 151,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.pickcondition_help }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 156,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(CategoryCards, { catalog, strings, onPick: setPickType }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 157,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 150,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("section", { className: "tool_murules-condition-editor card border-primary", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "card-header d-flex align-items-center justify-content-between gap-2", children: [
        /* @__PURE__ */ jsxDEV("div", { children: [
          /* @__PURE__ */ jsxDEV("h4", { className: "h5 mb-0", children: strings.configure_condition }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 163,
            columnNumber: 29
          }, this),
          pickedName ? /* @__PURE__ */ jsxDEV("div", { className: "small text-muted", children: pickedName }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 164,
            columnNumber: 43
          }, this) : null
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 162,
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
              setFormError("");
              setLoadError("");
            },
            children: strings.changecondition
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 166,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 161,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "card-body", children: [
        loadError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", role: "alert", children: [
          /* @__PURE__ */ jsxDEV("p", { className: "mb-2", children: loadError }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 183,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "btn btn-sm btn-outline-danger",
              onClick: () => setSchemaRetry((n) => n + 1),
              children: strings.retry || "Retry"
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 184,
              columnNumber: 33
            },
            this
          )
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 182,
          columnNumber: 29
        }, this) : null,
        busy && !schema ? /* @__PURE__ */ jsxDEV("p", { className: "text-muted mb-0", children: strings.loading_editor || strings.loadingcms || "\u2026" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 194,
          columnNumber: 29
        }, this) : null,
        formError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning", role: "alert", children: formError }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 196,
          columnNumber: 38
        }, this) : null,
        schema ? /* @__PURE__ */ jsxDEV(Fragment, { children: [
          /* @__PURE__ */ jsxDEV(
            SchemaForm,
            {
              schema,
              values,
              errors,
              strings,
              onChange: (next) => {
                setValues(next);
                setFormError("");
              }
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
              lineNumber: 199,
              columnNumber: 33
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
                lineNumber: 210,
                columnNumber: 37
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
                lineNumber: 221,
                columnNumber: 41
              },
              this
            ) : null
          ] }, void 0, true, {
            fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
            lineNumber: 209,
            columnNumber: 33
          }, this)
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
          lineNumber: 198,
          columnNumber: 29
        }, this) : null
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
        lineNumber: 180,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
      lineNumber: 160,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/EventStep.tsx",
    lineNumber: 110,
    columnNumber: 9
  }, this);
}
__name(EventStep, "EventStep");
export {
  EventStep as default
};
//# sourceMappingURL=EventStep.dev.js.map
