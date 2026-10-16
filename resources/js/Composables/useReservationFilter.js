import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { effective } from "@/Utils/effective.js"

export const useReservationFilter = (props) => {
    //--------------------------------------------ref-------------------------------------------
    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。
    const filterType = ref('all');   // 'all' | 'self' | 'staff' | 'vehicle' | 'client'
    const filterId   = ref(null);    // 職員・車両・利用者を選んだときの対象の id

    /**
     * 職員フィルターの処理
     * 職員のフィルターはシフトが入っていない職員まで含めるとプルダウンが長くなるので、
     * 表示している週に1日以上シフトが入っているか予約が入っている職員のみを取り出して表示する。
     */
    const selectedStaffName = ref('');

    /**
     * 利用者フィルターの処理
     * 利用者のフィルターは全利用者を表示するとプルダウンが長くなるので、
     * 表示している週に予約が入っている利用者のみを取り出して表示する。
     */
    // 選択中の利用者名（週を切り替えて予約がなくなっても、選択肢に名前を残すため）
    const selectedClientName = ref('');

    /**
     * 表示しているページの情報を取り出す
     * difinePropsとの違いは宣言しなくてもすべてのデータを扱える点
     *
     * props：このページに届いたデータすべて
     * url：今のページのURL	'/?week=2026-10-14'
     * component：表示しているVueページの名前
     */
    const page = usePage();

    //--------------------------------------------watch関数-------------------------------------------
    // watch: 指定した変数の値が変わったときに実行する。

    // 利用者を選んだら、その名前を覚えておく
    watch(filterId, (id) => {
        if(filterType.value !== 'client') return;

        const client = props.reservations.find(r => r.client_id === id)?.client;
        if (client) selectedClientName.value = client.last_name;
    });

    // 職員を選んだら、その名前を覚えておく
    // watch: 値が変わったときに実行する。
    watch(filterId, (id) => {
        if(filterType.value !== 'staff') return;

        const staff = staffOptions.value.find(option => option.id === id);
        if (staff) selectedStaffName.value = staff.name;
    });

    //--------------------------------------------computed関数-------------------------------------------
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

    return { filterType, filterId, filteredReservations, clientOptions, staffOptions }
}
