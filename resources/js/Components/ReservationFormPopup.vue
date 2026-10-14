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
    const props = defineProps({
        form: Object,
        formClients: Array,
        formStaff: Array,
        formSupportTypes: Array,
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
            <h1>予約申請</h1>
        </div>
        <div class="flex flex-col gap-2">
            <label>予約日
                <input type="date" v-model="form.date" class="rounded border px-3 py-1"/>
            </label>
            <div v-if="form.errors.date" class="text-red-600">{{ form.errors.date }}</div>

            <fieldset>
                <legend>予約時間</legend>
                <input type="time" v-model="form.start_time" class="rounded border px-3 py-1"/>
                ～<input type="time" v-model="form.end_time" class="rounded border px-3 py-1"/>
            </fieldset>
            <div v-if="form.errors.start_time" class="text-red-600">{{ form.errors.start_time }}</div>
            <div v-if="form.errors.end_time" class="text-red-600">{{ form.errors.end_time }}</div>

            <label>利用者氏名
                <select v-model="form.client_id">
                    <option :value="null">利用者を選択</option>
                    <option v-for="s in formClients" :key="s.id" :value="s.id">{{ s.last_name }}</option>
                </select>
            </label>
            <div v-if="form.errors.client_id" class="text-red-600">{{ form.errors.client_id }}</div>

            <fieldset>
                <legend>担当者</legend>
                <label v-for="s in formStaff" :key="s.id">
                    <input type="checkbox" v-model="form.staff_ids" :value="s.id"/>{{ s.name }}
                </label>
            </fieldset>

            <label>支援内容
                <select v-model="form.support_type_id">
                    <option :value="null">支援内容を選択</option>
                    <option v-for="s in formSupportTypes" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </label>
            <div v-if="form.errors.support_type_id" class="text-red-600">{{ form.errors.support_type_id }}</div>

            <fieldset>
                <legend>車両</legend>
                <label>
                    <input type="radio" v-model="form.vehicle_type_choice" value="care_vehicle"/>
                    福祉車両
                </label>
                <label>
                    <input type="radio" v-model="form.vehicle_type_choice" value="company_car"/>
                    普通社用車
                </label>
                <label>
                    <input type="radio" v-model="form.vehicle_type_choice" value="private_car"/>
                    私用車</label>
                <label>
                    <input type="radio" v-model="form.vehicle_type_choice" value="none"/>
                    車不要
                </label>
            </fieldset>
            <div v-if="form.errors.vehicle_type_choice" class="text-red-600">{{ form.errors.vehicle_type_choice }}</div>

            <label>備考
                <textarea v-model="form.remarks"></textarea>
            </label>
            <div v-if="form.errors.remarks" class="text-red-600">{{ form.errors.remarks }}</div>


            <button @click="onSubmit()" :disabled="form.processing">申請</button>
            <button @click="onClose()">閉じる</button>
        </div>
    </div>
</template>
