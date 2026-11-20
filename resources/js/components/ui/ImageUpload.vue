<template>
    <div class="tt-upload">
        <div
            class="tt-upload-box"
            :class="{ 'is-wide': wide, 'is-filled': !!modelValue, 'is-drag': dragging }"
            role="button"
            tabindex="0"
            :aria-label="$t(modelValue ? 'Replace image' : 'Upload image')"
            @click="pick"
            @keydown.enter.prevent="pick"
            @keydown.space.prevent="pick"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <v-progress-circular v-if="uploading" indeterminate size="26" width="3" color="primary" />
            <img v-else-if="modelValue" :src="url" :alt="$t(label)" />
            <v-icon v-else icon="mdi-image-plus-outline" size="26" />
        </div>
        <div class="tt-upload-meta">
            <b>{{ $t(label) }}</b>
            <span>{{ $t(hint || "PNG, JPG, WEBP or SVG, up to 4 MB.") }}</span>
            <div class="tt-upload-actions">
                <v-btn-secondary size="small" :disabled="uploading" @click="pick">
                    {{ $t(modelValue ? "Replace" : "Upload") }}
                </v-btn-secondary>
                <v-btn-tertiary v-if="modelValue" size="small" :disabled="uploading" @click="$emit('update:modelValue', null)">
                    {{ $t("Remove") }}
                </v-btn-tertiary>
            </div>
        </div>
        <input ref="input" type="file" accept="image/*" class="tt-visually-hidden" tabindex="-1" @change="onPick" />
    </div>
</template>

<script>
/*
 * Uploads an image to storage and returns its path through v-model.
 *   <ImageUpload v-model="form.logo" folder="brands" label="Brand logo" />
 */
import api, { fileUrl } from "@/api";

export default {
    name: "ImageUpload",
    props: {
        modelValue: { type: String, default: null },
        folder: { type: String, required: true },
        label: { type: String, default: "Image" },
        hint: { type: String, default: "" },
        wide: { type: Boolean, default: false },
    },
    emits: ["update:modelValue"],
    data: () => ({ uploading: false, dragging: false }),
    computed: {
        url() {
            return fileUrl(this.modelValue);
        },
    },
    methods: {
        pick() {
            if (!this.uploading) this.$refs.input.click();
        },
        onPick(e) {
            const file = e.target.files[0];
            e.target.value = "";
            if (file) this.upload(file);
        },
        onDrop(e) {
            this.dragging = false;
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith("image/")) this.upload(file);
        },
        async upload(file) {
            if (file.size > 4 * 1024 * 1024) {
                this.$store.dispatch("notify", { text: this.$t("This image is larger than 4 MB."), type: "error" });
                return;
            }
            const body = new FormData();
            body.append("file", file);
            body.append("folder", this.folder);
            this.uploading = true;
            try {
                const { data } = await api.post("/admin/uploads", body);
                this.$emit("update:modelValue", data.path);
            } catch (e) {
                /* message shown by the API client */
            } finally {
                this.uploading = false;
            }
        },
    },
};
</script>
