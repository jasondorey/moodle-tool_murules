/**
 * Web service wrappers for the React wizard.
 *
 * @module tool_murules/services/api
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {fetchOne} from '@moodle/lms/core/ajax';
import type {BootstrapPayload, EditorSchema} from '../types';

export async function loadBootstrap(ruleid: number): Promise<BootstrapPayload> {
    const res = await fetchOne<{payload: string}>({
        methodname: 'tool_murules_get_wizard_bootstrap',
        args: {ruleid},
    });
    return JSON.parse(res.payload) as BootstrapPayload;
}

export async function loadSchema(kind: 'condition' | 'action', type: string): Promise<EditorSchema> {
    const res = await fetchOne<{payload: string}>({
        methodname: 'tool_murules_get_editor_schema',
        args: {kind, type},
    });
    return JSON.parse(res.payload) as EditorSchema;
}

export async function validateConfig(
    kind: 'condition' | 'action',
    type: string,
    config: Record<string, unknown>,
): Promise<{ok: boolean; errors: Record<string, string>; config: Record<string, unknown>; description: string}> {
    const res = await fetchOne<{
        ok: boolean;
        errorsjson: string;
        configjson: string;
        description: string;
    }>({
        methodname: 'tool_murules_validate_editor_config',
        args: {
            kind,
            type,
            configjson: JSON.stringify(config),
        },
    });
    return {
        ok: !!res.ok,
        errors: JSON.parse(res.errorsjson || '{}') as Record<string, string>,
        config: JSON.parse(res.configjson || '{}') as Record<string, unknown>,
        description: res.description || '',
    };
}

export async function searchCourses(
    query: string,
    courseid = 0,
): Promise<Array<{id: number; name: string}>> {
    const res = await fetchOne<{courses: Array<{id: number; name: string}>}>({
        methodname: 'tool_murules_search_courses',
        args: {query, courseid, limit: 40},
    });
    return res.courses || [];
}

export async function loadCourseModules(
    courseid: number,
    mode: 'quiz' | 'activity',
): Promise<Array<{id: number; name: string}>> {
    if (!courseid) {
        return [];
    }
    const res = await fetchOne<{cms: Array<{id: number; name: string}>}>({
        methodname: 'tool_murules_get_course_cms',
        args: {courseid, mode},
    });
    return res.cms || [];
}

export async function estimateMatches(
    conditions: Array<{conditiontype: string; conditionconfig: Record<string, unknown>}>,
): Promise<{count: number; capped: boolean; broken: boolean; label: string}> {
    return fetchOne({
        methodname: 'tool_murules_estimate_matches',
        args: {
            conditionsjson: JSON.stringify(conditions),
            limit: 500,
        },
    });
}

export async function saveRule(draft: Record<string, unknown>): Promise<{
    ok: boolean;
    ruleid: number;
    message: string;
    errors: Record<string, string>;
}> {
    const res = await fetchOne<{
        ok: boolean;
        ruleid: number;
        message: string;
        errorsjson: string;
    }>({
        methodname: 'tool_murules_save_rule',
        args: {payloadjson: JSON.stringify(draft)},
    });
    return {
        ok: !!res.ok,
        ruleid: res.ruleid || 0,
        message: res.message || '',
        errors: JSON.parse(res.errorsjson || '{}') as Record<string, string>,
    };
}
