<script setup>
    //データ処理 JavaScript
    import { computed, ref, watch } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';

    //ReservationControllerからデータを受け取る
    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
    const props = defineProps({
        reservations: Array,
        shiftDetails: Array,
        weekStart: String,
        prevWeek: String,
        nextWeek: String,
        vehicleOptions: Array,
    });

    const page = usePage();

    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。
    const filterType = ref('all');   // 'all' | 'self' | 'staff' | 'vehicle' | 'client'
    const filterId   = ref(null);    // 職員・車両・利用者を選んだときの対象の id

    // 予約が未承認かどうか
    // 未承認の場合trueになる
    const isProvisional = (res) => res.status === 'provisional';


    /**
     * 利用者フィルターの処理
     * 利用者のフィルターは全利用者を表示するとプルダウンが長くなるので、
     * 表示している週に予約が入っている利用者のみを取り出して表示する。
     */
    // 選択中の利用者名（週を切り替えて予約がなくなっても、選択肢に名前を残すため）
    const selectedClientName = ref('');

    // 利用者を選んだら、その名前を覚えておく
    // watch: 値が変わったときに実行する。
    watch(filterId, (id) => {
        if(filterType.value !== 'client') return;

        const client = props.reservations.find(r => r.client_id === id)?.client;
        if (client) selectedClientName.value = client.last_name;
    });

    // 利用者の選択肢：表示中の週に予約がある利用者のみ（重複なし、id順）
    const clientOptions = computed(() => {
        const map = new Map();
        for (const r of props.reservations) {
            map.set(r.client_id, r.client.last_name);
        }
        // 選択中の利用者がこの週にいなければ、選択肢に残す
        if (filterType.value === 'client' && filterId.value !== null && !map.has(filterId.value)) {
            map.set(filterId.value, selectedClientName.value);
        }

            // mapを[id, lastname]の配列に変換
        const options =  [...map]
            .map(([id, lastName]) => ({ id, last_name: lastName }))
            .sort((a, b) => a.id - b.id);

        // 苗字ごとの人数を数える
        const count = {};
        for (const o of options) {
            count[o.last_name] = (count[o.last_name] ?? 0) + 1;
        }

        // 同じ苗字が複数いる場合だけ、苗字の後ろにIDを付ける
        // スプレッド構文：  { ...o, label: '鈴木（3）' } → { id: 3, last_name: '鈴木', label: '鈴木（3）' }
        return options.map(o => ({
            ...o,
            label: count[o.last_name] > 1 ? `${o.last_name}（${o.id}）` : o.last_name,
        }));
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

    /**
     * 職員フィルターの処理
     * 職員のフィルターはシフトが入っていない職員まで含めるとプルダウンが長くなるので、
     * 表示している週に1日以上シフトが入っているか予約が入っている職員のみを取り出して表示する。
     */
    const selectedStaffName = ref('');

    // 職員を選んだら、その名前を覚えておく
    // watch: 値が変わったときに実行する。
    watch(filterId, (id) => {
        if(filterType.value !== 'staff') return;

        const staff = staffOptions.value.find(option => option.id === id);
        if (staff) selectedStaffName.value = staff.name;
    });

    // 職員の選択肢：表示中の週に1日以上シフトが入っているか、予約がある職員のみ（重複なし、id順）
    const staffOptions = computed(() => {
        const map = new Map();

        //シフト一覧のスタッフ名を記録する。
        for (const s of props.shiftDetails) {
            const eff = effective(s);
            if(!(eff.amOff && eff.pmOff)){
                map.set(s.shift.staff_id, eff.name);
            }
        }

        //予約一覧のスタッフ名を記録する。
        for (const r of props.reservations) {
            for(const assignment of r.staff_assignments){
                if(assignment.staff != null){
                    map.set(assignment.staff_id, assignment.staff.name);
                }
            }
        }

        // 選択中の職員がこの週にいなければ、選択肢に残す
        if (filterType.value === 'staff' && filterId.value !== null && !map.has(filterId.value)) {
            map.set(filterId.value, selectedStaffName.value);
        }

        // mapを[id, name]の配列に変換
        return [...map]
            .map(([id, name]) => ({ id, name }))
            .sort((a, b) => a.id - b.id);
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

    // 予約情報を取得して、絞り込み条件に合う予約だけを返す（シフト・休みは絞り込まない）
    const filteredReservations = computed(() => {
        const id = filterId.value;
        const hasStaff = (r, staffId) => r.staff_assignments.some(a => a.staff_id === staffId); //some:配列の中に条件に合う要素が1つでもあればtrueを返す

        switch (filterType.value) {
            case 'self':
                return props.reservations.filter(r => hasStaff(r, page.props.auth.staffId));
            case 'staff':
                return id === null ? props.reservations : props.reservations.filter(r => hasStaff(r, id));
            case 'vehicle':
                return id === null ? props.reservations : props.reservations.filter(r => r.vehicle_id === id);
            case 'client':
                return id === null ? props.reservations : props.reservations.filter(r => r.client_id === id);
            default:
                return props.reservations;    // 'all'
        }
    });

    //予約情報を日付毎に格納
    const reservationsByDay = computed(() => {
        const map = {};
        //予約内容を日付毎のMapオブジェクトに格納する。
        for (const r of filteredReservations.value) {
            const day = r.date.slice(0, 10);    //yyyy-MM-ddに変換
            (map[day] ??= []).push(r);          //??=について：map[day]がnullか空なら配列を作成する。
        }
        return map;
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
    })
</script>

<!--
    タグ内の記号の意味

    コロン（:）:   v-bind:の省略形。文字列ではなくJavascriptの式として扱う。
    @：           v-on:の省略形。イベントが起きたときに実行する処理を書く。
 -->
<template>
    <!-- フィルター -->
    <div class="mb-2 flex items-center gap-2">
        <!--
            v-model
                プルダウンと ref を双方向につなぐ指定。
                プルダウンで選ぶと filterType が変わり、
                filterType を書き換えると、プルダウンの表示も変わる。

            @change="filterId = null"：
                条件の種類を切り替えたときに、前の選択IDをリセットします
        -->
        <select v-model="filterType" @change="filterId = null" class="rounded border px-2 py-1">
            <option value="all">全体</option>
            <option value="self">個人</option>
            <option value="staff">職員</option>
            <option value="vehicle">車両</option>
            <option value="client">利用者</option>
        </select>

        <select v-if="filterType === 'staff'" v-model="filterId" class="rounded border px-2 py-1">
            <option :value="null">職員を選択</option>
            <option v-for="s in staffOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>

        <select v-if="filterType === 'vehicle'" v-model="filterId" class="rounded border px-2 py-1">
            <option :value="null">車両を選択</option>
            <option v-for="v in vehicleOptions" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>

        <select v-if="filterType === 'client'" v-model="filterId" class="rounded border px-2 py-1">
            <option :value="null">利用者を選択</option>
            <option v-for="c in clientOptions" :key="c.id" :value="c.id">{{ c.label }}</option>
        </select>
    </div>

    <!-- 週操作ボタン -->
    <!--
        preserve-state
            ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
            refが保存される。
        preserve-scroll:
            ページを移動しても、スクロール位置をそのまま保つ
    -->
    <div class="mb-2 flex items-center gap-2">
        <Link :href="`/?week=${prevWeek}`" preserve-state preserve-scroll class="rounded border px-3 py-1">← 前週</Link>
        <Link href="/" preserve-state preserve-scroll class="rounded border px-3 py-1">今週</Link>
        <Link :href="`/?week=${nextWeek}`" preserve-state preserve-scroll class="rounded border px-3 py-1">次週 →</Link>
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
