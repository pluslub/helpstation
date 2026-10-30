<script setup>
    //データ処理 JavaScript
    import { computed } from 'vue';
    import { effective } from "@/Utils/effective.js"
    import { formatTime } from "@/Utils/formatTime.js"
    import { formatDate } from "@/Utils/formatDate.js"
    import { isProvisional } from "@/Utils/isProvisional.js"
    import { getVehicleTypeShortLabel } from "@/Utils/vehicleTypeLabel.js"
    import { usePage } from '@inertiajs/vue3';

    //Heroicons
    import { PencilIcon, EyeIcon, ClockIcon  } from '@heroicons/vue/24/outline';
    import { ArrowPathIcon } from '@heroicons/vue/16/solid';

    //ReservationControllerからデータを受け取る
    const props = defineProps({
        //絞り込み前の予約配列
        //reservationsの構造
        // reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        allReservations: Array,

        //絞り込み後の予約配列
        reservations: Array,

        shiftDetails: Array,
        weekStart: String,
        openedReservationId: Number,
        showYearOnFirstDay: { type: Boolean, default: true},
        clickableShift: { type: Boolean, default: false},
        clickableDate: String,
        readonly: { type: Boolean, default: false},
    });

    const emit = defineEmits(['update', 'approve', 'view', 'click-shift']);

    const page = usePage();
    //------------------------------------------------関数-----------------------------------------------
    const isPrivateCar = (res) => {
        return res.vehicle_type_choice === 'private_car'
    };

    const isNotUseCar = (res) => {
        return res.vehicle_type_choice === 'none'
    };

    const checkUsePrivateCar = (shift, day) => {
        return shiftByDay.value.privateCarMap[`${shift.staffId}-${day.date}`];
    };

    const hasShiftInfo = (day) => {
        return shiftByDay.value.shiftMap[day.date]?.length > 0 || shiftByDay.value.dayOffNameMap[day.date]?.length > 0;
    }

    const clickableShiftByDay = (day) => {
        return props.clickableShift && (props.clickableDate == null || props.clickableDate === day.date);
    }

    /**
     * ポップアップを出す位置の基準を作る。左右はその日の列の端（カードにかぶらないように）、上は押したボタンの高さ
     * @param e
     */
    const getPopupTargetRect = (e) => {
        const dayColumnRect = e.currentTarget.closest('[data-day-column]').getBoundingClientRect();
        const buttonRect = e.currentTarget.getBoundingClientRect();
        const rect = { left: dayColumnRect.left, right: dayColumnRect.right, top: buttonRect.top, bottom: buttonRect.bottom };
        return rect;
    }

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
                monthDay: `${month}/${day}(${weekNames[d.getDay()]})`,  //M/d(w)の文字列
                showYear: i === 0 ? props.showYearOnFirstDay : year !== result[i - 1].year,       //ループの一つ前のオブジェクトresult[i - 1]と比較
            });
        }
        return result;
    });

    //予約情報を日付毎に格納
    const reservationsByDay = computed(() => {
        const cardMap = {};

        //予約内容を日付毎のMapオブジェクトに格納する。
        for (const r of props.reservations) {
            const day = r.date.slice(0, 10);    //yyyy-MM-ddに変換

             //??=について：map[day]がnullか空なら配列を作成する。
            cardMap[day] ??= [];
            let card = {};

            let cardFilteredByClient = cardMap[day].find(c => r.client_id === c.client_id);
            if (cardFilteredByClient == null){
                card.client_id = r.client_id;
                card.client_last_name = r.client.last_name;
                card.reservations = [];
                cardMap[day].push(card);
            }else{
                card = cardFilteredByClient;
            }
            card.reservations.push(r);
        }
        return cardMap;
    });

    //シフト情報を取得して日付毎に格納
    const shiftByDay = computed(() => {
        const shiftMap = {};
        const dayOffNameMap = {};
        const privateCarMap = {};

        //シフト内容を日付毎のMapオブジェクトに格納する。
        //全休の場合は休みMapオブジェクトに格納する。
        for (const s of props.shiftDetails) {
            const day = s.date.slice(0, 10);
            const eff = effective(s);
            if(eff.amOff && eff.pmOff){
                (dayOffNameMap[day] ??= []).push(eff); //全休の人は名前を格納
            }else{
                (shiftMap[day] ??= []).push(eff);      //それ以外の人は全データ格納
            }

            //私用車利用マークをつけるためのマップを作成 {スタッフｰ日付: {start:~, end:~}}
            const hasStaff = (r, staffId) => r.staff_assignments.some(a => a.staff_id === staffId);
            const reservationByStaff = props.allReservations.filter(r => r.date.slice(0, 10) === day &&  hasStaff(r, eff.staffId));

            if(reservationByStaff.length !== 0){
                const map ={
                    start: isPrivateCar(reservationByStaff[0]),
                    end: isPrivateCar(reservationByStaff[reservationByStaff.length - 1])
                };
                privateCarMap[`${eff.staffId}-${day}`] = map;
            }
        }
        return {shiftMap, dayOffNameMap, privateCarMap};
    });

    const onClickUpdateButton = (res, e) => {
        const rect = getPopupTargetRect(e);
        emit('update', res, rect);
    }

    const onClickApproveButton = (res) => {
        emit('approve', res);
    }

    const onClickViewButton = (res, e) => {
        const rect = getPopupTargetRect(e);
        emit('view', res, rect);
    }

    const onClickShift = (date, staffId) => {
        emit('click-shift', date, staffId);
    }
</script>

<!--
    タグ内の記号の意味

    コロン（:）:   v-bind:の省略形。文字列ではなくJavascriptの式として扱う。
    @：           v-on:の省略形。イベントが起きたときに実行する処理を書く。
 -->
<template>
    <div class="flex flex-col gap-2">
        <!-- 日付・シフト・休み情報 -->
        <div class="grid grid-cols-7 gap-2 px-2 sticky top-(--header-height) z-4 bg-surface p-1 shadow-sm font-bold">
            <!-- computedされた日付配列を繰り返す（7回）各DOMを識別するために一意なkeyが必要 -->
            <div v-for="day in days" :key="day.date" class="min-w-0">
            <!-- 日付ラベル 週の先頭と年の切り替わりだけ年を表示する -->
                <div class="text-center p-1 m-1"
                    :class="{ 'text-sunday' : day.weekNum === 0,
                             'text-saturday' : day.weekNum === 6,
                             'bg-reservation rounded-lg' : day.date === today}">
                    <span v-if="day.showYear" class="text-xs">{{ day.year }}/</span>{{ day.monthDay }}
                </div>
            </div>
        </div>
        <!--
            シフト・休み情報
            computedにより日付毎に分別されたシフト情報を取得する。
        -->
        <div class="grid grid-cols-7 gap-2 px-2">
            <div v-for="day in days" :key="day.date" class="min-w-0">
                <div>
                    <div v-if="hasShiftInfo(day)"
                        class="grid grid-cols-[auto_auto_auto_auto_1fr] gap-x-1 rounded-lg bg-shift-soft border-t-4 border-shift p-1 text-sm">
                        <template v-for="shift in shiftByDay.shiftMap[day.date] ?? []" :key="shift.id">
                            <button class="grid grid-cols-subgrid col-span-5 text-left rounded items-center"
                                    type="button"
                                    @click="onClickShift(day.date, shift.staffId)"
                                    :disabled="!clickableShiftByDay(day)"
                                    :class="{ 'cursor-pointer hover:bg-reservation-soft' : clickableShiftByDay(day) }">
                                <span class="truncate min-w-0">{{ shift.name }}</span>
                                <span>
                                    <span v-if="checkUsePrivateCar(shift, day)?.start"
                                        title="私用車(直行)"
                                        class="rounded-full bg-ink w-2 h-2 inline-block">
                                    </span>
                                </span>
                                <span class="text-xs text-right tabular-nums">{{ formatTime(shift.start) }} 〜
                                </span>
                                <span class="text-xs tabular-nums">{{ formatTime(shift.end) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <span v-if="shift.amOff" class="badge bg-rest-soft text-rest font-bold">AM</span>
                                    <span v-else-if="shift.pmOff" class="badge bg-rest-soft text-rest font-bold">PM</span>
                                    <span v-if="checkUsePrivateCar(shift, day)?.end"
                                        title="私用車(直帰)"
                                        class="rounded-full bg-ink w-2 h-2 inline-block"></span>
                                    <span v-if="shift.changed"
                                        class="badge bg-shift text-white">
                                        <ArrowPathIcon class="w-4 h-4"/>
                                    </span>
                                </span>
                            </button>
                        </template>
                        <div v-if="shiftByDay.dayOffNameMap[day.date]" class="flex items-start col-span-full pt-2">
                            <span class="shrink-0">休：</span>
                            <div class="flex flex-wrap gap-x-1 gap-y-0.5 flex-1  min-w-0">
                                <span v-for="(dayOffStaff, index) in shiftByDay.dayOffNameMap[day.date] ?? []" :key="dayOffStaff.id"
                                        class="flex items-center gap-1">
                                    <span v-if="index >= 1">･</span>
                                    <span class="whitespace-nowrap">{{ dayOffStaff.name }}</span>
                                    <div v-if="dayOffStaff.changed"
                                        class="badge bg-shift text-white">
                                        <ArrowPathIcon class="w-4 h-4"/>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 予約カード -->
        <div class="grid grid-cols-7 gap-2 px-2">
            <div v-for="day in days" :key="day.date" class="flex flex-col gap-2 min-w-0" data-day-column>
                <template v-for="card in reservationsByDay[day.date] ?? []" :key="card.client_id">
                    <!-- 利用者ごとにカードを表示する -->
                    <div class="rounded-lg shadow-sm p-1 flex flex-col"
                        :class="card.reservations.some(r => isProvisional(r))   ? 'bg-provisional-soft border-2 border-dashed border-provisional-line'
                                                                                : 'bg-surface border-l-4 border-reservation'">
                        <!-- カードごとに予約情報を表示する -->
                        <div class="divide-y divide-line">
                            <!--カードを装飾する。予約が選択されているとき-->
                            <div v-for="(res, index) in card.reservations" :key="res.id"
                                :class="{ 'bg-reservation-soft': res.id === props.openedReservationId }"
                                class="p-1 rounded grid grid-cols-[4.5rem_1fr] gap-x-1">
                                <div class="col-start-1 row-start-1">
                                    <span v-if="index === 0">{{ card.client_last_name }}</span>
                                </div>
                                <!--支援内容-->
                                <div class="min-w-0 col-start-2 row-start-1">
                                    <div class="flex items-center gap-1">
                                        <span class="flex items-center gap-1 flex-1 min-w-0">
                                            <span class="truncate min-w-0">{{ res.support_type?.name ?? '削除済'}}</span>
                                            <span v-if="isPrivateCar(res)"
                                                    class="rounded-full bg-ink w-2 h-2 shrink-0"
                                                    title="私用車">
                                            </span>
                                        </span>

                                        <!-- 予約操作ボタン -->
                                        <div class="flex ml-auto shrink-0">
                                            <button title="仮登録"
                                                    v-if="isProvisional(res)"
                                                    :disabled="!page.props.auth.isAdmin || props.readonly"
                                                    @click="onClickApproveButton(res)"
                                                    class="badge bg-provisional-soft text-provisional">
                                                    <ClockIcon class="h-4 w-4 stroke-2"/>
                                            </button>
                                            <button
                                                v-if="page.props.auth.isAdmin && !props.readonly"
                                                @click="onClickUpdateButton(res, $event)">
                                                <PencilIcon class="h-4 w-4 stroke-2"/>
                                            </button>
                                            <button
                                                v-if="!page.props.auth.isAdmin && !props.readonly"
                                                @click="onClickViewButton(res, $event)">
                                                <EyeIcon class="h-4 w-4 stroke-2"/>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--担当者-->
                                <div class="grid grid-cols-2 gap-x-1 min-w-0 col-start-1 row-start-2 row-span-2 self-end">
                                    <span v-for="assignedStaff in res.staff_assignments"
                                        :key="assignedStaff.staff_id ?? 'none'"
                                        class="truncate text-sm">
                                        {{ assignedStaff.staff?.name ?? '未定' }}
                                    </span>
                                </div>
                                <div v-if="res.remarks" class="col-start-2 row-start-2">
                                    <span :title="res.remarks"
                                        class="text-ink-muted break-words line-clamp-2 text-xs">
                                        {{ res.remarks }}
                                    </span>
                                </div>
                                <!--時刻と車両-->
                                <div class="flex items-center gap-1 col-start-2 row-start-3">
                                    <span class="shrink-0 text-sm">{{ formatTime(res.start_time) }}〜{{ formatTime(res.end_time) }}</span>
                                    <!--車両ラベル-->
                                    <span class="flex items-center gap-1">
                                        <div v-if="!isPrivateCar(res) && !isNotUseCar(res)"
                                            class="badge font-bold"
                                            :title="res.vehicle?.name"
                                            :class="res.vehicle_type_choice === 'care_vehicle' ? 'bg-care-vehicle-soft text-care-vehicle' : 'bg-company-car-soft text-company-car'">
                                            {{ getVehicleTypeShortLabel(res.vehicle_type_choice) }}
                                        </div>
                                        <div v-if="res.vehicle_reassigned_flag"
                                            class="badge bg-reassigned text-white"
                                            title="配車変更">
                                            <ArrowPathIcon class="w-4 h-4"/>
                                        </div>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
