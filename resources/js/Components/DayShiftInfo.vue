<script setup>
    import { computed } from 'vue'
    import { usePage } from '@inertiajs/vue3';
    import { hasStaff } from '@/Utils/hasStaff.js'
    import { overlapTime } from '@/Utils/timeRange.js'
    import { getWorkTime } from '@/Utils/workTime.js'
    import { formatTime } from '@/Utils/formatTime.js'
    import { isProvisionalShift } from "@/Utils/isProvisional.js"
    import { ArrowPathIcon } from '@heroicons/vue/16/solid';

    const props = defineProps({
        //絞り込み前の予約配列
        //reservationsの構造
        // reservations:Array：'client','supportType','vehicle','staffAssignments.staff'
        shifts: Array,
        desiredOffs: Array,
        dayOffs: Array,
        privateCars: Object,
        allReservations: Array,
        day: Object,
        openedReservationId: Number,
        shiftClickableDate: String,
        highlightedDate: String,
        highlightedTime: Object,
        clickableShift: { type: Boolean, default: false},
        clickableDayOff: { type: Boolean, default: false},
        attached: { type: Boolean, default: false }
    });

    const page = usePage();

    const emit = defineEmits(['click-shift']);

    const shiftRowCount = computed(() => Math.ceil((props.shifts?.length ?? 0) / 2))

    const hasShiftInfo = () => {
        return props.shifts?.length > 0 || props.dayOffs?.length > 0 || props.desiredOffs?.length > 0;
    }

    const clickableShiftByDay = (day) => {
        return props.clickableShift && (props.shiftClickableDate  == null || props.shiftClickableDate  === day.date);
    }

    const checkUsePrivateCar = (shift, day) => {
        return props.privateCars[`${shift.staffId}-${day.date}`];
    };

    //入力中の日付・時間に、このシフトの職員が対応できるか
    const isAvailableShift = (shift, day) =>{
        // 目立たせる時間がない・違う日
        if(!props.highlightedTime) return false;
        if(day.date !== props.highlightedDate) return false;

        const workTime = getWorkTime(shift, page.props.amOffWorkStartTime, page.props.pmOffWorkEndTime)
        const workStartTime = workTime.start;
        const workEndTime = workTime.end;

        // 入力の時間（終了が空なら開始と同じ）
        const inputStartTime = props.highlightedTime.start_time;
        const inputEndTime = props.highlightedTime.end_time || inputStartTime;

        // 働ける時間に収まらない
        if(inputStartTime < workStartTime || workEndTime < inputEndTime) return false;

        //その日、同じ時間に担当している予約がある（編集中の予約は除く）
        const isBusy = props.allReservations.some(r =>
            r.id !== props.openedReservationId
            && r.date.slice(0, 10) === day.date
            && hasStaff(r, shift.staffId)
            && overlapTime(r.start_time, r.end_time, inputStartTime, inputEndTime)
        );
        return !isBusy;
    }

    const showWorkTime = (shift) => {
        const workTime = getWorkTime(shift, page.props.amOffWorkStartTime, page.props.pmOffWorkEndTime);
        return {start: formatTime(workTime.start), end: formatTime(workTime.end)};
    }

    const shiftWorkTime = computed(() => {
        return (props.shifts ?? []).map((shift) => {
            return { ...shift,
                    displayTime: showWorkTime(shift),
                    appliedTime: showWorkTime(shift.applied),
            };
        })
    });

    const displayAppliedTime = (shift) => {
        const appliedWorkTime = showWorkTime(shift.applied)

        if(shift.applied.amOff && shift.applied.pmOff) return  "申請：休み";

        let displayString =  "申請：" + appliedWorkTime.start + " - " + appliedWorkTime.end;

        if(shift.applied.amOff) return displayString + "(AM休)";
        if(shift.applied.pmOff) return displayString + "(PM休)";
        return displayString;
    };

    const onClickShift = (date, shift) => {
        emit('click-shift', date, shift);
    }
</script>

<template>
    <div v-if="hasShiftInfo(day)"
        class="bg-shift-soft p-1 text-sm"
        :class="attached ? 'rounded-b-lg' : 'border-t-4 border-shift rounded-lg'">
        <div class="md:max-xl:gap-y-1 grid grid-cols-[minmax(2rem,1fr)_auto_auto_1fr] md:max-xl:grid-cols-[minmax(0,1fr)_auto] max-md:grid-cols-[minmax(2rem,1fr)_auto_auto_1fr_minmax(2rem,1fr)_auto_auto_1fr] max-md:grid-flow-col max-md:grid-rows-[repeat(var(--shift-rows),auto)]"
            :style="{ '--shift-rows': shiftRowCount }">
            <button v-for="shift in shiftWorkTime"
                    :key="shift.id"
                    class="grid grid-cols-subgrid col-span-4 md:max-xl:col-span-2 text-left rounded items-center"
                    type="button"
                    @click="onClickShift(day.date, shift)"
                    :disabled="!clickableShiftByDay(day)"
                    :class="{   'cursor-pointer hover:bg-reservation-soft' : clickableShiftByDay(day),
                                'bg-highlight-soft' : isAvailableShift(shift, day),
                                'bg-provisional' : isProvisionalShift(shift)}">
                <span :title="shift.name" class="truncate min-w-0 pr-0.5">{{ shift.name }}</span>
                <span class="contents md:max-xl:flex md:max-xl:col-span-2 md:max-xl:row-start-2 justify-center">
                    <span class="text-xs flex items-center justify-end gap-1 tabular-nums whitespace-nowrap">
                        <span :class="{ 'invisible' : !checkUsePrivateCar(shift, day)?.start }"
                            title="私用車(直行)"
                            class="rounded-full bg-ink w-2 h-2 shrink-0">
                        </span>
                        <span class="text-xs tabular-nums">{{ shift.displayTime.start }}-</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="text-xs tabular-nums">{{ shift.displayTime.end }}</span>
                        <!--小サイズモニタ用の直帰マーク-->
                        <span :class="{ 'invisible' : !checkUsePrivateCar(shift, day)?.end }"
                            title="私用車(直帰)"
                            class="rounded-full bg-ink w-2 h-2 shrink-0 hidden md:max-xl:block">
                        </span>
                    </span>
                </span>
                <!--バッジ-->
                <span class="flex flex-wrap items-center pl-0.5 gap-0.5 md:max-xl:col-start-2 md:max-xl:row-start-1 md:max-xl:justify-end">
                    <!--スマホ、大サイズモニタ用の直帰マーク-->
                    <span v-if="checkUsePrivateCar(shift, day)?.end"
                        title="私用車(直帰)"
                        class="rounded-full bg-ink w-2 h-2 shrink-0 md:max-xl:hidden">
                    </span>
                    <span v-if="shift.amOff" class="badge bg-rest text-rest-soft text-xs">AM</span>
                    <span v-else-if="shift.pmOff" class="badge bg-rest text-rest-soft text-xs">PM</span>
                    <span v-if="shift.changed"
                        class="badge bg-rest text-rest-soft"
                        :title="displayAppliedTime(shift)">
                        <ArrowPathIcon class="w-3 h-3"/>
                    </span>
                </span>
                <template v-if="shift.changed">
                    <span class="text-xs text-ink-muted md:hidden text-right">申請：</span>
                    <template v-if="!shift.applied.amOff || !shift.applied.pmOff">
                        <span class="text-xs text-ink-muted md:hidden text-right">
                            {{shift.appliedTime.start}} -
                        </span>
                        <span class="col-span-2 text-xs text-ink-muted md:hidden">
                            {{shift.appliedTime.end}}
                            <span   v-if="shift.applied.amOff"
                                    class="text-xs text-ink-muted md:hidden">
                                (AM休)
                            </span>
                            <span   v-else-if="shift.applied.pmOff"
                                    class="text-xs text-ink-muted md:hidden">
                                (PM休)
                            </span>
                        </span>
                    </template>
                    <span v-else class="col-span-3 text-xs text-ink-muted md:hidden">申請：休み</span>
                </template>
            </button>
        </div>
        <div v-if="props.dayOffs?.length > 0 || props.desiredOffs?.length > 0" class= "pt-2">
            <div v-if="props.dayOffs?.length > 0" class="flex items-start">
                <span class="shrink-0">休：</span>
                <div class="flex flex-wrap gap-x-3 gap-y-0.5 flex-1 min-w-0">
                    <button type="button"
                            v-for="(dayOffStaff, index) in props.dayOffs ?? []" :key="dayOffStaff.id"
                            class="flex items-center gap-1 rounded"
                            :disabled="!clickableDayOff"
                            :class="{ 'cursor-pointer hover:bg-reservation-soft' : clickableDayOff }"
                            @click="onClickShift(day.date, dayOffStaff)">
                        <span class="whitespace-nowrap">{{ dayOffStaff.name }}</span>
                        <span v-if="dayOffStaff.changed"
                            class="badge bg-rest text-rest-soft"
                            :title="displayAppliedTime(dayOffStaff)">
                            <ArrowPathIcon class="w-3 h-3"/>
                        </span>
                    </button>
                </div>
            </div>
            <div v-if="props.desiredOffs?.length > 0" class="flex items-start">
                <span class="shrink-0">希望休：</span>
                <div class="flex flex-wrap gap-x-3 gap-y-0.5 flex-1 min-w-0">
                    <span v-for="(desiredOffStaff, index) in props.desiredOffs ?? []" :key="desiredOffStaff.id"
                            class="flex items-center gap-1">
                        <span class="whitespace-nowrap">{{ desiredOffStaff.name }}</span>
                        <span v-if="desiredOffStaff.amOff && !desiredOffStaff.pmOff"
                                class="badge bg-rest text-rest-soft text-xs">AM</span>
                        <span v-if="!desiredOffStaff.amOff && desiredOffStaff.pmOff"
                                class="badge bg-rest text-rest-soft text-xs">PM</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
