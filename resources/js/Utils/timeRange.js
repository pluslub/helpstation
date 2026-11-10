export const sliceTime = (time) => time.slice(0, 5);

export const overlapTime = (startTime1, endTime1, startTime2, endTime2) =>{
    const slicedStartTime1 = sliceTime(startTime1);
    const slicedEndTime1 = sliceTime(endTime1 || startTime1);
    const slicedStartTime2 = sliceTime(startTime2)
    const slicedEndTime2 = sliceTime(endTime2 || startTime2);
    return slicedStartTime1 === slicedStartTime2 || (slicedStartTime2 < slicedEndTime1 && slicedStartTime1 < slicedEndTime2);
}
