<script setup>
    //データ処理 JavaScript
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import { Link, router, useForm, usePage } from '@inertiajs/vue3';

    import WeekGrid from "@/Components/WeekGrid.vue";
    import ReservationFilter from '@/Components/ReservationFilter.vue';
    import ReservationFormPopup from "@/Components/ReservationFormPopup.vue";    //@はresources/jsを指す

    import { useReservationFilter } from "@/Composables/useReservationFilter.js"
    import { formatTime } from "@/Utils/formatTime.js"

    //Heroicons
    import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon} from '@heroicons/vue/24/outline';

    const page = usePage();

    //ReservationControllerからデータを受け取る
    const props = defineProps({
        reservations: Array,    //reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        shiftDetails: Array,
        weekStart: String,
        prevWeek: String,
        nextWeek: String,
        vehicleOptions: Array,
        formClients: Array,
        formStaff: Array,
        formSupportTypes: Array,
    });

    //予約追加画面
    const reservationForm = useForm({
        client_id: null,
        date: '',
        start_time: '',
        end_time: '',
        staff_ids: [],
        support_type_id: null,
        vehicle_type_choice: null,
        remarks: '',
    });

    //予約変更画面
    const reservationUpdateForm = useForm({
        date: '',
        start_time: '',
        end_time: '',
        staff_ids: [],
        support_type_id: null,
        vehicle_type_choice: null,
        remarks: '',
    });

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //日付直接入力欄でキーダウンされたかどうか
    //入力途中の遷移を防ぐため、keydown中なら無視し、カレンダーの選択・エンターキー押下・フォーカスの移動で遷移する
    let keydownOnInputDate = false;

    //--------------------------------------------ref-------------------------------------------
    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。

    //変更すると<ReservationFormPopup>のv-ifによりポップアップが表示される。
    const showPopup = ref(false);
    const popupMode = ref(null)

    const selectReservation = ref(null);

    const popupPositionLeft = ref(null);
    const popupPositionTop = ref(null);

    //自動で入れた日付を覚えておく
    const autofillDate = ref(null);

    //--------------------------------------------関数-------------------------------------------
    const setValue = (res) =>{
        reservationUpdateForm.date = res.date;
        reservationUpdateForm.start_time = res.start_time.slice(0, 5);
        reservationUpdateForm.end_time = res.end_time?.slice(0, 5) ?? '';
        reservationUpdateForm.support_type_id = res.support_type_id;
        reservationUpdateForm.vehicle_type_choice = res.vehicle_type_choice;
        reservationUpdateForm.remarks = res.remarks ?? '';

        reservationUpdateForm.staff_ids = res.staff_assignments.map(a => a.staff_id).filter(id => id !== null);

        reservationUpdateForm.defaults();

        selectReservation.value = res;
    }
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = date.getMonth() + 1;    //月は0～11で表されるので+1する
        const day = date.getDate();

        //'yyyy-MM-dd'の文字列を作成
        return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    const getDefaultReservationDate = () =>{
        const today = formatDate(new Date());
        return props.weekStart <= today && today < props.nextWeek ? today : props.weekStart;
    }

    const moveWeek = (date, isValid) => {
        if(!date || !isValid) return;
        if(props.weekStart <= date && date < props.nextWeek) return;

        router.get('/', { week: date }, { preserveState: true, preserveScroll: true });
    }

    //--------------------------------------------computed関数-------------------------------------------
    const isDirtyPopup = computed(() => {
        const checkArray = [
            reservationForm.date !== '' && reservationForm.date !== autofillDate.value,
            reservationForm.client_id !== null,
            reservationForm.support_type_id !== null,
            reservationForm.vehicle_type_choice !== null,
            reservationForm.start_time !== '',
            reservationForm.end_time !== '',
            reservationForm.remarks !== '',
            reservationForm.staff_ids.length > 0,
        ];
        return checkArray.some(a => a);
    });

    //-----------------------------------------------event----------------------------------------------
    //日付が直接入力されたときのイベント
    const onDateChange = (e) =>{
        if(keydownOnInputDate) return;
        moveWeek(e.target.value, e.target.validity.valid);
    };

    const onEnterOnInputDate = (e) =>{
        keydownOnInputDate = false;
        moveWeek(e.target.value, e.target.validity.valid);
    };

    const onBlurFromInputDate = (e) =>{
        keydownOnInputDate = false;
        moveWeek(e.target.value, e.target.validity.valid);
    };

    const onKeyDownOnInputDate = () =>{
        keydownOnInputDate = true;
    };

    /**
     * 予約入力ポップアップを表示するときの処理
     * 今日が表示中の週に入っていれば今日
     * 入っていなければ（前の週や次の週を表示中なら）その週の日曜日（weekStart）
     */
    const onClickEntryButton = () =>{
        //予約入力ポップアップを開いたときに、日付が空か自動入力の場合は、初期値を入力する。
        if(!reservationForm.date || reservationForm.date === autofillDate.value){
            const date = getDefaultReservationDate();
            reservationForm.date = date;    //予約入力ポップアップに日付の初期値を送る。
            autofillDate.value = date;      //自動入力の場合に他の日付に切り替えられるように記憶する。
        }

        popupMode.value = 'create';
        showPopup.value = true;
    };

    const onClosePopup = () =>{
        switch(popupMode.value) {
            case 'update':
                if(reservationUpdateForm.isDirty && !confirm("変更を破棄して閉じますか？")){
                    return;
                }
                showPopup.value = false;
                return;

            case 'create':
            case 'view':
            default:
                showPopup.value = false;
                return;
        }
    };

    const onSubmitPopup = () =>{
        switch(popupMode.value) {
            case 'create':
                reservationForm.post('/reservations', {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        reservationForm.reset();
                        showPopup.value = false;
                    },
                });
                return;

            case 'update':
                reservationUpdateForm.put(`/reservations/${selectReservation.value?.id}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        showPopup.value = false;
                    },
                });
                return;

            default:
                return;
        }

    }

    const onClearPopup = () =>{
        if(confirm("入力内容を消去しますか？")){
            reservationForm.reset();
            reservationForm.clearErrors();

            const date = getDefaultReservationDate();
            reservationForm.date = date;
            autofillDate.value = date;
        }
        return;
    }

    /**
     * 予約情報の削除
     * Popupで削除するのでPopup側に渡すデータ（SetValueメソッドで提供されたデータ）から削除情報を取り出す
     */
    const onDestroyPopup = () =>{
        const deleteMessage = `${selectReservation.value.client.last_name}様の予約（${selectReservation.value.date} ${formatTime(selectReservation.value.start_time)}）を削除しますか？`;
        if(!confirm(deleteMessage)){
            return;
        }
        reservationUpdateForm.delete(`/reservations/${selectReservation.value.id}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () =>{
                showPopup.value = false;
            },
        });
        return;
    }

    const onApproveReservation = (res) =>{
        if(!confirm(`${res.client.last_name}様の予約（${res.date} ${formatTime(res.start_time)}）を承認しますか？`)){
            return;
        }
        router.patch(`/reservations/${res.id}/approve`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const onUpdateReservation = (res) =>{
        setValue(res);

        popupMode.value = 'update';
        showPopup.value = true;
    };

    const onViewReservation = (res) =>{
        setValue(res);

        popupMode.value = 'view';
        showPopup.value = true;
    };

    //矢印キーが入力されたときのイベント
    //日付入力欄、プルダウン選択中は動かさない
    const handleKeydown = (e) =>{
        let displayWeekOnKeyDown = null;
        if( e.target.tagName === 'INPUT'
            || e.target.tagName === 'SELECT'
            || e.target.tagName === 'TEXTAREA') return;

        if(e.key === 'ArrowLeft'){
            displayWeekOnKeyDown = props.prevWeek;
        }else if(e.key === 'ArrowRight'){
            displayWeekOnKeyDown = props.nextWeek;
        }else{
            return;
        }

        router.get('/', { week: displayWeekOnKeyDown }, { preserveState: true, preserveScroll: true });
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

        <!-- 週操作ボタン -->
        <!--
            preserve-state
                ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
                refが保存される。
            preserve-scroll:
                ページを移動しても、スクロール位置をそのまま保つ
        -->
        <div class="mb-2 flex items-center gap-2">
            <Link :href="`/?week=${prevWeek}`" preserve-state preserve-scroll class="flex items-center gap-1">
                <ChevronLeftIcon class="w-5 h-5"/>前週
            </Link>
            <Link href="/" preserve-state preserve-scroll class="rounded border px-3 py-1">今週</Link>
            <input type="date"
                @change="onDateChange($event)"
                @keydown="onKeyDownOnInputDate()"
                @keydown.enter="onEnterOnInputDate($event)"
                @blur="onBlurFromInputDate($event)"
                :value="weekStart"
                class="rounded border px-3 py-1"
                :min="page.props.selectableMinDate"/>
            <Link :href="`/?week=${nextWeek}`" preserve-state preserve-scroll class="flex items-center gap-1">
                <ChevronRightIcon class="w-5 h-5"/>次週
            </Link>
        </div>
        <div class="mb-2 flex items-center gap-2">
            <Link href="/reservations/monthly" class="flex items-center gap-1">
                <CalendarDaysIcon class="w-5 h-5"/>月表示
            </Link>
        </div>

        <button @click="onClickEntryButton" class="flex items-center gap-1 relative ">
            <PlusIcon class="w-5 h-5"/>予約追加
            <span v-if="isDirtyPopup"
                title="入力中の予約があります"
                class="absolute rounded-full bg-red-500 -top-0 -right-2 w-1.5 h-1.5"></span>
        </button>
    </div>

    <!-- 週一覧画面 -->
        <WeekGrid
            :allReservations="props.reservations"
            :reservations="filteredReservations"
            :shiftDetails="shiftDetails"
            :weekStart="weekStart"
            @update="onUpdateReservation"
            @approve="onApproveReservation"
            @view="onViewReservation">
        </WeekGrid>

    <!-- 予約追加ポップアップ画面 -->
    <ReservationFormPopup
        v-if="showPopup"
        v-model:popupPositionLeft="popupPositionLeft"
        v-model:popupPositionTop="popupPositionTop"
        :form="popupMode === 'create' ? reservationForm : reservationUpdateForm"
        :formClients="formClients"
        :formStaff="formStaff"
        :formSupportTypes="formSupportTypes"
        :popupMode=popupMode
        :selectClientName="selectReservation?.client.last_name"
        @close="onClosePopup"
        @submit="onSubmitPopup"
        @clear="onClearPopup"
        @destroy="onDestroyPopup">
    </ReservationFormPopup>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
