/**
 * Poll a Phase 3a AiJob (GET /ai-jobs/{id}) until it completes or fails.
 * Resolves with the final payload ({ id, status, result, error }), rejects on failure.
 */
export function pollAiJob(id, { interval = 1500, timeout = 60000, onUpdate } = {}) {
    return new Promise((resolve, reject) => {
        const start = Date.now();

        const tick = async () => {
            try {
                const res = await fetch(`/ai-jobs/${id}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                onUpdate?.(data);

                if (data.status === 'completed') return resolve(data);
                if (data.status === 'failed') return reject(new Error(data.error || 'AI job failed'));
                if (Date.now() - start > timeout) return reject(new Error('AI job timed out'));

                setTimeout(tick, interval);
            } catch (e) {
                reject(e);
            }
        };

        tick();
    });
}
