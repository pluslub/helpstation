<script setup>
    import { ref, onMounted } from 'vue';
    import { FunnelIcon} from '@heroicons/vue/24/outline';

    const selectKey = ref(0);

    const filterType = defineModel('filterType');
    const filterId = defineModel('filterId');

    const props = defineProps({
        staffOptions: Array,
        vehicleOptions: Array,
        clientOptions: Array,
    });

    onMounted(() =>{
        document.fonts.ready.then(() => {
            selectKey.value++;
        });
    });
</script>

<template>
    <div class="flex items-center gap-2">
        <!--
            v-model
                プルダウンと ref を双方向につなぐ指定。
                プルダウンで選ぶと filterType が変わり、
                filterType を書き換えると、プルダウンの表示も変わる。

            @change="filterId = null"：
                条件の種類を切り替えたときに、前の選択IDをリセットします
        -->
        <div class="relative">
            <FunnelIcon class="absolute top-1/2 -translate-y-1/2 pointer-events-none left-2 w-5 h-5" aria-hidden="true"/>
            <select v-model="filterType" @change="filterId = null" :key="selectKey" class="field pl-8" aria-label="絞り込み">
                <option value="all">全体</option>
                <option value="self">個人</option>
                <option value="staff">職員</option>
                <option value="vehicle">車両</option>
                <option value="client">利用者</option>
            </select>
        </div>

        <select v-if="filterType === 'staff'" v-model="filterId" class="field">
            <option :value="null">職員を選択</option>
            <option v-for="s in staffOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>

        <select v-if="filterType === 'vehicle'" v-model="filterId" class="field">
            <option :value="null">車両を選択</option>
            <option v-for="v in vehicleOptions" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>

        <select v-if="filterType === 'client'" v-model="filterId" class="field">
            <option :value="null">利用者を選択</option>
            <option v-for="c in clientOptions" :key="c.id" :value="c.id">{{ c.label }}</option>
        </select>
    </div>
</template>
