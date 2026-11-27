let handler = null

export const setPopupBackHandler = (fn) =>{
    handler = fn;
}

export const clearPopupBackHandler = (fn) =>{
    if(handler === fn) handler = null;
}

export const handlePopupBack = (event) =>{
    if(handler !== null) handler(event)
}
