/**
 * Shared types for the tool_murules React wizard.
 *
 * @module tool_murules/types
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type WizardStep = 'event' | 'actions' | 'review';

export type FieldShowIf = {
    field: string;
    equals?: string;
    in?: string[];
    notin?: string[];
};

export type FieldOption = {value: string; label: string};

export type EditorField = {
    name: string;
    type: 'course' | 'coursemodule' | 'select' | 'text' | 'number' | 'textarea' | 'datetime';
    label: string;
    required?: boolean;
    options?: FieldOption[];
    mode?: 'quiz' | 'activity';
    dependsOn?: string;
    showif?: FieldShowIf;
};

export type EditorSchema = {
    fields: EditorField[];
    defaults: Record<string, unknown>;
};

export type ConditionItem = {
    conditiontype: string;
    conditionconfig: Record<string, unknown>;
    description?: string;
};

export type ActionItem = {
    id?: number;
    actiontype: string;
    actionconfig: Record<string, unknown>;
    description?: string;
};

export type Draft = {
    id: number;
    name: string;
    enabled: number;
    actionlimit: string;
    includesuspended: number;
    conditions: ConditionItem[];
    actions: ActionItem[];
};

export type CatalogCondition = {type: string; name: string; category: string};
export type CatalogCategory = {
    key: string;
    label: string;
    count: number;
    items: CatalogCondition[];
};
export type CatalogAction = {type: string; name: string};

export type BootstrapPayload = {
    draft: Draft;
    conditionCatalog: CatalogCategory[];
    actionCatalog: CatalogAction[];
    strings: Record<string, string>;
    actionLimits: FieldOption[];
};

export type WizardProps = {
    ruleid: number;
    returnurl: string;
};
