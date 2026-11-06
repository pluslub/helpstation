export const useDateInputCommit = (onCommit) => {
    //日付直接入力欄でキーダウンされたかどうか
    //入力途中の遷移を防ぐため、keydown中なら無視し、カレンダーの選択・エンターキー押下・フォーカスの移動で遷移する
    let keydownOnInputDate = false;

    /**
     * 日付入力欄でカレンダーで選択されたときのイベント
     * keydownOnInputDateは日付入力欄で入力を始めるとtrueになるので、
     * カレンダー以外ではこのメソッドは動かない
     * @param e
     */
    const onChange = (e) =>{
        if(keydownOnInputDate) return;
        onCommit(e.target.value, e.target.validity.valid);
    };

    //日付入力欄でエンターキーが押されたときのイベント
    const onEnter = (e) =>{
        keydownOnInputDate = false;
        onCommit(e.target.value, e.target.validity.valid);
    };

    //日付入力欄からフォーカスが離れたときのイベント
    const onBlur = (e) =>{
        keydownOnInputDate = false;
        onCommit(e.target.value, e.target.validity.valid);
    };

    /**
     * 日付入力欄が直接入力されたときのイベント
     * 入力中1文字ずつOnChangeが発生して日付が切り替わってしまうので、
     * 入力が始まるとkeydownOnInputDateをtrueにして、その間onChangeを実行しないようにする。
     * 入力欄でエンターキーを押すか、フォーカスが離れたときにfalseに変更する。
     */
    const onKeyDown = () =>{
        keydownOnInputDate = true;
    };

    return { onChange, onEnter, onBlur, onKeyDown }
}
