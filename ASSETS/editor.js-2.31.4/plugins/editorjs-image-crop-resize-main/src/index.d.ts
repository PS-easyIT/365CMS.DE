import Cropper from "cropperjs";

export interface CropperParams {
    api: any,
    data?: { croppedImage: string },
    config?: any,
    block?: any,
}

export default class CropperTune {
    static readonly isTune: boolean;

    constructor({ api, data, config, block }: CropperParams);

    render(): HTMLElement;

    save(): { croppedImage: string };
    openCropper(): void;

    protected createCropButton(icon: string, text: string): HTMLButtonElement;

    private cropper: Cropper;

    private scaleX: number;

    private scaleY: number;

}