<script setup>
    import { usePage } from '@inertiajs/vue3';

    import { isPrivateCar } from "@/Utils/isPrivateCar.js"
    import { isNotUseCar } from "@/Utils/isNotUseCar.js"
    import { isProvisional } from "@/Utils/isProvisional.js"
    import { overlapTime } from "@/Utils/timeRange.js"
    import { formatTime } from "@/Utils/formatTime.js"
    import { getVehicleTypeShortLabel } from "@/Utils/vehicleTypeLabel.js"

    //Heroicons
    import { PencilIcon, EyeIcon, ClockIcon, ExclamationTriangleIcon  } from '@heroicons/vue/24/outline';
    import { ArrowPathIcon } from '@heroicons/vue/16/solid';


    const props = defineProps({
        reservations: Array,
        openedReservationId: Number,
        highlightedDate: String,
        highlightedTime: Object,
        readonly: { type: Boolean, default: false},
    });

    const emit = defineEmits(['update', 'approve', 'view']);

    const page = usePage();

    const isOverlapping = (reservation) => {
        if(!props.highlightedTime) return false;
        if(reservation.id === props.openedReservationId) return false;
        if(reservation.date.slice(0, 10) !== props.highlightedDate) return false;

        return overlapTime(reservation.start_time, reservation.end_time,
                            props.highlightedTime.start_time, props.highlightedTime.end_time);
    }

    //予約ごとの表示背景を変える
    const cardClass = (res) => {
        const borderClass = isProvisional(res) ? 'border-2 border-dashed border-provisional-line' : 'border-l-4 border-reservation';

        let backgroundClass = null;
        if(res.id === props.openedReservationId){   //開いている予約
            backgroundClass = 'bg-reservation-soft';
        }else if(isOverlapping(res)){   //入力中の時間と重なる予約
            backgroundClass = 'bg-highlight-danger';
        }else if(isProvisional(res)){   //仮登録
            backgroundClass = 'bg-provisional-soft';
        }else {
            backgroundClass = 'bg-surface';
        }

        return [borderClass, backgroundClass];
    }

    /**
     * ポップアップを出す位置の基準を作る。左右はその日の列の端（カードにかぶらないように）、上は押したボタンの高さ
     * @param e
     */
    const getPopupTargetRect = (e) => {
        const dayColumnRect = e.currentTarget.closest('[data-day-column]').getBoundingClientRect();
        const buttonRect = e.currentTarget.getBoundingClientRect();
        const rect = { left: dayColumnRect.left, right: dayColumnRect.right, top: buttonRect.top, bottom: buttonRect.bottom };
        return rect;
    }

    const onClickUpdateButton = (res, e) => {
        const rect = getPopupTargetRect(e);
        emit('update', res, rect);
    }

    const onClickApproveButton = (res) => {
        emit('approve', res);
    }

    const onClickViewButton = (res, e) => {
        const rect = getPopupTargetRect(e);
        emit('view', res, rect);
    }
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-for="res in props.reservations ?? []" :key="res.id">
            <div :class="cardClass(res)"
                class="p-1 grid grid-cols-[4.5rem_1fr] md:max-2xl:grid-cols-[minmax(0,1fr)_auto] gap-x-1 rounded-lg shadow-sm  max-md:grid-cols-[7rem_1fr_5rem]">
                <!--利用者名-->
                <div class="col-start-1 row-start-1 self-center">
                    <span class="marker px-1">{{ res.client.last_name }}</span>
                </div>
                <!--支援内容-->
                <div class="min-w-0 col-start-2 row-start-1 md:max-2xl:col-start-1 max-2xl:contents">
                    <div class="flex items-center gap-1 max-2xl:contents">
                        <span class="flex-1 min-w-0 2xl:flex 2xl:items-center 2xl:gap-1 break-words md:max-2xl:col-span-2 md:max-2xl:row-start-3 md:max-2xl:line-clamp-3">
                            <span :title="res.support_type?.name ?? '削除済'"
                                    class="min-w-0 2xl:truncate">
                                    {{ res.support_type?.name ?? '削除済'}}
                            </span>
                            <span v-if="isPrivateCar(res)"
                                    class="inline-block align-middle rounded-full bg-ink w-2 h-2 shrink-0 max-2xl:mx-1"
                                    title="私用車">
                            </span>
                            <!--スマホ専用の備考-->
                            <span v-if="res.remarks" class="ml-1 text-xs text-ink-muted 2xl:hidden" :title="res.remarks">
                                {{ res.remarks }}
                            </span>
                        </span>

                        <!-- 予約操作ボタン -->
                        <div class="flex shrink-0 gap-1 max-md:gap-3 max-md:col-start-3 max-md:row-start-1 max-md:row-span-2 max-md:justify-self-end md:max-2xl:col-start-2 md:max-2xl:row-start-1 md:max-2xl:justify-self-end">
                            <button title="仮登録"
                                    v-if="isProvisional(res)"
                                    :disabled="!page.props.auth.isAdmin || props.readonly"
                                    @click="onClickApproveButton(res)"
                                    class="btn btn-outline border-line bg-reservation-soft text-provisional p-1.5 max-md:p-2">
                                    <ClockIcon class="h-4 w-4 stroke-2 max-md:w-5 max-md:h-5"/>
                            </button>
                            <button title="編集"
                                v-if="page.props.auth.isAdmin && !props.readonly"
                                @click="onClickUpdateButton(res, $event)"
                                class="btn btn-outline border-line p-1.5 max-md:p-2">
                                <PencilIcon class="h-4 w-4 stroke-2 max-md:w-5 max-md:h-5"/>
                            </button>
                            <button title="閲覧"
                                v-if="!page.props.auth.isAdmin && !props.readonly"
                                @click="onClickViewButton(res, $event)"
                                class="btn btn-outline border-line p-1.5 max-md:p-2">
                                <EyeIcon class="h-4 w-4 stroke-2 max-md:w-5 max-md:h-5"/>
                            </button>
                        </div>
                    </div>
                </div>
                <!--担当者-->
                <div class="grid grid-cols-2 gap-x-1 min-w-0 col-start-1 row-start-2 row-span-2 self-end md:max-2xl:row-start-4 max-2xl:row-span-1 md:max-2xl:flex md:max-2xl:flex-wrap md:max-2xl:col-span-2">
                    <span v-for="assignedStaff in res.staff_assignments"
                        :key="assignedStaff.staff_id ?? 'none'"
                        class="flex items-center gap-1 min-w-0"
                        :class="{ 'col-span-2': assignedStaff.staff_id === null }"
                        :title="assignedStaff.staff?.name ?? '未定'">
                        <span class="truncate text-sm">{{ assignedStaff.staff?.name ?? '未定' }}</span>
                        <span v-if="assignedStaff.staff_id === null" class="shrink-0">
                            <ExclamationTriangleIcon class="w-4 h-4 text-unassigned"/>
                        </span>
                    </span>
                </div>
                <div v-if="res.remarks" class="col-start-2 row-start-2 max-2xl:hidden">
                    <span :title="res.remarks"
                        class="text-ink-muted break-words line-clamp-2 text-xs">
                        {{ res.remarks }}
                    </span>
                </div>
                <!--時刻と車両-->
                <div class="flex items-end gap-1 max-md:gap-4 col-start-2 row-start-3 max-md:row-start-2 md:max-2xl:contents">
                    <span class="shrink-0 text-sm md:max-2xl:row-start-2 md:max-2xl:col-start-1 md:max-2xl:col-span-2">{{ formatTime(res.start_time) }}〜{{ formatTime(res.end_time) }}</span>
                    <!--車両ラベル-->
                    <span class="flex items-center gap-0.5 md:max-2xl:col-start-2 md:max-2xl:row-start-2 md:max-2xl:justify-self-end">
                        <div v-if="!isPrivateCar(res) && !isNotUseCar(res)"
                            class="badge font-bold"
                            :title="res.vehicle?.name"
                            :class="res.vehicle_type_choice === 'care_vehicle' ? 'bg-care-vehicle-soft text-care-vehicle' : 'bg-company-car-soft text-company-car'">
                            {{ getVehicleTypeShortLabel(res.vehicle_type_choice) }}
                        </div>
                        <div v-if="res.vehicle_reassigned_flag"
                            class="badge bg-reassigned text-white"
                            title="配車変更">
                            <ArrowPathIcon class="w-4 h-4"/>
                        </div>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
