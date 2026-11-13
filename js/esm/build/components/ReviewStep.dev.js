var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Review + save step for the React wizard.
 *
 * @module tool_murules/components/ReviewStep
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { estimateMatches, saveRule } from "../services/api";
function ReviewStep({
  draft,
  strings,
  actionLimits,
  returnurl,
  onBack,
  onDraftChange
}) {
  const [estimate, setEstimate] = useState("");
  const [estimateLoading, setEstimateLoading] = useState(false);
  const [estimateError, setEstimateError] = useState("");
  const [estimateRetry, setEstimateRetry] = useState(0);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [nameError, setNameError] = useState("");
  const whenSummary = draft.conditions.map((c) => c.description || c.conditiontype).join(" AND ");
  const thenSummary = draft.actions.map((a) => a.description || a.actiontype).join("; ");
  useEffect(() => {
    let cancelled = false;
    setEstimateLoading(true);
    setEstimateError("");
    estimateMatches(
      draft.conditions.map((c) => ({
        conditiontype: c.conditiontype,
        conditionconfig: c.conditionconfig
      }))
    ).then((res) => {
      if (!cancelled) {
        setEstimate(res.label);
      }
    }).catch((err) => {
      if (!cancelled) {
        setEstimate("");
        setEstimateError(err.message || strings.estimate_error || "Could not estimate matches");
      }
    }).finally(() => {
      if (!cancelled) {
        setEstimateLoading(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [draft.conditions, estimateRetry, strings.estimate_error]);
  const onSave = /* @__PURE__ */ __name(async () => {
    setError("");
    setNameError("");
    if (!draft.name.trim()) {
      setNameError(strings.error_rulename || "Enter a rule name.");
      return;
    }
    setBusy(true);
    try {
      const result = await saveRule({
        id: draft.id || 0,
        name: draft.name.trim(),
        enabled: draft.enabled ? 1 : 0,
        actionlimit: draft.actionlimit || "once",
        includesuspended: draft.includesuspended ? 1 : 0,
        conditions: draft.conditions,
        actions: draft.actions
      });
      if (!result.ok) {
        if (result.errors.name) {
          setNameError(result.errors.name);
        }
        setError(result.message || strings.save_error || strings.react_wizard_error || "Save failed");
        return;
      }
      window.location.href = returnurl;
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setBusy(false);
    }
  }, "onSave");
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-review-step", children: [
    /* @__PURE__ */ jsxDEV("h3", { className: "h4", children: strings.wizard_review }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 107,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: strings.wizard_review_help }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 108,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("label", { className: "form-label", htmlFor: "murules_rulename", children: [
        strings.rulename,
        " *"
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 111,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          id: "murules_rulename",
          type: "text",
          className: `form-control${nameError ? " is-invalid" : ""}`,
          value: draft.name,
          onChange: (e) => onDraftChange({ name: e.target.value })
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
          lineNumber: 112,
          columnNumber: 17
        },
        this
      ),
      nameError ? /* @__PURE__ */ jsxDEV("div", { className: "invalid-feedback d-block", children: nameError }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 119,
        columnNumber: 30
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 110,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "form-check mb-3", children: [
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          id: "murules_enabled",
          className: "form-check-input",
          type: "checkbox",
          checked: !!draft.enabled,
          onChange: (e) => onDraftChange({ enabled: e.target.checked ? 1 : 0 })
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
          lineNumber: 123,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("label", { className: "form-check-label", htmlFor: "murules_enabled", children: strings.enabled }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 130,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 122,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("label", { className: "form-label", htmlFor: "murules_actionlimit", children: strings.actionlimit }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 134,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV(
        "select",
        {
          id: "murules_actionlimit",
          className: "form-select",
          value: draft.actionlimit,
          onChange: (e) => onDraftChange({ actionlimit: e.target.value }),
          children: actionLimits.map((opt) => /* @__PURE__ */ jsxDEV("option", { value: opt.value, children: opt.label }, opt.value, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
            lineNumber: 142,
            columnNumber: 25
          }, this))
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
          lineNumber: 135,
          columnNumber: 17
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 133,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "form-check mb-4", children: [
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          id: "murules_suspended",
          className: "form-check-input",
          type: "checkbox",
          checked: !!draft.includesuspended,
          onChange: (e) => onDraftChange({ includesuspended: e.target.checked ? 1 : 0 })
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
          lineNumber: 148,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("label", { className: "form-check-label", htmlFor: "murules_suspended", children: strings.includesuspended }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 155,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 147,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("h4", { className: "h5", children: strings.wizard_review_recap }, void 0, false, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 160,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mb-2", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "small text-muted text-uppercase", children: strings.when }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 162,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-recap tool_murules-recap-when", children: whenSummary }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 163,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 161,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "small text-muted text-uppercase", children: strings.then }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 166,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-recap", children: thenSummary }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 167,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 165,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      /* @__PURE__ */ jsxDEV("strong", { children: [
        strings.matchingusers,
        ":"
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 170,
        columnNumber: 17
      }, this),
      " ",
      estimateLoading ? /* @__PURE__ */ jsxDEV("span", { className: "text-muted", children: strings.estimating || "Estimating\u2026" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 172,
        columnNumber: 21
      }, this) : estimate ? /* @__PURE__ */ jsxDEV("span", { className: "text-muted", children: estimate }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 174,
        columnNumber: 21
      }, this) : null,
      estimateError ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning mt-2 py-2", role: "alert", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "me-2", children: estimateError }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
          lineNumber: 178,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-outline-secondary",
            onClick: () => setEstimateRetry((n) => n + 1),
            children: strings.retry || "Retry"
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
            lineNumber: 179,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 177,
        columnNumber: 21
      }, this) : null
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 169,
      columnNumber: 13
    }, this),
    error ? /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", role: "alert", children: [
      /* @__PURE__ */ jsxDEV("p", { className: "mb-2", children: error }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 192,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-sm btn-outline-danger", disabled: busy, onClick: onSave, children: strings.retry || "Retry" }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 193,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 191,
      columnNumber: 17
    }, this) : null,
    /* @__PURE__ */ jsxDEV("div", { className: "d-flex flex-wrap gap-2", children: [
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", disabled: busy, onClick: onSave, children: busy ? strings.saving || strings.save : strings.save }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 200,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-outline-secondary", disabled: busy, onClick: onBack, children: strings.back }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
        lineNumber: 203,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
      lineNumber: 199,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/admin/tool/murules/js/esm/src/components/ReviewStep.tsx",
    lineNumber: 106,
    columnNumber: 9
  }, this);
}
__name(ReviewStep, "ReviewStep");
export {
  ReviewStep as default
};
//# sourceMappingURL=ReviewStep.dev.js.map
