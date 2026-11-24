import { sliceTime } from '@/Utils/timeRange.js'

// 実際に働ける時間（AM休・PM休を反映する）
export const getWorkTime = (shift, amOffWorkStartTime, pmOffWorkEndTime) =>{
    let workStartTime = shift.start ? sliceTime(shift.start) : "";
    let workEndTime = shift.end ? sliceTime(shift.end) : "";

    if(shift.amOff){
        if(workStartTime === ""){
            workStartTime = amOffWorkStartTime;
        }else{
            workStartTime = amOffWorkStartTime > workStartTime ? amOffWorkStartTime : workStartTime;
        }
    }
    if(shift.pmOff){
        if(workEndTime === ""){
            workEndTime = pmOffWorkEndTime;
        }else{
            workEndTime = pmOffWorkEndTime < workEndTime ? pmOffWorkEndTime : workEndTime;
        }
    }
    return {start: workStartTime, end: workEndTime}
}
