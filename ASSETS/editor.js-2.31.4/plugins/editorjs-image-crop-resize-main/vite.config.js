import { defineConfig } from "vite";

export default defineConfig({
    build: {
        lib: {
            entry: 'src/CropperTune.js',
            name: "CropperTune",
            fileName: "cropper-tune"
        },
        rollupOptions: {
            external: ["@editorjs/editorjs", "cropperjs"],
            output: {
                globals: {
                    "@editorjs/editorjs": "EditorJs",
                    "cropperjs": "Cropper"
                }
            }
        }
    }
})