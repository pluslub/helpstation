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
    import { ref, onMounted, onUnmounted, watch } from 'vue';
    import { vehicleTypeLabels } from "@/Utils/vehicleTypeLabel.js"
    import { PaperAirplaneIcon, XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';

    //--------------------------------------------変数-------------------------------------------
    const props = defineProps({
        form: Object,
        formClients: Array,
        formStaff: Array,
        formSupportTypes: Array,
        popupMode: String,
        selectClientName: String,
        selectReservationId: Number,
    });

    const popupPositionLeft = defineModel('popupPositionLeft');
    const popupPositionTop = defineModel('popupPositionTop');

    const popup = ref(null);

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

    //--------------------------------------------watch関数-------------------------------------------
    //ポップアップを開いたまま、別の予約の変更ボタンを押したときなどに位置を直す
    watch([() => props.selectReservationId, () => props.popupMode, popupPositionLeft, popupPositionTop], () => {
        if(popupPositionTop.value == null) return;
        clampPopupPosition(popupPositionLeft.value, popupPositionTop.value);
    }, {flush: 'post'})

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
        fixed rounded border  z-10 bg-white shadow-lg
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
        <fieldset :disabled="popupMode==='view'" class="p-2 ">
            <div class="flex flex-col gap-2">
                <label>予約日
                    <input type="date" v-model="form.date" class="rounded border px-3 py-1 bg-white"/>
                </label>
                <div v-if="form.errors.date" class="text-danger">{{ form.errors.date }}</div>

                <fieldset>
                    <legend class="sr-only">予約時間</legend>
                    <label class="flex items-center gap-2">予約時間
                        <input type="time" v-model="form.start_time" class="rounded border px-3 py-1 bg-white"/>
                        ～<input type="time" v-model="form.end_time" class="rounded border px-3 py-1 bg-white"/>
                    </label>
                </fieldset>
                <div v-if="form.errors.start_time" class="text-danger">{{ form.errors.start_time }}</div>
                <div v-if="form.errors.end_time" class="text-danger">{{ form.errors.end_time }}</div>

                <div class="flex gap-4">
                    <label class="flex items-center gap-2">利用者氏名
                        <select v-if="popupMode === 'create'" v-model="form.client_id" class="bg-white rounded border px-3 py-1">
                            <option :value="null">利用者を選択</option>
                            <option v-for="s in formClients" :key="s.id" :value="s.id">{{ s.last_name }}</option>
                        </select>
                        <div v-else>{{ selectClientName }}</div>
                    </label>
                </div>
                <div v-if="form.errors.client_id" class="text-danger">{{ form.errors.client_id }}</div>

                <fieldset>
                    <legend class="sr-only">担当者</legend>
                    <div class="flex gap-4">
                        <span>担当者</span>
                        <label v-for="s in formStaff" :key="s.id" class="flex items-center gap-1 p-1">
                            <input type="checkbox" v-model="form.staff_ids" :value="s.id"/>{{ s.name }}
                        </label>

                    </div>
                </fieldset>

                <div class="flex gap-4">
                    <label>支援内容
                        <select v-model="form.support_type_id" class="bg-white rounded border px-3 py-1">
                            <option :value="null">支援内容を選択</option>
                            <option v-for="s in formSupportTypes" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </label>
                </div>
                <div v-if="form.errors.support_type_id" class="text-danger">{{ form.errors.support_type_id }}</div>

                <fieldset class="p-1">
                    <legend class="sr-only">車両</legend>
                    <div class="flex gap-4">
                        <span>車両</span>
                        <label v-for="(label, value) in vehicleTypeLabels" :key="value" class="flex items-center gap-1">
                            <input type="radio" v-model="form.vehicle_type_choice" :value="value"/>
                            {{ label }}
                        </label>
                    </div>
                </fieldset>
                <div v-if="form.errors.vehicle_type_choice" class="text-danger">{{ form.errors.vehicle_type_choice }}</div>

                <label class="flex items-center gap-2">備考
                    <textarea v-model="form.remarks" id="remarks" class="bg-white rounded border px-3 py-1"></textarea>
                </label>
                <div v-if="form.errors.remarks" class="text-danger">{{ form.errors.remarks }}</div>
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
