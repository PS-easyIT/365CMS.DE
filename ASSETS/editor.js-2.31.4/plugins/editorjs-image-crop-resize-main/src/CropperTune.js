import Cropper from "cropperjs";
import 'cropperjs/dist/cropper.css';
import './CropperTune.css'
export default class CropperTune {
    static get isTune() {
        return true;
    }

    constructor({ api, data, config, block }) {
        console.log('cropper tune const', { api, data, block, config });

        this.data = data || {};
        this.api = api;
        this.config = config || {};
        this.block = block;
        this.button = null;
        this.originalImage = null;
        this.scaleX = 1;
        this.scaleY = 1;
        this.cropper = null;

    }
    render() {
        console.log('cropper tune called render method');

        const button = document.createElement('div');
        button.innerHTML = '✂️ Crop-Resize';
        button.classList.add('ce-settings__button');
        button.dataset.tool = 'Crop-Resize';
        button.style.cursor = 'pointer';
        button.addEventListener('click', () => this.openCropper());



        return button;
    }
    save() {
        return this.data;
    }

    createCropButton(icon, text) {
        const cropButton = document.createElement('button');
        cropButton.classList.add('crop-button');
        cropButton.innerHTML = `${icon} <span>${text}</span>`;
        return cropButton
    }

    openCropper() {
        const img = this.block.holder.querySelector('img');
        if (!img) return;

        if (this.cropper) {
            return;
        }

        if (!this.originalImage) {
            this.originalImage = img.src;
        }

        let wrapper = img.parentElement;
        if (!wrapper.classList.contains(".cropper-wrapper")) {
            wrapper = document.createElement('div');
            wrapper.classList.add("cropper-wrapper");
            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);
        }

        wrapper.style.position = "relative";
        wrapper.style.display = "inline-block";


        const oldButtons = wrapper.querySelector('.cropper-btns');
        if (oldButtons) oldButtons.remove();


        //init the cropperjs
        this.cropper = new Cropper(img, {
            aspectRatio: NaN,
            viewMode: 0,
            zoomable: true,
            scalable: true,
        });

        //buttons initialization
        const btnContainer = document.createElement("div");
        btnContainer.classList.add("cropper-btns");
        btnContainer.style.position = "relative";
        btnContainer.style.display = 'flex';
        btnContainer.style.gap = '6px';
        btnContainer.style.marginBottom = '8px'
        //apply crop , rotate buttons
        const applyButton = this.createCropButton("✔️", "Apply");
        const cancelButton = this.createCropButton("❌", "Cancel");
        const resetButton = this.createCropButton("🔄", "Reset");
        const rotateMinusFourtyFive = this.createCropButton("↪️", "Rotate -45");
        const rotatePlusFourtyFive = this.createCropButton("↩️", "Rotate +45");
        const invertY = this.createCropButton("↕️", "Flip-Y");
        const invertX = this.createCropButton("↔️", "Flip-X");


        const selectElement = document.createElement('select');
        selectElement.id = 'dropdown';
        const opts = [
            { value: '', text: 'Select width' },
            { value: 300, text: '300px' },
            { value: 500, text: '500px' },
            { value: 650, text: '650px' },
            { value: 800, text: '800px' }
        ];

        opts.forEach(item => {
            const option = document.createElement("option");
            option.textContent = item.text;
            option.value = item.value;
            selectElement.append(option);
        });


        btnContainer.append(selectElement, applyButton, cancelButton, resetButton, rotateMinusFourtyFive, rotatePlusFourtyFive, invertX, invertY);
        wrapper.insertBefore(btnContainer, img);

        selectElement.addEventListener('change', (e) => {
            const selectedWidth = e.target.value;
            if (selectedWidth && selectedWidth !== '') {
                const canvas = this.cropper.getCroppedCanvas({ width: selectedWidth });
                this.cropper.destroy();
                this.cropper = null;
                img.src = canvas.toDataURL("image/png");
                this.cropper = new Cropper(img, {
                    aspectRatio: NaN,
                    viewMode: 0,
                    zoomable: true,
                    scalable: true,
                })
            }
        })



        //function rotate image to negative 45 degs:
        rotateMinusFourtyFive.onclick = () => {
            this.cropper.rotate(45);
        }

        //function rotate image to plus 45 degrees:
        rotatePlusFourtyFive.onclick = () => {
            this.cropper.rotate(-45);
        }

        invertX.onclick = () => {
            this.scaleX = this.scaleX === 1 ? -1 : 1;
            this.cropper.scaleX(this.scaleX);
        }

        invertY.onclick = () => {
            this.scaleY = this.scaleY === 1 ? -1 : 1;
            this.cropper.scaleY(this.scaleY);

        }


        applyButton.onclick = () => {
            const canvas = this.cropper.getCroppedCanvas();
            img.src = canvas.toDataURL("image/png");
            this.data.croppedImage = img.src;
            this.cropper.destroy();
            this.cropper = null;
            btnContainer.remove();
        }

        cancelButton.onclick = () => {
            this.cropper.destroy();
            this.cropper = null;
            img.src = this.originalImage;
            btnContainer.remove();
        }
        resetButton.onclick = () => {
            this.cropper.destroy();
            this.cropper = null;
            img.src = this.originalImage;
            this.cropper = new Cropper(img, {
                aspectRatio: NaN,
                viewMode: 0,
                zoomable: true,
                scalable: true,
            })
        }
    }


}