<script setup>
    //データ処理 JavaScript
    import { ref, onMounted, onUnmounted } from 'vue';
    import { Link, router, useForm } from '@inertiajs/vue3';
    import ReservationFormPopup from "@/Components/ReservationFormPopup.vue";    //@はresources/jsを指す

    import WeekGrid from "@/Components/WeekGrid.vue";
    import ReservationFilter from '@/Components/ReservationFilter.vue';

    import { useReservationFilter } from "@/Composables/useReservationFilter.js"
    import { formatTime } from "@/Utils/formatTime.js"

    //Heroicons
    import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon} from '@heroicons/vue/24/outline';


    //ReservationControllerからデータを受け取る
    const props = defineProps({
        reservations: Array,    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        shiftDetails: Array,
        weekStart: String,
        prevWeek: String,
        nextWeek: String,
        vehicleOptions: Array,
        formClients: Array,
        formStaff: Array,
        formSupportTypes: Array,
    });

    //予約追加画面
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

    //予約変更画面
    const reservationUpdateForm = useForm({
        date: '',
        start_time: '',
        end_time: '',
        staff_ids: [],
        support_type_id: null,
        vehicle_type_choice: null,
        remarks: '',
    });

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //--------------------------------------------ref-------------------------------------------
    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。
    const showPopup = ref(false);
    const popupMode = ref(null)

    const selectReservationId = ref(null);
    const selectClientName = ref(null);

    //--------------------------------------------関数-------------------------------------------
    const setValue = (res) =>{
        reservationUpdateForm.date = res.date;
        reservationUpdateForm.start_time = res.start_time.slice(0, 5);
        reservationUpdateForm.end_time = res.end_time.slice(0, 5);
        reservationUpdateForm.support_type_id = res.support_type_id;
        reservationUpdateForm.vehicle_type_choice = res.vehicle_type_choice;
        reservationUpdateForm.remarks = res.remarks ?? '';

        reservationUpdateForm.staff_ids = res.staff_assignments.map(a => a.staff_id).filter(id => id !== null);

        reservationUpdateForm.defaults();
        selectClientName.value = res.client.last_name;
    }

    //--------------------------------------------event-------------------------------------------
    //日付が直接入力されたときのイベント
    const onDateChange = (e) =>{
        const date = e.target.value;
        if(!date) return;
        router.get('/', { week: date }, { preserveState: true, preserveScroll: true });
    };

    const onClickEntryButton = () =>{
        popupMode.value = 'create';
        showPopup.value = true;
    };

    const onClosePopup = () =>{
        switch(popupMode.value) {
            case 'update':
                if(reservationUpdateForm.isDirty && !confirm("変更を破棄して閉じますか？")){
                    showPopup.value = false;
                    return;
                }
                showPopup.value = false;
                return;

            case 'create':
            case 'view':
            default:
                showPopup.value = false;
                return;
        }
    };

    const onSubmitPopup = () =>{
        switch(popupMode.value) {
            case 'create':
                reservationForm.post('/reservations', {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        reservationForm.reset();
                        showPopup.value = false;
                    },
                });
                return;

            case 'update':
                reservationUpdateForm.put(`/reservations/${selectReservationId.value}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        showPopup.value = false;
                    },
                });
                return;

            default:
                return;
        }

    }

    const onApproveReservation = (res) =>{
        if(!confirm(`${res.client.last_name}様の予約（${res.date} ${formatTime(res.start_time)}）を承認しますか？`)){
            return;
        }
        router.patch(`/reservations/${res.id}/approve`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const onUpdateReservation = (res) =>{
        selectReservationId.value = res.id;
        setValue(res);

        popupMode.value = 'update';
        showPopup.value = true;
    };

    const onViewReservation = (res) =>{
        selectReservationId.value = res.id;
        setValue(res);

        popupMode.value = 'view';
        showPopup.value = true;
    };

    //矢印キーが入力されたときのイベント
    //日付入力欄、プルダウン選択中は動かさない
    const handleKeydown = (e) =>{
        let displayWeekOnKeyDown = null;
        if( e.target.tagName === 'INPUT'
            || e.target.tagName === 'SELECT'
            || e.target.tagName === 'TEXTAREA') return;

        if(e.key === 'ArrowLeft'){
            displayWeekOnKeyDown = props.prevWeek;
        }else if(e.key === 'ArrowRight'){
            displayWeekOnKeyDown = props.nextWeek;
        }else{
            return;
        }

        router.get('/', { week: displayWeekOnKeyDown }, { preserveState: true, preserveScroll: true });
    };

    //ページ表示時にキーダウンイベントを追加
    onMounted(() =>{
        window.addEventListener("keydown", handleKeydown);
    });

    //ページ破棄後にキーダウンイベントを削除（ほかのページでも動いてしまうため）
    onUnmounted(() =>{
        window.removeEventListener("keydown", handleKeydown);
    });
</script>

<!--
    タグ内の記号の意味

    コロン（:）:   v-bind:の省略形。文字列ではなくJavascriptの式として扱う。
    @：           v-on:の省略形。イベントが起きたときに実行する処理を書く。
 -->
<template>
    <div class="sticky top-0 z-5 bg-white">
        <!-- フィルター -->
        <ReservationFilter
            :staffOptions="staffOptions"
            :vehicleOptions="props.vehicleOptions"
            :clientOptions="clientOptions"
            v-model:filterType="filterType"
            v-model:filterId="filterId"
        ></ReservationFilter>

        <!-- 週操作ボタン -->
        <!--
            preserve-state
                ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
                refが保存される。
            preserve-scroll:
                ページを移動しても、スクロール位置をそのまま保つ
        -->
        <div class="mb-2 flex items-center gap-2">
            <Link :href="`/?week=${prevWeek}`" preserve-state preserve-scroll class="flex items-center gap-1">
                <ChevronLeftIcon class="w-5 h-5"/>前週
            </Link>
            <Link href="/" preserve-state preserve-scroll class="rounded border px-3 py-1">今週</Link>
            <input type="date" @change="onDateChange($event)" :value="weekStart" class="rounded border px-3 py-1"/>
            <Link :href="`/?week=${nextWeek}`" preserve-state preserve-scroll class="flex items-center gap-1">
                <ChevronRightIcon class="w-5 h-5"/>次週
            </Link>
        </div>
        <div class="mb-2 flex items-center gap-2">
            <Link href="/reservations/monthly" class="flex items-center gap-1">
                <CalendarDaysIcon class="w-5 h-5"/>月表示
            </Link>
        </div>

        <button @click="onClickEntryButton" class="flex items-center gap-1">
            <PlusIcon class="w-5 h-5"/>予約追加
        </button>
    </div>

    <!-- 週一覧画面 -->
        <WeekGrid
            :reservations="filteredReservations"
            :shiftDetails="shiftDetails"
            :weekStart="weekStart"
            @update="onUpdateReservation"
            @approve="onApproveReservation"
            @view="onViewReservation">
        </WeekGrid>

    <!-- 予約追加ポップアップ画面 -->
    <ReservationFormPopup
        v-if="showPopup"
        :form="popupMode === 'create' ? reservationForm : reservationUpdateForm"
        :formClients="formClients"
        :formStaff="formStaff"
        :formSupportTypes="formSupportTypes"
        :popupMode=popupMode
        :selectClientName=selectClientName
        @close="onClosePopup"
        @submit="onSubmitPopup">
    </ReservationFormPopup>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
