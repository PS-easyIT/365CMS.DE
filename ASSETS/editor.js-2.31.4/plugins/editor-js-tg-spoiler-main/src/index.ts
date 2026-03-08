// @ts-nocheck
import type { InlineTool, SanitizerConfig } from '@editorjs/editorjs';

export default class Spoiler implements InlineTool {

  public static isInline = true;

  public static title = 'Spoiler';

  public static get sanitize(): SanitizerConfig {
    return {
      span: {
        class: 'tg-spoiler',
      },
    };
  }

  private readonly commandName: string = 'spoiler';

  private readonly CSS = {
    button: 'ce-inline-tool',
    buttonActive: 'ce-inline-tool--active',
    buttonModifier: 'ce-inline-tool--spoiler',
  };

  private nodes: { button: HTMLButtonElement } = {
    button: null,
  };

  public render(): HTMLElement {
    this.nodes.button = document.createElement('button');
    this.nodes.button.type = 'button';
    this.nodes.button.classList.add(this.CSS.button, this.CSS.buttonModifier);
    this.nodes.button.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M14.5 8.50001C13.5 7 10.935 6.66476 9.75315 7.79706C9.27092 8.25909 9 8.88574 9 9.53915C9 10.1926 9.27092 10.8192 9.75315 11.2812C10.9835 12.46 13.0165 11.5457 14.2468 12.7244C14.7291 13.1865 15 13.8131 15 14.4665C15 15.1199 14.7291 15.7466 14.2468 16.2086C12.8659 17.5317 10 17.5 9 16"></path>
      </svg>
    `;

    return this.nodes.button;
  }

  public surround(): void {
    document.execCommand(this.commandName);

    this.addSpoilerClass();
  }

  private addSpoilerClass(): void {
    const selection = window.getSelection();
    if (!selection || !selection.rangeCount) return;

    const range = selection.getRangeAt(0);
    const commonAncestor = range.commonAncestorContainer;

    const spoilers = this.findAllSpoilerTags(commonAncestor, range);

    spoilers.forEach(sp => {
      if (!sp.classList.contains('tg-spoiler')) {
        sp.classList.add('tg-spoiler');
      }
    });
  }

  private findAllSpoilerTags(container: Node, range: Range): HTMLElement[] {
    const spoilers: HTMLElement[] = [];

    if (container.nodeType === Node.ELEMENT_NODE && container.nodeName === 'SPAN') {
      if (range.intersectsNode(container)) {
        spoilers.push(container as HTMLElement);
      }
    }

    container.childNodes.forEach(child => {
      spoilers.push(...this.findAllSpoilerTags(child, range));
    });

    return spoilers;
  }

  public checkState(): boolean {
    const isActive = document.queryCommandState(this.commandName);
    this.nodes.button.classList.toggle(this.CSS.buttonActive, isActive);
    return isActive;
  }

}

