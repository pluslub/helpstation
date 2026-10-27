<script setup>
    //データ処理 JavaScript
    import { onMounted, onUnmounted } from 'vue';
    import { Link, router, usePage } from '@inertiajs/vue3';

    import WeekGrid from "@/Components/WeekGrid.vue";
    import ReservationFilter from '@/Components/ReservationFilter.vue';

    import { useReservationFilter } from "@/Composables/useReservationFilter.js"

    //Heroicons
    import { ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon} from '@heroicons/vue/24/outline';

    const page = usePage();

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

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //日付直接入力欄でキーダウンされたかどうか
    //入力途中の遷移を防ぐため、keydown中なら無視し、カレンダーの選択・エンターキー押下・フォーカスの移動で遷移する
    let keydownOnInputDate = false;
    //--------------------------------------------関数-------------------------------------------
    const moveMonth = (month, isValid) => {
        if(!month || !isValid) return;
        if(props.month === month) return;

        router.get('/reservations/monthly/', { month: month }, { preserveState: true, preserveScroll: true });
    }

    //--------------------------------------------event-------------------------------------------
    //日付が直接入力されたときのイベント
    const onDateChange = (e) =>{
        if(keydownOnInputDate) return;
        moveMonth(e.target.value, e.target.validity.valid);
    };

    const onEnterOnInputDate = (e) =>{
        keydownOnInputDate = false;
        moveMonth(e.target.value, e.target.validity.valid);
    };

    const onBlurFromInputDate = (e) =>{
        keydownOnInputDate = false;
        moveMonth(e.target.value, e.target.validity.valid);
    };

    const onKeyDownOnInputDate = () =>{
        keydownOnInputDate = true;
    };

    //矢印キーが入力されたときのイベント
    //日付入力欄、プルダウン選択中は動かさない
    const handleKeydown = (e) =>{
        let displayMonthOnKeyDown = null;
        if( e.target.tagName === 'INPUT'
            || e.target.tagName === 'SELECT'
            || e.target.tagName === 'TEXTAREA') return;

        if(e.key === 'ArrowLeft'){
            displayMonthOnKeyDown = props.prevMonth;
        }else if(e.key === 'ArrowRight'){
            displayMonthOnKeyDown = props.nextMonth;
        }else{
            return;
        }

        router.get('/reservations/monthly/', { month: displayMonthOnKeyDown }, { preserveState: true, preserveScroll: true });
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
    <div class="bg-surface border-b border-line shadow-sm sticky top-0 z-5 px-2 py-2 flex flex-col gap-2">
        <div class="flex justify-between flex-wrap gap-2">
            <!-- 月操作ボタン -->
            <!--
                preserve-state
                    ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
                    refが保存される。
                preserve-scroll:
                    ページを移動しても、スクロール位置をそのまま保つ
            -->
            <div class="flex items-center gap-2">
                <Link
                    :href="`/reservations/monthly/?month=${prevMonth}`"
                    preserve-state preserve-scroll
                    class="btn bg-neutral text-ink">
                    <ChevronLeftIcon class="w-5 h-5"/>先月</Link>
                <Link
                    href="/reservations/monthly/"
                    preserve-state preserve-scroll
                    class="btn bg-neutral text-ink">今月</Link>
                <input type="month"
                    @change="onDateChange($event)"
                    @keydown="onKeyDownOnInputDate()"
                    @keydown.enter="onEnterOnInputDate($event)"
                    @blur="onBlurFromInputDate($event)"
                    :value="month"
                    class="field"
                    :min="page.props.selectableMinDate.slice(0, 7)"/>
                <Link
                    :href="`/reservations/monthly/?month=${nextMonth}`"
                    preserve-state preserve-scroll
                    class="btn bg-neutral text-ink">
                    来月<ChevronRightIcon class="w-5 h-5"/></Link>
            </div>
            <div class="flex items-center gap-2">
                <Link href="/" class="btn btn-outline border-line text-ink">
                    <CalendarDaysIcon class="w-5 h-5"/>週表示</Link>
            </div>
        </div>
        <!-- フィルター -->
        <ReservationFilter
            :staffOptions="staffOptions"
            :vehicleOptions="props.vehicleOptions"
            :clientOptions="clientOptions"
            v-model:filterType="filterType"
            v-model:filterId="filterId"
        ></ReservationFilter>
    </div>

    <!-- 月一覧画面 -->
    <div class="flex flex-col gap-6 px-2">
        <div v-for="day in props.sundayList" :key="day">
            <WeekGrid
                :allReservations="props.reservations"
                :reservations="filteredReservations"
                :shiftDetails="shiftDetails"
                :weekStart="day"
                readonly>
            </WeekGrid>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
