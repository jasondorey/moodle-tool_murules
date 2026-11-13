var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * React create/edit rule wizard (Event → Actions → Review).
 *
 * Mounted via data-react-component="@moodle/lms/tool_murules/Wizard".
 *
 * @module tool_murules/Wizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useMemo, useState } from "react";
import ActionsStep from "./components/ActionsStep";
import EventStep from "./components/EventStep";
import ReviewStep from "./components/ReviewStep";
import { loadBootstrap } from "./services/api";
const STEPS = ["event", "actions", "review"];
function Wizard({ ruleid = 0, returnurl }) {
  const [boot, setBoot] = useState(null);
  const [draft, setDraft] = useState(null);
  const [step, setStep] = useState("event");
  const [error, setError] = useState("");
  useEffect(() => {
    let cancelled = false;
    loadBootstrap(ruleid).then((payload) => {
      if (cancelled) {
        return;
      }
      setBoot(payload);
      setDraft(payload.draft);
    }).catch((err) => {
      if (!cancelled) {
        setError(err.message || "Failed to load wizard");
      }
    });
    return () => {
      cancelled = true;
    };
  }, [ruleid]);
  const strings = boot?.strings || {};
  const stepIndex = STEPS.indexOf(step);
  const whenSummary = useMemo(() => {
    if (!draft) {
      return "";
    }
    return draft.conditions.map((c) => c.description || c.conditiontype).join(" AND ");
  }, [draft]);
  if (error) {
    return /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", children: error }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 56,
      columnNumber: 16
    }, this);
  }
  if (!boot || !draft) {
    return /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.react_wizard_loading || "Loading\u2026" }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 59,
      columnNumber: 16
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-react-wizard", children: [
    /* @__PURE__ */ jsxDEV("nav", { className: "tool_murules-wizard-steps mb-3", "aria-label": strings.wizard_steps_label || "Wizard", children: [
      /* @__PURE__ */ jsxDEV("ul", { className: "nav nav-pills flex-wrap gap-1", children: STEPS.map((key, idx) => {
        let cls = "nav-link";
        if (key === step) {
          cls += " active";
        } else if (idx < stepIndex) {
          cls += " text-success";
        }
        return /* @__PURE__ */ jsxDEV("li", { className: "nav-item", children: /* @__PURE__ */ jsxDEV("span", { className: cls, "aria-current": key === step ? "step" : void 0, children: [
          /* @__PURE__ */ jsxDEV("span", { className: "badge text-bg-light text-dark me-1", "aria-hidden": "true", children: idx + 1 }, void 0, false, {
            fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
            lineNumber: 76,
            columnNumber: 37
          }, this),
          strings[`wizard_${key}`] || key
        ] }, void 0, true, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 75,
          columnNumber: 33
        }, this) }, key, false, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 74,
          columnNumber: 29
        }, this);
      }) }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 65,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "tool_murules-wizard-progress text-muted small mb-0", children: strings.wizard_progress ? strings.wizard_progress.replace("{$a->current}", String(stepIndex + 1)).replace("{$a->total}", String(STEPS.length)).replace("{$a->label}", strings[`wizard_${step}`] || step) : `Step ${stepIndex + 1} of ${STEPS.length}` }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 85,
        columnNumber: 17
      }, this),
      step === "event" ? /* @__PURE__ */ jsxDEV("p", { className: "tool_murules-wizard-next text-muted small mb-0", children: strings.wizard_next_actions }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 94,
        columnNumber: 21
      }, this) : null,
      step === "actions" ? /* @__PURE__ */ jsxDEV("p", { className: "tool_murules-wizard-next text-muted small mb-0", children: strings.wizard_next_review }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 97,
        columnNumber: 21
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 64,
      columnNumber: 13
    }, this),
    step === "event" ? /* @__PURE__ */ jsxDEV(
      EventStep,
      {
        catalog: boot.conditionCatalog,
        conditions: draft.conditions,
        strings,
        onChange: (conditions) => setDraft({ ...draft, conditions }),
        onContinue: () => setStep("actions")
      },
      void 0,
      false,
      {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 102,
        columnNumber: 17
      },
      this
    ) : null,
    step === "actions" ? /* @__PURE__ */ jsxDEV(
      ActionsStep,
      {
        catalog: boot.actionCatalog,
        actions: draft.actions,
        whenSummary,
        strings,
        onChange: (actions) => setDraft({ ...draft, actions }),
        onBack: () => setStep("event"),
        onContinue: () => setStep("review")
      },
      void 0,
      false,
      {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 112,
        columnNumber: 17
      },
      this
    ) : null,
    step === "review" ? /* @__PURE__ */ jsxDEV(
      ReviewStep,
      {
        draft,
        strings,
        actionLimits: boot.actionLimits,
        returnurl,
        onBack: () => setStep("actions"),
        onDraftChange: (patch) => setDraft({ ...draft, ...patch })
      },
      void 0,
      false,
      {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 124,
        columnNumber: 17
      },
      this
    ) : null,
    /* @__PURE__ */ jsxDEV("div", { className: "mt-4", children: /* @__PURE__ */ jsxDEV("a", { className: "btn btn-link px-0", href: returnurl, children: strings.cancelwizard }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 135,
      columnNumber: 17
    }, this) }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 134,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
    lineNumber: 63,
    columnNumber: 9
  }, this);
}
__name(Wizard, "Wizard");
export {
  Wizard as default
};
//# sourceMappingURL=Wizard.dev.js.map
