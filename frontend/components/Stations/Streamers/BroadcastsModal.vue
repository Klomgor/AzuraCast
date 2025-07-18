<template>
    <modal
        id="streamer_broadcasts"
        ref="$modal"
        size="xl"
        centered
        :title="$gettext('Streamer Broadcasts')"
        @hidden="onHidden"
    >
        <template v-if="listUrl">
            <inline-player class="text-start bg-primary rounded mb-2 p-1" />

            <broadcasts-modal-toolbar
                :batch-url="batchUrl"
                :selected-items="selectedItems"
                @relist="() => refresh()"
            />

            <data-table
                id="station_streamer_broadcasts"
                ref="$dataTable"
                selectable
                paginated
                :fields="fields"
                :provider="listItemProvider"
                @row-selected="onRowSelected"
            >
                <template #cell(download)="row">
                    <template v-if="row.item.recording?.downloadUrl">
                        <play-button :url="row.item.recording?.downloadUrl"/>
                        <a
                            class="name btn p-0 ms-2"
                            :href="row.item.recording?.downloadUrl"
                            target="_blank"
                            :title="$gettext('Download')"
                        >
                            <icon :icon="IconDownload" />
                        </a>
                    </template>
                    <template v-else>
                    &nbsp;
                    </template>
                </template>
                <template #cell(actions)="row">
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="doDelete(row.item.links.delete)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </template>
            </data-table>
        </template>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import formatFileSize from "~/functions/formatFileSize";
import InlinePlayer from "~/components/InlinePlayer.vue";
import Icon from "~/components/Common/Icon.vue";
import PlayButton from "~/components/Common/PlayButton.vue";
import {computed, ref, shallowRef, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {IconDownload} from "~/components/Common/icons";
import {useHasModal} from "~/functions/useHasModal.ts";
import {usePlayerStore, useProvidePlayerStore} from "~/functions/usePlayerStore.ts";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import BroadcastsModalToolbar from "~/components/Stations/Streamers/BroadcastsModalToolbar.vue";
import {useDialog} from "~/functions/useDialog.ts";
import {ApiStationStreamerBroadcast} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";

const streamerId = ref<number | null>(null);
const listUrl = ref<string | null>(null);
const batchUrl = ref<string | null>(null);

const {$gettext} = useTranslate();

const {formatIsoAsDateTime} = useStationDateTimeFormatter();

type Row = ApiStationStreamerBroadcast;

const fields: DataTableField<Row>[] = [
    {
        key: 'download',
        label: ' ',
        sortable: false,
        class: 'shrink pe-3'
    },
    {
        key: 'timestampStart',
        label: $gettext('Start Time'),
        sortable: false,
        formatter: (value) => formatIsoAsDateTime(value),
        class: 'ps-3'
    },
    {
        key: 'timestampEnd',
        label: $gettext('End Time'),
        sortable: false,
        formatter: (value) => {
            return value === null
                ? $gettext('Live')
                : formatIsoAsDateTime(value);
        }
    },
    {
        key: 'size',
        label: $gettext('Size'),
        sortable: false,
        formatter: (_value, _key, item) => {
            if (!item.recording?.size) {
                return '';
            }

            return formatFileSize(item.recording.size);
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation([
        QueryKeys.StationStreamers,
    ], [
        'broadcasts',
        streamerId
    ]),
    {
        enabled: computed(() => listUrl.value !== null),
    }
);

const refresh = () => {
    void listItemProvider.refresh();
}

const {confirmDelete} = useDialog();
const {notifySuccess} = useNotify();
const {axios} = useAxios();

const selectedItems = shallowRef([]);

const onRowSelected = (items: Row[]) => {
    selectedItems.value = items;
};

const doDelete = (url: string) => {
    void confirmDelete({
        title: $gettext('Delete Broadcast?'),
    }).then((result) => {
        if (result.value) {
            void axios.delete(url).then((resp) => {
                notifySuccess(resp.data.message);
                refresh();
            });
        }
    });
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const open = (newStreamerId: number, newListUrl: string, newBatchUrl: string) => {
    streamerId.value = newStreamerId;
    listUrl.value = newListUrl;
    batchUrl.value = newBatchUrl;

    show();
};

useProvidePlayerStore('broadcasts');

const {stop} = usePlayerStore();

const onHidden = () => {
    stop();

    streamerId.value = null;
    listUrl.value = null;
    batchUrl.value = null;
};

defineExpose({
    open
});
</script>
