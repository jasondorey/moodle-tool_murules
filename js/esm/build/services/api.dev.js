var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Web service wrappers for the React wizard.
 *
 * @module tool_murules/services/api
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { fetchOne } from "@moodle/lms/core/ajax";
async function loadBootstrap(ruleid) {
  const res = await fetchOne({
    methodname: "tool_murules_get_wizard_bootstrap",
    args: { ruleid }
  });
  return JSON.parse(res.payload);
}
__name(loadBootstrap, "loadBootstrap");
async function loadSchema(kind, type) {
  const res = await fetchOne({
    methodname: "tool_murules_get_editor_schema",
    args: { kind, type }
  });
  return JSON.parse(res.payload);
}
__name(loadSchema, "loadSchema");
async function validateConfig(kind, type, config) {
  const res = await fetchOne({
    methodname: "tool_murules_validate_editor_config",
    args: {
      kind,
      type,
      configjson: JSON.stringify(config)
    }
  });
  return {
    ok: !!res.ok,
    errors: JSON.parse(res.errorsjson || "{}"),
    config: JSON.parse(res.configjson || "{}"),
    description: res.description || ""
  };
}
__name(validateConfig, "validateConfig");
async function searchCourses(query, courseid = 0) {
  const res = await fetchOne({
    methodname: "tool_murules_search_courses",
    args: { query, courseid, limit: 40 }
  });
  return res.courses || [];
}
__name(searchCourses, "searchCourses");
async function loadCourseModules(courseid, mode) {
  if (!courseid) {
    return [];
  }
  const res = await fetchOne({
    methodname: "tool_murules_get_course_cms",
    args: { courseid, mode }
  });
  return res.cms || [];
}
__name(loadCourseModules, "loadCourseModules");
async function estimateMatches(conditions) {
  return fetchOne({
    methodname: "tool_murules_estimate_matches",
    args: {
      conditionsjson: JSON.stringify(conditions),
      limit: 500
    }
  });
}
__name(estimateMatches, "estimateMatches");
async function saveRule(draft) {
  const res = await fetchOne({
    methodname: "tool_murules_save_rule",
    args: { payloadjson: JSON.stringify(draft) }
  });
  return {
    ok: !!res.ok,
    ruleid: res.ruleid || 0,
    message: res.message || "",
    errors: JSON.parse(res.errorsjson || "{}")
  };
}
__name(saveRule, "saveRule");
export {
  estimateMatches,
  loadBootstrap,
  loadCourseModules,
  loadSchema,
  saveRule,
  searchCourses,
  validateConfig
};
//# sourceMappingURL=api.dev.js.map
