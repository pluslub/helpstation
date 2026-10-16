//管理者による変更をチェックし、１件分のシフトデータを出力する。
export const effective = (s) => {
    const changed = s.admin_modified_flag;
    return {
        id : s.id,
        name: s.shift.staff.name,
        start: changed ? s.modified_start_time : s.applied_start_time,
        end: changed ? s.modified_end_time : s.applied_end_time,
        amOff: changed ? s.modified_am_off : s.applied_am_off,
        pmOff: changed ? s.modified_pm_off : s.applied_pm_off,
        changed,
    }
};
