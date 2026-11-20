<script setup>
    import { computed, onMounted, onUnmounted } from 'vue';
    import { usePage } from '@inertiajs/vue3'
    import { PaperAirplaneIcon, XMarkIcon } from '@heroicons/vue/24/outline';

    const props = defineProps({
        form: Object,
        staffName: String,
        date: String,
    })

    const page = usePage();

    const emit = defineEmits(['close', 'submit'])
    const onClose = () =>{
        emit('close');
    }

    const onSubmit = () =>{
        emit('submit');
    }

    const offType =  computed({
        get() {
            if(props.form.am_off && props.form.pm_off) return 'all';
            if(props.form.am_off) return 'am';
            if(props.form.pm_off) return 'pm';
            return 'none';
        },
        set(input) {
            props.form.am_off = input === 'all' || input === 'am';
            props.form.pm_off = input === 'all' || input === 'pm';

            if(input === 'all') {
                props.form.start_time = '';
                props.form.end_time = '';
            }
            if(input === 'am') props.form.start_time = page.props.amOffWorkStartTime;
            if(input === 'pm') props.form.end_time = page.props.pmOffWorkEndTime;
        },
    })

    const onKeydownEscape = (e) =>{
        if(e.key !== 'Escape') return;

        //日本語入力中は閉じない
        if(e.isComposing) return;

        onClose();
    }

    onMounted(() => {
        window.addEventListener("keydown", onKeydownEscape);
    })

    onUnmounted(() => {
        window.removeEventListener("keydown", onKeydownEscape);
    })
</script>

<template>
    <div class="fixed z-10 bg-surface inset-0 max-h-screen overflow-y-auto
        md:rounded md:border md:shadow-lg md:top-0 md:bottom-auto md:left-auto md:right-0 md:w-120">
        <!--ヘッダー-->
        <div class="flex justify-between items-center p-2 border-b border-line">
            <h1>シフト変更</h1>
            <button @click="onClose()">
                <XMarkIcon class="w-5 h-5"/>
            </button>
        </div>
        <div class="flex flex-col gap-2 p-2">
            <!--シフト登録者情報-->
            <div class="form-row">
                <span>職員</span>
                <div>{{ props.staffName }}</div>
            </div>
            <div class="form-row">
                <span>日付</span>
                <div>{{ props.date }}</div>
            </div>
            <!--休暇選択ボタン-->
            <div class="form-row">
                <span>休み</span>
                <div class="flex flex-wrap gap-2">
                    <label class="mark">
                        <input type="radio" v-model="offType" value="none" name="off_type" />出勤
                    </label>
                    <label class="mark">
                        <input type="radio" v-model="offType" value="am" name="off_type" />午前休
                    </label>
                    <label class="mark">
                        <input type="radio" v-model="offType" value="pm" name="off_type" />午後休
                    </label>
                    <label class="mark">
                        <input type="radio" v-model="offType" value="all" name="off_type" />終日休み
                    </label>
                </div>
            </div>
            <div v-if="form.errors.am_off" class="form-danger">{{ form.errors.am_off }}</div>
            <div v-if="form.errors.am_off" class="form-danger">{{ form.errors.pm_off }}</div>
            <!--シフト時間入力欄-->
            <!--終日休みなら時刻を入力できないようにする。-->
            <div class="flex flex-col gap-2">
                <label class="form-row">
                    <span>始業時刻</span>
                    <input type="time" v-model="form.start_time" :disabled="offType === 'all'" class="field"/>
                </label>
                <div v-if="form.errors.start_time" class="form-danger">{{ form.errors.start_time }}</div>

                <label class="form-row">
                    <span>終業時刻</span>
                    <input type="time" v-model="form.end_time" :disabled="offType === 'all'" class="field"/>
                </label>
                <div v-if="form.errors.end_time" class="form-danger">{{ form.errors.end_time }}</div>
            </div>
        </div>
        <!--ボタン群-->
        <div class="flex gap-2 p-2">
            <button @click="onSubmit()"
                    :disabled="form.processing"
                    class="btn bg-reservation text-ink">
                <PaperAirplaneIcon class="w-5 h-5"/>変更
            </button>
            <button @click="onClose()"
                    class="btn bg-neutral text-ink">
                閉じる
            </button>
        </div>
    </div>
</template>
