<template>
    <div class="cms-editor" :class="{ 'is-error': error }">
        <div v-if="editor" class="cms-toolbar">
            <!-- Row 1: text style -->
            <div class="cms-row">
                <select class="cms-select" :value="blockType" title="Text style" @change="setBlock($event.target.value)">
                    <option value="p">Paragraph</option>
                    <option v-for="l in 4" :key="l" :value="l">Heading {{ l }}</option>
                </select>
                <select class="cms-select" :value="attr('fontFamily')" title="Font family" @change="setFont($event.target.value)">
                    <option value="">Default font</option>
                    <option v-for="f in fonts" :key="f.label" :value="f.value">{{ f.label }}</option>
                </select>
                <select class="cms-select cms-select-sm" :value="attr('fontSize')" title="Font size" @change="setSize($event.target.value)">
                    <option value="">Size</option>
                    <option v-for="s in sizes" :key="s" :value="s">{{ s }}</option>
                </select>
                <span class="cms-sep"></span>
                <template v-for="(group, gi) in groups" :key="gi">
                    <v-btn v-for="b in group" :key="b.icon" icon size="small" variant="text" :class="{ 'is-on': b.on }" :disabled="b.disabled" :aria-label="b.title" @click="b.run">
                        <v-icon size="19">{{ b.icon }}</v-icon>
                        <v-tooltip activator="parent" location="top">{{ b.title }}</v-tooltip>
                    </v-btn>
                    <span class="cms-sep"></span>
                </template>
                <label class="cms-color" title="Text color">
                    <v-icon size="19">mdi-format-color-text</v-icon><i :style="{ background: attr('color') || '#1c1917' }"></i>
                    <input type="color" :value="attr('color') || '#1c1917'" @input="editor.chain().focus().setColor($event.target.value).run()" />
                </label>
                <label class="cms-color" title="Highlight color">
                    <v-icon size="19">mdi-format-color-highlight</v-icon><i :style="{ background: attr('backgroundColor') || '#fde047' }"></i>
                    <input type="color" :value="attr('backgroundColor') || '#fde047'" @input="editor.chain().focus().setBgColor($event.target.value).run()" />
                </label>
                <v-btn icon size="small" variant="text" aria-label="Clear colors" @click="editor.chain().focus().unsetColor().unsetBgColor().run()">
                    <v-icon size="18">mdi-format-clear</v-icon><v-tooltip activator="parent" location="top">Clear text and highlight colors</v-tooltip>
                </v-btn>
            </div>

            <!-- Row 2: contextual image controls -->
            <div v-if="editor.isActive('image')" class="cms-row cms-row-image">
                <span class="cms-hint"><v-icon size="16">mdi-image-outline</v-icon> Image</span>
                <v-btn v-for="w in ['25%', '50%', '75%', '100%']" :key="w" size="x-small" variant="tonal" :color="attrImg('width') === w ? 'primary' : undefined" class="text-none" @click="editor.chain().focus().updateAttributes('image', { width: w }).run()">{{ w }}</v-btn>
                <span class="cms-sep"></span>
                <v-btn v-for="a in ['left', 'center', 'right']" :key="a" icon size="x-small" variant="tonal" :color="attrImg('align') === a ? 'primary' : undefined" :aria-label="'Align image ' + a" @click="editor.chain().focus().updateAttributes('image', { align: a }).run()">
                    <v-icon size="16">mdi-format-align-{{ a }}</v-icon>
                </v-btn>
                <span class="cms-sep"></span>
                <v-btn size="x-small" variant="text" color="error" class="text-none" prepend-icon="mdi-delete-outline" @click="editor.chain().focus().deleteSelection().run()">Remove</v-btn>
            </div>
        </div>

        <editor-content :editor="editor" class="cms-surface" />
        <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp,image/gif" hidden @change="onPick" />

        <!-- Link dialog -->
        <v-dialog v-model="linkDlg.open" max-width="460">
            <v-card>
                <v-card-title class="text-subtitle-1 font-weight-bold pt-4">Insert link</v-card-title>
                <v-card-text>
                    <v-btn-toggle v-model="linkDlg.type" mandatory density="comfortable" variant="outlined" class="mb-4" divided>
                        <v-btn value="internal" class="text-none">Internal page</v-btn>
                        <v-btn value="external" class="text-none">External URL</v-btn>
                    </v-btn-toggle>
                    <v-autocomplete v-if="linkDlg.type === 'internal'" v-model="linkDlg.href" :items="pageItems" item-title="title" item-value="value" label="Choose a page" variant="outlined" density="comfortable" hide-details="auto" no-data-text="No pages yet" />
                    <v-text-field v-else v-model="linkDlg.href" label="URL" placeholder="https://example.com, mailto:… or tel:…" variant="outlined" density="comfortable" :error-messages="linkDlg.error" />
                    <v-checkbox v-model="linkDlg.newTab" label="Open in a new tab" density="compact" hide-details class="mt-2" />
                </v-card-text>
                <v-card-actions class="px-4 pb-4">
                    <v-btn v-if="editor && editor.isActive('link')" variant="text" color="error" class="text-none" @click="removeLink">Remove link</v-btn>
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="linkDlg.open = false">Cancel</v-btn>
                    <v-btn color="#0f9d6b" variant="flat" class="text-none" @click="applyLink">Apply</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="toast.open" color="#b3261e" timeout="3500" location="bottom right">{{ toast.text }}</v-snackbar>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch, onBeforeUnmount } from "vue";
import axios from "axios";
import { Extension } from "@tiptap/core";
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import TextStyle from "@tiptap/extension-text-style";
import Color from "@tiptap/extension-color";
import FontFamily from "@tiptap/extension-font-family";
import Link from "@tiptap/extension-link";
import Image from "@tiptap/extension-image";
import Table from "@tiptap/extension-table";
import TableRow from "@tiptap/extension-table-row";
import TableHeader from "@tiptap/extension-table-header";
import TableCell from "@tiptap/extension-table-cell";
import "./css/cms-content.scss";

const props = defineProps({
    modelValue: { type: String, default: "" },
    pages: { type: Array, default: () => [] }, // [{ title, slug }] for internal links
    error: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);

// font-size and highlight are stored as inline styles on a <span> (textStyle mark), which the server sanitizer allows
const TextStyleExtras = Extension.create({
    name: "textStyleExtras",
    addGlobalAttributes() {
        return [{
            types: ["textStyle"],
            attributes: {
                fontSize: { default: null, parseHTML: (el) => el.style.fontSize || null, renderHTML: (a) => (a.fontSize ? { style: `font-size: ${a.fontSize}` } : {}) },
                backgroundColor: { default: null, parseHTML: (el) => el.style.backgroundColor || null, renderHTML: (a) => (a.backgroundColor ? { style: `background-color: ${a.backgroundColor}` } : {}) },
            },
        }];
    },
    addCommands() {
        return {
            setFontSize: (v) => ({ chain }) => chain().setMark("textStyle", { fontSize: v }).run(),
            unsetFontSize: () => ({ chain }) => chain().setMark("textStyle", { fontSize: null }).removeEmptyTextStyle().run(),
            setBgColor: (v) => ({ chain }) => chain().setMark("textStyle", { backgroundColor: v }).run(),
            unsetBgColor: () => ({ chain }) => chain().setMark("textStyle", { backgroundColor: null }).removeEmptyTextStyle().run(),
        };
    },
});

// image with width (as a % style) and alignment (as a class)
const CmsImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: { default: null, parseHTML: (el) => el.style.width || null, renderHTML: (a) => (a.width ? { style: `width: ${a.width}` } : {}) },
            align: { default: "center", parseHTML: (el) => (el.className.match(/cms-align-(left|center|right)/) || [])[1] || "center", renderHTML: (a) => ({ class: `cms-align-${a.align}` }) },
        };
    },
});

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3, 4] } }),
        Underline, TextStyle, Color, FontFamily, TextStyleExtras,
        TextAlign.configure({ types: ["heading", "paragraph"] }),
        Link.configure({ openOnClick: false, autolink: false, HTMLAttributes: { rel: "noopener noreferrer" } }),
        CmsImage.configure({ allowBase64: false }),
        Table.configure({ resizable: false }), TableRow, TableHeader, TableCell,
    ],
    editorProps: { attributes: { class: "cms-content", spellcheck: "true" } },
    onUpdate: ({ editor: e }) => emit("update:modelValue", e.isEmpty ? "" : e.getHTML()),
});

watch(() => props.modelValue, (v) => {
    const e = editor.value;
    if (!e || v === e.getHTML() || (!v && e.isEmpty)) return;
    e.commands.setContent(v || "", false);
});
onBeforeUnmount(() => editor.value && editor.value.destroy());

// ── toolbar state ──
const fonts = [
    { label: "Arial", value: "Arial, sans-serif" },
    { label: "Verdana", value: "Verdana, sans-serif" },
    { label: "Georgia", value: "Georgia, serif" },
    { label: "Times New Roman", value: '"Times New Roman", serif' },
    { label: "Courier New", value: '"Courier New", monospace' },
];
const sizes = ["12px", "14px", "16px", "18px", "20px", "24px", "28px", "32px", "40px"];
const attr = (k) => (editor.value ? editor.value.getAttributes("textStyle")[k] || "" : "");
const attrImg = (k) => (editor.value ? editor.value.getAttributes("image")[k] : "");
const blockType = computed(() => {
    const e = editor.value;
    return e ? [1, 2, 3, 4].find((l) => e.isActive("heading", { level: l })) || "p" : "p";
});
const setBlock = (v) => {
    const c = editor.value.chain().focus();
    (v === "p" ? c.setParagraph() : c.setHeading({ level: Number(v) })).run();
};
const setFont = (v) => (v ? editor.value.chain().focus().setFontFamily(v).run() : editor.value.chain().focus().unsetFontFamily().run());
const setSize = (v) => (v ? editor.value.chain().focus().setFontSize(v).run() : editor.value.chain().focus().unsetFontSize().run());

const groups = computed(() => {
    const e = editor.value;
    if (!e) return [];
    const c = () => e.chain().focus();
    const b = (icon, title, run, on = false, disabled = false) => ({ icon, title, run, on, disabled });
    return [
        [
            b("mdi-format-bold", "Bold (Ctrl+B)", () => c().toggleBold().run(), e.isActive("bold")),
            b("mdi-format-italic", "Italic (Ctrl+I)", () => c().toggleItalic().run(), e.isActive("italic")),
            b("mdi-format-underline", "Underline (Ctrl+U)", () => c().toggleUnderline().run(), e.isActive("underline")),
        ],
        ["left", "center", "right", "justify"].map((a) => b(`mdi-format-align-${a}`, `Align ${a}`, () => c().setTextAlign(a).run(), e.isActive({ textAlign: a }))),
        [
            b("mdi-format-list-bulleted", "Bulleted list", () => c().toggleBulletList().run(), e.isActive("bulletList")),
            b("mdi-format-list-numbered", "Numbered list", () => c().toggleOrderedList().run(), e.isActive("orderedList")),
            b("mdi-format-quote-close", "Blockquote", () => c().toggleBlockquote().run(), e.isActive("blockquote")),
            b("mdi-minus", "Horizontal divider", () => c().setHorizontalRule().run()),
        ],
        [
            b("mdi-link-variant", "Link (internal or external)", openLink, e.isActive("link")),
            b("mdi-image-plus-outline", "Insert image", () => fileInput.value.click()),
            b("mdi-table", "Insert table", () => c().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(), e.isActive("table")),
            ...(e.isActive("table")
                ? [
                    b("mdi-table-column-plus-after", "Add column", () => c().addColumnAfter().run()),
                    b("mdi-table-row-plus-after", "Add row", () => c().addRowAfter().run()),
                    b("mdi-table-column-remove", "Delete column", () => c().deleteColumn().run()),
                    b("mdi-table-row-remove", "Delete row", () => c().deleteRow().run()),
                    b("mdi-table-remove", "Delete table", () => c().deleteTable().run()),
                ]
                : []),
        ],
        [
            b("mdi-undo", "Undo (Ctrl+Z)", () => c().undo().run(), false, !e.can().undo()),
            b("mdi-redo", "Redo (Ctrl+Y)", () => c().redo().run(), false, !e.can().redo()),
        ],
    ];
});

// ── links ──
const linkDlg = reactive({ open: false, type: "internal", href: "", newTab: false, error: "" });
const pageItems = computed(() => props.pages.map((p) => ({ title: `${p.title}  (/${p.slug})`, value: "/" + p.slug })));
function openLink() {
    const cur = editor.value.getAttributes("link");
    linkDlg.href = cur.href || "";
    linkDlg.type = !cur.href || cur.href.startsWith("/") ? "internal" : "external";
    linkDlg.newTab = cur.target === "_blank";
    linkDlg.error = "";
    linkDlg.open = true;
}
function applyLink() {
    const href = (linkDlg.href || "").trim();
    if (!href) return (linkDlg.error = "Enter a URL or choose a page.");
    if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(href)) return (linkDlg.error = "Use http(s)://, mailto:, tel: or a page path.");
    editor.value.chain().focus().extendMarkRange("link").setLink({ href, target: linkDlg.newTab ? "_blank" : null }).run();
    linkDlg.open = false;
}
function removeLink() {
    editor.value.chain().focus().extendMarkRange("link").unsetLink().run();
    linkDlg.open = false;
}

// ── image upload ──
const fileInput = ref(null);
const toast = reactive({ open: false, text: "" });
const fail = (t) => Object.assign(toast, { open: true, text: t });
async function onPick(ev) {
    const file = ev.target.files && ev.target.files[0];
    ev.target.value = "";
    if (!file) return;
    if (!/^image\/(png|jpe?g|webp|gif)$/.test(file.type)) return fail("Only JPG, PNG, WebP or GIF images are allowed.");
    if (file.size > 4 * 1024 * 1024) return fail("Image must be 4 MB or smaller.");
    const body = new FormData();
    body.append("image", file);
    try {
        const { data } = await axios.post("/api/cms/uploads", body);
        editor.value.chain().focus().setImage({ src: data.url, alt: file.name.replace(/\.[^.]+$/, "") }).run();
    } catch (e) {
        fail((e.response && e.response.data && (e.response.data.message || Object.values(e.response.data.errors || {})[0]?.[0])) || "Image upload failed.");
    }
}
</script>

<style>
.cms-editor { border: 1px solid #e7e2dd; border-radius: 10px; background: #fff; overflow: hidden; }
.cms-editor.is-error { border-color: #dc2626; }
.cms-toolbar { position: sticky; top: 0; z-index: 2; background: #fbfafa; border-bottom: 1px solid #e7e2dd; padding: 6px 8px; }
.cms-row { display: flex; flex-wrap: wrap; align-items: center; gap: 2px; }
.cms-row + .cms-row { margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e7e2dd; }
.cms-row .v-btn.is-on { background: #e0f5ea; color: #0f7a4d; }
.cms-sep { width: 1px; height: 20px; background: #e0dbd6; margin: 0 5px; }
.cms-select { height: 32px; border: 1px solid #e0dbd6; border-radius: 6px; background: #fff; padding: 0 6px; font-size: 13px; margin-right: 4px; max-width: 150px; }
.cms-select-sm { max-width: 78px; }
.cms-color { position: relative; width: 32px; height: 32px; display: inline-grid; place-items: center; border-radius: 50%; cursor: pointer; }
.cms-color:hover { background: rgba(0, 0, 0, 0.05); }
.cms-color i { position: absolute; left: 8px; right: 8px; bottom: 4px; height: 3px; border-radius: 2px; }
.cms-color input { position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
.cms-hint { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #78716c; margin-right: 6px; }
.cms-surface .ProseMirror { min-height: 340px; max-height: 70vh; overflow-y: auto; padding: 18px 22px; outline: none; }
.cms-surface .ProseMirror img.ProseMirror-selectednode { outline: 3px solid #0f9d6b; }
.cms-surface .ProseMirror .selectedCell { background: #e0f5ea; }
.cms-surface .ProseMirror p.is-editor-empty:first-child::before { content: attr(data-placeholder); color: #a8a29e; float: left; height: 0; pointer-events: none; }
</style>
