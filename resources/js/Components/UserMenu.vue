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
        <div class="flex items-center gap-1 hidden md:flex">
            <UserCircleIcon class="w-5 h-5"/>{{ page.props.auth.name }}
        </div>
        <Link href="/logout" method="post"
            as="button"
            @before="onBefore"
            class="btn-base bg-surface text-ink py-2 md:btn-outline md:border-line md:shadow-sm md:py-1.5"
             aria-label="ログアウト">
            <ArrowRightStartOnRectangleIcon class="w-5 h-5"/>
            <span class="hidden md:inline">ログアウト</span>
        </Link>
    </div>
</template>
