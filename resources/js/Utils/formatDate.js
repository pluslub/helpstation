export const formatDate = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth() + 1;    //月は0～11で表されるので+1する
    const day = date.getDate();

    //'yyyy-MM-dd'の文字列を作成
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}
