<script setup>
    //データ処理 JavaScript
    import { computed } from 'vue';
    import { effective } from "@/Utils/effective.js"
    import { formatTime } from "@/Utils/formatTime.js"
    import { isProvisional } from "@/Utils/isProvisional.js"
    import { getVehicleTypeLabel } from "@/Utils/vehicleTypeLabel.js"
    import { usePage } from '@inertiajs/vue3';

    //Heroicons
    import { PencilIcon, EyeIcon, ClockIcon  } from '@heroicons/vue/24/outline';
    import { ArrowPathIcon} from '@heroicons/vue/16/solid';

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
        readonly: { type: Boolean, default: false},
    });

    const emit = defineEmits(['update', 'approve', 'view']);

    const page = usePage();
    //------------------------------------------------関数-----------------------------------------------
    const isPrivateCar = (res) => {
        return res.vehicle_type_choice === 'private_car'
    };

    const checkUsePrivateCar = (shift, day) => {
        return shiftByDay.value.privateCarMap[`${shift.staffId}-${day.date}`];
    };

    const hasShiftInfo = (day) => {
        return shiftByDay.value.shiftMap[day.date]?.length > 0 || shiftByDay.value.dayOffNameMap[day.date]?.length > 0;
    }

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
                showYear: i === 0 || year !== result[i - 1].year,       //ループの一つ前のオブジェクトresult[i - 1]と比較
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
        const rect = e.currentTarget.getBoundingClientRect();
        emit('update', res, rect);
    }

    const onClickApproveButton = (res) => {
        emit('approve', res);
    }

    const onClickViewButton = (res, e) => {
        const rect = e.currentTarget.getBoundingClientRect();
        emit('view', res, rect);
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
        <div class="grid grid-cols-7 gap-2">
            <!-- computedされた日付配列を繰り返す（7回）各DOMを識別するために一意なkeyが必要 -->
            <div v-for="day in days" :key="day.date" class="min-w-0">
            <!-- 日付ラベル 週の先頭と年の切り替わりだけ年を表示する -->
                <span v-if="day.showYear" class="text-xs text-ink-muted">{{ day.year }}/</span>{{ day.monthDay }}

                <!--
                    シフト・休み情報
                    computedにより日付毎に分別されたシフト情報を取得する。
                -->
                <div v-if="hasShiftInfo(day)"
                    class="rounded-lg bg-shift-soft border-t-4 border-shift p-1 text-sm">
                    <template v-for="shift in shiftByDay.shiftMap[day.date] ?? []" :key="shift.id">
                        <div class="flex items-center gap-1 whitespace-nowrap">
                            <span class="truncate min-w-0">{{ shift.name }}</span>
                            <div v-if="checkUsePrivateCar(shift, day)?.start"
                                title="私用車(直行)"
                                class="rounded-full bg-ink w-2 h-2"></div>
                            <span class="text-xs shrink-0">{{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}</span>
                            <span v-if="shift.amOff" class="badge bg-rest-soft text-rest font-bold">AM</span>
                            <span v-else-if="shift.pmOff" class="badge bg-rest-soft text-rest font-bold">PM</span>
                            <div v-if="checkUsePrivateCar(shift, day)?.end"
                                title="私用車(直帰)"
                                class="rounded-full bg-ink w-2 h-2"></div>
                            <div v-if="shift.changed"
                                class="badge bg-shift text-white">
                                <ArrowPathIcon class="w-4 h-4"/>
                            </div>
                        </div>
                    </template>
                    <div v-if="shiftByDay.dayOffNameMap[day.date]" class="flex items-start">
                        <span class="shrink-0">休：</span>
                        <div class="flex flex-wrap gap-x-1 gap-y-0.5 flex-1  min-w-0">
                            <span
                                v-for="(dayOffStaff, index) in shiftByDay.dayOffNameMap[day.date] ?? []" :key="dayOffStaff.id"
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

        <!-- 予約カード -->
        <div class="grid grid-cols-7 gap-2">
            <div v-for="day in days" :key="day.date" class="flex flex-col gap-2 min-w-0">
                <template v-for="card in reservationsByDay[day.date] ?? []" :key="card.client_id">
                    <!-- 利用者ごとにカードを表示する -->
                    <div class="rounded-lg shadow-sm p-2 flex flex-col gap-1"
                        :class="card.reservations.some(r => isProvisional(r))   ? 'bg-provisional-soft border-2 border-dashed border-provisional-line'
                                                                                : 'bg-surface border-l-4 border-reservation'">

                        <div class="border-b border-line pb-1">
                            <span>{{ card.client_last_name }}</span>
                        </div>

                        <!-- カードごとに予約情報を表示する -->
                        <div class="divide-y divide-line">
                            <!--カードを装飾する。予約が選択されているとき-->
                            <div v-for="res in card.reservations" :key="res.id"
                                :class="{ 'bg-reservation-soft': res.id === props.openedReservationId }"
                                class="p-1 rounded">
                                <div class="flex items-center gap-1">
                                    <span class="shrink-0">{{ formatTime(res.start_time) }}〜{{ formatTime(res.end_time) }}</span>

                                    <!-- 予約操作ボタン -->
                                    <div class="flex ml-auto">
                                        <button title="仮登録"
                                                v-if="isProvisional(res)"
                                                :disabled="!page.props.auth.isAdmin || props.readonly"
                                                @click="onClickApproveButton(res)"
                                                class="badge bg-provisional-soft text-provisional">
                                                <ClockIcon class="h-4 w-4"/>仮
                                        </button>
                                        <button
                                            v-if="page.props.auth.isAdmin && !props.readonly"
                                            @click="onClickUpdateButton(res, $event)">
                                            <PencilIcon class="h-4 w-4"/>
                                        </button>
                                        <button
                                            v-if="!page.props.auth.isAdmin && !props.readonly"
                                            @click="onClickViewButton(res, $event)">
                                            <EyeIcon class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-x-1">
                                    <span v-for="(assignedStaff, index) in res.staff_assignments" :key="assignedStaff.staff_id ?? 'none'" class="whitespace-nowrap">
                                        <span v-if="index >= 1">･</span>
                                        {{ assignedStaff.staff?.name ?? '未定' }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-1">
                                    <!--支援内容ラベル-->
                                    <span class="flex items-center gap-1 min-w-0">
                                        <div class="shrink-0">{{ res.support_type?.name ?? '削除済'}}</div>
                                        <div v-if="res.remarks" :title="res.remarks" class="line-clamp-1">: {{ res.remarks }}</div>
                                        <div v-if="isPrivateCar(res)" class="rounded-full bg-ink w-2 h-2" title="私用車"></div>
                                    </span>
                                </div>
                                <div>
                                    <!--車両ラベル-->
                                    <span class="flex items-center gap-1">
                                        <div v-if="!isPrivateCar(res)">{{ res.vehicle?.name ?? getVehicleTypeLabel(res.vehicle_type_choice) }}</div>
                                        <div v-if="res.vehicle_reassigned_flag"
                                            class="badge bg-reassigned text-white"
                                            title="配車変更">
                                            <ArrowPathIcon class="w-4 h-4"/>配車変更
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
