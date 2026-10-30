import { ref, onMounted, onUnmounted } from 'vue';

export const useHeaderHeight = () => {
    //ヘッダーのdiv 高さを取得するため
    const headerRef = ref(null);

    //画面上部の帯の高さを測り、日付の行を止める位置（--header-height）に使う WeekGridのtop-(--header-height)
    const headerResizeObserver = new ResizeObserver((entries) => {
        document.documentElement.style.setProperty('--header-height', entries[0].borderBoxSize[0].blockSize + 'px');
    });

    onMounted(() =>{
        headerResizeObserver.observe(headerRef.value);
    });

    onUnmounted(() =>{
        headerResizeObserver.disconnect();
    });

    return headerRef;
}
