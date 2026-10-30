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
    import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
    import { vehicleTypeLabels } from "@/Utils/vehicleTypeLabel.js"
    import { PaperAirplaneIcon, XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';

    //--------------------------------------------変数-------------------------------------------
    const props = defineProps({
        form: Object,
        formClients: Array,
        formStaff: Array,   //['id', 'name']
        formSupportTypes: Array,
        popupMode: String,
        selectClientName: String,
        selectReservationId: Number,
    });

    const popupPositionLeft = defineModel('popupPositionLeft');
    const popupPositionTop = defineModel('popupPositionTop');

    const popup = ref(null);
    const addStaffId = ref(null);

    let popupGapTop = null;
    let popupGapLeft = null;

    const emit = defineEmits(['close', 'submit', 'clear', 'destroy']);
    const onClose = () =>{
        emit('close');
    }
    const onSubmit = () =>{
        emit('submit');
    }
    const onClear = () =>{
        emit('clear');
    }
    const onDestroy = () =>{
        emit('destroy');
    }

    //--------------------------------------------関数-------------------------------------------
    /**
     * ポップアップが画面の外に出ないように、位置を範囲内に収める
     * ドラッグ中・表示したとき・位置が変わったときに使う
     */
    const clampPopupPosition = (left, top) =>{
        const rect = popup.value.getBoundingClientRect();

        //画面右、下の限界値（画面サイズからポップアップの大きさを引いた値）
        const maxLeft = window.innerWidth - rect.width;
        const maxTop = window.innerHeight - rect.height;

        //最小値は0、最大値は上の限界値
        popupPositionLeft.value = Math.min(Math.max(0, left), maxLeft);
        popupPositionTop.value = Math.min(Math.max(0, top), maxTop);
    }

    //--------------------------------------------computed, watch関数-------------------------------------------
    //ポップアップを開いたまま、別の予約の変更ボタンを押したときなどに位置を直す
    watch([() => props.selectReservationId, () => props.popupMode, popupPositionLeft, popupPositionTop], () => {
        if(popupPositionTop.value == null) return;
        clampPopupPosition(popupPositionLeft.value, popupPositionTop.value);
    }, {flush: 'post'})

    const selectedStaffs = computed(() => {
        return props.formStaff.filter((s) => props.form.staff_ids.includes(s.id));
    });

    const unselectedStaffs = computed(() => {
        return props.formStaff.filter((s) => !props.form.staff_ids.includes(s.id));
    });
    //--------------------------------------------event-------------------------------------------
    //ポップアップのヘッダーをクリックしたらドラッグ開始と判断する
    const onDragStart = (e) =>{
        if(!window.matchMedia('(min-width: 768px)').matches){
            return; //スマホは移動できない
        }

        //ポップアップをクリックしたときに位置を取得してずれないようにする
        const popupRect = popup.value.getBoundingClientRect();
        popupGapLeft = e.clientX - popupRect.left;
        popupGapTop = e.clientY - popupRect.top;

        window.addEventListener("pointermove", onDragMove);
        window.addEventListener("pointerup", onDragEnd);
    }

    const onDragMove = (e) =>{
        //マウスの位置から、つかんだ位置のずれを引いた値がポップアップの左上
        const left = e.clientX - popupGapLeft;
        const top = e.clientY - popupGapTop;

        clampPopupPosition(left, top);
    }

    const onDragEnd = () => {
        window.removeEventListener("pointermove", onDragMove);
        window.removeEventListener("pointerup", onDragEnd);
    }

    const onKeydownEscape = (e) =>{
        if(e.key !== 'Escape') return;

        //日本語入力中は閉じない
        if(e.isComposing) return;

        onClose();
    }

    const onSelectedStaff = () =>{
        props.form.staff_ids.push(addStaffId.value);
        addStaffId.value = null;
    }

    const onUnselectStaff = (unselectedStaffId) =>{
        props.form.staff_ids = props.form.staff_ids.filter((id) => id !== unselectedStaffId);
    }

    //ポップアップを表示したときに、画面からはみ出していれば位置を直す
    onMounted(() => {
        window.addEventListener("keydown", onKeydownEscape);

        //スマホ、または位置の指定がない場合は何もしない
        if(popupPositionTop.value == null) return;
        clampPopupPosition(popupPositionLeft.value, popupPositionTop.value);
    })

    onUnmounted(() => {
        window.removeEventListener("keydown", onKeydownEscape);
        window.removeEventListener("pointermove", onDragMove);
        window.removeEventListener("pointerup", onDragEnd);
    })
    </script>

<template>
    <div
        ref="popup"
        :style="popupPositionTop != null ? {top:`${popupPositionTop}px`, left:`${popupPositionLeft}px`, right:'auto'} : {}"
        class="
        fixed rounded border z-10 bg-surface shadow-lg
        inset-0 max-h-screen overflow-y-auto
        md:top-0 md:bottom-auto md:left-auto md:right-0 md:w-120">   <!-- md:大きい画面用設定 -->
        <div @pointerdown="onDragStart" class="bg-reservation-soft p-2 cursor-move select-none touch-none flex justify-between">
            <div>
                <h1 v-if="popupMode === 'create'">予約申請</h1>
                <h1 v-else-if="popupMode === 'update'">予約変更</h1>
                <h1 v-else>予約内容</h1>
            </div>
            <button @click="onClose()" @pointerdown.stop class="">
                <XMarkIcon class="w-5 h-5"/>
            </button>
        </div>
        <fieldset :disabled="popupMode==='view'" class="p-2">
            <div class="flex flex-col gap-2">
                <label class="form-row">
                    <span>予約日</span>
                    <input type="date" v-model="form.date" class="field"/>
                </label>
                <div v-if="form.errors.date" class="form-danger">{{ form.errors.date }}</div>

                <fieldset>
                    <legend class="sr-only">予約時間</legend>
                    <label class="form-row">
                        <span>予約時間</span>
                        <span  class="flex items-center gap-1">
                            <input type="time" v-model="form.start_time" class="field"/>
                            ～<input type="time" v-model="form.end_time" class="field"/>
                        </span >
                    </label>
                </fieldset>
                <div v-if="form.errors.start_time" class="form-danger">{{ form.errors.start_time }}</div>
                <div v-if="form.errors.end_time" class="form-danger">{{ form.errors.end_time }}</div>

                <label class="form-row">
                    <span>利用者氏名</span>
                    <select v-if="popupMode === 'create'" v-model="form.client_id" class="field">
                        <option :value="null">利用者を選択</option>
                        <option v-for="s in formClients" :key="s.id" :value="s.id">{{ s.last_name }}</option>
                    </select>
                    <div v-else>{{ selectClientName }}</div>
                </label>
                <div v-if="form.errors.client_id" class="form-danger">{{ form.errors.client_id }}</div>

                <fieldset>
                    <legend class="sr-only">担当者</legend>
                    <div class="form-row">
                        <span>担当者</span>
                        <div class="flex flex-col gap-1">
                            <div v-if="selectedStaffs.length > 0" class="flex flex-wrap gap-1">
                                <span v-for="s in selectedStaffs" :key="s.id" class="badge bg-reservation-soft text-ink">
                                    {{ s.name }}
                                    <button type="button" @click="onUnselectStaff(s.id)" :aria-label="`${s.name}を外す`">
                                        <XMarkIcon class="w-4 h-4"/>
                                    </button>
                                </span>
                            </div>
                            <select v-model="addStaffId" @change="onSelectedStaff()" class="field">
                                <option :value="null">担当者を選択</option>
                                <option v-for="s in unselectedStaffs" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <label class="form-row">
                    <span>支援内容</span>
                    <select v-model="form.support_type_id" class="field">
                        <option :value="null">支援内容を選択</option>
                        <option v-for="s in formSupportTypes" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </label>
                <div v-if="form.errors.support_type_id" class="form-danger">{{ form.errors.support_type_id }}</div>

                <fieldset>
                    <legend class="sr-only">車両</legend>
                    <div class="form-row">
                        <span>車両</span>
                        <div class="flex flex-wrap gap-x-3">
                            <label v-for="(label, value) in vehicleTypeLabels" :key="value" class="mark">
                                <input type="radio" v-model="form.vehicle_type_choice" :value="value"/>
                                {{ label }}
                            </label>
                        </div>
                    </div>
                </fieldset>
                <div v-if="form.errors.vehicle_type_choice" class="form-danger">{{ form.errors.vehicle_type_choice }}</div>

                <label class="form-row">
                    <span>備考</span>
                    <textarea v-model="form.remarks" id="remarks" class="field w-full field-sizing-content"></textarea>
                </label>
                <div v-if="form.errors.remarks" class="form-danger">{{ form.errors.remarks }}</div>
            </div>
        </fieldset>
        <div class="pb-2 flex justify-center gap-20">
            <!--:disabled="form.processing" ：　フォーム送信中はボタンを無効にする-->
            <button
                v-if="popupMode !=='view'"
                @click="onSubmit()"
                :disabled="form.processing"
                class="btn bg-reservation text-ink">
                <PaperAirplaneIcon class="w-5 h-5"/>申請
            </button>
            <button
                v-if="popupMode ==='create'"
                @click="onClear()"
                :disabled="form.processing"
                class="btn bg-neutral text-ink">
                <TrashIcon class="w-5 h-5"/>クリア
            </button>
            <button
                v-if="popupMode ==='update'"
                @click="onDestroy()"
                :disabled="form.processing"
                class="btn btn-outline border-danger text-danger">
                <TrashIcon class="w-5 h-5"/>削除
            </button>
        </div>
    </div>
</template>
