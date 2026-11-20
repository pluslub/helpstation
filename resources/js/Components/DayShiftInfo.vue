<script setup>
    import { computed } from 'vue'
    import { usePage } from '@inertiajs/vue3';
    import { hasStaff } from '@/Utils/hasStaff.js'
    import { sliceTime, overlapTime } from '@/Utils/timeRange.js'
    import { formatTime } from '@/Utils/formatTime.js'
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
        clickableShift: { type: Boolean, default: false},
        shiftClickableDate: String,
        highlightedDate: String,
        highlightedTime: Object,
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

        // 実際に働ける時間（AM休・PM休を反映する）
        let workStartTime = sliceTime(shift.start);
        let workEndTime = sliceTime(shift.end);

        if(shift.amOff){
            workStartTime = page.props.amOffWorkStartTime > workStartTime ? page.props.amOffWorkStartTime : workStartTime;
        }
        if(shift.pmOff){
            workEndTime = page.props.pmOffWorkEndTime < workEndTime ? page.props.pmOffWorkEndTime : workEndTime;
        }

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

    const onClickShift = (date, staffId) => {
        emit('click-shift', date, staffId);
    }
</script>

<template>
    <div v-if="hasShiftInfo(day)"
        class="bg-shift-soft p-1 text-sm"
        :class="attached ? 'rounded-b-lg' : 'border-t-4 border-shift rounded-lg'">
        <div class="gap-x-1 grid grid-cols-[auto_auto_auto_1fr] max-md:grid-cols-[auto_auto_auto_1fr_auto_auto_auto_1fr] max-md:grid-flow-col max-md:grid-rows-[repeat(var(--shift-rows),auto)]"
            :style="{ '--shift-rows': shiftRowCount }">
            <button v-for="shift in props.shifts ?? []"
                    :key="shift.id"
                    class="grid grid-cols-subgrid col-span-4 text-left rounded items-center"
                    type="button"
                    @click="onClickShift(day.date, shift.staffId)"
                    :disabled="!clickableShiftByDay(day)"
                    :class="{   'cursor-pointer hover:bg-reservation-soft' : clickableShiftByDay(day),
                                'bg-highlight-soft' : isAvailableShift(shift, day) }">
                <span :title="shift.name" class="truncate min-w-0 pr-2">{{ shift.name }}</span>
                <span class="text-xs flex items-center justify-end gap-1 tabular-nums">
                    <span v-if="checkUsePrivateCar(shift, day)?.start"
                        title="私用車(直行)"
                        class="rounded-full bg-ink w-2 h-2 shrink-0">
                    </span>
                    {{ formatTime(shift.start) }} 〜
                </span>
                <span class="text-xs tabular-nums">{{ formatTime(shift.end) }}
                </span>
                <span class="flex items-center gap-1">
                    <span v-if="shift.amOff" class="badge bg-rest text-rest-soft text-xs">AM</span>
                    <span v-else-if="shift.pmOff" class="badge bg-rest text-rest-soft text-xs">PM</span>
                    <span v-if="checkUsePrivateCar(shift, day)?.end"
                        title="私用車(直帰)"
                        class="rounded-full bg-ink w-2 h-2 shrink-0"></span>
                    <span v-if="shift.changed"
                        class="badge bg-rest text-rest-soft">
                        <ArrowPathIcon class="w-4 h-4"/>
                    </span>
                </span>
            </button>
        </div>
        <div v-if="props.dayOffs?.length > 0 || props.desiredOffs?.length > 0" class= "pt-2">
            <div v-if="props.dayOffs?.length > 0" class="flex items-start">
                <span class="shrink-0">休：</span>
                <div class="flex flex-wrap gap-x-3 gap-y-0.5 flex-1 min-w-0">
                    <span v-for="(dayOffStaff, index) in props.dayOffs ?? []" :key="dayOffStaff.id"
                            class="flex items-center gap-1">
                        <span class="whitespace-nowrap">{{ dayOffStaff.name }}</span>
                        <div v-if="dayOffStaff.changed"
                            class="badge bg-rest text-rest-soft">
                            <ArrowPathIcon class="w-4 h-4"/>
                        </div>
                    </span>
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
