<template>
    <button
        ref="btn"
        type="button"
        class="btn btn-copy btn-link btn-xs"
        :aria-label="$gettext('Copy to Clipboard')"
        @click.prevent="doCopy"
    >
        <icon
            class="sm"
            :icon="IconCopy"
        />
        <span v-if="!hideText">{{ copyText }}</span>
    </button>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {refAutoReset, useClipboard} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";
import {IconCopy} from "~/components/Common/icons";

const props = withDefaults(
    defineProps<{
        text: string,
        hideText?: boolean
    }>(),
    {
        hideText: false,
    }
);

const {$gettext} = useTranslate();

const copyText = refAutoReset(
    $gettext('Copy to Clipboard'),
    1000
);

const clipboard = useClipboard({legacy: true});

const doCopy = async () => {
    await clipboard.copy(props.text);
    copyText.value = $gettext('Copied!');
};
</script>
