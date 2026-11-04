<script setup>
    import { computed } from 'vue';
    import { useForm } from '@inertiajs/vue3';

    const loginForm = useForm({
            login_id : '',
            password : '',
        });

    const hasNonHalfWidthChar = computed(() => {
        //半角の英数字・記号以外の文字を探す
        return /[^\x21-\x7E]/.test(loginForm.login_id);
    })

    const onSubmit = () =>{
        loginForm.post('/login');
    }
    const onInput = () =>{
        loginForm.clearErrors('login_id');
    }
</script>

<template>
    <div class="bg-base min-h-screen flex items-center justify-center">
        <div class="bg-surface rounded-lg shadow-sm p-6 flex flex-col gap-6">
            <h1>ログイン</h1>
            <form @submit.prevent="onSubmit" class="flex flex-col gap-3">
                <label class="form-row">
                    <span>ID</span>
                    <input type="text"
                            autocomplete="username"
                            v-model="loginForm.login_id"
                            class="field"
                            autocapitalize="off"
                            autocorrect="off"
                            spellcheck="false"
                            inputmode="latin"
                            @input="onInput">
                </label>
                <div v-if="loginForm.errors.login_id" class="form-danger">{{ loginForm.errors.login_id }}</div>
                <div v-else-if="hasNonHalfWidthChar" class="form-danger">半角英数字で入力してください。</div>
                <label class="form-row">
                    <span>パスワード</span>
                    <input type="password" autocomplete="current-password" v-model="loginForm.password" class="field">
                </label>
                <div v-if="loginForm.errors.password" class="form-danger">{{ loginForm.errors.password }}</div>
                <button type="submit" class="btn bg-reservation-medium text-ink self-center p-2 pl-8 pr-8" :disabled="loginForm.processing">
                    ログイン
                </button>
            </form>
            <span class="text-ink-muted">※半角英数字で入力してください。</span>
        </div>
    </div>
</template>
