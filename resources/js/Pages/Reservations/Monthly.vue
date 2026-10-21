<script setup>
    //データ処理 JavaScript
    import { onMounted, onUnmounted } from 'vue';
    import { Link, router } from '@inertiajs/vue3';

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

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //--------------------------------------------event-------------------------------------------
    //日付が直接入力されたときのイベント
    const onDateChange = (e) =>{
        const month = e.target.value;
        if(!month) return;
        router.get('/reservations/monthly/', { month: month }, { preserveState: true, preserveScroll: true });
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
            <input type="month" @change="onDateChange($event)"
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
            :allReservations="props.reservations"
            :reservations="filteredReservations"
            :shiftDetails="shiftDetails"
            :weekStart="day"
            readonly>
        </WeekGrid>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
