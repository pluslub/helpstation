<!--
    使用可能な変数
    const reservationForm = useForm({
        client_id: null,
        date: '',
        start_time: '',
        end_time: '',
        staff_ids: [],
        support_type_id: null,
        vehicle_type_choice: null,
        remarks: '',
    });
-->
<script setup>
    import { vehicleTypeLabels } from "@/Utils/vehicleTypeLabel.js"
    import { PaperAirplaneIcon, XMarkIcon } from '@heroicons/vue/24/outline';

    const props = defineProps({
        form: Object,
        formClients: Array,
        formStaff: Array,
        formSupportTypes: Array,
        popupMode: String,
        selectClientName: String,
    });

    const emit = defineEmits(['close', 'submit']);
    const onClose = () =>{
        emit('close');
    }
    const onSubmit = () =>{
        emit('submit');
    }
    </script>

<template>
    <div class="
        fixed rounded border p-2 z-10
        bg-yellow-50 shadow-lg inset-0 max-h-screen overflow-y-auto
        md:top-0 md:bottom-auto md:left-auto md:right-0">
        <div>
            <h1 v-if="popupMode === 'create'">予約申請</h1>
            <h1 v-else-if="popupMode === 'update'">予約変更</h1>
            <h1 v-else>予約内容</h1>
        </div>
        <fieldset :disabled="popupMode==='view'">
            <div class="flex flex-col gap-2">
                <label>予約日
                    <input type="date" v-model="form.date" class="rounded border px-3 py-1 bg-white"/>
                </label>
                <div v-if="form.errors.date" class="text-red-600">{{ form.errors.date }}</div>

                <fieldset>
                    <legend>予約時間</legend>
                    <input type="time" v-model="form.start_time" class="rounded border px-3 py-1 bg-white"/>
                    ～<input type="time" v-model="form.end_time" class="rounded border px-3 py-1 bg-white"/>
                </fieldset>
                <div v-if="form.errors.start_time" class="text-red-600">{{ form.errors.start_time }}</div>
                <div v-if="form.errors.end_time" class="text-red-600">{{ form.errors.end_time }}</div>

                <label>利用者氏名
                    <select v-if="popupMode === 'create'" v-model="form.client_id" class="bg-white rounded border px-3 py-1">
                        <option :value="null">利用者を選択</option>
                        <option v-for="s in formClients" :key="s.id" :value="s.id">{{ s.last_name }}</option>
                    </select>
                    <div v-else>{{ selectClientName }}</div>
                </label>
                <div v-if="form.errors.client_id" class="text-red-600">{{ form.errors.client_id }}</div>

                <fieldset>
                    <legend>担当者</legend>
                    <label v-for="s in formStaff" :key="s.id" class="px-1 py-1">
                        <input type="checkbox" v-model="form.staff_ids" :value="s.id"/>{{ s.name }}
                    </label>
                </fieldset>

                <label>支援内容
                    <select v-model="form.support_type_id" class="bg-white rounded border px-3 py-1">
                        <option :value="null">支援内容を選択</option>
                        <option v-for="s in formSupportTypes" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </label>
                <div v-if="form.errors.support_type_id" class="text-red-600">{{ form.errors.support_type_id }}</div>

                <fieldset class="px-1 py-1">
                    <legend>車両</legend>
                    <label v-for="(label, value) in vehicleTypeLabels" :key="value">
                        <input type="radio" v-model="form.vehicle_type_choice" :value="value"/>
                        {{ label }}
                    </label>
                </fieldset>
                <div v-if="form.errors.vehicle_type_choice" class="text-red-600">{{ form.errors.vehicle_type_choice }}</div>

                <label>備考
                    <textarea v-model="form.remarks" id="remarks" class="bg-white rounded border px-3 py-1"></textarea>
                </label>
                <div v-if="form.errors.remarks" class="text-red-600">{{ form.errors.remarks }}</div>
            </div>
        </fieldset>
        <div>
            <button
                v-if="popupMode !=='view'"
                @click="onSubmit()"
                :disabled="form.processing"
                class="flex items-center gap-1">
                <PaperAirplaneIcon class="w-5 h-5"/>申請
            </button>
            <button @click="onClose()">
                <XMarkIcon class="w-5 h-5"/>
            </button>
        </div>
    </div>
</template>
