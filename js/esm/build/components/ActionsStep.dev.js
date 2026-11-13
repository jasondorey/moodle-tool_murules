var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Actions step: multi-action THEN editors.
 *
 * @module tool_murules/components/ActionsStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { loadSchema, validateConfig } from "../services/api";
import SchemaForm from "./SchemaForm";
function ActionsStep({
  catalog,
  actions,
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
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    if (!pickType) {
      setSchema(null);
      return;
    }
    let cancelled = false;
    setBusy(true);
    loadSchema("action", pickType).then((s) => {
      if (!cancelled) {
        setSchema(s);
        setValues({ ...s.defaults });
        setErrors({});
      }
    }).finally(() => {
      if (!cancelled) {
        setBusy(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [pickType]);
  const addAction = /* @__PURE__ */ __name(async (andContinue) => {
    if (!pickType || !schema) {
      return;
    }
    setBusy(true);
    try {
      const result = await validateConfig("action", pickType, values);
      if (!result.ok) {
        setErrors(result.errors);
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
    } finally {
      setBusy(false);
    }
  }, "addAction");
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-actions-step", children: [
    /* @__PURE__ */ jsxDEV("h3", { className: "h4", children: strings.wizard_actions }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 96,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.wizard_actions_help || strings.actions_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 97,
      columnNumber: 13
    }, this),
    whenSummary ? /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "small text-muted text-uppercase", children: strings.when }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 100,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-recap", children: whenSummary }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 101,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 99,
      columnNumber: 17
    }, this) : null,
    /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: strings.actions_heading }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 105,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted small", children: strings.actions_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 106,
      columnNumber: 13
    }, this),
    actions.length === 0 ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-secondary", children: strings.noactionsyet }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 108,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("ul", { className: "list-group mb-3", children: actions.map((act, idx) => /* @__PURE__ */ jsxDEV(
        "li",
        {
          className: "list-group-item d-flex align-items-center justify-content-between gap-2",
          children: [
            /* @__PURE__ */ jsxDEV("div", { className: "me-auto", children: [
              /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-secondary me-2", children: idx + 1 }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
                lineNumber: 118,
                columnNumber: 37
              }, this),
              act.description || act.actiontype
            ] }, void 0, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 117,
              columnNumber: 33
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
                lineNumber: 121,
                columnNumber: 33
              },
              this
            )
          ]
        },
        `${act.actiontype}-${idx}`,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 113,
          columnNumber: 29
        },
        this
      )) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 111,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mb-3 d-flex flex-wrap gap-2", children: [
        /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: onContinue, children: strings.continuereview }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 132,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-outline-secondary", onClick: onBack, children: strings.back }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 135,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 131,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 110,
      columnNumber: 17
    }, this),
    !pickType ? /* @__PURE__ */ jsxDEV("div", { className: "mt-3", children: [
      /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: actions.length ? strings.configure_next_action : strings.select_action }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 144,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "list-group tool_murules-action-picker", children: catalog.map((item) => /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "list-group-item list-group-item-action d-flex justify-content-between align-items-center",
          onClick: () => setPickType(item.type),
          children: [
            /* @__PURE__ */ jsxDEV("span", { children: item.name }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 155,
              columnNumber: 33
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "btn btn-sm btn-outline-primary", children: strings.pickcondition }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 156,
              columnNumber: 33
            }, this)
          ]
        },
        item.type,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 149,
          columnNumber: 29
        },
        this
      )) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 147,
        columnNumber: 21
      }, this),
      !actions.length ? /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link mt-3 px-0", onClick: onBack, children: strings.back }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 161,
        columnNumber: 25
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 143,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("div", { className: "mt-3", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "d-flex justify-content-between align-items-center mb-2", children: [
        /* @__PURE__ */ jsxDEV("h4", { className: "h5 mb-0", children: strings.configure_next_action }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 169,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link btn-sm", onClick: () => setPickType(null), children: strings.changecondition }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 170,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 168,
        columnNumber: 21
      }, this),
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
            fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
            lineNumber: 176,
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
              onClick: () => addAction(actions.length === 0),
              children: actions.length ? strings.addanotheraction : strings.saveandcontinue
            },
            void 0,
            false,
            {
              fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
              lineNumber: 184,
              columnNumber: 33
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
              lineNumber: 193,
              columnNumber: 37
            },
            this
          ) : null
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
          lineNumber: 183,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 175,
        columnNumber: 25
      }, this) : /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: busy ? "\u2026" : "" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
        lineNumber: 205,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
      lineNumber: 167,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/ActionsStep.tsx",
    lineNumber: 95,
    columnNumber: 9
  }, this);
}
__name(ActionsStep, "ActionsStep");
export {
  ActionsStep as default
};
//# sourceMappingURL=ActionsStep.dev.js.map
