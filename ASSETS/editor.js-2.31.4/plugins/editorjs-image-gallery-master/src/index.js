/**
 * Build styles
 */
require('./index.css').toString();
require('./gallery-grid/gallery-grid.css').toString();

/**
 * Build gallery grid
 */
const gg = require('./gallery-grid/gallery-grid');

/**
 * ImageGallery Tool for the Editor.js
 * Works with image URLs and requires no server-side uploader.
 *
 * @typedef {object} ImageGalleryData
 * @property {string[]} urls — images URL
 * @property {boolean} editImages - activate or deactivate for show textarea with images urls
 * @property {boolean} bkgMode - activate or deactivate dark mode
 * @property {boolean} layoutDefault - should layout be the default
 * @property {boolean} layoutHorizontal - should layout be the horizontal
 * @property {boolean} layoutSquare - should layout be the square
 * @property {boolean} layoutWithGap - should layout be with gap
 * @property {boolean} layoutWithFixedSize - should layout be with fixed size
 */
class ImageGallery {
    static get toolbox() {
        return {
            title: 'Image Gallery',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-images" viewBox="0 0 16 16"><path d="M4.502 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/> <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.998 2zM14 2H4a1 1 0 0 0-1 1h9.002a2 2 0 0 1 2 2v7A1 1 0 0 0 15 11V3a1 1 0 0 0-1-1zM2.002 4a1 1 0 0 0-1 1v8l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71a.5.5 0 0 1 .577-.094l1.777 1.947V5a1 1 0 0 0-1-1h-10z"/></svg>'
        };
    }

    /**
     * Render plugin`s main Element and fill it with saved data
     *
     * @param {{data: ImageGalleryData, api: object}}
     *   data — previously saved data
     *   api - Editor.js API
     */
    constructor({data, api}) {
        
        /**
         * Nodes cache
         */
        this.nodes = {
            wrapper: null,
            urls: null,
            editImages: null,
            bkgMode: null,
            layoutDefault: null,
            layoutHorizontal: null,
            layoutSquare: null,
            layoutWithGap: null,
            layoutWithFixedSize: null,
        };
        
        /**
         * Tool's initial data
         */
        this.data = {
            urls: data.urls || '',
            editImages: data.editImages !== undefined ? data.editImages : true,
            bkgMode: data.bkgMode !== undefined ? data.bkgMode : false,
            layoutDefault: data.layoutDefault !== undefined ? data.layoutDefault : true,
            layoutHorizontal: data.layoutHorizontal !== undefined ? data.layoutHorizontal : false,
            layoutSquare: data.layoutSquare !== undefined ? data.layoutSquare : false,
            layoutWithGap: data.layoutWithGap !== undefined ? data.layoutWithGap : false,
            layoutWithFixedSize: data.layoutWithFixedSize !== undefined ? data.layoutWithFixedSize : false,
        };

        /**
         * Editor.js API
         */
        this.api = api
        this.wrapper = undefined;

        /**
         * Available Gallery settings
         */
        this.settings = [
            {
                name: 'editImages',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/></svg>`,
                title: 'Edit Images',
            },
            {
                name: 'bkgMode',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layers-fill" viewBox="0 0 16 16"><path d="M7.765 1.559a.5.5 0 0 1 .47 0l7.5 4a.5.5 0 0 1 0 .882l-7.5 4a.5.5 0 0 1-.47 0l-7.5-4a.5.5 0 0 1 0-.882l7.5-4z"/><path d="m2.125 8.567-1.86.992a.5.5 0 0 0 0 .882l7.5 4a.5.5 0 0 0 .47 0l7.5-4a.5.5 0 0 0 0-.882l-1.86-.992-5.17 2.756a1.5 1.5 0 0 1-1.41 0l-5.17-2.756z"/></svg>`,
                title: 'Background Mode',
            },
            {
                name: 'layoutDefault',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-columns" viewBox="0 0 16 16"><path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V2zm8.5 0v8H15V2H8.5zm0 9v3H15v-3H8.5zm-1-9H1v3h6.5V2zM1 14h6.5V6H1v8z"/></svg>`,
                title: 'Default Layout',
            },
            {
                name: 'layoutHorizontal',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid-3x2-gap" viewBox="0 0 16 16"><path d="M4 4v2H2V4h2zm1 7V9a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1zm0-5V4a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1zm5 5V9a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1zm0-5V4a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1zM9 4v2H7V4h2zm5 0h-2v2h2V4zM4 9v2H2V9h2zm5 0v2H7V9h2zm5 0v2h-2V9h2zm-3-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V4zm1 4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-2z"/></svg>`,
                title: 'Horizontal Layout',
            },
            {
                name: 'layoutSquare',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-square" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/></svg>`,
                title: 'Square Layout',
            },
            {
                name: 'layoutWithGap',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-columns-gap" viewBox="0 0 16 16"><path d="M6 1v3H1V1h5zM1 0a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zm14 12v3h-5v-3h5zm-5-1a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-5zM6 8v7H1V8h5zM1 7a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1H1zm14-6v7h-5V1h5zm-5-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1h-5z"/></svg>`,
                title: 'Layout With Gap',
            },
            {
                name: 'layoutWithFixedSize',
                icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid-3x3" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13zM1.5 1a.5.5 0 0 0-.5.5V5h4V1H1.5zM5 6H1v4h4V6zm1 4h4V6H6v4zm-1 1H1v3.5a.5.5 0 0 0 .5.5H5v-4zm1 0v4h4v-4H6zm5 0v4h3.5a.5.5 0 0 0 .5-.5V11h-4zm0-1h4V6h-4v4zm0-5h4V1.5a.5.5 0 0 0-.5-.5H11v4zm-1 0V1H6v4h4z"/></svg>`,
                title: 'Layout With Fixed Size',
            },
        ];

        /**
         * Save block index
         */
        this.blockIndex = this.api.blocks.getCurrentBlockIndex();
    }

    /**
     * Creates a Block:
     *  1) Creates a textarea to paste images urls
     *  2) Start to load all urls
     *  3) After loading, append images to gallery
     *
     * @public
     */
    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('image-gallery');

        if (this.data && this.data.urls) {
            this._imageGallery(this.data.urls);
            return this.wrapper;
        }

        let urls

        const textarea = document.createElement('textarea');
        textarea.className = "image-gallery-" + this.blockIndex;
        textarea.placeholder = 'Paste your photos URL ...';
        ['paste', 'change', 'keyup'].forEach(evt =>
            textarea.addEventListener(evt, (event) => {
                if (evt === 'paste')
                    urls = event.clipboardData.getData('text').split("\n");
                else
                    urls = textarea.value.split("\n");
                this._imageGallery(urls);
            }, false)
        );

        textarea.value = this.data && this.data.urls ? this.data.urls : '';

        this.wrapper.appendChild(textarea);

        this.nodes.wrapper = this.wrapper;
        this.nodes.urls = urls;

        return this.wrapper;
    }

    /**
     * @public
     * @param {Element} blockContent - Tool's wrapper
     * @returns {ImageGalleryData}
     */
    save(blockContent) {
        const photos = blockContent.querySelectorAll('img');
        const imgUrls = [];

        photos.forEach((img, index) => {
            imgUrls[index] = img.src
        });

        return Object.assign(this.data, {
            urls: imgUrls,
        });
    }

    validate(savedData) {
        let imagesToSave = [];
        let indexCount = 0;

        savedData.urls.forEach((url) => {
            if (url.trim() !== "" && this._isImgUrl(url)) {
                imagesToSave[indexCount] = url;
                indexCount++;
            }
        });

        savedData.urls = imagesToSave

        return true;
    }

    /**
     * Sanitizer rules
     */
    static get sanitize() {
        return {
            urls: {},
            editImages: {},
            bkgMode: {},
            layoutDefault: {},
            layoutHorizontal: {},
            layoutSquare: {},
            layoutWithGap: {},
            layoutWithFixedSize: {},
        };
    }


    /**
     * Returns images data
     *
     * @returns {ImageGalleryData}
     */
    get data() {
        return this._data;
    }

    /**
     * Set images data and update the view
     *
     * @param {ImageGalleryData} data
     */
    set data(data) {
            this._data = Object.assign({}, this.data, data);

        if (this.nodes.urls) {
            this.nodes.urls = this.data.urls;
        }
    }

    /**
     * Makes buttons with tunes: background mode, layout default, layout horizontal,
     * square layout, layout with gap and layout with fixed size
     *
     * @returns {HTMLDivElement}
     */
    renderSettings() {
        const wrp = document.createElement('div');

        this.settings.forEach(tune => {
            let button = document.createElement('div');

            button.classList.add('cdx-settings-button');
            button.innerHTML = tune.icon;
            button.title = tune.title;
            wrp.appendChild(button);

            button.addEventListener('click', () => {
                this._toggleTune(tune.name);
                button.classList.toggle('cdx-settings-button--active');
            });
        });

        return wrp;
    }

    /**
     * @private
     * Click on the Settings Button
     * @param {string} tune — tune name from this.settings
     */
    _toggleTune(tune) {
        if (tune === "bkgMode" || tune === "editImages") {
            this.data[tune] = !this.data[tune];
        } else {
            this.settings.forEach(t => {
                if (t.name !== "bkgMode" && t.name !== "editImages") {
                    if (t.name === tune)
                        this.data[t.name] = true;
                    else
                        this.data[t.name] = false;
                }
            });
        }

        this._acceptTuneView();
    }

    /**
     * Add specified logic corresponds with activated tunes
     * @private
     */
    _acceptTuneView() {

        let gallery = document.querySelector('div#image-gallery-' + this.blockIndex + ' > div.gg-box');
        let urlsInput = document.querySelector('textarea.image-gallery-' + this.blockIndex);

        if (gallery !== null) {

            gallery.getAttributeNames().forEach(attr => {
                gallery.removeAttribute(attr)
            });

            gallery.className = '';
            gallery.classList.add('gg-box');
            gallery.id = '';

            this.settings.forEach(tune => {
                switch (tune.name) {
                    case 'editImages':
                        if (this.data.editImages && urlsInput.classList.contains('textarea-hide'))
                            urlsInput.classList.remove('textarea-hide');
                        else if (!this.data.editImages && !urlsInput.classList.contains('textarea-hide'))
                            urlsInput.classList.add('textarea-hide');
                        break;
                    case 'bkgMode':
                        if (this.data.bkgMode) {
                            gallery.classList.add('dark');
                            gg.galleryOptions({
                                selector: ".dark",
                                darkMode: true
                            });
                        }
                        break;
                    case 'layoutDefault':
                        break;
                    case 'layoutHorizontal':
                        if (this.data.layoutHorizontal) {
                            gallery.id = 'horizontal';
                            gg.galleryOptions({
                                selector: "#horizontal",
                                layout: "horizontal"
                            });
                        }
                        break;
                    case 'layoutSquare':
                        if (this.data.layoutSquare) {
                            gallery.id = 'square';
                            gg.galleryOptions({
                                selector: "#square",
                                layout: "square"
                            });
                        }
                        break;
                    case 'layoutWithGap':
                        if (this.data.layoutWithGap) {
                            gallery.id = 'gap';
                            gg.galleryOptions({
                                selector: "#gap",
                                gapLength: 10
                            });
                        }
                        break;
                    case 'layoutWithFixedSize':
                        if (this.data.layoutWithFixedSize) {
                            gallery.id = 'heightWidth';
                            gg.galleryOptions({
                                selector: "#heightWidth",
                                rowHeight: 180,
                                columnWidth: 280
                            });
                        }
                        break;
                }
            });
        }

    }

    /**
     * @private
     * Create image gallery
     * @param {string[]} urls — list of gallery url images
     */
    _imageGallery(urls) {

        let imgCount = 0

        const oldContainers = document.querySelectorAll('#image-gallery-' + this.blockIndex);
        oldContainers.forEach((oldContainer) => {
            if (oldContainer !== null) {
                oldContainer.remove();
            }
        });

        const box = document.createElement('div');
        box.className = 'gg-box';

        urls.forEach((url, index) => {
            if (this._isImgUrl(url) != null) {
                imgCount += 1;
                let img = document.createElement('img');
                img.id = 'gg-img-' + index;
                img.src = url.toString().trim();
                box.appendChild(img);
            }
        });

        const gallery = document.createElement('div');
        gallery.className = 'gg-container';
        gallery.id = 'image-gallery-' + this.blockIndex;
        gallery.appendChild(box)

        //this.wrapper.innerHTML = ''; // Hide url input after paste

        if (imgCount > 0) this.wrapper.appendChild(gallery);

        gg.loadGallery();

        this._acceptTuneView();
    }

    /**
     * @private
     * Validate image url
     * @param {string} imgUrl — Image URL to be validated
     */
    _isImgUrl(imgUrl) {
        return imgUrl.match(/https?:\/\/\S+\.(gif|jpe?g|tiff|png|webp)$/i)
    }

}

module.exports = ImageGallery;