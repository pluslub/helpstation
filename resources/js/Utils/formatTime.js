/**
 * 時間 hh:mm:ssからh:mmに変換する
 *
 * `（@の上の文字）バッククォートについて
 * 中に${（変数や計算）}を書くと、結果が埋め込まれる。
 */
export const formatTime = (time) => {
    if(!time) return '';
    const [h, m] = time.split(':');     // '09:00:00' → ['09', '00', '00']
    return `${Number(h)}:${m}`;         // Number('09') は 9 になる
};
