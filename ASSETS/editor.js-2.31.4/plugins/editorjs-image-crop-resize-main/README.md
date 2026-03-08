# editorjs-image-crop-resize
A plugin for cropping , resizing and editing the images inside the 
editorjs's image block.
## 🏅 Features
✂️ Crop & Resize — adjust images with precise cropping and custom width presets.

🔄 Rotate & Flip — rotate images ±45° and flip horizontally or vertically.

🎨 Non-destructive editing — original image is preserved until you apply changes.

⚡ Seamless Editor.js integration — works as a tune for the Image tool.

🖼️ Base64 save option — cropped images are stored directly in Editor.js output data.
## 📦 Installation

Install my-project with npm

```bash
  npm install editorjs-image-crop-resize
```
    
## ⚙️ Usage/Examples

```javascript
//import editorjs and its imageTool first

import CropperTune from "editorjs-image-crop-resize";
import "editorjs-image-crop-resize/dist/cropper-tune.css";

const editor = new EditorJS({
  holder: "editorjs",
  tools: {
    image: {
      class: ImageTool,
      config : {
          //backend image uploader logic
      }
      tunes: ["CropperTune"],
    },
    CropperTune: {
      class: CropperTune
    }
  }
});
```
You also can load the package via cdn

```
<!-- CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editorjs-image-crop-resize@1.0.0/dist/cropper-tune.css">


<!-- UMD build -->
<script src="https://cdn.jsdelivr.net/npm/editorjs-image-crop-resize@1.0.0/dist/cropper-tune.umd.js"></script>

```


## 🌐  Demo

Live link : https://editorjs-image-crop-resize.vercel.app/

![](https://res.cloudinary.com/dfmtemqoz/image/upload/Screenshot_2025-09-24_003003_khe2k1.png)


## Contributions / Raise Issue
Contributions are welcome. Feel free to add new features and make a PR. Raise issues in the issues section.

- Fork the git repo
- Add new branch for features 
- Commit the fork locally
- Make a PR


## Dependencies
Install the required deps beforehand.

- editorjs : ^2.29.1
-  cropperjs : ^1.5.13
## My Socials
[![linkedin](https://img.shields.io/badge/linkedin-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/muhammad-affan-anass/)
[![GMAIL](https://img.shields.io/badge/Gmail-D14836?style=for-the-badge&logo=gmail&logoColor=white)](mailto:muhammadaffanpvt@gmail.com
)