<script setup>
    //データ処理 JavaScript
    import { computed } from 'vue';
    import { effective } from "@/Utils/effective.js"
    import { formatTime } from "@/Utils/formatTime.js"
    import { isProvisional } from "@/Utils/isProvisional.js"
    import { getVehicleTypeLabel } from "@/Utils/vehicleTypeLabel.js"
    import { usePage } from '@inertiajs/vue3';

    //Heroicons
    import { PencilIcon, CheckCircleIcon, EyeIcon } from '@heroicons/vue/24/outline';
    import { TruckIcon, ExclamationTriangleIcon, ArrowPathIcon, ClockIcon } from '@heroicons/vue/16/solid';

    //ReservationControllerからデータを受け取る
    const props = defineProps({
        //reservationsの構造
        // reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        reservations: Array,

        shiftDetails: Array,
        weekStart: String,
    });

    const emit = defineEmits(['update', 'approve', 'view']);

    const page = usePage();

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
        }
        return {shiftMap, dayOffNameMap};
    });

    const onClickUpdateButton = (res) => {
        emit('update', res);
    }

    const onClickApproveButton = (res) => {
        emit('approve', res);
    }

    const onClickViewButton = (res) => {
        emit('view', res);
    }
</script>

<!--
    タグ内の記号の意味

    コロン（:）:   v-bind:の省略形。文字列ではなくJavascriptの式として扱う。
    @：           v-on:の省略形。イベントが起きたときに実行する処理を書く。
 -->
<template>
    <!-- 日付・シフト・休み情報 -->
    <div class="grid grid-cols-7 gap-2">
        <div v-for="day in days" :key="day.date">
        <!-- 日付 -->
            <span v-if="day.showYear" class="text-xs text-gray-500">{{ day.year }}/</span>{{ day.monthDay }}

            <!-- シフト・休み情報 -->
            <div class="text-sm text-gray-600">
                <template v-for="shift in shiftByDay.shiftMap[day.date] ?? []" :key="shift.id">
                    <div class="flex items-center gap-1">
                        {{ shift.name }} {{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}
                        <span v-if="shift.amOff">（午前休）</span>
                        <span v-else-if="shift.pmOff">（午後休）</span>
                        <div v-if="shift.changed"
                            class="rounded bg-cyan-500 px-1 text-xs text-white flex items-center gap-1">
                            <ArrowPathIcon class="w-4 h-4"/>変更
                        </div>
                    </div>
                </template>
                <div v-if="shiftByDay.dayOffNameMap[day.date]">休：
                    <span
                        v-for="dayOffStaff in shiftByDay.dayOffNameMap[day.date] ?? []" :key="dayOffStaff.id"
                        class="flex items-center gap-1">
                        {{ dayOffStaff.name }}
                        <div v-if="dayOffStaff.changed"
                            class="rounded bg-cyan-500 px-1 text-xs text-white flex items-center gap-1">
                            <ArrowPathIcon class="w-4 h-4"/>変更
                        </div>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 予約カード -->
    <div class="grid grid-cols-7 gap-2">
        <div v-for="day in days" :key="day.date">
            <template v-for="card in reservationsByDay[day.date] ?? []" :key="card.client_id">
                <!-- 利用者ごとにカードを表示する
                     :class="条件 ? 'クラス' : ''"
                -->
                <div class="rounded border p-2 space-y-1"
                    :class="card.reservations.some(r => isProvisional(r)) ? 'border-dashed border-amber-500 bg-amber-50':''">

                    <div>
                        <span>{{ card.client_last_name }}</span>
                    </div>

                    <!-- カードごとに予約情報を表示する -->
                     <div class="space-y-2">
                        <div v-for="res in card.reservations" :key="res.id">
                            <div class="flex items-center gap-1">
                                <span v-for="assignedStaff in res.staff_assignments" :key="assignedStaff.staff_id ?? 'none'">
                                    {{ assignedStaff.staff?.name ?? '未定' }}
                                </span>
                                <span>{{ formatTime(res.start_time) }}〜{{ formatTime(res.end_time) }}</span>
                                <!-- 予約操作ボタン -->
                                 <button v-if="isProvisional(res)"
                                        :disabled="!page.props.auth.isAdmin"
                                        @click="onClickApproveButton(res)"
                                        class="rounded bg-amber-500 px-1 text-xs text-white flex items-center gap-1">
                                        <ClockIcon class="h-5 w-5"/>仮登録
                                </button>
                                <div>
                                    <button
                                        v-if="page.props.auth.isAdmin"
                                        @click="onClickUpdateButton(res)">
                                        <PencilIcon class="h-4 w-4"/>
                                    </button>
                                    <button
                                        v-if="!page.props.auth.isAdmin"
                                        @click="onClickViewButton(res)">
                                        <EyeIcon class="h-4 w-4"/>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="flex items-center gap-1">
                                    <div>{{ res.support_type?.name ?? '削除済'}}</div>
                                    <div v-if="res.remarks" class="line-clamp-1">: {{ res.remarks }}</div>
                                </span>
                                <span class="flex items-center gap-1">
                                    <div>{{ res.vehicle?.name ?? getVehicleTypeLabel(res.vehicle_type_choice) }}</div>
                                    <div v-if="res.vehicle_reassigned_flag"
                                        class="rounded bg-red-500 px-1 text-xs text-white flex items-center gap-0.5"
                                        title="配車変更">
                                        <ExclamationTriangleIcon class="h-4 w-4"/>
                                        <TruckIcon class="h-4 w-4"/>
                                    </div>
                                </span>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
