<script setup>
    //データ処理 JavaScript
    import {computed} from 'vue';
    import { Link } from '@inertiajs/vue3';

    //ReservationControllerからデータを受け取る
    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
    const props = defineProps({
        reservations: Array,
        shiftDetails: Array,
        weekStart: String,
        prevWeek: String,
        nextWeek: String,
    });

    /**
     * 時間 hh:mm:ssからh:mmに変換する
     *
     * `（@の上の文字）バッククォートについて
     * 中に${（変数や計算）}を書くと、結果が埋め込まれる。
     */
    const formatTime = (time) => {
        if(!time) return '';
        const [h, m] = time.split(':');     // '09:00:00' → ['09', '00', '00']
        return `${Number(h)}:${m}`;         // Number('09') は 9 になる
    }

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

    //予約を日付毎に取得
    const reservationsByDay = computed(() => {
        const map = {};
        //予約内容を日付毎のMapオブジェクトに格納する。
        for (const r of props.reservations) {
            const day = r.date.slice(0, 10);    //yyyy-MM-ddに変換
            (map[day] ??= []).push(r);
        }
        return map;
    });

    //管理者による変更をチェックし、１件分のシフトデータを出力する。
    const effective = (s) => {
        const changed = s.admin_modified_flag;
        return {
            id : s.id,
            name: s.shift.staff.name,
            start: changed ? s.modified_start_time : s.applied_start_time,
            end: changed ? s.modified_end_time : s.applied_end_time,
            amOff: changed ? s.modified_am_off : s.applied_am_off,
            pmOff: changed ? s.modified_pm_off : s.applied_pm_off,
            changed,
        }
    }

    //シフトを日付毎に取得
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
    })

    //予約が未承認かどうか
    //未承認の場合trueになる
    const isProvisional = (res) => res.status === 'provisional';
</script>

<template>
    <!-- 週操作ボタン -->
    <div class="mb-2 flex items-center gap-2">
        <Link :href="`/?week=${prevWeek}`" preserve-scroll class="rounded border px-3 py-1">← 前週</Link>
        <Link href="/" preserve-scroll class="rounded border px-3 py-1">今週</Link>
        <Link :href="`/?week=${nextWeek}`" preserve-scroll class="rounded border px-3 py-1">次週 →</Link>
    </div>

    <div class="grid grid-cols-7 gap-2">
        <div v-for="day in days" :key="day.date">
            <span v-if="day.showYear" class="text-xs text-gray-500">{{ day.year }}/</span>{{ day.monthDay }}

            <!-- 予約カード -->
            <template v-for="res in reservationsByDay[day.date] ?? []" :key="res.id">
                <div class="rounded border p-2"
                    :class="{
                            'border-dashed border-amber-500 bg-amber-50' : isProvisional(res),
                            'border-red-500': res.vehicle_reassigned_flag,
                    }">
                    <span>{{ res.client.last_name }} {{ formatTime(res.start_time) }}〜{{ formatTime(res.end_time) }}</span>

                    <span v-if="isProvisional(res)"
                        class="rounded bg-amber-500 px-1 text-xs text-white">仮登録</span>

                    <span v-if="res.vehicle_reassigned_flag"
                        class="rounded bg-red-500 px-1 text-xs text-white">配車変更</span>
                    <br>

                    <span v-for="assignedStaff in res.staff_assignments" :key="assignedStaff.staff_id ?? 'none'">
                        {{ assignedStaff.staff?.name ?? '未定' }}
                    </span>
                    <span>{{ res.support_type?.name ?? '削除済'}} {{ res.vehicle?.name ?? 'なし' }}</span>
                </div>
            </template>
            <!-- シフト・休み情報 -->
            <div class="text-sm text-gray-600">
                <template v-for="shift in shiftByDay.shiftMap[day.date] ?? []" :key="shift.id">
                    <div>
                        {{ shift.name }} {{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}
                        <span v-if="shift.amOff">（午前休）</span>
                        <span v-else-if="shift.pmOff">（午後休）</span>
                        <span v-if="shift.changed"
                            class="rounded bg-cyan-500 px-1 text-xs text-white">変更</span>
                    </div>
                </template>
                <div v-if="shiftByDay.dayOffNameMap[day.date]">休：
                    <span v-for="dayOffStaff in shiftByDay.dayOffNameMap[day.date] ?? []" :key="dayOffStaff.id">
                        {{ dayOffStaff.name }}<span v-if="dayOffStaff.changed"
                            class="rounded bg-cyan-500 px-1 text-xs text-white">変更</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
