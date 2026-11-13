var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Connect-like wizard stepper (Moodle/Bootstrap native).
 *
 * @module tool_murules/components/Stepper
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const STEPS = ["event", "actions", "review"];
function Stepper({ current, strings }) {
  const currentIndex = STEPS.indexOf(current);
  const nextHint = current === "event" ? strings.wizard_next_actions : current === "actions" ? strings.wizard_next_review : "";
  return /* @__PURE__ */ jsxDEV(
    "nav",
    {
      className: "tool_murules-stepper mb-4",
      "aria-label": strings.wizard_steps_label || "Wizard",
      children: [
        /* @__PURE__ */ jsxDEV("ol", { className: "tool_murules-stepper-list list-unstyled d-flex flex-wrap align-items-stretch gap-0 mb-2", children: STEPS.map((key, idx) => {
          const done = idx < currentIndex;
          const active = key === current;
          let stateClass = "tool_murules-stepper-item";
          if (active) {
            stateClass += " is-active";
          } else if (done) {
            stateClass += " is-done";
          }
          return /* @__PURE__ */ jsxDEV("li", { className: stateClass, children: [
            idx > 0 ? /* @__PURE__ */ jsxDEV("span", { className: "tool_murules-stepper-connector", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
              lineNumber: 42,
              columnNumber: 40
            }, this) : null,
            /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-stepper-body", children: [
              /* @__PURE__ */ jsxDEV(
                "span",
                {
                  className: `tool_murules-stepper-index${done ? " text-bg-success" : active ? " text-bg-primary" : " text-bg-secondary"}`,
                  "aria-hidden": "true",
                  children: done ? "\u2713" : idx + 1
                },
                void 0,
                false,
                {
                  fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
                  lineNumber: 44,
                  columnNumber: 33
                },
                this
              ),
              /* @__PURE__ */ jsxDEV("span", { className: "tool_murules-stepper-label", children: strings[`wizard_${key}`] || key }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
                lineNumber: 50,
                columnNumber: 33
              }, this)
            ] }, void 0, true, {
              fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
              lineNumber: 43,
              columnNumber: 29
            }, this),
            active ? /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: strings.wizard_current_step || "Current step" }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
              lineNumber: 54,
              columnNumber: 39
            }, this) : null
          ] }, key, true, {
            fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
            lineNumber: 41,
            columnNumber: 25
          }, this);
        }) }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
          lineNumber: 30,
          columnNumber: 13
        }, this),
        /* @__PURE__ */ jsxDEV("p", { className: "tool_murules-wizard-progress text-muted small mb-0", "aria-live": "polite", children: strings.wizard_progress ? strings.wizard_progress.replace("{$a->current}", String(currentIndex + 1)).replace("{$a->total}", String(STEPS.length)).replace("{$a->label}", strings[`wizard_${current}`] || current) : `Step ${currentIndex + 1} of ${STEPS.length}` }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
          lineNumber: 59,
          columnNumber: 13
        }, this),
        nextHint ? /* @__PURE__ */ jsxDEV("p", { className: "tool_murules-wizard-next text-muted small mb-0 fw-semibold", children: nextHint }, void 0, false, {
          fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
          lineNumber: 68,
          columnNumber: 17
        }, this) : null
      ]
    },
    void 0,
    true,
    {
      fileName: "public/admin/tool/murules/js/esm/src/components/Stepper.tsx",
      lineNumber: 26,
      columnNumber: 9
    },
    this
  );
}
__name(Stepper, "Stepper");
export {
  Stepper as default
};
//# sourceMappingURL=Stepper.dev.js.map
