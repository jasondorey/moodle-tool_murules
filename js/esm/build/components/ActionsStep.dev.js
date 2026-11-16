var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Actions step: card picker + multi-action THEN editors.
 *
 * @module tool_murules/components/ActionsStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useMemo, useState } from "react";
import { loadSchema, validateConfig } from "../services/api";
import ActionCards from "./ActionCards";
import SchemaForm from "./SchemaForm";
function ActionsStep({
  catalog,
  actions,
  conditions = [],
  whenSummary,
  strings,
  onChange,
  onBack,
  onContinue
}) {
  const [pickType, setPickType] = useState(null);
  const [schema, setSchema] = useState(null);
  const [values, setValues] = useState({});
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [busy, setBusy] = useState(false);
  const [loadError, setLoadError] = useState("");
  const [schemaRetry, setSchemaRetry] = useState(0);
  const tenantId = useMemo(() => {
    const tenantCondition = conditions.find(
      (c) => c.conditiontype === "tenant_member" || c.conditiontype === "user_first_auth_tenant"
    );
    return Number(tenantCondition?.conditionconfig?.tenantid || 0);
  }, [conditions]);
  useEffect(() => {
    if (!pickType) {
      setSchema(null);
      return;
    }
    let cancelled = false;
    setBusy(true);
    setLoadError("");
    loadSchema("action", pickType).then((s) => {
      if (!cancelled) {
        setSchema(s);
        setValues({ ...s.defaults });
        setErrors({});
        setFormError("");
      }
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
  const addAction = /* @__PURE__ */ __name(async (andContinue) => {
    if (!pickType || !schema) {
      return;
    }
    setBusy(true);
    setFormError("");
    try {
      const result = await validateConfig("action", pickType, values);
      if (!result.ok) {
        setErrors(result.errors);
        setFormError(strings.form_has_errors || "Fix the highlighted fields and try again.");
        return;
      }
      onChange([
        ...actions,
        {
          actiontype: pickType,
          actionconfig: result.config,
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
  }, "addAction");
  const pickedName = catalog.find((i) => i.type === pickType)?.name;
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-actions-step", children: [
    /* @__PURE__ */ jsxDEV("h3", { className: "h4", children: strings.wizard_actions }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 125,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.wizard_actions_help || strings.actions_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 126,
      columnNumber: 13
    }, this),
    whenSummary ? /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "small text-muted text-uppercase", children: strings.when }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 129,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-recap", children: whenSummary }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 130,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 128,
      columnNumber: 17
    }, this) : null,
    /* @__PURE__ */ jsxDEV("section", { className: "tool_murules-saved-block mb-4", "aria-label": strings.actions_heading, children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: strings.actions_heading }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 135,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.actions_help }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 136,
        columnNumber: 17
      }, this),
      actions.length === 0 ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-secondary mb-0", children: strings.noactionsyet }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 138,
        columnNumber: 21
      }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV("ul", { className: "list-group mb-3 tool_murules-saved-list", children: actions.map((act, idx) => /* @__PURE__ */ jsxDEV(
          "li",
          {
            className: "list-group-item d-flex align-items-center justify-content-between gap-2",
            children: [
              /* @__PURE__ */ jsxDEV("div", { className: "me-auto", children: [
                /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-primary me-2", children: "THEN" }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                  lineNumber: 148,
                  columnNumber: 41
                }, this),
                /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary me-2", children: idx + 1 }, void 0, false, {
                  fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                  lineNumber: 149,
                  columnNumber: 41
                }, this),
                act.description || act.actiontype
              ] }, void 0, true, {
                fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                lineNumber: 147,
                columnNumber: 37
              }, this),
              /* @__PURE__ */ jsxDEV(
                "button",
                {
                  type: "button",
                  className: "btn btn-outline-danger btn-sm",
                  onClick: () => onChange(actions.filter((_, i) => i !== idx)),
                  children: strings.removeaction
                },
                void 0,
                false,
                {
                  fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                  lineNumber: 152,
                  columnNumber: 37
                },
                this
              )
            ]
          },
          `${act.actiontype}-${idx}`,
          true,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 143,
            columnNumber: 33
          },
          this
        )) }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 141,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "d-flex flex-wrap gap-2", children: [
          /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: onContinue, children: strings.continuereview }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 163,
            columnNumber: 29
          }, this),
          /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-outline-secondary", onClick: onBack, children: strings.back }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 166,
            columnNumber: 29
          }, this)
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 162,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 140,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 134,
      columnNumber: 13
    }, this),
    !pickType ? /* @__PURE__ */ jsxDEV("section", { "aria-label": strings.select_action, children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: actions.length ? strings.configure_next_action : strings.select_action }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 176,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.pickaction_help || "" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 179,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(ActionCards, { catalog, strings, onPick: setPickType }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 180,
        columnNumber: 21
      }, this),
      !actions.length ? /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link mt-3 px-0", onClick: onBack, children: strings.back }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 182,
        columnNumber: 25
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 175,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("section", { className: "card border-primary", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "card-header d-flex justify-content-between align-items-center gap-2", children: [
        /* @__PURE__ */ jsxDEV("div", { children: [
          /* @__PURE__ */ jsxDEV("h4", { className: "h5 mb-0", children: strings.configure_action || strings.configure_next_action }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 191,
            columnNumber: 29
          }, this),
          pickedName ? /* @__PURE__ */ jsxDEV("div", { className: "small text-muted", children: pickedName }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 192,
            columnNumber: 43
          }, this) : null
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 190,
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
            children: strings.changeaction || strings.changecondition
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 194,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 189,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "card-body", children: [
        loadError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", role: "alert", children: [
          /* @__PURE__ */ jsxDEV("p", { className: "mb-2", children: loadError }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 211,
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
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 212,
              columnNumber: 33
            },
            this
          )
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 210,
          columnNumber: 29
        }, this) : null,
        busy && !schema ? /* @__PURE__ */ jsxDEV("p", { className: "text-muted mb-0", children: strings.loading_editor || "\u2026" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 222,
          columnNumber: 29
        }, this) : null,
        formError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning", role: "alert", children: formError }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 224,
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
              tenantId: pickType === "enrol_course" ? tenantId : 0,
              onChange: (next) => {
                setValues(next);
                setFormError("");
              }
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 227,
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
                onClick: () => addAction(actions.length === 0),
                children: actions.length ? strings.addanotheraction : strings.saveandcontinue
              },
              void 0,
              false,
              {
                fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                lineNumber: 239,
                columnNumber: 37
              },
              this
            ),
            !actions.length ? /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "btn btn-outline-secondary",
                disabled: busy,
                onClick: () => addAction(false),
                children: strings.addanotheraction
              },
              void 0,
              false,
              {
                fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                lineNumber: 248,
                columnNumber: 41
              },
              this
            ) : null
          ] }, void 0, true, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 238,
            columnNumber: 33
          }, this)
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 226,
          columnNumber: 29
        }, this) : null
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 208,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 188,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
    lineNumber: 124,
    columnNumber: 9
  }, this);
}
__name(ActionsStep, "ActionsStep");
export {
  ActionsStep as default
};
//# sourceMappingURL=ActionsStep.dev.js.map
