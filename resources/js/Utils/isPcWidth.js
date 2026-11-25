const getPcMinWidth = () => {
    const width = getComputedStyle(document.documentElement).getPropertyValue('--breakpoint-md');
    return width.trim();
}

export const isPcWidth = () => {
    return window.matchMedia(`(min-width: ${getPcMinWidth()})`).matches;
}
