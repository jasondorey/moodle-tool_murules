var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Schema-driven config form (Formslib replacement for the wizard).
 *
 * @module tool_murules/components/SchemaForm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useMemo, useState } from "react";
import { loadCourseModules, searchCourses } from "../services/api";
function visible(field, values) {
  const showif = field.showif;
  if (!showif) {
    return true;
  }
  const current = String(values[showif.field] ?? "");
  if (showif.equals !== void 0) {
    return current === showif.equals;
  }
  if (showif.in) {
    return showif.in.includes(current);
  }
  if (showif.notin) {
    return !showif.notin.includes(current);
  }
  return true;
}
__name(visible, "visible");
function toDatetimeLocal(ts) {
  const n = Number(ts) || 0;
  if (!n) {
    return "";
  }
  const d = new Date(n * 1e3);
  const pad = /* @__PURE__ */ __name((x) => String(x).padStart(2, "0"), "pad");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
__name(toDatetimeLocal, "toDatetimeLocal");
function fromDatetimeLocal(value) {
  if (!value) {
    return 0;
  }
  const ms = Date.parse(value);
  return Number.isFinite(ms) ? Math.floor(ms / 1e3) : 0;
}
__name(fromDatetimeLocal, "fromDatetimeLocal");
function SchemaForm({ schema, values, errors = {}, strings, onChange }) {
  const [courseOptions, setCourseOptions] = useState([]);
  const [courseQuery, setCourseQuery] = useState("");
  const [cmsByField, setCmsByField] = useState({});
  const courseFields = useMemo(
    () => schema.fields.filter((f) => f.type === "course"),
    [schema.fields]
  );
  const cmFields = useMemo(
    () => schema.fields.filter((f) => f.type === "coursemodule"),
    [schema.fields]
  );
  useEffect(() => {
    let cancelled = false;
    const preferred = courseFields.length ? Number(values[courseFields[0].name] || 0) : 0;
    searchCourses(courseQuery, preferred).then((courses) => {
      if (!cancelled) {
        setCourseOptions(courses);
      }
    }).catch(() => {
      if (!cancelled) {
        setCourseOptions([]);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [courseQuery, courseFields, values]);
  useEffect(() => {
    let cancelled = false;
    Promise.all(
      cmFields.map(async (field) => {
        const courseid = Number(values[field.dependsOn || "courseid"] || 0);
        const mode = field.mode === "quiz" ? "quiz" : "activity";
        const cms = await loadCourseModules(courseid, mode);
        return [field.name, cms];
      })
    ).then((entries) => {
      if (!cancelled) {
        setCmsByField(Object.fromEntries(entries));
      }
    }).catch(() => {
      if (!cancelled) {
        setCmsByField({});
      }
    });
    return () => {
      cancelled = true;
    };
  }, [cmFields, values]);
  const setValue = /* @__PURE__ */ __name((name, value) => {
    const next = { ...values, [name]: value };
    cmFields.forEach((field) => {
      if (field.dependsOn === name) {
        next[field.name] = 0;
      }
    });
    onChange(next);
  }, "setValue");
  return /* @__PURE__ */ jsxDEV("div", { className: "tool_murules-schema-form", children: schema.fields.map((field) => {
    if (!visible(field, values)) {
      return null;
    }
    const err = errors[field.name];
    const id = `murules_field_${field.name}`;
    const commonLabel = /* @__PURE__ */ jsxDEV("label", { className: "form-label", htmlFor: id, children: [
      field.label,
      field.required ? " *" : ""
    ] }, void 0, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
      lineNumber: 130,
      columnNumber: 21
    }, this);
    let control = null;
    if (field.type === "course") {
      control = /* @__PURE__ */ jsxDEV("div", { className: "d-flex flex-column gap-2", children: [
        /* @__PURE__ */ jsxDEV(
          "input",
          {
            type: "search",
            className: "form-control form-control-sm",
            placeholder: strings.search_courses || "Course",
            value: courseQuery,
            onChange: (e) => setCourseQuery(e.target.value),
            "aria-label": strings.search_courses || "Course"
          },
          void 0,
          false,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
            lineNumber: 140,
            columnNumber: 29
          },
          this
        ),
        /* @__PURE__ */ jsxDEV(
          "select",
          {
            id,
            className: `form-select${err ? " is-invalid" : ""}`,
            value: String(values[field.name] ?? 0),
            onChange: (e) => setValue(field.name, Number(e.target.value) || 0),
            children: [
              /* @__PURE__ */ jsxDEV("option", { value: "0", children: strings.choosedots || "\u2026" }, void 0, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
                lineNumber: 154,
                columnNumber: 33
              }, this),
              courseOptions.map((c) => /* @__PURE__ */ jsxDEV("option", { value: c.id, children: c.name }, c.id, false, {
                fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
                lineNumber: 156,
                columnNumber: 37
              }, this))
            ]
          },
          void 0,
          true,
          {
            fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
            lineNumber: 148,
            columnNumber: 29
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
        lineNumber: 139,
        columnNumber: 25
      }, this);
    } else if (field.type === "coursemodule") {
      const cms = cmsByField[field.name] || [];
      control = /* @__PURE__ */ jsxDEV(
        "select",
        {
          id,
          className: `form-select${err ? " is-invalid" : ""}`,
          value: String(values[field.name] ?? 0),
          onChange: (e) => setValue(field.name, Number(e.target.value) || 0),
          disabled: !Number(values[field.dependsOn || "courseid"] || 0),
          children: [
            /* @__PURE__ */ jsxDEV("option", { value: "0", children: cms.length ? strings.choosedots || "\u2026" : strings.nocms || "No activities" }, void 0, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
              lineNumber: 171,
              columnNumber: 29
            }, this),
            cms.map((cm) => /* @__PURE__ */ jsxDEV("option", { value: cm.id, children: cm.name }, cm.id, false, {
              fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
              lineNumber: 175,
              columnNumber: 33
            }, this))
          ]
        },
        void 0,
        true,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 164,
          columnNumber: 25
        },
        this
      );
    } else if (field.type === "select") {
      control = /* @__PURE__ */ jsxDEV(
        "select",
        {
          id,
          className: `form-select${err ? " is-invalid" : ""}`,
          value: String(values[field.name] ?? ""),
          onChange: (e) => setValue(field.name, e.target.value),
          children: (field.options || []).map((opt) => /* @__PURE__ */ jsxDEV("option", { value: opt.value, children: opt.label }, opt.value, false, {
            fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
            lineNumber: 188,
            columnNumber: 33
          }, this))
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 181,
          columnNumber: 25
        },
        this
      );
    } else if (field.type === "textarea") {
      control = /* @__PURE__ */ jsxDEV(
        "textarea",
        {
          id,
          className: `form-control${err ? " is-invalid" : ""}`,
          rows: 5,
          value: String(values[field.name] ?? ""),
          onChange: (e) => setValue(field.name, e.target.value)
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 194,
          columnNumber: 25
        },
        this
      );
    } else if (field.type === "datetime") {
      control = /* @__PURE__ */ jsxDEV(
        "input",
        {
          id,
          type: "datetime-local",
          className: `form-control${err ? " is-invalid" : ""}`,
          value: toDatetimeLocal(values[field.name]),
          onChange: (e) => setValue(field.name, fromDatetimeLocal(e.target.value))
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 204,
          columnNumber: 25
        },
        this
      );
    } else if (field.type === "number") {
      control = /* @__PURE__ */ jsxDEV(
        "input",
        {
          id,
          type: "number",
          className: `form-control${err ? " is-invalid" : ""}`,
          value: values[field.name] === void 0 || values[field.name] === null ? "" : String(values[field.name]),
          onChange: (e) => setValue(
            field.name,
            e.target.value === "" ? "" : Number(e.target.value)
          )
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 214,
          columnNumber: 25
        },
        this
      );
    } else {
      control = /* @__PURE__ */ jsxDEV(
        "input",
        {
          id,
          type: "text",
          className: `form-control${err ? " is-invalid" : ""}`,
          value: String(values[field.name] ?? ""),
          onChange: (e) => setValue(field.name, e.target.value)
        },
        void 0,
        false,
        {
          fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
          lineNumber: 229,
          columnNumber: 25
        },
        this
      );
    }
    return /* @__PURE__ */ jsxDEV("div", { className: "mb-3", children: [
      commonLabel,
      control,
      err ? /* @__PURE__ */ jsxDEV("div", { className: "invalid-feedback d-block", children: err }, void 0, false, {
        fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
        lineNumber: 243,
        columnNumber: 32
      }, this) : null
    ] }, field.name, true, {
      fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
      lineNumber: 240,
      columnNumber: 21
    }, this);
  }) }, void 0, false, {
    fileName: "public/admin/tool/murules/js/esm/src/components/SchemaForm.tsx",
    lineNumber: 122,
    columnNumber: 9
  }, this);
}
__name(SchemaForm, "SchemaForm");
export {
  SchemaForm as default
};
//# sourceMappingURL=SchemaForm.dev.js.map
