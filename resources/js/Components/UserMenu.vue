<script setup>
    import { Link, usePage } from '@inertiajs/vue3';
    import { ArrowRightStartOnRectangleIcon, UserCircleIcon } from '@heroicons/vue/24/outline';

    const page = usePage();

    const props = defineProps({
        hasDraft: Boolean
    });

    const onBefore = () =>{
        if(!props.hasDraft) return true;
        if(!confirm("入力中の予約があります。ログアウトしますか？")){
            return false;
        }
        return true;
    }
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="flex items-center gap-1">
            <UserCircleIcon class="w-5 h-5"/>
            {{ page.props.auth.name }}
        </div>
        <Link href="/logout" method="post" as="button" @before="onBefore" class="btn btn-outline border-line text-ink">
            <ArrowRightStartOnRectangleIcon class="w-5 h-5"/>ログアウト
        </Link>
    </div>
</template>
