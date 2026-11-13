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
import Stepper from "./components/Stepper";
import { loadBootstrap } from "./services/api";
function Wizard({ ruleid = 0, returnurl }) {
  const [boot, setBoot] = useState(null);
  const [draft, setDraft] = useState(null);
  const [step, setStep] = useState("event");
  const [error, setError] = useState("");
  const [retryToken, setRetryToken] = useState(0);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError("");
    loadBootstrap(ruleid).then((payload) => {
      if (cancelled) {
        return;
      }
      setBoot(payload);
      setDraft(payload.draft);
    }).catch((err) => {
      if (!cancelled) {
        setError(err.message || "Failed to load wizard");
        setBoot(null);
        setDraft(null);
      }
    }).finally(() => {
      if (!cancelled) {
        setLoading(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [ruleid, retryToken]);
  const strings = boot?.strings || {};
  const whenSummary = useMemo(() => {
    if (!draft) {
      return "";
    }
    return draft.conditions.map((c) => c.description || c.conditiontype).join(" AND ");
  }, [draft]);
  if (loading) {
    return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-react-wizard", "aria-busy": "true", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "placeholder-glow", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "placeholder col-7 mb-3" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 68,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "placeholder col-12 mb-2" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 69,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "placeholder col-10 mb-2" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 70,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 67,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.react_wizard_loading || "Loading\u2026" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 72,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 66,
      columnNumber: 13
    }, this);
  }
  if (error || !boot || !draft) {
    return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-react-wizard", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", role: "alert", children: [
        /* @__PURE__ */ jsxDEV("p", { className: "mb-2", children: error || strings.react_wizard_error || "Could not load wizard" }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
          lineNumber: 81,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-outline-danger",
            onClick: () => setRetryToken((n) => n + 1),
            children: strings.retry || "Retry"
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
            lineNumber: 82,
            columnNumber: 21
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 80,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("a", { className: "btn btn-link px-0", href: returnurl, children: strings.cancelwizard || "Cancel" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 90,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 79,
      columnNumber: 13
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-react-wizard", children: [
    /* @__PURE__ */ jsxDEV("header", { className: "tool_murules-wizard-header mb-3", children: [
      /* @__PURE__ */ jsxDEV("h2", { className: "h3 mb-1", children: draft.id ? strings.editrule : strings.createrule }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 98,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "text-muted mb-0", children: strings.wizard_intro || strings.wizard_event_help }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
        lineNumber: 101,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 97,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(Stepper, { current: step, strings }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 106,
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
        lineNumber: 109,
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
        lineNumber: 119,
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
        lineNumber: 131,
        columnNumber: 17
      },
      this
    ) : null,
    /* @__PURE__ */ jsxDEV("div", { className: "mt-4", children: /* @__PURE__ */ jsxDEV("a", { className: "btn btn-link px-0", href: returnurl, children: strings.cancelwizard }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 142,
      columnNumber: 17
    }, this) }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
      lineNumber: 141,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/Wizard.tsx",
    lineNumber: 96,
    columnNumber: 9
  }, this);
}
__name(Wizard, "Wizard");
export {
  Wizard as default
};
//# sourceMappingURL=Wizard.dev.js.map
