import EditorJS from "@editorjs/editorjs";
import ImageTool from "@editorjs/image";
import CropperTune from "../src/CropperTune.js";
import '../src/CropperTune.css'

const editor = new EditorJS({
    holder: "editorjs",
    tools: {
        image: {
            class: ImageTool,
            config: {
                uploader: {
                    uploadByFile(file) {
                        return Promise.resolve({
                            success: 1,
                            file: { url: URL.createObjectURL(file) },
                        });
                    },
                },
            },
            tunes: ['Cropper'],
        },
        Cropper: {
            class: CropperTune
        }
    },
    data: {
        blocks: [



            {
                type: 'image',
                data: {
                    file: {
                        url: 'https://images.unsplash.com/photo-1586165368502-1bad197a6461?q=80&w=1258&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
                    }
                }
            }
        ]
    }


});

const saveBtn = document.getElementById('save-btn');
const oupt = document.getElementById('output');
saveBtn.addEventListener("click", async () => {
    try {
        let savedData = await editor.save();
        savedData?.blocks?.forEach((block) => {
            if (block.type == "image" && block.tunes?.Cropper?.croppedImage) {
                block.tunes.Cropper.croppedImage = block.tunes.Cropper.croppedImage.slice(0, 20) + '...';
            }
        })
        oupt.textContent = JSON.stringify(savedData, null, 2);
        oupt.style.display = 'block';
    } catch (error) {
        console.error(error)
    }


})