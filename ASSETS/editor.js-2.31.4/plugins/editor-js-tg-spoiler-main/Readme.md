![npm](https://img.shields.io/badge/npm-v1.0.1-blue?style=plastic)

# editor-js-tg-spoiler

Inline tool for adding spoilers to text fragments for [Editor.js](https://github.com/codex-team/editor.js), 
supporting export to telegram.

# Installation

## Install via NPM
Get the package

```bash
npm install --save @iizotikov/editor-js-tg-spoiler

// or Yarn

yarn add @iizotikov/editor-js-tg-spoiler
```

## Include module in your application 

```ts
import Spoiler from '@iizotikov/editor-js-tg-spoiler';
```

## Usage

Add a new Tool to the `tools` property of the Editor.js initial config.

```ts
import EditorJs from '@editorjs/editorjs';
import Spoiler from '@iizotikov/editor-js-tg-spoiler';

const editor = new EditorJS({
  // ...
  tools: {
    // ...
    spoiler: Spoiler
  },
});
```

Right now it highlights spoilered text with black background (at Telegram it's looks like default Telegram spoiler). 

If you want to add custom background, just override class `.tg-spoiler` anywhere in your code with `!important` keyword for `background-color`.

I recommend to add spoiler the same color as your text, to create an illusion of hiding.

For example:
```css
/*index.css*/

.tg-spoiler {
  background-color: white !important;
}
```

![image](https://github.com/user-attachments/assets/db6b1888-10d6-4a0f-a6c0-96b066759998)

![image](https://github.com/user-attachments/assets/dce855c2-bacc-4fd3-bcfb-68d8305a6373)


## Output data

Final output looks like this:

```json
{
    "type" : "paragraph",
    "data" : {
        "text" : "<span class=\"tg-spoiler\">Hidden text</span>"
    }
}
```

Also supports spoiler of `bold`, `italic`, `underline` and `strikethrough` texts.

At Telegram it's looks like default Telegram spoiler.

