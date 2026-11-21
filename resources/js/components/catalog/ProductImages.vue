<template>
    <div>
        <!-- Only the photos are draggable; the "Add images" tile always stays last. -->
        <VueDraggable v-model="images" handle=".tt-gallery-img" draggable=".tt-gallery-item" ghost-class="is-ghost" :animation="150" class="tt-gallery">
                <figure v-for="(img, index) in images" :key="img.path" class="tt-gallery-item" :class="{ 'is-main': index === 0 }">
                    <div class="tt-gallery-img" :title="$t('Drag to reorder')">
                        <img :src="url(img.path)" :alt="img.alt || ''" loading="lazy" />
                        <span v-if="index === 0" class="tt-gallery-badge">{{ $t("Main image") }}</span>
                    </div>
                    <figcaption>
                        <input v-model="img.alt" class="tt-gallery-alt" :placeholder="$t('Describe the image')" :aria-label="$t('Image description for search engines and screen readers')" />
                        <v-btn-tertiary icon="mdi-close" size="x-small" :aria-label="$t('Remove image')" @click="remove(index)" />
                    </figcaption>
                </figure>
                <button
                    v-if="images.length < max"
                    type="button"
                    class="tt-gallery-add"
                    :class="{ 'is-drag': dragging }"
                    @click="$refs.input.click()"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop"
                >
                    <v-progress-circular v-if="uploading" indeterminate size="26" width="3" color="primary" />
                    <template v-else>
                        <v-icon size="26">mdi-image-plus-outline</v-icon>
                        <span>{{ $t("Add images") }}</span>
                        <small>{{ $t("or drop them here") }}</small>
                    </template>
                </button>
        </VueDraggable>
        <p class="tt-muted tt-small mt-2">{{ $t("Up to {n} images. The first one is shown in lists. Square images of at least 800 × 800 pixels look best.", { n: max }) }}</p>
        <input ref="input" type="file" accept="image/*" multiple class="tt-visually-hidden" tabindex="-1" @change="onPick" />
    </div>
</template>

<script>
/* Product photo gallery: upload several at once, drag to reorder, describe each one. v-model: [{ path, alt }] */
import api, { fileUrl } from "@/api";

export default {
    name: "ProductImages",
    props: {
        modelValue: { type: Array, default: () => [] },
        altDefault: { type: String, default: "" },
        max: { type: Number, default: 12 },
    },
    emits: ["update:modelValue"],
    data: () => ({ uploading: false, dragging: false }),
    computed: {
        images: {
            get() {
                return this.modelValue;
            },
            set(v) {
                this.$emit("update:modelValue", v);
            },
        },
    },
    methods: {
        url: fileUrl,
        remove(i) {
            this.images = this.images.filter((_, j) => j !== i);
        },
        onPick(e) {
            const files = [...e.target.files];
            e.target.value = "";
            this.upload(files);
        },
        onDrop(e) {
            this.dragging = false;
            this.upload([...e.dataTransfer.files].filter((f) => f.type.startsWith("image/")));
        },
        async upload(files) {
            const room = this.max - this.images.length;
            const tooBig = files.filter((f) => f.size > 4 * 1024 * 1024);
            if (tooBig.length) this.$store.dispatch("notify", { text: this.$t("{n} images are larger than 4 MB and were skipped.", { n: tooBig.length }), type: "warning" });

            const list = files.filter((f) => f.size <= 4 * 1024 * 1024).slice(0, room);
            if (!list.length) return;

            this.uploading = true;
            const added = [];
            try {
                for (const file of list) {
                    const body = new FormData();
                    body.append("file", file);
                    body.append("folder", "products");
                    const { data } = await api.post("/admin/uploads", body);
                    added.push({ path: data.path, alt: this.altDefault });
                }
            } finally {
                this.images = [...this.images, ...added];
                this.uploading = false;
            }
        },
    },
};
</script>
