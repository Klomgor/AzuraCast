<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_webhookurl"
                class="col-md-12"
                :field="v$.config.webhookurl"
                :label="$gettext('RadioReg Webhook URL')"
                :description="$gettext('Found under the settings page for the corresponding RadioReg station.')"
            />

            <form-group-field
                id="form_config_apikey"
                class="col-md-6"
                :field="v$.config.apikey"
                :label="$gettext('RadioReg Organization API Key')"
                :description="$gettext('An API token is issued on a per-organization basis and are found on the org. settings page.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

defineProps<WebhookComponentProps>();

const form = defineModel<ApiGenericForm>('form', {required: true});

const {v$, tabClass} = useVuelidateOnFormTab(
    form,
    {
        config: {
            webhookurl: {required},
            apikey: {required}
        }
    },
    () => ({
        config: {
            webhookurl: '',
            apikey: ''
        }
    })
);
</script>
