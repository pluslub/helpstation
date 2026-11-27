import { watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3'
import { setPopupBackHandler, clearPopupBackHandler  } from '@/Utils/popupBackHandler.js'

export const usePopupHistory = (showPopup, onClose) => {
    const page = usePage();

    let pushed = false;
    let latestState = null;

    // ① 開いたら履歴を1つ足す（watch）
    watch(showPopup, (isOpen) => {
        if(!isOpen) return;
        history.pushState({ ...history.state }, '')
        pushed = true;
    });

    // ② 戻るボタンを受け取る関数（popstate）
    const onPopstate = (event) => {
        if(!showPopup.value) return;  //ポップアップが閉じているなら何もしない
        onClose();
        pushed = false;

        //同じURLならInertiaを止めて履歴を最新の内容で上書きする。違うURLならInertiaに前のページを表示させる
        if(page.url === event.state?.page?.url){
            event.stopImmediatePropagation();
            if(latestState){
                history.replaceState(latestState, '');
                latestState = null;
            }
        }
    }

    // ③ ②を登録・解除する（onMounted / onUnmounted）
    onMounted(() =>{
        setPopupBackHandler(onPopstate)
    });

    onUnmounted (() =>{
        clearPopupBackHandler(onPopstate);
    });

    // ④ 閉じるボタン用の関数
    const closePopup = () => {
        if(pushed){
            latestState = history.state;
            history.back();
        }
        else onClose();
    }

    const beforeNavigate = () =>{
        if(pushed){
            pushed = false;
            return true;
        }else{
            return false;
        }
    }

    return { closePopup, beforeNavigate };
}
