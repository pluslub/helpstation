<script setup>
    //データ処理 JavaScript
    import { computed, ref, watch } from 'vue';
    import { Link } from "@inertiajs/vue3";

    import { effective } from "@/Utils/effective.js"
    import { formatDate } from "@/Utils/formatDate.js"
    import { hasStaff } from "@/Utils/hasStaff.js"
    import { isPrivateCar } from "@/Utils/isPrivateCar.js"

    import DayReservationList from './DayReservationList.vue';
    import DayShiftInfo from './DayShiftInfo.vue';

    import { ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

    //ReservationControllerからデータを受け取る
    const props = defineProps({
        //絞り込み前の予約配列
        //reservationsの構造
        // reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        allReservations: Array,
        reservations: Array,    //絞り込み後の予約配列
        shiftDetails: Array,
        weekStart: String,
        openedReservationId: Number,
        shiftClickableDate: String,
        highlightedDate: String,
        highlightedTime: Object,
        prevWeek: String,
        nextWeek: String,
        pickedDate: String,
        showYearOnFirstDay: { type: Boolean, default: true},
        clickableShift: { type: Boolean, default: false},
        clickableDayOff: { type: Boolean, default: false},
        clickableDateHeader: { type: Boolean, default: false},
        readonly: { type: Boolean, default: false},
    });

    const emit = defineEmits(['update', 'approve', 'view', 'click-shift', 'click-date-header']);

    //------------------------------------------------関数-----------------------------------------------

    const today = formatDate(new Date());

    //--------------------------------------------computed関数-------------------------------------------
    /**
     * 今週の７日間の日付を入力する。
     * computed(() => {}) ：　ほかのデータから計算して作る値
     * props.weekStartが変更されると自動で再計算される。
     * props.weekStartが変更されるまで計算結果を記憶する。
     *
     * 入力：props.weekStart　週の最初の日'yyyy-MM-dd'
     * 出力：{ date: 'yyyy-MM-dd', year, weekNum, monthDay: 'm/d(曜)', showYear } の配列
     */
    const days = computed(() => {
        const weekNames = ['日', '月', '火', '水', '木', '金', '土'];
        const result = [];
        for(let i = 0; i < 7; i++){     //props.weekStartから１週間分の日付を作成する
            const d = new Date(props.weekStart + 'T00:00:00');  //端末のタイムゾーンの0時として解釈させる
            d.setDate(d.getDate() + i)
            const year = d.getFullYear();
            const month = d.getMonth() + 1;    //月は0～11で表されるので+1する
            const day = d.getDate();

            //結果をオブジェクトで返す
            result.push({
                //'yyyy-MM-dd'の文字列を作成
                date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,

                year: year,
                month: month,
                day: day,
                weekNum: d.getDay(),
                weekName: weekNames[d.getDay()],
                monthDay: `${month}/${day}(${weekNames[d.getDay()]})`,  //M/d(w)の文字列
                showYear: i === 0 ? props.showYearOnFirstDay : year !== result[i - 1].year,       //ループの一つ前のオブジェクトresult[i - 1]と比較
                showMonth: i === 0 || day === 1,
            });
        }
        return result;
    });

    //予約情報を日付毎に格納
    const reservationsByDay = computed(() => {
        const reservationsByDay = {};

        //予約内容を日付毎のMapオブジェクトに格納する。
        for (const r of props.reservations) {
            const day = r.date.slice(0, 10);    //yyyy-MM-ddに変換

             //??=について：map[day]がnullか空なら配列を作成する。
            reservationsByDay[day] ??= [];

            reservationsByDay[day].push(r);
        }
        return reservationsByDay;
    });

    //シフト情報を取得して日付毎に格納
    const shiftByDay = computed(() => {
        const shiftMap = {};
        const desiredOffNameMap = {};
        const dayOffNameMap = {};
        const privateCarMap = {};

        //シフト内容を日付毎のMapオブジェクトに格納する。
        //全休の場合は休みMapオブジェクトに格納する。
        for (const s of props.shiftDetails) {
            const day = s.date.slice(0, 10);
            const eff = effective(s);
            if(eff.desiredOff){
                (desiredOffNameMap[day] ??= []).push(eff); //希望休の人の名前を格納
            }
            if(eff.amOff && eff.pmOff){
                if(!eff.desiredOff) (dayOffNameMap[day] ??= []).push(eff); //1日休みの人の名前を格納
            }else{
                (shiftMap[day] ??= []).push(eff);      //それ以外の人の全データ格納
            }

            //私用車利用マークをつけるためのマップを作成 {スタッフｰ日付: {start:~, end:~}}
            const reservationByStaff = props.allReservations.filter(r => r.date.slice(0, 10) === day &&  hasStaff(r, eff.staffId));

            if(reservationByStaff.length !== 0){
                const map ={
                    start: isPrivateCar(reservationByStaff[0]),
                    end: isPrivateCar(reservationByStaff[reservationByStaff.length - 1])
                };
                privateCarMap[`${eff.staffId}-${day}`] = map;
            }
        }
        return {shiftMap, desiredOffNameMap, dayOffNameMap, privateCarMap};
    });

    const onClickDateHeader = (date) => {
        emit('click-date-header', date);
    }

    const onUpdateReservation = (res, rect) => {
        emit('update', res, rect);
    }

    const onApproveReservation = (res) => {
        emit('approve', res);
    }

    const onViewReservation = (res, rect) => {
        emit('view', res, rect);
    }

    const onClickShift  = (date, shift ) => {
        emit('click-shift', date, shift);
    }

    //---------------------------------------スマホ用---------------------------------------------------------
    const getDefaultSelectedDate = () =>{
        //選択した日付が表示している週の範囲にあればそれを返す。
        if(days.value.some(d => d.date === props.pickedDate)) return props.pickedDate;
        if(days.value.some(d => d.date === props.highlightedDate)) return props.highlightedDate;
        return days.value.some(d => d.date === today) ? today : props.weekStart;
    }

    const selectedDate = ref(getDefaultSelectedDate());
    const selectedDay = computed(() => days.value.find(d => d.date === selectedDate.value))
    watch(() => props.weekStart, () => { selectedDate.value = getDefaultSelectedDate(); });
    watch(() => props.highlightedDate, () => {if(props.highlightedDate && days.value.some(d => d.date === props.highlightedDate)) selectedDate.value = props.highlightedDate });
    watch(() => props.pickedDate, () => {if(props.pickedDate && days.value.some(d => d.date === props.pickedDate)) selectedDate.value = props.pickedDate });

    //シフトの要約を作る　勤務〇・休〇・希望休〇
    const shiftCountByDay = computed(() => {
        const countByDay ={};
        for(const day of days.value){
            const selectedDate = day.date;

            const shiftCount = (shiftByDay.value.shiftMap[selectedDate] ?? []).length;
            const dayoffCount = (shiftByDay.value.dayOffNameMap[selectedDate] ?? []).length;
            const desiredOffCount = (shiftByDay.value.desiredOffNameMap[selectedDate] ?? []).length;
            const total = shiftCount + dayoffCount + desiredOffCount;

            countByDay[selectedDate] = {work: shiftCount, dayOff: dayoffCount, desiredOff: desiredOffCount, total: total};
        }
        return countByDay;
    });
</script>

<!--
    タグ内の記号の意味

    コロン（:）:   v-bind:の省略形。文字列ではなくJavascriptの式として扱う。
    @：           v-on:の省略形。イベントが起きたときに実行する処理を書く。
 -->
<template>
    <div>
        <!--PC用画面　1024px以上で表示-->
        <div class="hidden md:flex md:flex-col gap-2">
            <!-- 日付・シフト・休み情報 -->
            <div class="grid grid-cols-7 gap-2 px-2 py-1  sticky top-(--header-height) z-4 bg-surface shadow-sm font-bold">
                <!-- computedされた日付配列を繰り返す（7回）各DOMを識別するために一意なkeyが必要 -->
                <div v-for="day in days" :key="day.date" class="min-w-0 p-1" :class="{ 'bg-highlight-soft rounded-lg' : day.date === highlightedDate }">
                <!-- 日付ラベル 週の先頭と年の切り替わりだけ年を表示する -->
                    <button type="button" class="w-full text-center p-1 rounded-lg"
                            :class="{ 'text-sunday' : day.weekNum === 0,
                                    'text-saturday' : day.weekNum === 6,
                                    'bg-reservation-medium' : day.date === today,
                                    'hover:bg-reservation-soft cursor-pointer': clickableDateHeader, }"
                            :disabled="!clickableDateHeader"
                            @click="onClickDateHeader(day.date)">
                        <span v-if="day.showYear" class="text-xs">{{ day.year }}/</span>{{ day.monthDay }}
                    </button>
                </div>
            </div>
            <!--
                シフト・休み情報
                computedにより日付毎に分別されたシフト情報を取得する。
            -->
             <details open class="group">
                <summary class="list-none cursor-pointer grid grid-cols-7 gap-2 px-2">
                    <div v-for="day in days"
                        :key="day.date"
                        class="bg-shift-soft rounded-lg border-t-4 border-shift px-2 py-1 flex items-center gap-1 group-open:rounded-b-none"
                        :class="{ invisible: shiftCountByDay[day.date].total === 0 }">
                        <span class="text-xs text-ink-muted">
                            出{{ shiftCountByDay[day.date].work }}・
                            休{{ shiftCountByDay[day.date].dayOff }}・
                            希休{{ shiftCountByDay[day.date].desiredOff }}
                        </span>
                        <ChevronDownIcon class="group-open:rotate-180 w-4 h-4 ml-auto "/>
                </div>
                </summary>

                <div class="grid grid-cols-7 gap-2 px-2">
                    <div v-for="day in days" :key="day.date" class="min-w-0">
                        <DayShiftInfo
                            :shifts="shiftByDay.shiftMap[day.date]??[]"
                            :desiredOffs ="shiftByDay.desiredOffNameMap[day.date]??[]"
                            :dayOffs ="shiftByDay.dayOffNameMap[day.date]??[]"
                            :privateCars = "shiftByDay.privateCarMap"
                            :allReservations="props.allReservations"
                            :openedReservationId="openedReservationId"
                            :day="day"
                            :clickableShift="clickableShift"
                            :clickableDayOff="clickableDayOff"
                            :shiftClickableDate="shiftClickableDate"
                            :highlighted-date="highlightedDate"
                            :highlighted-time="highlightedTime"
                            @click-shift="onClickShift"
                            attached>
                        </DayShiftInfo>
                    </div>
                </div>
            </details>

            <!-- 予約カード -->
            <div class="grid grid-cols-7 gap-2 px-2">
                <div v-for="day in days" :key="day.date" class="flex flex-col gap-2 min-w-0" data-day-column>
                            <!--カードを装飾する。選択されている予約：オレンジ、同じ時間帯の予約：ピンク-->
                    <DayReservationList
                        :reservations="reservationsByDay[day.date]??[]"
                        :openedReservationId="openedReservationId"
                        :highlighted-date="highlightedDate"
                        :highlighted-time="highlightedTime"
                        :readonly="readonly"
                        @update="onUpdateReservation"
                        @approve="onApproveReservation"
                        @view="onViewReservation">
                    </DayReservationList>
                </div>
            </div>
        </div>
        <!--スマホ用画面-->
        <div class="md:hidden pb-20">
            <div class="grid grid-cols-[auto_repeat(7,1fr)_auto] sticky top-0 z-4 bg-surface pb-2">
                <Link :href="`/?week=${prevWeek}`"
                    preserve-state preserve-scroll
                    class="btn-base px-2 text-ink h-full bg-surface"
                    :class="{ invisible: !prevWeek }"
                    aria-label="前週">
                    <ChevronLeftIcon class="w-5 h-5"/>
                </Link>
                <div v-for="day in days"
                    :key="day.date"
                    class="rounded-lg p-1">
                    <button type="button"
                            @click="selectedDate = day.date"
                            :class="{   'ring-2 ring-reservation font-bold': day.date === selectedDate,
                                        'bg-reservation-medium' : day.date === today,
                                        'bg-surface' : day.date !== today,
                                        'text-sunday' : day.weekNum === 0,
                                        'text-saturday' : day.weekNum === 6,
                                    }"
                            class="btn-base px-0 flex flex-col items-center rounded-lg w-full text-sm">
                            <span>{{ day.weekName }}</span>
                            <span v-if="day.showMonth">
                                <span class="text-xs whitespace-nowrap tracking-tighter">{{ day.month }}/</span>{{ day.day }}
                            </span>
                            <span v-else>{{ day.day }}</span>
                    </button>
                </div>
                <Link :href="`/?week=${nextWeek}`"
                    preserve-state preserve-scroll
                    class="btn-base px-2 text-ink h-full bg-surface"
                    :class="{ invisible: !nextWeek }"
                    aria-label="次週">
                    <ChevronRightIcon class="w-5 h-5"/>
            </Link>
            </div>
            <div v-if="selectedDay"
                class="flex flex-col gap-2 px-2">
                <details class="group">
                    <summary class="list-none cursor-pointer flex items-center relative justify-center  gap-1 bg-shift-soft rounded-lg border-t-4 border-shift px-2 py-1 group-open:rounded-b-none">
                        <span class="text-xs text-ink-muted">
                            出勤{{ shiftCountByDay[selectedDay.date].work }}・
                            休暇{{ shiftCountByDay[selectedDay.date].dayOff }}・
                            希望休{{ shiftCountByDay[selectedDay.date].desiredOff }}
                        </span>
                        <ChevronDownIcon class="group-open:rotate-180 w-4 h-4 absolute right-2"/></summary>
                    <DayShiftInfo
                        :shifts="shiftByDay.shiftMap[selectedDay?.date]??[]"
                        :desiredOffs ="shiftByDay.desiredOffNameMap[selectedDay?.date]??[]"
                        :dayOffs ="shiftByDay.dayOffNameMap[selectedDay?.date]??[]"
                        :privateCars = "shiftByDay.privateCarMap"
                        :allReservations="props.allReservations"
                        :openedReservationId="openedReservationId"
                        :day="selectedDay"
                        :clickableShift="clickableShift"
                        :clickableDayOff="clickableDayOff"
                        :shiftClickableDate="shiftClickableDate"
                        :highlighted-date="highlightedDate"
                        :highlighted-time="highlightedTime"
                        @click-shift="onClickShift"
                        attached >
                    </DayShiftInfo>
                </details>
                <div data-day-column>
                    <DayReservationList
                        :reservations="reservationsByDay[selectedDay?.date]??[]"
                        :openedReservationId="openedReservationId"
                        :highlighted-date="highlightedDate"
                        :highlighted-time="highlightedTime"
                        :readonly="readonly"
                        @update="onUpdateReservation"
                        @approve="onApproveReservation"
                        @view="onViewReservation">
                    </DayReservationList>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
