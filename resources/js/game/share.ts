export function shareOnXUrl(text: string, url?: string): string {
    const params = new URLSearchParams({ text });

    if (url) {
        params.set('url', url);
    }

    return `https://x.com/intent/post?${params.toString()}`;
}
