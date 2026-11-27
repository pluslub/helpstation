<script setup>
    import { computed, onMounted, onUnmounted } from 'vue';
    import { PaperAirplaneIcon, XMarkIcon } from '@heroicons/vue/24/outline';

    const props = defineProps({
        form: Object,
        staffName: String,
        date: String,
    })

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
        <div class="bg-shift-medium p-2 flex justify-between">
            <h1>シフト変更</h1>
            <button @click="onClose()"
                    aria-label="閉じる">
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
            <fieldset class="min-w-0">
                <legend class="sr-only">休み</legend>
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
            </fieldset>
            <div v-if="form.errors.am_off" class="form-danger">{{ form.errors.am_off }}</div>
            <div v-if="form.errors.pm_off" class="form-danger">{{ form.errors.pm_off }}</div>
            <!--シフト時間入力欄-->
            <!--終日休みなら時刻を入力できないようにする。
                午前休・午後休なら時刻を空にできる-->
            <fieldset class="min-w-0">
                <legend class="sr-only">シフト時間</legend>
                <label class="form-row">
                    <span>シフト時刻</span>
                    <span class="flex items-center gap-1 w-full min-w-0">
                        <input type="time" v-model="form.start_time" :disabled="offType === 'all'" class="field min-w-0"/>
                        ～<input type="time" v-model="form.end_time" :disabled="offType === 'all'" class="field min-w-0"/>
                    </span>
                </label>
            </fieldset>
            <div v-if="form.errors.start_time" class="form-danger">{{ form.errors.start_time }}</div>
            <div v-if="form.errors.end_time" class="form-danger">{{ form.errors.end_time }}</div>
        </div>
        <!--ボタン群-->
        <div class="pb-2 flex justify-center gap-20">
            <button @click="onSubmit()"
                    :disabled="form.processing"
                    class="btn btn-outline border-line bg-shift-medium text-ink">
                <PaperAirplaneIcon class="w-5 h-5"/>変更
            </button>
            <button @click="onClose()"
                    class="btn btn-outline border-line bg-surface text-ink">閉じる
            </button>
        </div>
    </div>
</template>
