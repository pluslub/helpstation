<script setup>
    //データ処理 JavaScript
    import {computed} from 'vue';

    //ReservationControllerからデータを受け取る
    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
    const props = defineProps({reservations:Array, weekStart:String, shiftDetails:Array});

    //時間 hh:mm:ssからh:mmに変換する
    const formatTime = (time) => {
        if(!time) return '';
        const [h, m] = time.split(':');      // '09:00:00' → ['09', '00', '00']
        return `${Number(h)}:${m}`;          // Number('09') は 9 になる
    }

    /**
     * 今週の７日間の日付を入力する。
     * computed(() => {}) ：　ほかのデータから計算して作る値
     * props.weekStartが変更されると自動で再計算される。
     * props.weekStartが変更されるまで計算結果を記憶する。
     *
     * 出力：文字列"yyyy-MM-dd"の配列
     */
    const days = computed(() => {
        const result = [];
        for(let i = 0; i < 7; i++){
            const d = new Date(props.weekStart + 'T00:00:00');
            d.setDate(d.getDate() + i)
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            result.push(`${y}-${m}-${day}`);
        }
        return result;
    });

    //予約を日付毎に取得
    const reservationsByDay = computed(() => {
        const map = {};
        for (const r of props.reservations) {
            const day = r.date.slice(0, 10);
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
        for (const s of props.shiftDetails) {
            const day = s.date.slice(0, 10);
            const eff = effective(s);
            if(eff.amOff && eff.pmOff){
                (dayOffNameMap[day] ??= []).push(eff); //全休の人は名前を取得
            }else{
                (shiftMap[day] ??= []).push(eff);      //それ以外の人は全データ取得
            }
        }
        return {shiftMap, dayOffNameMap};
    })

    const isProvisional = (res) => res.status === 'provisional';
</script>

<template>
    <div class="grid grid-cols-7 gap-2">
        <div v-for="day in days" :key="day">
            <h2>{{ day }}</h2>
            <!-- 予約カード -->
            <template v-for="res in reservationsByDay[day] ?? []" :key="res.id">
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
                <template v-for="shift in shiftByDay.shiftMap[day] ?? []" :key="shift.id">
                    <div v-if="shift.amOff">
                        {{ shift.name }} {{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}（午前休）<span v-if="shift.changed">＊</span>
                    </div>
                    <div v-else-if="shift.pmOff">
                        {{ shift.name }} {{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}（午後休）<span v-if="shift.changed">＊</span>
                    </div>
                    <div v-else>
                        {{ shift.name }} {{ formatTime(shift.start) }}〜{{ formatTime(shift.end) }}<span v-if="shift.changed">＊</span>
                    </div>
                </template>
                <div v-if="shiftByDay.dayOffNameMap[day]">休：
                    <span v-for="dayOffStaff in shiftByDay.dayOffNameMap[day] ?? []" :key="dayOffStaff.id">
                        {{ dayOffStaff.name }}<span v-if="dayOffStaff.changed">＊</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
