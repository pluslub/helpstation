<script setup>
    //データ処理 JavaScript
    import {computed} from 'vue';

    //ReservationControllerからデータを受け取る
    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
    const props = defineProps({reservations:Array, weekStart:String});

    /**
     * 今週の７日間の日付を入力する。
     * computed(() => {}) ：　ほかのデータから計算して作る値
     * props.weekStartが閻行されると自動で再計算される。
     * props.weekStartが変更されるまで計算結果を記憶する。
     *
     * 出力：文字列"yyyy-MM-dd"の配列
     */
    const days = computed(() => {
        const result = [];
        for(let i = 0; i < 7; i++){
            const d = new Date(props.weekStart);
            d.setDate(d.getDate() + i)
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            result.push(`${y}-${m}-${day}`);
        }
        return result;
    });

    //予約日時
    const reservationsByDay = computed(() => {
        const map = {};
        for (const r of props.reservations) {
            const day = r.date.slice(0, 10);
            (map[day] ??= []).push(r);
        }
        return map;
    });
</script>

<template>
    <div class="grid grid-cols-7 gap-2">
        <div v-for="day in days" :key="day">
            <h2>{{ day }}</h2>
            <div v-for="r in reservationsByDay[day] ?? []" :key="r.id" class="rounded border p-2">
                {{ r.start_time }}〜{{ r.end_time }} {{ r.client.last_name }} {{ r.staff_assignments[0].staff.name }}
            </div>
        </div>
    </div>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
