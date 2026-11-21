<template>
    <div class="tt-rich" :class="{ 'is-focused': focused }">
        <div v-if="editor" class="tt-rich-bar" role="toolbar" :aria-label="$t('Text formatting')">
            <v-btn-tertiary
                v-for="b in buttons"
                :key="b.label"
                icon
                size="small"
                :active="b.active ? b.active() : false"
                :aria-label="$t(b.label)"
                :title="$t(b.label)"
                @click="b.run()"
            >
                <v-icon size="19">{{ b.icon }}</v-icon>
            </v-btn-tertiary>
            <span class="tt-rich-sep"></span>
            <v-btn-tertiary icon size="small" :aria-label="$t('Insert image')" :title="$t('Insert image')" :loading="uploading" @click="$refs.file.click()">
                <v-icon size="19">mdi-image-plus-outline</v-icon>
            </v-btn-tertiary>
            <v-btn-tertiary icon size="small" :aria-label="$t('Insert table')" :title="$t('Insert table')" @click="insertTable">
                <v-icon size="19">mdi-table-plus</v-icon>
            </v-btn-tertiary>
            <v-btn-tertiary v-if="editor.isActive('table')" icon size="small" :aria-label="$t('Delete table')" :title="$t('Delete table')" @click="editor.chain().focus().deleteTable().run()">
                <v-icon size="19">mdi-table-remove</v-icon>
            </v-btn-tertiary>
            <input ref="file" type="file" accept="image/*" class="tt-visually-hidden" tabindex="-1" @change="onImage" />
        </div>
        <EditorContent :editor="editor" class="tt-rich-body cms-content" />
    </div>
</template>

<script>
/*
 * Rich text editor for product descriptions and pages. v-model is HTML.
 *   <RichText v-model="form.description" folder="products" />
 * The server cleans the HTML before showing it on the website.
 */
import { markRaw } from "vue";
import { Editor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import Image from "@tiptap/extension-image";
import TextAlign from "@tiptap/extension-text-align";
import Table from "@tiptap/extension-table";
import TableRow from "@tiptap/extension-table-row";
import TableHeader from "@tiptap/extension-table-header";
import TableCell from "@tiptap/extension-table-cell";
import api, { fileUrl } from "@/api";

export default {
    name: "RichText",
    components: { EditorContent },
    props: {
        modelValue: { type: String, default: "" },
        folder: { type: String, default: "pages" },
        placeholder: { type: String, default: "" },
    },
    emits: ["update:modelValue"],
    data: () => ({ editor: null, focused: false, uploading: false }),
    computed: {
        buttons() {
            const e = this.editor;
            const c = () => e.chain().focus();
            return [
                { label: "Heading", icon: "mdi-format-header-2", run: () => c().toggleHeading({ level: 2 }).run(), active: () => e.isActive("heading", { level: 2 }) },
                { label: "Subheading", icon: "mdi-format-header-3", run: () => c().toggleHeading({ level: 3 }).run(), active: () => e.isActive("heading", { level: 3 }) },
                { label: "Bold", icon: "mdi-format-bold", run: () => c().toggleBold().run(), active: () => e.isActive("bold") },
                { label: "Italic", icon: "mdi-format-italic", run: () => c().toggleItalic().run(), active: () => e.isActive("italic") },
                { label: "Underline", icon: "mdi-format-underline", run: () => c().toggleUnderline().run(), active: () => e.isActive("underline") },
                { label: "Bulleted list", icon: "mdi-format-list-bulleted", run: () => c().toggleBulletList().run(), active: () => e.isActive("bulletList") },
                { label: "Numbered list", icon: "mdi-format-list-numbered", run: () => c().toggleOrderedList().run(), active: () => e.isActive("orderedList") },
                { label: "Quote", icon: "mdi-format-quote-close", run: () => c().toggleBlockquote().run(), active: () => e.isActive("blockquote") },
                { label: "Align left", icon: "mdi-format-align-left", run: () => c().setTextAlign("left").run(), active: () => e.isActive({ textAlign: "left" }) },
                { label: "Align centre", icon: "mdi-format-align-center", run: () => c().setTextAlign("center").run(), active: () => e.isActive({ textAlign: "center" }) },
                { label: "Link", icon: "mdi-link-variant", run: () => this.setLink(), active: () => e.isActive("link") },
                { label: "Undo", icon: "mdi-undo", run: () => c().undo().run() },
                { label: "Redo", icon: "mdi-redo", run: () => c().redo().run() },
            ];
        },
    },
    watch: {
        modelValue(v) {
            if (this.editor && v !== this.editor.getHTML()) this.editor.commands.setContent(v || "", false);
        },
    },
    mounted() {
        // markRaw: Vue must not make the editor reactive (it is huge and would loop forever).
        this.editor = markRaw(new Editor({
            content: this.modelValue || "",
            extensions: [
                StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
                Underline,
                Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { rel: "noopener", target: null } }),
                Image,
                TextAlign.configure({ types: ["heading", "paragraph"] }),
                Table.configure({ resizable: false }),
                TableRow,
                TableHeader,
                TableCell,
            ],
            editorProps: { attributes: { "aria-label": this.$t(this.placeholder || "Description"), spellcheck: "true" } },
            onUpdate: ({ editor }) => this.$emit("update:modelValue", editor.isEmpty ? "" : editor.getHTML()),
            onFocus: () => (this.focused = true),
            onBlur: () => (this.focused = false),
        }));
    },
    beforeUnmount() {
        this.editor?.destroy();
    },
    methods: {
        setLink() {
            const previous = this.editor.getAttributes("link").href || "";
            const url = window.prompt(this.$t("Link address (leave empty to remove)"), previous);
            if (url === null) return;
            const chain = this.editor.chain().focus().extendMarkRange("link");
            url ? chain.setLink({ href: url }).run() : chain.unsetLink().run();
        },
        insertTable() {
            this.editor.chain().focus().insertTable({ rows: 3, cols: 2, withHeaderRow: true }).run();
        },
        async onImage(e) {
            const file = e.target.files[0];
            e.target.value = "";
            if (!file) return;
            const body = new FormData();
            body.append("file", file);
            body.append("folder", this.folder);
            this.uploading = true;
            try {
                const { data } = await api.post("/admin/uploads", body);
                this.editor.chain().focus().setImage({ src: fileUrl(data.path), alt: "" }).run();
            } finally {
                this.uploading = false;
            }
        },
    },
};
</script>
