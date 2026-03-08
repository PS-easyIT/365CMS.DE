import { BlockToolConstructorOptions, MenuConfig, MoveEvent } from '@editorjs/editorjs/types/tools';
import './index.css';
import { API, BlockAPI, BlockToolConstructable, BlockToolData, ConversionConfig, PasteConfig, PasteEvent, SanitizerConfig, ToolboxConfig, ToolConfig, type BlockTool } from '@editorjs/editorjs';
import { accordionIcon, gearIcon } from './icons';
import { IconChevronUp } from '@codexteam/icons';



type Data = BlockToolData<{
    settings: {
        blockCount: number;
        defaultExpanded?: boolean;
    },
    title: string;
}>
type Config = {
    /**
     * Default value for the accordion to be expanded or not during read mode.
     */
    defaultExpanded: boolean;
    overrides?: {

        classes?: {
            wrapper?: string;
            title?: string;
            settings?: string;
            settingsPopover?: string;
            settingsContent?: string;
            settingsBlockConfig?: string;
            settingsCheckbox?: string;
            settingsDelimiter?: string;
        },
        styles?: {
            /**
             * Rules applied to .ce-block[data-readonly] .accordion-wrapper
             */
            blockWrapper?: string;
            /**
             * Rules applied to .ce-block__content when the editor is readonly
            */
            //  full selector: '.ce-block[data-readonly]:where(> .ce-block__content, + .ce-block .ce-block__content , + .ce-block + .ce-block .ce-block__content...)`
            blockContent?: string;
            /**
             * Rules applied to the last block content
             */
            lastBlockContent?: string;
            /**
             * Rules applied to the inside content of the block, like paragraphs, headings, lists, etc.
             * explanatory selector: '.ce-block__content > *'
            */
            insideContent?: string;
        };
        settingsIcon?: HTMLElement
    }
    disableAnimation?: boolean;
    /**
     * The max allowed block count
     * @default 10
     */
    maxBlockCount?: number;

    //TODO might look into this if it is better (would require to listen to the DOM changes manually)
    // useJSDrawing?:boolean
}
export default class Accordion implements BlockTool {
    public static get toolbox(): ToolboxConfig {
        return {
            title: 'Accordion',
            icon: accordionIcon
        };
    }
    public static get isReadOnlySupported() {
        return true;
    }
    public static get isInline() {
        return false;
    }
    public static WRAPPER_ATTRIBUTE_NAME = 'data-accordion-wrapper' as const;
    public CSSOpenVariableName: string
    private wrapper: HTMLElement;
    public readonly styleSheetId = 'editorjs-accordion-styles';
    private readonly styleEl: HTMLStyleElement
    public data: Data;
    private api: API
    private block: BlockAPI;
    private config: Config;
    private readonly: boolean;
    private _opened: boolean;

    constructor({ data, api, block, readOnly, config }: BlockToolConstructorOptions<Data, Config>) {
        this.CSSOpenVariableName = `--acc-opened-${block.id}`;
        const defaultConfig: Config = {
            defaultExpanded: true,
            maxBlockCount: 10,
        };
        this.config = { ...defaultConfig, ...(config ?? {}) };
        const defaultData: Data = {
            settings: {
                blockCount: 3,
                defaultExpanded: this.config.defaultExpanded,
            },
            title: '',
        }
        this.data = { ...defaultData, ...data };
        if (!this.data.settings) {
            this.data.settings = defaultData.settings;
        }
        if (!this.data.title) {
            this.data.title = defaultData.title;
        }
        this.api = api;
        this.block = block;
        this.readonly = readOnly;
        this._opened = Boolean(this.data.settings.defaultExpanded ?? this.config.defaultExpanded);
        this.wrapper = document.createElement('div');
        const styleEl = document.getElementById(this.styleSheetId)
        if (!styleEl) {
            this.styleEl = document.createElement('style');
            this.styleEl.id = this.styleSheetId;
            document.head.appendChild(this.styleEl);
        }
        else
            this.styleEl = styleEl as HTMLStyleElement;

        this.verifyGivenStyles();
    }
    // toolbox?: ToolboxConfig | undefined;
    // pasteConfig?: PasteConfig | undefined;
    // conversionConfig?: ConversionConfig | undefined;
    // title?: string | undefined;
    // prepare?(data: { toolName: string; config: ToolConfig; }): void | Promise<void> {
    //     throw new Error('Method not implemented.');
    // }
    // reset?(): void | Promise<void> {
    //     throw new Error('Method not implemented.');
    // }
    // sanitize?: SanitizerConfig | undefined;
    // renderSettings?(): HTMLElement | MenuConfig {
    //     // throw new Error('Method not implemented.');
    // }
    validate(blockData: BlockToolData): boolean {
        if (!blockData.settings || typeof blockData.settings.blockCount !== 'number')
            return false;
        if (blockData.settings.blockCount < 1)
            return false;
        if (typeof blockData.title !== 'string')
            return false;

        return true;
    }
    // merge?(blockData: BlockToolData): void {
    //     throw new Error('Method not implemented.');
    // }
    // onPaste?(event: PasteEvent): void {
    //     throw new Error('Method not implemented.');
    // }
    // destroy?(): void {
    //     console.log('Accordion block destroyed');
    // }
    // updated?(): void {
    //     console.log('Accordion block updated');
    // }
    // public removed?(): void {

    // }

    // public moved?(event: MoveEvent): void {
    // }


    public render() {
        this.wrapper = document.createElement('div');
        if (this.readonly)
            this.wrapper.setAttribute("data-readonly", '')
        this.wrapper.classList.add(this.CSS.wrapper);
        const title = document.createElement("div");
        title.setAttribute('contenteditable', this.readonly ? 'false' : 'true');
        title.textContent = this.data.title ?? "";
        this.wrapper.appendChild(title);
        title.addEventListener('input', (event) => {
            if (!(event.target instanceof HTMLDivElement)) return;
            this.data.title = event.target.textContent
            this.block.dispatchChange();
        });

        const emptyEl = document.createElement("span")
        emptyEl.style.width = "30px";
        emptyEl.style.zIndex = "-1"
        this.wrapper.appendChild(emptyEl)


        const settings = document.createElement("div");
        settings.classList.add(this.CSS.settings);
        settings.setAttribute('aria-label', this.api.i18n.t("Settings"));

        settings.setAttribute('type', 'button');
        if (this.config.overrides?.settingsIcon) {
            settings.appendChild(this.config.overrides.settingsIcon)
        }
        else {
            settings.innerHTML = gearIcon
        }
        settings.addEventListener('click', () => {
            const popover = document.createElement('div');
            popover.classList.add(this.CSS.settingsPopover);

            const minCountValue = 1
            const countId = `blockCount-${this.block.id}`;
            popover.innerHTML = /*html*/`
                <div class="${this.CSS.settingsContent}">
                    <div class="${this.CSS.settingsBlockConfig}">
                        <label for="${countId}">${this.api.i18n.t("Block Count")}</label>
                        <input type="number" id="${countId}" class="${this.api.styles.input}" value="${this.data.settings?.blockCount ?? 1}" min="${minCountValue}" max="${this.config.maxBlockCount}">
                        <button type="button" class="${this.CSS.saveSettings}">${this.api.i18n.t("Save")}</button>
                    </div>
                    <div class="${this.CSS.settingsDelimiter}"></div>
                    <div  class="${this.CSS.settingsBlockConfig}">
                        <label for="defaultExpanded">${this.api.i18n.t("Default Expanded")}</label>
                        <input type="checkbox" id="defaultExpanded" class="${this.CSS.settingsCheckbox}" ${this.data.settings?.defaultExpanded ? 'checked' : ''}>
                    </div>
                </div>
            `;

            const defaultExpandedCheckbox = popover.querySelector<HTMLInputElement>('#defaultExpanded');
            const countInput = popover.querySelector<HTMLInputElement>(`#${countId}`);
            const saveButton = popover.querySelector<HTMLButtonElement>('.save-settings');
            if (countInput && saveButton) {
                countInput.addEventListener('input', stopPropagation);
                countInput.addEventListener("keydown", stopPropagation);
                countInput.addEventListener("keyup", stopPropagation);
                countInput.addEventListener("keypress", stopPropagation);


                saveButton.addEventListener('click', () => {
                    this.data.settings.blockCount = Math.max(minCountValue, Math.min(parseInt(countInput.value), this.config.maxBlockCount ?? Number.MAX_SAFE_INTEGER))
                    this.block.dispatchChange();
                    popover.remove();

                    this.renderAccordionBlocks()
                });
            }
            defaultExpandedCheckbox?.addEventListener('change', (event) => {
                if (!(event.target instanceof HTMLInputElement)) return;
                this.data.settings.defaultExpanded = event.target.checked;
                this.block.dispatchChange();
            })
            function removeListener(event: PointerEvent) {
                if (event.target === settings || popover.contains(event.target as Node))
                    return;
                popover.remove();
                document.removeEventListener('click', removeListener, { capture: true });
            }
            document.addEventListener('click', removeListener, { capture: true });
            settings.insertAdjacentElement('afterend', popover);
        });

        const dropDown = document.createElement('div');
        dropDown.classList.add(this.CSS.chevronIcon);
        dropDown.innerHTML = IconChevronUp;

        if (!this.readonly) {

            this.wrapper.appendChild(settings);
            this.wrapper.removeEventListener("click", this.toggleAccordion.bind(this));
        }
        else {
            this.wrapper.addEventListener("click", this.toggleAccordion.bind(this));
            this.wrapper.appendChild(dropDown)
        }

        return this.wrapper;
    }

    rendered(): void {
        this.block.holder.parentElement?.style.setProperty(this.CSSOpenVariableName, this._opened ? '1fr' : '0fr');
        this.renderAccordionBlocks()
        this.opened = this._opened;
    }


    public save(blockContent: HTMLElement) {
        return this.data;
    }

    public get opened() {
        return this._opened;
    }

    public set opened(value: boolean) {
        this._opened = value;
        this.block.holder.parentElement?.style.setProperty(this.CSSOpenVariableName, this._opened ? '1fr' : '0fr');
        this.rotateChevronIcon();
    }

    private get EditorCSS() {
        return {
            block: "ce-block",
            block_content: "ce-block__content",
        } as const
    }

    private get CSS() {
        return {
            wrapper: (`accordion-wrapper ` + (this.config.overrides?.classes?.wrapper || '')).trim(),
            title: ((`accordion-title `) + this.config.overrides?.classes?.title || '').trim(),

            settings: ("accordion-settings " + (this.config.overrides?.classes?.settings || '')).trim(),
            settingsPopover: ("settings-popover " + (this.config.overrides?.classes?.settingsPopover || '')).trim(),
            settingsContent: ("settings-content " + (this.config.overrides?.classes?.settingsContent || '')).trim(),
            settingsBlockConfig: "settings-block-config" + (this.config.overrides?.classes?.settingsBlockConfig || ''),
            settingsCheckbox: "settings-checkbox" + (this.config.overrides?.classes?.settingsCheckbox || ''),
            saveSettings: ("save-settings " + (this.config.overrides?.classes?.settingsContent || '')).trim(),
            settingsDelimiter: "settings-delimiter" + (this.config.overrides?.classes?.settingsDelimiter || ''),
            chevronIcon: "accordion-chevron-icon",
            chevronIconRotated: "accordion-chevron-icon-rotated",
            cssAccordionBorderColorVar: "--acc-border-color" + this.block.id,
        } as const
    }


    private getBlocks() {
        const blocks: HTMLElement[] = [];
        const blockCount = this.data.settings.blockCount || 1;

        if (!(this.block.holder.nextElementSibling instanceof HTMLElement)) return [];
        let next = this.block.holder.nextElementSibling;

        for (let i = 0; i < blockCount; i++) {
            blocks.push(next);
            if (!(next.nextElementSibling instanceof HTMLElement)) break;
            const isNextWrapper = next.nextElementSibling.hasAttribute(Accordion.WRAPPER_ATTRIBUTE_NAME);
            if (isNextWrapper) break;
            next = next.nextElementSibling;

        }
        return blocks;
    }

    private renderAccordionBlocks() {
        this.block.holder.setAttribute(Accordion.WRAPPER_ATTRIBUTE_NAME, this.data.settings.blockCount.toString());
        if (this.readonly)
            this.block.holder.setAttribute("data-readonly", '');
        // create style sheet styles
        const count = this.data.settings.blockCount || 1;

        const isStylesAlreadyDrawn = this.styleEl.textContent?.includes(`.${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}="${count}"][data-id="${this.block.id}"]`);
        if (isStylesAlreadyDrawn) return;

        let allStyles = '';

        const closeAnimationRules = `
                will-change: grid-template-rows;
                transition: grid-template-rows .5s;
                -webkit-transition: grid-template-rows .5s;
                -moz-transition: grid-template-rows .5s;
                -ms-transition: grid-template-rows .5s;
                -o-transition: grid-template-rows .5s;
        `
        const readonlyBlockRules = `
                display: grid;
                overflow: hidden;
                ${this.config.disableAnimation ? "" : closeAnimationRules}
                grid-template-rows: var(${this.CSSOpenVariableName}, 0fr);
                ${this.config.overrides?.styles?.blockWrapper ?? ""}
`;

        // width: 650px;
        const readonlyContentRules = `
                min-height: 0;
                will-change:border;
                transition: border .3s;
                -webkit-transition: border .3s;
                -moz-transition: border .3s;
                -ms-transition: border .3s;
                -o-transition: border .3s;
                /* Because display grid from parent prevents this to use default max width of 650px */
                width: 650px;
                ${this.config.overrides?.styles?.blockContent ?? ""}
                `
        // visibility: ${this.opened ? 'visible' : 'hidden'};

        const cssCreaonlyBlockStyles = this.generateAccordionSelector({ count, rules: readonlyBlockRules, extraSelector: `:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}])`, includeWrapperId: true, readonly: true })
        const cssReadonlyContentStyles = this.generateAccordionSelector({ count, rules: readonlyContentRules, extraSelector: `:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}]) .${this.EditorCSS.block_content}`, readonly: true });

        allStyles += "\n\n/*CSS block, READONLY  styles*/\n" + cssCreaonlyBlockStyles;
        allStyles += "\n\n/*CSS block content, READONLY styles*/\n" + cssReadonlyContentStyles;
        const variableName = this.CSS.cssAccordionBorderColorVar
        const editContentRules = `
                border-left: 1px solid var(${variableName}, transparent);
                border-right: 1px solid var(${variableName}, transparent);
                will-change: border;
                transition: border .3s;
                -webkit-transition: border .3s;
                -moz-transition: border .3s;
                -ms-transition: border .3s;
                -o-transition: border .3s;
                min-height: 0;
                /* Because display grid from parent prevents this to use default max width of 650px */
                width: 650px;
                ${this.config.overrides?.styles?.blockContent ?? ""}
                `;
        const editLastContentRules = `
                border-bottom: 1px solid var(${variableName}, transparent);
                border-radius: 0 0 15px 15px;
                -webkit-border-radius: 0 0 15px 15px;
                -moz-border-radius: 0 0 15px 15px;
                -ms-border-radius: 0 0 15px 15px;
                -o-border-radius: 0 0 15px 15px;
                ${this.config.overrides?.styles?.lastBlockContent ?? ""}
                `

        const cssEditContentStyles = this.generateAccordionSelector({ count, rules: editContentRules, extraSelector: ` .${this.EditorCSS.block_content}`, readonly: false });
        const editLastBlockContentStyles = this.generateAccordtionLastSelector({ count, rules: editLastContentRules, extraSelector: ` .${this.EditorCSS.block_content}`, readonly: false });

        allStyles += "\n\n/*CSS block content, EDIT styles*/\n" + cssEditContentStyles + '\n' + "\n\n/*CSS last block content, EDIT styles*/\n" + editLastBlockContentStyles;

        const insideContentElementRules = `
            padding-inline: 20px;
            ${this.config.overrides?.styles?.insideContent ?? ""}
        `
        const cssInsideContentStyles = this.generateAccordionSelector({ count, rules: insideContentElementRules, extraSelector: `:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}]) .${this.EditorCSS.block_content} > *` });

        const hoverAccordionStyles = /*css*/`
            .codex-editor__redactor:has([${Accordion.WRAPPER_ATTRIBUTE_NAME}][data-id="${this.block.id}"]:hover,[${Accordion.WRAPPER_ATTRIBUTE_NAME}][data-id="${this.block.id}"]:focus-within) {
                ${this.CSS.cssAccordionBorderColorVar}: var(--acc-border-color);
            }
        `

        allStyles += cssInsideContentStyles + hoverAccordionStyles;
        // append styles for the current COUNTa
        this.styleEl.textContent += allStyles;

    }

    private generateAccordionSelector({
        count,
        rules,
        extraSelector = '',
        includeWrapperId = false
        , readonly
    }: {
        count: number, rules: string, extraSelector?: string, includeWrapperId?: boolean;
        readonly?: boolean;
    }): string {
        const parts: string[] = [];
        const readonlyPart = readonly === undefined ? "" : readonly ? "[data-readonly]" : ":not([data-readonly])"

        for (let i = 1; i <= count; i++) {
            const siblingChain = Array(i).fill(`+ .${this.EditorCSS.block}:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}])`).join(' ');
            parts.push(`.${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}="${count}"][data-id="${this.block.id}"]${readonlyPart}:has(.${this.CSS.wrapper}) ${siblingChain}${extraSelector}`);
        }

        const selector = parts.join(',\n');
        return `${selector} { ${rules} }`;
    }

    private generateAccordtionLastSelector({
        count,
        rules,
        extraSelector = ''
        , readonly
    }: {
        count: number, rules: string, extraSelector?: string,
        readonly?: boolean;
    }): string {
        const parts: string[] = [];

        /**
         * Example 
         *  
            first scenario, all blocks exist, and are not another wrapper 
            .ce-block[data-accordion-wrapper="3"]:has(.accordion-wrapper)+.ce-block:not([data-accordion-wrapper])+.ce-block:not([data-accordion-wrapper])+.ce-block:not([data-accordion-wrapper]) .ce-block__content,
            second scenario, only some blocks exist, ant there are not another wrapper, ex accordion at end of editor 
            .ce-block[data-accordion-wrapper="3"]:has(.accordion-wrapper)+.ce-block:not([data-accordion-wrapper])+.ce-block:not([data-accordion-wrapper]):last-child .ce-block__content,
            .ce-block[data-accordion-wrapper="3"]:has(.accordion-wrapper)+.ce-block:not([data-accordion-wrapper]):last-child .ce-block__content,

            third scenario, blocks exist but are cut off by another wrapper 
            .ce-block[data-accordion-wrapper="3"]:has(.accordion-wrapper)+.ce-block:not([data-accordion-wrapper])+.ce-block:not([data-accordion-wrapper]):has(+.ce-block[data-accordion-wrapper]) .ce-block__content,
            .ce-block[data-accordion-wrapper="3"]:has(.accordion-wrapper)+.ce-block:not([data-accordion-wrapper]):has(+.ce-block[data-accordion-wrapper]) .ce-block__content {
                background-color: red;
            }
         */
        const siblingChain = Array(count).fill(`+ .${this.EditorCSS.block}:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}])`).join(' ');
        parts.push(`.${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}="${count}"][data-id="${this.block.id}"]${readonly ? "[data-readonly]" : ":not([data-readonly])"}:has(.${this.CSS.wrapper}) ${siblingChain}${extraSelector}`);
        for (let i = count - 1; i > 0; i--) {
            const partialChainWithoutWrapper = Array(i).fill(`+ .${this.EditorCSS.block}:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}])`).join(' ');
            parts.push(`.${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}="${count}"][data-id="${this.block.id}"]${readonly ? "[data-readonly]" : ":not([data-readonly])"}:has(.${this.CSS.wrapper}) ${partialChainWithoutWrapper}:last-child${extraSelector}`);
        }

        for (let i = count - 1; i > 0; i--) {
            const partialChainWithWrapper = Array(i).fill(`+ .${this.EditorCSS.block}:not([${Accordion.WRAPPER_ATTRIBUTE_NAME}])`).join(' ');
            parts.push(`.${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}="${count}"][data-id="${this.block.id}"]${readonly ? "[data-readonly]" : ":not([data-readonly])"}:has(.${this.CSS.wrapper}) ${partialChainWithWrapper}:has(+ .${this.EditorCSS.block}[${Accordion.WRAPPER_ATTRIBUTE_NAME}])${extraSelector}`);
        }

        const selector = parts.join(',\n');
        return `${selector} { ${rules} }`;
    }

    private toggleAccordion(event: Event) {
        this.opened = !this.opened;
    }


    private rotateChevronIcon() {
        const chevronIcon = this.wrapper.querySelector(`.${this.CSS.chevronIcon}`);
        if (!(chevronIcon instanceof HTMLElement)) return;
        if (this.opened) {
            chevronIcon.classList.remove(this.CSS.chevronIconRotated);
        } else {
            chevronIcon.classList.add(this.CSS.chevronIconRotated);
        }
    }

    private verifyGivenStyles() {
        const rules = [this.config.overrides?.styles?.blockWrapper, this.config.overrides?.styles?.blockContent, this.config.overrides?.styles?.lastBlockContent, this.config.overrides?.styles?.insideContent];
        for (const rule of rules) {
            if (rule && !validateCssRules(rule)) {
                console.warn(`Invalid CSS rules provided: '${rule}'. Insert only the css styles`);
                break;
            }
        }

    }
}

function validateCssRules(rules: string): boolean {
    const testElement = document.createElement('div');
    testElement.style.cssText = rules;
    return testElement.style.cssText === rules;
}
function stopPropagation(event: Event) {
    event.stopPropagation();
}
