<script setup>
    //データ処理 JavaScript
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import { Link, router, useForm, usePage } from '@inertiajs/vue3';

    import WeekGrid from "@/Components/WeekGrid.vue";
    import ReservationFilter from '@/Components/ReservationFilter.vue';
    import ReservationFormPopup from "@/Components/ReservationFormPopup.vue";    //@はresources/jsを指す
    import ShiftEditPopup from '@/Components/ShiftEditPopup.vue'
    import UserMenu from "@/Components/UserMenu.vue";

    import { useReservationFilter } from "@/Composables/useReservationFilter.js"
    import { useHeaderHeight } from "@/Composables/useHeaderHeight.js"
    import { useDateInputCommit } from "@/Composables/useDateInputCommit.js"
    import { usePopupHistory } from "@/Composables/usePopupHistory.js"
    import { formatTime } from "@/Utils/formatTime.js"
    import { formatDate } from "@/Utils/formatDate.js"
    import { sliceTime } from "@/Utils/timeRange.js"
    import { isPcWidth } from "@/Utils/isPcWidth.js"


    //Heroicons
    import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

    const page = usePage();

    //ReservationControllerのindexメソッドからデータを受け取る
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

    //シフト変更画面
    const shiftEditForm = useForm({
        start_time: '',
        end_time: '',
        am_off: false,
        pm_off: false,
    });

    const { filterId, filterType, filteredReservations, clientOptions, staffOptions } = useReservationFilter(props);

    //予約入力ポップアップの幅
    //ReservationFormPopupのmd:w-120(480px)に合わせるように
    const popupWidth = 480;

    //ボタンとポップアップの隙間（px）
    const popupDisplayMargin = 2;

    const headerRef = useHeaderHeight();

    //--------------------------------------------ref-------------------------------------------
    // ref：画面の操作で書き換わる値を入れる箱。変わったらこれを使っているcomputedが再計算される。

    //変更すると<ReservationFormPopup>のv-ifによりポップアップが表示される。
    const showPopup = ref(false);
    const popupMode = ref(null)
    const showShiftPopup = ref(false);

    const selectReservation = ref(null);
    const selectedShift = ref(null);

    const popupPositionLeft = ref(null);
    const popupPositionTop = ref(null);

    //自動で入れた日付を覚えておく
    const autofillDate = ref(null);

    //カレンダー等で選んだ日付を入力する
    const pickedDate = ref(null);

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

    const getDefaultReservationDate = () =>{
        const today = formatDate(new Date());
        return props.weekStart <= today && today < props.nextWeek ? today : props.weekStart;
    }

    const isAnyPopupOpen = computed (() => {
        return showPopup.value || showShiftPopup.value
    })

    const hideAllPopups = () => {
        showPopup.value = false;
        showShiftPopup.value = false;
        return;
    }

    const { closePopup, beforeNavigate } = usePopupHistory(isAnyPopupOpen, hideAllPopups);
    const moveWeek = (date, isValid) => {
        if(!date || !isValid) return;
        pickedDate.value = date;

        //表示されてる週の日付の場合何もしない。
        if(props.weekStart <= date && date < props.nextWeek) return;

        router.get('/', { week: date }, { preserveState: true, preserveScroll: true, replace: beforeNavigate() });

    }
    const { onChange, onKeyDown, onEnter, onBlur } = useDateInputCommit(moveWeek);

    const openDatePicker = (e) => {
        try{
            e.target.showPicker();
        }catch{
            //何もしない
        }
    }

    //予約入力ポップアップの位置を設定する
    const setPopupPosition = (left, top) =>{
        if(isPcWidth()){
            //ポップアップの位置を調整
            popupPositionLeft.value = left;
            popupPositionTop.value = top;
        }else{
            //スマホの場合は位置を指定しない
            popupPositionLeft.value = null;
            popupPositionTop.value = null;
        }
    }

    //予約入力ポップアップの位置を計算する
    const setSidePopupPosition = (rect, width) =>{
        let left = null;
        let top = null;

        //ポップアップの位置を調整
        //右側に出してはみ出す場合は左側へ
        if(rect.right + width + popupDisplayMargin <= document.documentElement.clientWidth ){
            left = rect.right + popupDisplayMargin;
        }else{
            left = rect.left - width - popupDisplayMargin;
        }
        top = rect.top;

        setPopupPosition(left, top);
    }

    const setValueToShiftEditForm = (shift) =>{
        shiftEditForm.start_time = shift.start ? sliceTime(shift.start) : "";
        shiftEditForm.end_time = shift.end ? sliceTime(shift.end) : "";
        shiftEditForm.am_off = shift.amOff;
        shiftEditForm.pm_off = shift.pmOff;
        shiftEditForm.defaults();
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

    const openedReservationId = computed(() => {
        if(showPopup.value && popupMode.value !== 'create'){
            return selectReservation.value?.id;
        }else{
            return null;
        }
    });

    const highlightedDate = computed(() => {
        if(showPopup.value && popupMode.value !== 'view' && activeForm.value.date){
            return activeForm.value?.date;
        }else{
            return null;
        }
    });

    const highlightedTime = computed(() => {
        if(showPopup.value && popupMode.value !== 'view' && activeForm.value.start_time){
            return { start_time:activeForm.value?.start_time, end_time:activeForm.value?.end_time };
        }else{
            return null;
        }
    });

    const clickableShift = computed(() => {
        return (showPopup.value && ( popupMode.value === 'create' || popupMode.value === 'update'))
                || (!showPopup.value && page.props.auth.isAdmin)
    });

    const clickableDayOff = computed(() => {
        return !showPopup.value && page.props.auth.isAdmin;
    });

    const shiftClickableDate = computed(() => {
        if(showPopup.value && popupMode.value === 'update'){
            return 	reservationUpdateForm.date;
        }else{
            return null;
        }
    });

    const clickableDateHeader = computed(() => {
        return showPopup.value && popupMode.value !== 'view'
    });

    const activeForm = computed(() => {
        return popupMode.value === 'create' ? reservationForm : reservationUpdateForm;
    })

    //-----------------------------------------------event----------------------------------------------
    /**
     * 予約入力ポップアップを表示するときの処理
     * 今日が表示中の週に入っていれば今日
     * 入っていなければ（前の週や次の週を表示中なら）その週の日曜日（weekStart）
     */
    const onClickEntryButton = (e) =>{
        //予約入力ポップアップを開いたときに、日付が空か自動入力の場合は、初期値を入力する。
        if(!reservationForm.date || reservationForm.date === autofillDate.value){
            const date = getDefaultReservationDate();
            reservationForm.date = date;    //予約入力ポップアップに日付の初期値を送る。
            autofillDate.value = date;      //自動入力の場合に他の日付に切り替えられるように記憶する。
        }

        //押したボタンの位置を取得してポップアップの位置を調整
        const rect = e.currentTarget.getBoundingClientRect();
        setPopupPosition(rect.left, rect.bottom + popupDisplayMargin);

        popupMode.value = 'create';
        showShiftPopup.value = false;
        showPopup.value = true;
    };

    /**
     * 予約入力ポップアップが閉じられた時の処理
     * 予約更新の際、form.isDirtyメソッドで変更があったか調べてあればメッセージを出す。
     * それ以外の場合は何もせずに閉じる
     */
    const onClosePopup = () =>{
        switch(popupMode.value) {
            case 'update':
                if(reservationUpdateForm.isDirty && !confirm("変更を破棄して閉じますか？")){
                    return;
                }
                closePopup();
                return;

            case 'create':
            case 'view':
            default:
                closePopup();
                return;
        }
    };

    /**
     * 予約入力ポップアップで申請ボタンを押下された時の処理
     * 新規追加はPost:/reservations, 更新はPut:/reservations/予約IDに遷移し、
     * それ以外はなにもしない
     *
        preserveState: true    画面の更新時に表示中の内容を保持する
        preserveScroll: true   画面の更新時にスクロールの位置を保持する。
     */
    const onSubmitPopup = () =>{
        switch(popupMode.value) {
            case 'create':
                reservationForm.post('/reservations', {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        closePopup();
                    },
                });
                return;

            case 'update':
                reservationUpdateForm.put(`/reservations/${selectReservation.value?.id}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () =>{
                        closePopup();
                    },
                });
                return;

            default:
                return;
        }

    }

    /**
     * 予約入力ポップアップの内容を破棄する処理
     * 新規追加の場合のみ注意メッセージを出してから破棄する。
     * それ以外は何もしない
     */
    const onClearPopup = () =>{
        switch(popupMode.value) {
            case 'create':
                if(confirm("入力内容を消去しますか？")){
                    reservationForm.reset();
                    reservationForm.clearErrors();

                    const date = getDefaultReservationDate();
                    reservationForm.date = date;
                    autofillDate.value = date;
                }
                return;

            default:
                return;
        }
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
                closePopup();
            },
        });
        return;
    }

    /**
     * 仮申請アイコンを押したときの処理
     * 管理者以外は承認できないようにする。
     * @param res, rect
     */
    const onApproveReservation = (res) =>{
        if(!page.props.auth.isAdmin) return;

        if(!confirm(`${res.client.last_name}様の予約（${res.date} ${formatTime(res.start_time)}）を承認しますか？`)){
            return;
        }
        router.patch(`/reservations/${res.id}/approve`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    /**
     * 予約更新ボタンを押したときの処理
     * 管理者はポップアップウインドウを表示する。
     * それ以外はなにもしない。
     *
     * setValue　ポップアップウインドウに渡すデータをformに入力する。
     * formはisDirtyメソッドで変更状況を調べたり、
     * form.postのように書くことで、そのformをロックして関連するボタンの操作を止めることができる
     * @param res, rect
     */
    const onUpdateReservation = (res, rect) =>{
        if(!page.props.auth.isAdmin) return;
        if(showPopup.value && openedReservationId.value === res.id && popupMode.value === 'update') return;

        setValue(res);
        setSidePopupPosition(rect, popupWidth);

        popupMode.value = 'update';
        showShiftPopup.value = false;
        showPopup.value = true;
    };

    /**
     * 予約閲覧ボタンを押したときの処理
     * ポップアップウインドウを表示する。
     * @param res, event
     */
    const onViewReservation = (res, rect) =>{
        if(showPopup.value && openedReservationId.value === res.id && popupMode.value === 'view') return;

        setValue(res);
        setSidePopupPosition(rect, popupWidth);

        popupMode.value = 'view';
        showShiftPopup.value = false;
        showPopup.value = true;
    };

    /**
     * 矢印キーが入力されたときのイベント
     * 日付入力欄、プルダウン選択中は動かさない
     */
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

    const onClickShift = (date, shift) =>{
        //シフト部分を押せないならなにもしない。
        if(!clickableShift.value) return;
        //予約のポップアップが閉じていて管理者ならシフト編集画面を開く
        if(!showPopup.value && page.props.auth.isAdmin){
            selectedShift.value = { id: shift.id, date: date, name: shift.name };

            setValueToShiftEditForm(shift);
            shiftEditForm.clearErrors();
            showShiftPopup.value = true;
            return;
        }
        //終日休みの人をクリックしても予約申請に入力されない。
        if(shift.amOff && shift.pmOff) return;

        //シフトがクリックできる日付かチェックする。
        if(shiftClickableDate.value != null && shiftClickableDate.value !== date) return;

        //予約フォームにシフト情報を渡す。
        if(activeForm.value.date === date){
            if(activeForm.value.staff_ids.includes(shift.staffId)){
                activeForm.value.staff_ids = activeForm.value.staff_ids.filter(id => id !== shift.staffId);
            }else{
                activeForm.value.staff_ids.push(shift.staffId);
            }
        }else{
            activeForm.value.date = date;
            activeForm.value.staff_ids = [ shift.staffId ];
        }
    };

    const onCloseShiftEditPopup = () =>{
        closePopup();
    };

    const onSubmitShiftEditPopup = () =>{
        shiftEditForm.put(`/shift-details/${selectedShift.value.id}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () =>{
                closePopup();
            },
        });
    };

    const onClickDateHeader = (date) =>{
        activeForm.value.date = date;
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
    <!--border-b        下側に線を引く
        border-line     app.cssの--color-lineを参照する
        shadow-sm       小さく影をつける
        sticky top-0    画面の上端に固定する
        z-5             重なったときに手前に表示する　ポップアップ(z-10)よりも下
        p-2             内側に8pxの余白を設ける
        gap-2           要素ごとの間隔を8px設ける
        flex            要素を flex の入れ物にする　gap・items-・justify-などが適用できるようになる
        flex-col        並べる向きを縦にする
    -->
    <div ref="headerRef"
        class="bg-surface md:sticky md:top-0 z-5 p-2 flex flex-wrap gap-2 md:grid md:grid-cols-[minmax(max-content,1fr)_auto_minmax(max-content,1fr)]">
        <!-- 週操作ボタン -->
        <!--
            preserve-state
                ページを移動したときにコンポーネントを作り直さずにpropsだけを入れ替えるので、
                refが保存される。
            preserve-scroll:
                ページを移動しても、スクロール位置をそのまま保つ
        -->
        <!-- フィルター -->
        <ReservationFilter
            :staffOptions="staffOptions"
            :vehicleOptions="props.vehicleOptions"
            :clientOptions="clientOptions"
            v-model:filterType="filterType"
            v-model:filterId="filterId"
            class="md:justify-self-start order-1 md:order-none">
        </ReservationFilter>
        <!--週の操作　PC版のみ表示-->
        <div class="hidden gap-2 items-center justify-self-center md:flex md:order-none md:w-auto">
            <Link :href="`/?week=${prevWeek}`"
                    preserve-state preserve-scroll
                    class="btn btn-outline border-line text-ink"
                     aria-label="前週">
                <ChevronLeftIcon class="w-5 h-5"/>
                <span class="hidden xl:inline">前週</span>
            </Link>
            <Link href="/" preserve-state preserve-scroll class="btn btn-outline border-line text-ink">
                <span>今週</span>
            </Link>
            <input type="date"
                @change="onChange($event)"
                @keydown="onKeyDown()"
                @keydown.enter="onEnter($event)"
                @blur="onBlur($event)"
                :value="weekStart"
                class="field hidden md:block"
                :min="page.props.selectableMinDate"
                :max="page.props.selectableMaxDate"/>
            <Link :href="`/?week=${nextWeek}`"
                    preserve-state preserve-scroll
                    class="btn btn-outline border-line text-ink"
                    aria-label="次週">
                <ChevronRightIcon class="w-5 h-5"/>
                <span class="hidden xl:inline">次週</span>
            </Link>
        </div>
        <div class="flex items-center gap-2 md:justify-self-end ml-auto order-2 md:order-none">
            <button @click="onClickEntryButton($event)"
                    class="btn bg-reservation-medium text-ink relative hidden md:inline-flex">
                <PlusIcon class="w-5 h-5"/>予約追加
                <span v-if="isDirtyPopup"
                    title="入力中の予約があります"
                    class="absolute rounded-full bg-danger ring-2 ring-surface top-0 -right-0.5 w-2.5 h-2.5"></span>
            </button>
            <button @click="onClickEntryButton($event)"
                    class="btn bg-reservation-medium text-ink md:hidden fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 rounded-full w-14 h-14 shadow-lg flex items-center justify-center z-6 "
                    aria-label="予約追加">
                <PlusIcon class="w-5 h-5"/>
                <span v-if="isDirtyPopup"
                    title="入力中の予約があります"
                    class="absolute rounded-full bg-danger ring-2 ring-surface top-0 -right-0.5 w-2.5 h-2.5"></span>
            </button>
            <Link href="/reservations/monthly" class="btn btn-outline border-line text-ink hidden md:inline-flex">
                <CalendarDaysIcon class="w-5 h-5"/>
                <span class="hidden xl:inline">月表示</span>
            </Link>
            <!--スマホ用週操作-->
            <div class="flex items-center gap-2 md:hidden">
                <Link href="/" preserve-state preserve-scroll class="btn btn-outline border-line text-ink">
                    <span>今週</span>
                </Link>
                <div class="relative overflow-hidden md:hidden btn btn-outline border-line text-ink py-2">
                    <CalendarDaysIcon class="w-5 h-5"/>
                    <input type="date"
                            @change="onChange($event);$event.target.blur()"
                            @keydown="onKeyDown()"
                            @keydown.enter="onEnter($event)"
                            @blur="onBlur($event)"
                            @click="openDatePicker($event)"
                            class="absolute inset-0 w-full h-full min-w-0 appearance-none opacity-0"
                            aria-label="日付を指定"
                            :value="weekStart"
                            :min="page.props.selectableMinDate"
                            :max="page.props.selectableMaxDate"/>
                </div>
            </div>
            <UserMenu class="pl-4" :hasDraft="isDirtyPopup"/>
        </div>
    </div>

    <!-- 週一覧画面 -->
        <WeekGrid
            :allReservations="props.reservations"
            :reservations="filteredReservations"
            :shiftDetails="shiftDetails"
            :weekStart="weekStart"
            :clickableShift="clickableShift"
            :clickableDayOff="clickableDayOff"
            :shiftClickableDate="shiftClickableDate"
            :clickableDateHeader="clickableDateHeader"
            :openedReservationId="openedReservationId"
            :highlighted-date="highlightedDate"
            :highlighted-time="highlightedTime"
            :prevWeek="prevWeek"
            :nextWeek="nextWeek"
            :pickedDate="pickedDate"
            @update="onUpdateReservation"
            @approve="onApproveReservation"
            @view="onViewReservation"
            @click-shift="onClickShift"
            @click-date-header="onClickDateHeader">
        </WeekGrid>

    <!-- 予約追加ポップアップ画面 -->
    <ReservationFormPopup
        v-if="showPopup"
        v-model:popupPositionLeft="popupPositionLeft"
        v-model:popupPositionTop="popupPositionTop"
        :form="activeForm"
        :formClients="formClients"
        :formStaff="formStaff"
        :formSupportTypes="formSupportTypes"
        :popupMode=popupMode
        :selectClientName="selectReservation?.client.last_name"
        :selectReservationId="selectReservation?.id"
        @close="onClosePopup"
        @submit="onSubmitPopup"
        @clear="onClearPopup"
        @destroy="onDestroyPopup"
        @move-week="moveWeek">
    </ReservationFormPopup>

    <ShiftEditPopup
        v-if="showShiftPopup"
        :form="shiftEditForm"
        :staffName="selectedShift.name"
        :date="selectedShift.date"
        @close="onCloseShiftEditPopup"
        @submit="onSubmitShiftEditPopup">
    </ShiftEditPopup>
</template>

<style>
    /* CSS Tailwindなので基本的に使わない */
</style>
