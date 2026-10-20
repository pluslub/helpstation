<script setup>
    //データ処理 JavaScript
    import { ref, onMounted, onUnmounted } from 'vue';
    import { Link, router, useForm } from '@inertiajs/vue3';

    import WeekGrid from "@/Components/WeekGrid.vue";
    import ReservationFilter from '@/Components/ReservationFilter.vue';

    import { useReservationFilter } from "@/Composables/useReservationFilter.js"

    //Heroicons
    import { ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon} from '@heroicons/vue/24/outline';

    //ReservationControllerからデータを受け取る
    const props = defineProps({
        reservations: Array,    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        shiftDetails: Array,
        month: String,
        prevMonth: String,
        nextMonth: String,
        sundayList: Array,
        vehicleOptions: Array,
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

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //--------------------------------------------ref-------------------------------------------
    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。
    const showPopup = ref(false);

    //--------------------------------------------event-------------------------------------------
    //日付が直接入力されたときのイベント
    const onDateChange = (e) =>{
        const date = e.target.value;
        if(!date) return;
        router.get('/', { week: date }, { preserveState: true, preserveScroll: true });
    };

    const onClickEntryButton = () =>{
        showPopup.value = true;
    };

    const onClosePopup = () =>{
        showPopup.value = false;
    };

    const onSubmitPopup = () =>{
        reservationForm.post('/reservations', {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () =>{
                reservationForm.reset();
                showPopup.value = false;
            },
        });
    }

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

        <!-- 月操作ボタン -->
        <!--
            preserve-state
                ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
                refが保存される。
            preserve-scroll:
                ページを移動しても、スクロール位置をそのまま保つ
        -->
        <div class="mb-2 flex items-center gap-2">
            <Link
                :href="`/reservations/monthly/?month=${prevMonth}`"
                preserve-state preserve-scroll
                class="flex items-center gap-1">
                <ChevronLeftIcon class="w-5 h-5"/>先月</Link>
            <Link
                href="/reservations/monthly/"
                preserve-state preserve-scroll
                class="rounded border px-3 py-1">今月</Link>
            <input type="date" @change="onDateChange($event)"
                :value="month"
                class="rounded border px-3 py-1"/>
            <Link
                :href="`/reservations/monthly/?month=${nextMonth}`"
                preserve-state preserve-scroll
                class="flex items-center gap-1">
                来月<ChevronRightIcon class="w-5 h-5"/></Link>
        </div>
        <div class="mb-2 flex items-center gap-2">
            <Link href="/" class="flex items-center gap-1">
                <CalendarDaysIcon class="w-5 h-5"/>週表示</Link>
        </div>
    </div>

    <!-- 月一覧画面 -->
    <div v-for="day in props.sundayList" :key="day">
        <WeekGrid
            :reservations="filteredReservations"
            :shiftDetails="shiftDetails"
            :weekStart="day">
        </WeekGrid>
    </div>

    <!-- 予約追加・編集ポップアップ画面 -->
    <ReservationFormPopup
        v-if="showPopup"
        :form="reservationForm"
        :formClients="formClients"
        :formStaff="formStaff"
        :formSupportTypes="formSupportTypes"
        @close="onClosePopup"
        @submit="onSubmitPopup">
    </ReservationFormPopup>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
