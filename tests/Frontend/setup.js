import { afterEach, vi } from 'vitest';

afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();

    document.body.innerHTML = '';
});
